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
  --itemize-changes \
  "$SORGENTE"/ "$DESTINAZIONE"/

[ ${#PROVA[@]} -gt 0 ] && exit 0

# storage e' escluso dalla copia: esiste solo in produzione e deve restare scrivibile
mkdir -p "$DESTINAZIONE/storage/logs" "$DESTINAZIONE/storage/cache"

echo ">>> permessi"
# storage/ resta fuori: i file li crea Apache come www-data e non sono nostri da
# modificare. Il resto dell'albero e' dell'utente proprietario e va leggibile a www-data.
find "$DESTINAZIONE" -path "$DESTINAZIONE/storage" -prune -o -print0 \
  | xargs -0 chgrp "$GRUPPO"
find "$DESTINAZIONE" -path "$DESTINAZIONE/storage" -prune -o -type d -print0 \
  | xargs -0 chmod 775
find "$DESTINAZIONE" -path "$DESTINAZIONE/storage" -prune -o -type f -print0 \
  | xargs -0 chmod 664
chmod 775 "$DESTINAZIONE"/bin/*.php 2>/dev/null || true

echo ">>> migrazioni"
ORANGEROAD_CONFIG="$CONFIG" php "$DESTINAZIONE/bin/console.php" migrate

echo ">>> semi"
ORANGEROAD_CONFIG="$CONFIG" php "$DESTINAZIONE/bin/console.php" seed

echo ">>> fatto."
