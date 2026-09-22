<?php

declare(strict_types=1);

/**
 * La fotografia del personaggio: ritaglio, riscrittura, condivisione del file.
 *
 * Il pezzo delicato non e' il caricamento, e' il RITAGLIO: arriva da una
 * pagina, quindi arriva da chiunque, e se il server si fidasse dei tre numeri
 * basterebbe cambiarli a mano per far leggere a GD pixel fuori dall'immagine.
 * Per questo `ritaglio()` sta in un metodo suo e si prova da solo, senza
 * passare per un caricamento HTTP.
 *
 * Le prove che toccano il disco lo fanno in una radice temporanea: non e' una
 * precauzione teorica, e' che la radice vera e' la cartella di lavoro e una
 * prova che lascia in giro dei .webp sporca il repository.
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Game\Ritratto;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzfoto';

$radice = sys_get_temp_dir() . '/prova-ritratti-' . getmypid();
$cartella = $radice . '/assets/img/ritratti';

/** Un personaggio usa e getta. Senza utente: qui l'account non c'entra. */
function attore(string $nome): int
{
    Database::run(
        'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato,
             luogo, anno_nascita, nato_mese, nato_giorno, esper,
             rissa, testa, dai_suki, cuore, pf, pf_max, pp, pp_max, compostezza, compostezza_max)
         VALUES (NULL, ?, ?, ?, ?, 2, ?, ?, ?, 1970, 5, 5, 0, 8, 10, 9, 7, 11, 11, 9, 9, 6, 6)',
        [$nome, ucfirst(PREFISSO), 'f', 'superiori', 'completa', 'attivo', 'gradini']
    );
    return (int) Database::lastInsertId();
}

/**
 * Un'immagine con la meta' sinistra rossa e la destra blu: serve a dimostrare
 * che il ritaglio ritaglia davvero il pezzo richiesto e non uno a caso.
 *
 * `$tinta` cambia la sfumatura del rosso, e non e' un vezzo. La prima versione
 * di queste prove non ce l'aveva, e due prove fallivano dicendo che due
 * immagini diverse producevano lo stesso file: erano diverse **prima** del
 * ritaglio (900x600 e 500x500) e identiche **dopo**, perche' il quadrato
 * centrale di una meta'-e-meta' e' sempre la stessa meta'-e-meta'. Il codice
 * aveva ragione — il nome e' l'impronta del RISULTATO, e il risultato era lo
 * stesso. Sbagliava il materiale di prova.
 *
 * Le tinte devono restare DISTINTE fra loro: una prima correzione le limitava
 * dal basso con un `max()`, due di loro finivano sullo stesso valore e la
 * prova tornava a fallire per lo stesso motivo di prima.
 */
function immagineDueColori(int $larghezza, int $altezza, int $tinta = 0): string
{
    $im = imagecreatetruecolor($larghezza, $altezza);
    imagefilledrectangle($im, 0, 0, intdiv($larghezza, 2) - 1, $altezza - 1,
        imagecolorallocate($im, 220 - $tinta * 25, 20, 20));
    imagefilledrectangle($im, intdiv($larghezza, 2), 0, $larghezza - 1, $altezza - 1,
        imagecolorallocate($im, 20, 20, 220));
    $f = tempnam(sys_get_temp_dir(), 'pr') . '.png';
    imagepng($im, $f);
    imagedestroy($im);
    return $f;
}

/** @return array{0:int,1:int,2:int} rosso, verde, blu al centro dell'immagine */
function centro(string $file): array
{
    $im = imagecreatefromwebp($file);
    $c  = imagecolorat($im, (int) (imagesx($im) / 2), (int) (imagesy($im) / 2));
    $r  = imagecolorsforindex($im, $c);
    imagedestroy($im);
    return [$r['red'], $r['green'], $r['blue']];
}

function fileNellaCartella(string $cartella): array
{
    if (!is_dir($cartella)) {
        return [];
    }
    return array_values(array_filter(scandir($cartella) ?: [],
        static fn (string $n): bool => str_ends_with($n, '.webp')));
}

// =============================================================================
titolo('Il ritaglio, prima ancora di toccare il disco');

prova('un rettangolo valido passa com\'è', function () {
    uguale([100, 50, 200], Ritratto::ritaglio(800, 600, 100, 50, 200));
});

prova('un rettangolo che sborda rientra nei bordi', function () {
    // Chiede il quadrato 300 a partire da (700, 500) su un'immagine 800x600:
    // uscirebbe da tutte e due le parti. Deve rientrare, non fallire.
    [$sx, $sy, $lato] = Ritratto::ritaglio(800, 600, 700, 500, 300);
    uguale(300, $lato);
    uguale(500, $sx, 'a destra si ferma a larghezza - lato');
    uguale(300, $sy, 'in basso si ferma ad altezza - lato');
});

