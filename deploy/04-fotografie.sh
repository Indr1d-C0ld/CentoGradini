#!/usr/bin/env bash
# Riporta nella cartella di lavoro le fotografie caricate dai giocatori.
#
#   ./deploy/04-fotografie.sh            copia
#   ./deploy/04-fotografie.sh --prova    mostra cosa farebbe, senza farlo
#
# Serve perche' quelle immagini nascono SOLO sull'installazione viva: le scrive
# Apache quando un giocatore carica una fotografia, e `02-pubblica.sh` le salta
# apposta in andata — una copia con --delete le cancellerebbe tutte a ogni
# pubblicazione. Senza questo passo il backup integrale avrebbe un buco
# esattamente dove sta l'unico contenuto che i giocatori mettono di loro.
#
# La copia e' uno SPECCHIO, --delete compreso: la cartella di lavoro deve dire
# la verita' su cosa c'e' adesso in produzione. Quello che e' stato tolto non si
# perde lo stesso, perche' resta nella storia del repository privato: e' li'
# che sta il backup, non in una cartella che accumula file di cui nessuno sa
# piu' di chi sono.
#
# Se poi quelle immagini debbano finire nella storia del repository lo decide
# `.gitignore`, non questo script: qui si stabilisce soltanto che esistano
# anche fuori dall'installazione viva.
#
# `-rt` e non `-a`: i file in produzione appartengono a www-data, e provare a
# riprodurne proprietario e permessi da utente normale fallisce.
set -euo pipefail

SORGENTE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VIVO="${ORANGEROAD_HTML:-/var/www/orangeroad}"
CARTELLA="assets/img/ritratti"

PROVA=()
[ "${1:-}" = "--prova" ] && PROVA=(--dry-run) && echo ">>> prova a vuoto, non tocco niente"

if [ ! -d "$VIVO/$CARTELLA" ]; then
  echo ">>> in $VIVO non c'e' $CARTELLA: niente da ritirare."
  exit 0
fi

mkdir -p "$SORGENTE/$CARTELLA"

echo ">>> ritiro le fotografie da $VIVO/$CARTELLA"
rsync -rt --delete "${PROVA[@]}" --itemize-changes \
  --exclude '.gitkeep' \
  "$VIVO/$CARTELLA"/ "$SORGENTE/$CARTELLA"/

# Le fotografie degli abitanti non bastano da sole: bisogna sapere di chi e'
# ciascuna, e quello lo sa solo il database dell'installazione viva. Lo si
# chiede alla sua console e lo si mette per iscritto in db/ritratti_abitanti.php
# (l'intestazione resta, le righe si rifanno), che e' da dove il seme lo
# riprende quando un'installazione rinasce da questo repository.
ELENCO="$SORGENTE/db/ritratti_abitanti.php"
if [ -f "$ELENCO" ] && [ -f "$VIVO/bin/console.php" ]; then
  if RIGHE=$(php "$VIVO/bin/console.php" ritratti:abitanti); then
    if [ ${#PROVA[@]} -eq 0 ]; then
      { sed '/^return \[$/q' "$ELENCO"; [ -n "$RIGHE" ] && printf '%s\n' "$RIGHE"; echo '];'; } > "$ELENCO.nuovo"
      php -l "$ELENCO.nuovo" >/dev/null && mv "$ELENCO.nuovo" "$ELENCO"
    fi
    echo ">>> abitanti con la fotografia: $(printf '%s' "$RIGHE" | grep -c '=>' || true) (db/ritratti_abitanti.php)"
  else
    echo "!!! non riesco a chiedere alla console di $VIVO chi porta quale fotografia"
  fi
fi

QUANTE=$(find "$SORGENTE/$CARTELLA" -type f ! -name '.gitkeep' | wc -l)
PESO=$(du -sh "$SORGENTE/$CARTELLA" 2>/dev/null | cut -f1)
echo ">>> in archivio: ${QUANTE} fotografie, ${PESO}."
