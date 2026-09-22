<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    private static string $viewPath = '';

    public static function setPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/');
    }

    /** @param array<string,mixed> $data */
    public static function render(string $name, array $data = [], ?string $layout = 'layout'): string
    {
        $content = self::renderPartial($name, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderPartial($layout, $data + [
            'content' => $content,
            'title'   => $data['title'] ?? Config::get('app.name', 'Cento Gradini'),
            'ambiente'  => self::ambiente($name),
            'larghezza' => self::larghezza($name),
        ]);
    }

    /**
     * Quale «aria» respira la pagina.
     *
     * Le pagine di soglia — ingresso, iscrizione, conferma, errore — hanno il
     * cielo di tramonto sopra la scalinata: e' la prima immagine del manga e
     * l'unica cosa del quartiere che non cambia mai. Le pagine dove si gioca
     * davvero restano pulite, perche' li' lo sfondo e' il quartiere stesso e
     * due immagini sovrapposte non si leggono.
     *
     * La regola sta qui e non nei controller perche' e' una scelta di
     * presentazione, e perche' cosi' e' una riga da leggere invece di venti da
     * cercare. Elencare le pagine di gioco sarebbe stato piu' corto; elencare
     * le soglie e' piu' difficile da sbagliare quando se ne aggiunge una.
     */
    private static function ambiente(string $vista): string
    {
        $vista = ltrim($vista, '/');
        if (in_array($vista, ['home', 'opera', 'santuario'], true)
            || str_starts_with($vista, 'auth/')
            || str_starts_with($vista, 'errors/')) {
            return 'scalinata';
        }
        return '';
    }

    /**
     * Quanto è larga la colonna.
     *
     * Il corpo del sito è tarato sulla lettura: quarantadue rem sono la misura
     * di riga che si legge senza fatica. Due tipi di pagina non sono prosa e
     * in quella colonna stanno strette:
     *
     *  * la carta del quartiere, che è un disegno — a 42rem i nomi dei
     *    ventidue luoghi si accavallano;
     *  * tutto il pannello di amministrazione, che è fatto di tabelle a sei o
     *    sette colonne. Misurato: l'elenco degli utenti chiede 673 pixel, e in
     *    colonna da 42rem gliene restano 548. Il risultato era una barra di
     *    scorrimento orizzontale dentro il riquadro, cioè dati nascosti dietro
     *    un gesto che nessuno fa.
     *
     * Tutte le altre no, perché allargare per abitudine peggiora la lettura
     * dappertutto.
     */
    private static function larghezza(string $vista): string
    {
        $v = ltrim($vista, '/');
        return ($v === 'gioco/quartiere' || str_starts_with($v, 'admin/')) ? 'largo' : '';
    }


    /** @param array<string,mixed> $data */
    public static function renderPartial(string $name, array $data = []): string
    {
        $file = self::$viewPath . '/' . ltrim($name, '/') . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vista non trovata: {$name} ({$file})");
        }

        $render = static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            require $__file;
            return (string) ob_get_clean();
        };

        return $render($file, $data);
    }
}