prova('le coordinate negative si azzerano', function () {
    uguale([0, 0, 100], Ritratto::ritaglio(400, 400, -50, -900, 100));
});

prova('un lato più grande del lato corto non si accetta', function () {
    // Non si "accorcia" il lato tenendo l'angolo: si ricomincia dal ripiego,
    // perche' un ritaglio del genere non l'ha chiesto nessuna interfaccia —
    // se arriva, arriva da qualcuno che sta provando.
    [$sx, $sy, $lato] = Ritratto::ritaglio(400, 900, 0, 0, 5000);
    uguale(400, $lato, 'il lato corto è 400');
    uguale(0, $sx);
});

prova('senza ritaglio si centra, ma un po\' più in alto', function () {
    // 400x800: il quadrato e' 400, e parte a (800-400)*0.18 = 72 da sopra.
    // Centrare sul centro geometrico (a 200) taglierebbe la testa.
    uguale([0, 72, 400], Ritratto::ritaglio(400, 800, 0, 0, 0));
    vero(Ritratto::ritaglio(400, 800, 0, 0, 0)[1] < (800 - 400) / 2,
        'la finestra deve stare sopra il centro, non sotto');
});

prova('su un\'immagine larga il ripiego centra in orizzontale', function () {
    uguale([200, 0, 400], Ritratto::ritaglio(800, 400, 0, 0, 0));
});

// =============================================================================
titolo('L\'indirizzo della fotografia');

prova('un nome con dei percorsi dentro viene ridotto al nome', function () {
    // Il nome arriva dal database, non dalla rete — ma il giorno in cui una
    // migrazione sbagliata ci scrive dentro un percorso, meglio servirlo
    // rotto che servire /etc/passwd.
    uguale('img/ritratti/passwd', Ritratto::url('../../../etc/passwd'));
});

prova('chi non ha fotografia non ha indirizzo', function () {
    uguale(null, Ritratto::url(null));
    uguale(null, Ritratto::url(''));
    uguale(null, Ritratto::di(null));
    uguale(null, Ritratto::di(['nome' => 'Madoka']), 'una riga senza la colonna non deve rompere');
    uguale('img/ritratti/x.webp', Ritratto::di(['ritratto_file' => 'x.webp']));
});

// =============================================================================
titolo('Dal file caricato al file servito');

prova('una fotografia diventa un quadrato di 320 pixel in WebP', function () use ($radice, $cartella) {
    $pg = attore('Una');
    $f  = immagineDueColori(900, 600, 1);
    $res = Ritratto::caricaDaFile($pg, $f, 0, 0, 0, $radice);
    unlink($f);
    vero($res['ok'], $res['error'] ?? '');

    $riga = Database::first('SELECT ritratto_file, ritratto_hash, ritratto_at FROM personaggi WHERE id = ?', [$pg]);
    vero($riga['ritratto_file'] !== null, 'la riga deve puntare al file');
    vero($riga['ritratto_at'] !== null, 'deve restare scritto da quando');

    $percorso = $cartella . '/' . $riga['ritratto_file'];
    vero(is_file($percorso), 'il file deve esistere in ' . $percorso);
    uguale($riga['ritratto_hash'] . '.webp', $riga['ritratto_file'],
        'il nome del file è l\'impronta del contenuto');
    uguale(hash_file('sha256', $percorso), (string) $riga['ritratto_hash'],
        'l\'impronta scritta deve essere quella del file servito');

    $misure = getimagesize($percorso);
    uguale(320, $misure[0]);
    uguale(320, $misure[1]);
    uguale(IMAGETYPE_WEBP, $misure[2], 'quello che si serve è sempre WebP, qualunque cosa arrivi');
});

prova('il ritaglio ritaglia davvero il pezzo che gli si chiede', function () use ($radice, $cartella) {
    // Meta' sinistra rossa, meta' destra blu. Si chiede il quadrato tutto a
    // destra: al centro deve uscire blu. Senza questa prova, un `sx` ignorato
    // passerebbe inosservato — l'immagine ci sarebbe comunque.
    $pg = attore('Due');
    $f  = immagineDueColori(800, 400, 2);
    $res = Ritratto::caricaDaFile($pg, $f, 400, 0, 400, $radice);
    unlink($f);
    vero($res['ok'], $res['error'] ?? '');

    $riga = Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$pg]);
    [$r, , $b] = centro($cartella . '/' . $riga['ritratto_file']);
    vero($b > $r + 60, "al centro doveva esserci del blu, e invece r={$r} b={$b}");
});

