<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use GdImage;

/**
 * La fotografia del personaggio: il volto che gli altri vedono nel quartiere.
 *
 * **Quello che si carica non e' quello che si serve.** Il file portato da casa
 * non arriva mai al disco: viene riaperto con GD, ritagliato nel quadrato che
 * il giocatore ha scelto col riquadro, riscalato e riscritto in WebP. In
 * `assets/img/ritratti/` finisce un'immagine costruita qui dentro, in un
 * formato deciso da noi. E' l'unico modo serio di accettare immagini da
 * sconosciuti: un JPEG con dentro del PHP resta un JPEG finche' qualcuno non
 * lo serve come PHP, e la via piu' sicura e' non conservare mai l'originale.
 *
 * Il tipo si decide guardando i byte, non l'estensione ne' quello che dichiara
 * il browser: quelli se li scrive chi carica.
 *
 * Il nome del file e' l'impronta sha256 del risultato. Due conseguenze, tutte
 * e due volute: non e' indovinabile, e due personaggi con la stessa fotografia
 * condividono un solo file sul disco — per cui cancellarlo quando uno dei due
 * la cambia lascerebbe l'altro senza faccia, e infatti `rimuoviFile()`
 * controlla prima che non lo usi piu' nessuno.
 *
 * Il ritaglio arriva dal riquadro come rettangolo in pixel dell'immagine
 * ORIGINALE (`sx`, `sy`, `lato`) e non come «zoom e spostamento»: quei due
 * numeri significherebbero qualcosa solo conoscendo la misura del riquadro
 * sullo schermo di chi carica, che il server non conosce e non deve credere.
 * Un rettangolo in pixel dell'originale il server lo sa verificare da solo, e
 * infatti lo riporta dentro i bordi qualunque cosa arrivi.
 */
final class Ritratto
{
    private const DIR = 'assets/img/ritratti';
    private const URL = 'img/ritratti';

    /** Il lato dell'immagine finale. Basta per uno schermo fitto, non di piu'. */
    public const LATO = 320;

    private const PESO_MAX = 6_291_456;   // 6 MB in ingresso
    private const LATO_MIN = 96;          // sotto, non c'e' niente da ritagliare
    private const LATO_MAX = 8000;

    /** Il fondo su cui si appoggia un PNG trasparente: la crema della pagina. */
    private const FONDO = [245, 233, 220];

    private const FORMATI = [
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_JPEG => 'jpeg',
        IMAGETYPE_WEBP => 'webp',
    ];

    /** L'indirizzo, relativo ad `assets/`, della fotografia — o null se non c'e'. */
    public static function url(?string $file): ?string
    {
        return $file === null || $file === '' ? null : self::URL . '/' . basename($file);
    }

    /**
     * La fotografia di un personaggio, preso per riga.
     *
     * Accetta anche una riga senza le colonne del ritratto (una SELECT
     * parziale): in quel caso risponde null invece di rompersi, perche' una
     * pagina che elenca nomi non deve essere costretta a chiedere tutto.
     *
     * @param array<string,mixed>|null $pg
     */
    public static function di(?array $pg): ?string
    {
        return self::url(isset($pg['ritratto_file']) ? (string) $pg['ritratto_file'] : null);
    }

