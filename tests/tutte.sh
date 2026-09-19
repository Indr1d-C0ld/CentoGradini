#!/usr/bin/env bash
#
# Tutte le prove, in fila.
#
#   bash tests/tutte.sh
#
# Le unitarie non toccano il mondo; la prova end-to-end crea e cancella un
# utente, e si rifiuta di girare sull'installazione di produzione se non le si
# dice esplicitamente di farlo (vedi tests/e2e_accesso.sh).

set -uo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

rosso=0
for f in tests/test_*.php; do
  printf '\n\033[1;36m%s\033[0m\n' "$f"
  php "$f" || rosso=1
done

for f in tests/e2e_*.sh; do
  printf '\n\033[1;36m%s\033[0m' "$f"
  bash "$f" "$@" || rosso=1
done

printf '\n'
if [[ "${rosso}" -eq 0 ]]; then
  printf '\033[0;32mTutto verde.\033[0m\n\n'
else
  printf '\033[0;31mQualcosa e'"'"' rosso.\033[0m\n\n'
fi
exit "${rosso}"
