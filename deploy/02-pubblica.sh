#!/usr/bin/env bash
# Pubblica il lavoro da /srv/orangeroad a /var/www/orangeroad.
# Idempotente: si puo' rilanciare quante volte si vuole.
#
#   ./deploy/02-pubblica.sh            pubblica, migra, semina
#   ./deploy/02-pubblica.sh --prova    mostra cosa farebbe, senza farlo
set -euo pipefail

SORGENTE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DESTINAZIONE="${ORANGEROAD_HTML:-/var/www/orangeroad}"
CONFIG="${ORANGEROAD_CONFIG_PROD:-/etc/orangeroad/config.php}"
GRUPPO="${ORANGEROAD_GRUPPO:-www-data}"

PROVA=()
[ "${1:-}" = "--prova" ] && PROVA=(--dry-run) && echo ">>> prova a vuoto, non tocco niente"

[ -d "$DESTINAZIONE" ] || { echo "manca $DESTINAZIONE"; exit 1; }
[ -f "$CONFIG" ]       || { echo "manca $CONFIG"; exit 1; }

# `assets/img/ritratti/` e' escluso per lo stesso motivo di `storage/`: quelle
# fotografie le carica il giocatore e le scrive Apache: esistono solo in
# produzione, e una copia con --delete le cancellerebbe tutte.
echo ">>> copio i file"
rsync -a --delete "${PROVA[@]}" \
  --exclude '.git/' \
  --exclude '.gitignore' \
  --exclude '.claude/' \
  --exclude 'config/config.php' \
  --exclude 'storage/' \
  --exclude 'fonti/siti/' \
  --exclude 'fonti/canone/' \
  --exclude 'tests/' \
  --exclude 'assets/img/ritratti/' \
  --itemize-changes \
  "$SORGENTE"/ "$DESTINAZIONE"/

[ ${#PROVA[@]} -gt 0 ] && exit 0

# storage e ritratti sono esclusi dalla copia: esistono solo in produzione e
# devono restare scrivibili da Apache.
mkdir -p "$DESTINAZIONE/storage/logs" "$DESTINAZIONE/storage/cache" \
         "$DESTINAZIONE/assets/img/ritratti"
# La cartella dei ritratti la crea il deploy una volta sola, appartenente a noi
# ma scrivibile dal gruppo: e' Apache che ci mette dentro i file, e da quel
# momento sono suoi.
chgrp "$GRUPPO" "$DESTINAZIONE/assets/img/ritratti" 2>/dev/null || true
chmod 775 "$DESTINAZIONE/assets/img/ritratti"

echo ">>> permessi"
# storage/ e assets/img/ritratti/ restano fuori: quei file li crea Apache come
# www-data e non sono nostri da modificare — provarci fallirebbe, e con
# `set -e` porterebbe giu' il deploy. Il resto dell'albero e' dell'utente proprietario e va
# leggibile a www-data.
POTA=(-path "$DESTINAZIONE/storage" -o -path "$DESTINAZIONE/assets/img/ritratti")
find "$DESTINAZIONE" \( "${POTA[@]}" \) -prune -o -print0 \
  | xargs -0 chgrp "$GRUPPO"
find "$DESTINAZIONE" \( "${POTA[@]}" \) -prune -o -type d -print0 \
  | xargs -0 chmod 775
find "$DESTINAZIONE" \( "${POTA[@]}" \) -prune -o -type f -print0 \
  | xargs -0 chmod 664
chmod 775 "$DESTINAZIONE"/bin/*.php 2>/dev/null || true

echo ">>> migrazioni"
ORANGEROAD_CONFIG="$CONFIG" php "$DESTINAZIONE/bin/console.php" migrate

echo ">>> semi"
ORANGEROAD_CONFIG="$CONFIG" php "$DESTINAZIONE/bin/console.php" seed

echo ">>> fatto."