    /**
     * Prende il file caricato, ritaglia il quadrato scelto e lo salva.
     *
     * @param array<string,mixed> $file la voce di $_FILES
     * @return array{ok:bool,error?:string}
     */
    public static function carica(int $pgId, array $file, int $sx, int $sy, int $lato, string $radice): array
    {
        $errore = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errore === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Non hai scelto nessuna fotografia.'];
        }
        if ($errore === UPLOAD_ERR_INI_SIZE || $errore === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => 'La fotografia è troppo pesante.'];
        }
        if ($errore !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Caricamento non riuscito (codice ' . $errore . ').'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        // `is_uploaded_file` e' la garanzia che il percorso arrivi davvero da
        // un caricamento e non sia un file del server che qualcuno ha nominato
        // in un campo. In prova la si salta apposta (vedi `caricaDaFile`).
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'File non valido.'];
        }
        if ((int) ($file['size'] ?? 0) > self::PESO_MAX) {
            return ['ok' => false, 'error' => 'La fotografia supera i 6 MB.'];
        }

        return self::lavora($pgId, $tmp, $sx, $sy, $lato, $radice);
    }

    /**
     * Come `carica()`, ma da un file che sta gia' sul disco del server.
     *
     * Esiste per due usi onesti: le prove automatiche, che non hanno un
     * caricamento HTTP vero da mostrare a `is_uploaded_file`, e un eventuale
     * riempimento dei personaggi non giocanti da riga di comando. Non va
     * chiamata con un percorso che arriva dalla rete: sceglierebbe chi chiama
     * quale file del server leggere.
     *
     * @return array{ok:bool,error?:string}
     */
    public static function caricaDaFile(int $pgId, string $percorso, int $sx, int $sy, int $lato, string $radice): array
    {
        if (!is_file($percorso)) {
            return ['ok' => false, 'error' => 'File inesistente.'];
        }
        if (filesize($percorso) > self::PESO_MAX) {
            return ['ok' => false, 'error' => 'La fotografia supera i 6 MB.'];
        }
        return self::lavora($pgId, $percorso, $sx, $sy, $lato, $radice);
    }

    /** @return array{ok:bool,error?:string} */
    private static function lavora(int $pgId, string $tmp, int $sx, int $sy, int $lato, string $radice): array
    {
        $info = @getimagesize($tmp);
        if ($info === false || !isset(self::FORMATI[$info[2]])) {
            return ['ok' => false, 'error' => 'Serve un JPEG, un PNG o un WebP. '
                . 'Gli SVG non si accettano: possono contenere codice.'];
        }
        [$larghezza, $altezza] = $info;
        if ($larghezza < self::LATO_MIN || $altezza < self::LATO_MIN) {
            return ['ok' => false, 'error' => 'Fotografia troppo piccola: almeno '
                . self::LATO_MIN . ' pixel per lato.'];
        }
        if ($larghezza > self::LATO_MAX || $altezza > self::LATO_MAX) {
            return ['ok' => false, 'error' => 'Fotografia troppo grande: non oltre '
                . self::LATO_MAX . ' pixel per lato.'];
        }

        [$sx, $sy, $lato] = self::ritaglio($larghezza, $altezza, $sx, $sy, $lato);

        $sorgente = match (self::FORMATI[$info[2]]) {
            'png'  => @imagecreatefrompng($tmp),
            'jpeg' => @imagecreatefromjpeg($tmp),
            'webp' => @imagecreatefromwebp($tmp),
        };
        if (!$sorgente instanceof GdImage) {
            return ['ok' => false, 'error' => 'Non riesco ad aprire questa immagine.'];
        }

        $out = imagecreatetruecolor(self::LATO, self::LATO);
        imagefilledrectangle($out, 0, 0, self::LATO, self::LATO,
            imagecolorallocate($out, self::FONDO[0], self::FONDO[1], self::FONDO[2]));
        imagecopyresampled($out, $sorgente, 0, 0, $sx, $sy, self::LATO, self::LATO, $lato, $lato);
        imagedestroy($sorgente);

        $temporaneo = tempnam(sys_get_temp_dir(), 'ritr');
        if ($temporaneo === false) {
            imagedestroy($out);
            return ['ok' => false, 'error' => 'Non riesco a scrivere un file temporaneo.'];
        }
        imagewebp($out, $temporaneo, 86);
        imagedestroy($out);

        $hash = (string) hash_file('sha256', $temporaneo);
        $nome = $hash . '.webp';
        $dir  = rtrim($radice, '/') . '/' . self::DIR;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            @unlink($temporaneo);
            return ['ok' => false, 'error' => 'Non riesco a scrivere la cartella delle fotografie.'];
        }

        $vecchio = Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$pgId]);
        if ($vecchio === null) {
            @unlink($temporaneo);
            return ['ok' => false, 'error' => 'Personaggio inesistente.'];
        }

        // Prima la riga, poi il file: se il database rifiuta, sul disco non
        // resta niente di orfano. L'ordine opposto lascerebbe immagini che non
        // sono di nessuno e che nessuno andrebbe piu' a cercare.
        Database::run(
            'UPDATE personaggi SET ritratto_file = ?, ritratto_hash = ?, ritratto_at = NOW() WHERE id = ?',
            [$nome, $hash, $pgId]
        );

        @rename($temporaneo, $dir . '/' . $nome);
        @chmod($dir . '/' . $nome, 0664);

        self::rimuoviFile($radice, (string) ($vecchio['ritratto_file'] ?? ''), $nome);

        return ['ok' => true];
    }

    /**
     * Riporta il ritaglio dentro i bordi, e ne inventa uno se non e' arrivato.
     *
     * Sta in un metodo suo perche' e' la regola che le prove hanno bisogno di
     * verificare senza passare per GD. Il ripiego centra sul lato corto ma un
     * po' piu' in alto del centro geometrico: in un ritratto la testa sta in
     * alto, e tagliare dal centro decapita.
     *
     * @return array{0:int,1:int,2:int}
     */
    public static function ritaglio(int $larghezza, int $altezza, int $sx, int $sy, int $lato): array
    {
        $maxLato = min($larghezza, $altezza);
        if ($lato < 16 || $lato > $maxLato) {
            $lato = $maxLato;
            $sx = (int) (($larghezza - $lato) / 2);
            $sy = (int) max(0, ($altezza - $lato) * 0.18);
        }
        return [
            max(0, min($sx, $larghezza - $lato)),
            max(0, min($sy, $altezza - $lato)),
            $lato,
        ];
    }

    /** Toglie la fotografia a un personaggio, e il file se non serve ad altri. */
    public static function togli(int $pgId, string $radice): void
    {
        $r = Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$pgId]);
        Database::run(
            'UPDATE personaggi SET ritratto_file = NULL, ritratto_hash = NULL, ritratto_at = NULL WHERE id = ?',
            [$pgId]
        );
        self::rimuoviFile($radice, (string) ($r['ritratto_file'] ?? ''), null);
    }

    /**
     * Toglie dal disco un'immagine che non serve piu' — ma solo se non la sta
     * usando nessun altro personaggio.
     */
    private static function rimuoviFile(string $radice, ?string $file, ?string $tranne): void
    {
        if ($file === null || $file === '' || $file === $tranne) {
            return;
        }
        $ancora = Database::first('SELECT COUNT(*) n FROM personaggi WHERE ritratto_file = ?', [$file]);
        if ((int) ($ancora['n'] ?? 0) > 0) {
            return;
        }
        $percorso = rtrim($radice, '/') . '/' . self::DIR . '/' . basename($file);
        if (is_file($percorso)) {
            @unlink($percorso);
        }
    }
}