prova('due persone con la stessa fotografia occupano un file solo', function () use ($radice, $cartella) {
    $uno = attore('Tre');
    $due = attore('Quattro');
    $f   = immagineDueColori(500, 500, 3);
    Ritratto::caricaDaFile($uno, $f, 0, 0, 0, $radice);
    Ritratto::caricaDaFile($due, $f, 0, 0, 0, $radice);
    unlink($f);

    $a = Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$uno]);
    $b = Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$due]);
    uguale($a['ritratto_file'], $b['ritratto_file'], 'stesso contenuto, stesso nome');
    uguale(1, count(array_filter(fileNellaCartella($cartella),
        static fn (string $n): bool => $n === $a['ritratto_file'])),
        'e sul disco ce n\'è una copia sola');

    // E togliendola a uno, l'altro non resta senza faccia.
    Ritratto::togli($uno, $radice);
    vero(is_file($cartella . '/' . $b['ritratto_file']),
        'il file serve ancora all\'altra persona: non si cancella');
    uguale(null, Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$uno])['ritratto_file']);

    // Quando non serve piu' a nessuno, allora sparisce.
    Ritratto::togli($due, $radice);
    vero(!is_file($cartella . '/' . $b['ritratto_file']),
        'l\'ultimo che la toglie porta via anche il file');
});

prova('cambiare fotografia non lascia in giro la vecchia', function () use ($radice, $cartella) {
    $pg = attore('Cinque');
    $primo   = immagineDueColori(500, 500, 4);
    $secondo = immagineDueColori(640, 480, 5);
    Ritratto::caricaDaFile($pg, $primo, 0, 0, 0, $radice);
    $vecchio = Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$pg])['ritratto_file'];
    Ritratto::caricaDaFile($pg, $secondo, 0, 0, 0, $radice);
    $nuovo = Database::first('SELECT ritratto_file FROM personaggi WHERE id = ?', [$pg])['ritratto_file'];
    unlink($primo);
    unlink($secondo);

    vero($vecchio !== $nuovo, 'due immagini diverse, due file diversi');
    vero(!is_file($cartella . '/' . $vecchio), 'la vecchia non serve più a nessuno');
    vero(is_file($cartella . '/' . $nuovo));
    Ritratto::togli($pg, $radice);
});

// =============================================================================
titolo('Quello che non si accetta');

prova('un file che non è un\'immagine viene respinto', function () use ($radice) {
    $pg = attore('Sei');
    $f  = tempnam(sys_get_temp_dir(), 'pr');
    file_put_contents($f, "<?php echo 'ciao';");
    $res = Ritratto::caricaDaFile($pg, $f, 0, 0, 0, $radice);
    unlink($f);
    vero(!$res['ok'], 'un file PHP non deve passare per una fotografia');
});

prova('un SVG viene respinto anche se è un\'immagine vera', function () use ($radice) {
    // Un SVG e' XML, e l'XML puo' contenere script. Non si accetta, e il
    // messaggio lo dice invece di lasciare l'utente a indovinare.
    $pg = attore('Sette');
    $f  = tempnam(sys_get_temp_dir(), 'pr') . '.svg';
    file_put_contents($f, '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"></svg>');
    $res = Ritratto::caricaDaFile($pg, $f, 0, 0, 0, $radice);
    unlink($f);
    vero(!$res['ok']);
    vero(str_contains($res['error'] ?? '', 'SVG'), 'il messaggio deve nominare il caso');
});

prova('un\'immagine troppo piccola viene respinta', function () use ($radice) {
    $pg = attore('Otto');
    $f  = immagineDueColori(40, 40, 1);
    $res = Ritratto::caricaDaFile($pg, $f, 0, 0, 0, $radice);
    unlink($f);
    vero(!$res['ok']);
    vero(str_contains($res['error'] ?? '', '96'), 'il messaggio deve dire quanto ci vuole');
});

prova('un personaggio che non esiste non lascia file orfani', function () use ($radice, $cartella) {
    $prima = fileNellaCartella($cartella);
    $f = immagineDueColori(400, 400, 3);
    $res = Ritratto::caricaDaFile(999999999, $f, 0, 0, 0, $radice);
    unlink($f);
    vero(!$res['ok'], 'non c\'è nessuno a cui attaccarla');
    uguale($prima, fileNellaCartella($cartella), 'e sul disco non deve restare niente');
});

// --- pulizia ------------------------------------------------------------------
Database::run('DELETE FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]);
foreach (fileNellaCartella($cartella) as $n) {
    unlink($cartella . '/' . $n);
}
@rmdir($cartella);
@rmdir($radice . '/assets/img');
@rmdir($radice . '/assets');
@rmdir($radice);

riepilogo();
