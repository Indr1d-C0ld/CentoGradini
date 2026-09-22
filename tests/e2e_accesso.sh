#!/usr/bin/env bash
#
# Cento Gradini — prova end-to-end del giro di iscrizione e accesso.
#
#   bash tests/e2e_accesso.sh [base_url]
#
# Senza argomenti avvia da sola il server incorporato di PHP su una porta
# libera e lo spegne alla fine. Con un URL usa quello (utile per provare il
# deploy vero).
#
# La prova crea un utente con un nome improbabile e lo cancella comunque vada,
# anche se fallisce a meta': si ripulisce da sola.

set -uo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
RADICE="$PWD"

BASE=""
FORZA=0
for arg in "$@"; do
  case "$arg" in
    --anche-in-produzione) FORZA=1 ;;
    *) BASE="$arg" ;;
  esac
done

# --- Su quale installazione stiamo per lavorare? --------------------------
# Config::load sceglie il primo file che trova, e /etc/orangeroad/
# vince sul config di progetto: senza questo avviso e' fin troppo facile
# credere di provare in locale mentre si sta scrivendo sul mondo vero.
LETTURA=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $u = 0;
  try { $u = (int) (App\Core\Database::first("SELECT COUNT(*) n FROM users")["n"] ?? 0); } catch (Throwable) {}
  echo App\Core\Config::sourceFile(), "|", App\Core\Config::get("app.env"), "|", $u;
' 2>/dev/null)
CONFIG="${LETTURA%%|*}"; RESTO="${LETTURA#*|}"; AMBIENTE="${RESTO%%|*}"; UTENTI="${RESTO##*|}"

printf '\n\033[1;36mConfigurazione\033[0m\n'
printf '  file     : %s\n  ambiente : %s\n  utenti   : %s\n' \
       "${CONFIG:-nessuno}" "${AMBIENTE:-?}" "${UTENTI:-?}"

if [[ "${AMBIENTE}" == "production" && "${FORZA}" -ne 1 ]]; then
  printf '\n\033[0;31mQuesta e'"'"' l'"'"'installazione di produzione.\033[0m\n'
  printf 'La prova crea e cancella un utente: sul mondo vero si fa solo di proposito.\n'
  printf 'Per procedere comunque:  bash tests/e2e_accesso.sh --anche-in-produzione\n'
  printf 'Per provare in locale:   ORANGEROAD_CONFIG=config/config.php bash tests/e2e_accesso.sh\n\n'
  exit 2
fi
PID=""
BISCOTTI="$(mktemp)"
UTENTE="prova-e2e-$$"
# Il nome del PERSONAGGIO non puo' contenere cifre (e' un nome di persona, non
# un identificativo): il suffisso univoco si scrive in lettere.
SUFFISSO=$(printf '%s' "$$" | tr '0123456789' 'abcdefghij')
PGNOME="Prova${SUFFISSO}"
EMAIL="prova-e2e-$$@example.invalid"
PASSWORD="parolalungabastante"

ok=0; ko=0
verde() { printf '  \033[0;32m✓\033[0m %s\n' "$*"; ok=$((ok+1)); }
rosso() { printf '  \033[0;31m✗\033[0m %s\n' "$*"; ko=$((ko+1)); }
titolo(){ printf '\n\033[1;36m%s\033[0m\n' "$*"; }

pulisci() {
  php bin/console.php user:delete "${UTENTE}" >/dev/null 2>&1 <<< "SI" || true
  php -r '
    require "src/autoload.php"; require "src/Support/helpers.php";
    $GLOBALS["__project_root"] = getcwd();
    App\Core\Config::load(getcwd());
    // Anche gli avvisi per lamministratore parlano dellutente di prova ma vanno
    // a un altro indirizzo: si cercano per contenuto, non per destinatario.
    // (Niente apostrofi qui dentro: questo PHP vive dentro apici di shell.)
    try { App\Core\Database::run("DELETE FROM mail_queue WHERE destinatario LIKE ? OR corpo LIKE ? OR oggetto LIKE ?",
        ["prova-e2e-%@example.invalid", "%prova-e2e-%", "%prova-e2e-%"]); } catch (Throwable) {}
    try { App\Core\Database::run("DELETE FROM rate_limits WHERE rkey LIKE ?", ["%127.0.0.1%"]); } catch (Throwable) {}
    // Le tracce sopravvivono al personaggio (ON DELETE SET NULL): senza
    // questa riga il quartiere di prova resta pieno di fantasmi.
    try { App\Core\Database::run("DELETE FROM tracce WHERE testo LIKE ?", ["%Prova%"]); } catch (Throwable) {}
  ' >/dev/null 2>&1 || true
  [[ -n "${PID}" ]] && kill "${PID}" 2>/dev/null
  rm -f "${BISCOTTI}"
}
trap pulisci EXIT

# --- Il server ----------------------------------------------------------------
if [[ -z "${BASE}" ]]; then
  PORTA=$(php -r 'for($p=8099;$p<8200;$p++){$s=@fsockopen("127.0.0.1",$p,$e,$m,0.2); if($s===false){echo $p; exit;} fclose($s);} echo 0;')
  [[ "${PORTA}" == "0" ]] && { echo "nessuna porta libera"; exit 1; }
  php -S "127.0.0.1:${PORTA}" -t "${RADICE}" "${RADICE}/index.php" >/dev/null 2>&1 &
  PID=$!
  BASE="http://127.0.0.1:${PORTA}/index.php"
  for _ in $(seq 1 40); do
    curl -fsS "${BASE}/salute" >/dev/null 2>&1 && break
    sleep 0.25
  done
fi

G()  { curl -sS      -b "${BISCOTTI}" -c "${BISCOTTI}" "$@"; }
GL() { curl -sS -L   -b "${BISCOTTI}" -c "${BISCOTTI}" "$@"; }

# «La pagina contiene questo?»
#
# Sembra piu' semplice scrivere  G url | grep -q testo  — e per otto asserzioni
# su nove funziona. Alla nona no: con `pipefail`, `grep -q` esce appena trova
# la corrispondenza, curl riceve SIGPIPE mentre sta ancora scrivendo, e la
# pipeline restituisce 141 nonostante il testo CI FOSSE. Succede solo sulle
# pagine piu' grandi del buffer del tubo, quindi si presenta come un guasto
# intermittente e si va a cercarlo dalla parte sbagliata. Qui la risposta si
# raccoglie prima e si cerca dopo.
contiene() {   # contiene <url> <espressione> [-i]
  local corpo
  corpo=$(G "$1") || return 1
  grep -q ${3:-} -- "$2" <<< "${corpo}"
}
contiene_seguendo() {
  local corpo
  corpo=$(GL "$1") || return 1
  grep -q -- "$2" <<< "${corpo}"
}

gettone() {
  # Il campo nascosto _token della pagina indicata.
  # Qui il tubo va bene: `grep -o` legge fino in fondo e non chiude prima.
  G "$1" | grep -o 'name="_token" value="[0-9a-f]*"' | head -1 | sed 's/.*value="//;s/"//'
}

titolo "Cento Gradini — prova end-to-end (${BASE})"

# --- Pagine pubbliche ----------------------------------------------------------
titolo "Pagine pubbliche"
if contiene "${BASE}/salute" '"ok":true'; then verde "/salute risponde e il database e' raggiungibile"; else rosso "/salute"; fi
if contiene "${BASE}/" 'Cento Gradini'; then verde "/ si apre"; else rosso "/"; fi
if contiene "${BASE}/opera" 'Izumi Matsumoto'; then verde "/opera cita l'autore dell'opera"; else rosso "/opera"; fi
CODICE=$(curl -sS -o /dev/null -w '%{http_code}' "${BASE}/questa-pagina-non-esiste")
if [[ "${CODICE}" == "404" ]]; then verde "una pagina inesistente da' 404"; else rosso "404 atteso, ottenuto ${CODICE}"; fi

# Il foglio di stile deve arrivare come CSS, non come pagina d'errore travestita.
# Sotto il server incorporato ci vuole il rimando esplicito agli statici in
# index.php; sotto Apache la RewriteCond -f. Se salta uno dei due il sito si
# vede nudo e nessun'altra prova se ne accorge.
STATICO="${BASE%/index.php}/assets/css/kor.css"
TIPO=$(curl -sS -o /dev/null -w '%{content_type}' "${STATICO}")
if [[ "${TIPO}" == text/css* ]]; then verde "il foglio di stile e' servito come text/css"; else rosso "kor.css servito come '${TIPO}'"; fi

# --- Le cartelle che non devono uscire dal server -------------------------------
# Ha senso solo contro un deploy vero: col server incorporato di PHP non c'e'
# nessun Apache da verificare. Il controllo esiste perche' la regola che le
# protegge e' facilissima da scrivere in modo che NON si applichi: il
# DocumentRoot e' un symlink, e Apache abbina i <Directory> sul percorso non
# risolto. Il sintomo e' silenzioso — nessun errore, nessun avviso, solo i
# sorgenti raggiungibili — quindi va cercato apposta.
if [[ "${BASE}" != *"/index.php" ]]; then
  titolo "Cartelle non pubbliche"
  for RISERVATA in /src/Core/Config.php /views/layout.php /bin/tick.php /db/migrations /config/config.example.php; do
    CODICE=$(curl -sS -o /dev/null -w '%{http_code}' "${BASE}${RISERVATA}")
    if [[ "${CODICE}" == "403" || "${CODICE}" == "404" ]]; then
      verde "${RISERVATA} non e' servita (${CODICE})"
    else
      rosso "${RISERVATA} risponde ${CODICE}: la regola <Directory> non si sta applicando"
    fi
  done
fi

# --- Il cancello: senza account non si entra -----------------------------------
titolo "Il cancello"
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/quartiere")
if [[ "${DEST}" == *"/accesso" ]]; then verde "/quartiere rimanda all'accesso"; else rosso "/quartiere non protetto (-> '${DEST}')"; fi

# --- CSRF ----------------------------------------------------------------------
titolo "Gettone CSRF"
CODICE=$(curl -sS -o /dev/null -w '%{http_code}' -b "${BISCOTTI}" -c "${BISCOTTI}" \
         -d "login=x&password=y" "${BASE}/accesso")
if [[ "${CODICE}" == "400" ]]; then verde "una POST senza gettone viene respinta (400)"; else rosso "POST senza gettone: atteso 400, ottenuto ${CODICE}"; fi

# --- Iscrizione ------------------------------------------------------------------
titolo "Iscrizione"
T=$(gettone "${BASE}/iscrizione")
if [[ -n "${T}" ]]; then verde "il modulo di iscrizione porta un gettone"; else rosso "nessun gettone nel modulo"; fi

DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" -d "username=${UTENTE}" -d "email=${EMAIL}" \
  -d "password=${PASSWORD}" -d "password_confirm=${PASSWORD}" "${BASE}/iscrizione")
if [[ "${DEST}" == *"/conferma-inviata" ]]; then verde "l'iscrizione porta alla pagina di conferma"; else rosso "iscrizione -> '${DEST}'"; fi

STATO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $u = App\Core\Database::first("SELECT status FROM users WHERE username = ?", [$argv[1]]);
  echo $u["status"] ?? "assente";
' "${UTENTE}")
if [[ "${STATO}" == "pending" ]]; then verde "l'account nasce in attesa di conferma"; else rosso "stato atteso 'pending', ottenuto '${STATO}'"; fi

# --- Password non coincidenti ----------------------------------------------------
T=$(gettone "${BASE}/iscrizione")
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" -d "username=altro-$$" -d "email=altro-$$@example.invalid" \
  -d "password=unaparola" -d "password_confirm=unaltraparola" "${BASE}/iscrizione")
if [[ "${DEST}" == *"/iscrizione" ]]; then verde "due password diverse rimandano al modulo"; else rosso "password diverse -> '${DEST}'"; fi

# --- Senza conferma non si entra --------------------------------------------------
titolo "Prima della conferma"
T=$(gettone "${BASE}/accesso")
G -o /tmp/kor_e2e_login.$$ -d "_token=${T}" -d "login=${UTENTE}" -d "password=${PASSWORD}" "${BASE}/accesso" >/dev/null
if contiene "${BASE}/accesso" "confermare il tuo indirizzo\|non è ancora confermato" -i; then
  verde "l'accesso e' negato e lo dice: manca la conferma"
else
  rosso "l'accesso prima della conferma non avverte"
fi
rm -f /tmp/kor_e2e_login.$$

# --- Conferma ----------------------------------------------------------------------
titolo "Conferma dell'indirizzo"
GETTONE_MAIL=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $m = App\Core\Database::first("SELECT corpo FROM mail_queue WHERE destinatario = ? ORDER BY id DESC LIMIT 1", [$argv[1]]);
  if ($m === null) { echo ""; exit; }
  preg_match("/conferma\?token=([0-9a-f]+)/", (string) $m["corpo"], $x);
  echo $x[1] ?? "";
' "${EMAIL}")
if [[ -n "${GETTONE_MAIL}" ]]; then verde "il messaggio di conferma contiene un collegamento"; else rosso "nessun messaggio di conferma in coda"; fi

if contiene "${BASE}/conferma?token=ffff" "non riuscita"; then verde "un gettone inventato viene respinto"; else rosso "gettone inventato accettato"; fi
if contiene "${BASE}/conferma?token=${GETTONE_MAIL}" "Indirizzo confermato"; then verde "il gettone vero conferma l'indirizzo"; else rosso "conferma non riuscita"; fi
if contiene "${BASE}/conferma?token=${GETTONE_MAIL}" "Indirizzo confermato"; then verde "riaprire lo stesso collegamento non da' errore (doppio clic)"; else rosso "il secondo clic rompe la conferma"; fi

# --- Accesso -------------------------------------------------------------------------
titolo "Accesso"
T=$(gettone "${BASE}/accesso")
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" -d "login=${UTENTE}" -d "password=sbagliatissima" "${BASE}/accesso")
if [[ "${DEST}" == *"/accesso" ]]; then verde "la password sbagliata non fa entrare"; else rosso "password sbagliata -> '${DEST}'"; fi

T=$(gettone "${BASE}/accesso")
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" -d "login=${UTENTE}" -d "password=${PASSWORD}" "${BASE}/accesso")
if [[ "${DEST}" == *"/quartiere" ]]; then verde "la password giusta porta nel quartiere"; else rosso "accesso -> '${DEST}'"; fi

if contiene_seguendo "${BASE}/quartiere" "${UTENTE}"; then verde "la testata saluta per nome"; else rosso "la testata non riconosce l'utente"; fi

# --- Il mondo ---------------------------------------------------------------------------
titolo "Il personaggio"
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/quartiere")
if [[ "${DEST}" == *"/personaggio/nuovo" ]]; then verde "senza personaggio il quartiere rimanda alla creazione"; else rosso "gate del personaggio assente (-> '${DEST}')"; fi

T=$(gettone "${BASE}/personaggio/nuovo")
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" -d "cognome=Kasuga" -d "nome=${PGNOME}" -d "sesso=f" \
  -d "classe=superiori-2" -d "nato_giorno=15" -d "nato_mese=11" -d "stirpe=esper" \
  "${BASE}/personaggio/nuovo")
if [[ "${DEST}" == *"/personaggio/abilita" ]]; then verde "la creazione porta alla distribuzione dei punti"; else rosso "creazione -> '${DEST}'"; fi

# Il compleanno del 15 novembre in 2ª superiore deve dare la coorte 1970:
# è il modello delle coorti giapponesi, e se sbaglia sbaglia in silenzio.
COORTE=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT anno_nascita, sezione, anno FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p === null ? "assente" : $p["anno_nascita"] . "/" . $p["sezione"] . $p["anno"];
' "${PGNOME}")
if [[ "${COORTE}" == "1970/superiori2" ]]; then verde "la classe determina l'anno di nascita (${COORTE})"; else rosso "coorte: ${COORTE} (atteso 1970/superiori2)"; fi

# Finché la scheda è aperta, il quartiere non si apre.
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/quartiere")
if [[ "${DEST}" == *"/personaggio/abilita" ]]; then verde "con la scheda aperta il quartiere aspetta"; else rosso "il cancello della scheda non tiene (-> '${DEST}')"; fi

PAGINA=$(G "${BASE}/personaggio/abilita")
if grep -q "principale" <<< "${PAGINA}"; then verde "i poteri sono stati tirati"; else rosso "nessun potere principale in pagina"; fi
if grep -q "il tuo scopo" <<< "${PAGINA}"; then verde "i tratti portano un obiettivo"; else rosso "nessun obiettivo nei tratti"; fi

# La distribuzione: prima sbagliata, poi giusta.
PUNTI=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT * FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p === null ? 0 : App\Game\Scheda::puntiDaDistribuire($p);
' "${PGNOME}")
T=$(gettone "${BASE}/personaggio/abilita")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  -d "p_rissa=0" -d "p_testa=0" -d "p_dai_suki=0" -d "p_cuore=0" "${BASE}/personaggio/abilita" >/dev/null
CHIUSA=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT scheda FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p["scheda"] ?? "assente";
' "${PGNOME}")
if [[ "${CHIUSA}" == "abbozzo" ]]; then verde "non si chiude la scheda lasciando punti da spendere"; else rosso "scheda chiusa a vuoto: ${CHIUSA}"; fi

# La spartizione non si puo' calcolare in bash: dipende dai tiri, e un
# personaggio che ha gia' 13 di Cuore non ne regge altri quattro. Se la fa
# dire da PHP, che sa a che punto sta ciascuna abilita'.
SPARTIZIONE=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $pg = App\Core\Database::first("SELECT * FROM personaggi WHERE nome = ?", [$argv[1]]);
  if ($pg === null) { echo ""; return; }
  $restano = App\Game\Scheda::puntiDaDistribuire($pg);
  $p = array_fill_keys(App\Game\Scheda::ABILITA, 0);
  while ($restano > 0) {
      $mosso = false;
      foreach (App\Game\Scheda::ABILITA as $a) {
          if ($restano <= 0) { break; }
          if ((int) $pg[$a] + $p[$a] < 15) { $p[$a]++; $restano--; $mosso = true; }
      }
      if (!$mosso) { break; }
  }
  $q = [];
  foreach ($p as $a => $v) { $q[] = "p_{$a}={$v}"; }
  echo implode("&", $q);
' "${PGNOME}")
T=$(gettone "${BASE}/personaggio/abilita")
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  --data-raw "${SPARTIZIONE}" "${BASE}/personaggio/abilita")
if [[ "${DEST}" == *"/personaggio" ]]; then verde "distribuiti i punti, la scheda si chiude"; else rosso "chiusura -> '${DEST}'"; fi

SCHEDA=$(G "${BASE}/personaggio")
if grep -q "punti ferita" <<< "${SCHEDA}"; then verde "la scheda mostra i valori derivati"; else rosso "la scheda non mostra i PF"; fi
if grep -q "giorni al compleanno\|è il tuo compleanno" <<< "${SCHEDA}"; then verde "la scheda conta i giorni al compleanno"; else rosso "nessun compleanno in scheda"; fi

PAGINA=$(G "${BASE}/quartiere")
if grep -q "Dove tutto" <<< "${PAGINA}"; then verde "si comincia in cima ai gradini"; else rosso "punto di partenza sbagliato"; fi
if grep -q "1987" <<< "${PAGINA}"; then verde "l'orologio dice in che anno siamo"; else rosso "l'anno non compare da nessuna parte"; fi
if grep -qE "aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre|gennaio|febbraio|marzo" <<< "${PAGINA}"; then verde "e che giorno e'"; else rosso "la data non compare"; fi

titolo "Carta e orologio"
CARTA=$(G "${BASE}/api/carta")
if grep -q '"nodi"' <<< "${CARTA}"; then verde "/api/carta risponde"; else rosso "/api/carta"; fi
NODI=$(php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo count($d["nodi"] ?? []);' <<< "${CARTA}")
if [[ "${NODI}" -ge 15 ]]; then verde "la carta porta ${NODI} luoghi"; else rosso "la carta ha solo ${NODI} luoghi"; fi
QUI=$(php -r '$d=json_decode(file_get_contents("php://stdin"),true); foreach ($d["nodi"] as $n) if ($n["qui"]) { echo $n["k"]; return; } echo "nessuno";' <<< "${CARTA}")
if [[ "${QUI}" == "gradini" ]]; then verde "la carta sa dove sono"; else rosso "la carta mi mette in '${QUI}'"; fi

BATT=$(G "${BASE}/api/battito")
if grep -q '"quando"' <<< "${BATT}"; then verde "/api/battito risponde"; else rosso "/api/battito"; fi

titolo "Spostamento"
if contiene "${BASE}/luogo/abcb" "ABCB"; then verde "la scheda di un luogo si apre"; else rosso "/luogo/abcb"; fi

# Le immagini dei luoghi sono di due nature, e la pagina deve dirlo. E' la
# regola piu' importante di tutto il progetto applicata alle figure: non si
# presenta un'immagine inventata con lo stesso aspetto di una fotografia.
if contiene "${BASE}/luogo/abcb" "Immagine generata"; then
  verde "un'immagine generata si dichiara tale"; else rosso "l'ABCB non dichiara l'immagine generata"; fi
if contiene "${BASE}/luogo/stazione" "Fotografia di"; then
  verde "una fotografia vera porta l'autore"; else rosso "la stazione non attribuisce la fotografia"; fi
if contiene "${BASE}/luogo/stazione" "CC BY-SA"; then
  verde "e la licenza"; else rosso "manca la licenza sulla fotografia"; fi
# Dal 20/09/2026 tutti e ventidue i luoghi hanno un'immagine, quindi non c'e'
# piu' un luogo su cui provare il ripiego dal vivo: lo copre la prova
# unitaria, che toglie il file e verifica che la pagina non si rompa.
if contiene "${BASE}/luogo/dischi" "Immagine generata"; then
  verde "anche il negozio di dischi ha la sua immagine, dichiarata"; else rosso "il negozio di dischi non ha immagine"; fi
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/luogo/non_esiste")
if [[ "${DEST}" == *"/quartiere" ]]; then verde "un luogo inventato riporta al quartiere"; else rosso "/luogo/non_esiste -> '${DEST}'"; fi

# La meta è «il viale degli alberi» e NON l'ABCB, che sarebbe il posto più
# naturale: il bar apre alle 11 e chiude alle 23, quindi per metà giornata di
# gioco il movimento verrebbe rifiutato e la prova fallirebbe a seconda
# dell'ora — cioè in modo apparentemente casuale, su una macchina sì e su
# un'altra no. Il viale non chiude mai. Se un giorno servisse provare una meta
# con orari, va scelta leggendo prima /api/carta, non a mano.
META_PROVA="viale"
META_NOME="Il viale degli alberi"

T=$(gettone "${BASE}/quartiere")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" -d "verso=${META_PROVA}" "${BASE}/vai" >/dev/null
VIAGGIO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT verso FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p === null ? "assente" : ($p["verso"] ?? "fermo");
' "${PGNOME}")
if [[ "${VIAGGIO}" == "${META_PROVA}" ]]; then
  verde "ci si incammina verso ${META_NOME}"
else
  rosso "dopo /vai il personaggio e' '${VIAGGIO}'"
  # Il perche' lo sa il messaggio lampo: senza, si va a cercare il guasto
  # nell'instradamento quando invece era il luogo a essere chiuso.
  G "${BASE}/quartiere" | grep -o '"avviso errore[^>]*>[^<]*' | head -1 | sed 's/^/        motivo: /'
fi

T=$(gettone "${BASE}/quartiere")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" -d "verso=liceo" "${BASE}/vai" >/dev/null
META=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT verso, luogo FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p === null ? "assente" : ($p["verso"] ?? ("arrivato:" . $p["luogo"]));
' "${PGNOME}")
# Se nel frattempo e' arrivato la prova non ha senso: si salta invece di
# dichiarare un guasto che non c'e'.
if [[ "${META}" == "${META_PROVA}" ]]; then
  verde "non si cambia meta a meta' strada"
elif [[ "${META}" == arrivato:* ]]; then
  verde "(saltata: arrivato prima del controllo)"
else
  rosso "a meta' strada la meta e' diventata '${META}'"
fi

# Il tempo di gioco passa quattro volte piu' in fretta: cinque minuti di
# strada sono poco piu' di un minuto reale. Qui si salta avanti spostando
# l'arrivo nel passato, invece di aspettare davvero.
php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  App\Core\Database::run("UPDATE personaggi SET arrivo_gts = arrivo_gts - 100000 WHERE nome = ?", [$argv[1]]);
' "${PGNOME}" 2>/dev/null
php bin/tick.php
DOVE=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT luogo, verso FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p === null ? "assente" : ($p["luogo"] . ($p["verso"] === null ? "" : " (ancora per strada)"));
' "${PGNOME}")
if [[ "${DOVE}" == "${META_PROVA}" ]]; then verde "il battito fa arrivare chi e' per strada"; else rosso "dopo il battito e' in '${DOVE}'"; fi

# Si guarda il titolo del riquadro «Dove sei», non la pagina intera: il nome
# della meta compare anche nell'elenco delle uscite, quindi un grep generico
# passerebbe pure restando fermi dov'eravamo.
DOVE_DICE=$(G "${BASE}/quartiere" | grep -A3 'occhiello">Dove sei' | grep -o '<h2[^>]*>[^<]*' | sed 's/.*>//')
if [[ "${DOVE_DICE}" == "${META_NOME}" ]]; then
  verde "arrivato: il riquadro «Dove sei» mostra il nuovo luogo"
else
  rosso "il riquadro «Dove sei» dice '${DOVE_DICE}' invece di '${META_NOME}'"
fi

PRESENZE=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT id FROM personaggi WHERE nome = ?", [$argv[1]]);
  if ($p === null) { echo "assente"; return; }
  $n = App\Core\Database::first("SELECT COUNT(*) n FROM presenze WHERE personaggio_id = ?", [$p["id"]]);
  $a = App\Core\Database::first("SELECT COUNT(*) n FROM presenze WHERE personaggio_id = ? AND al_gts IS NULL", [$p["id"]]);
  echo $n["n"], "/", $a["n"];
' "${PGNOME}")
if [[ "${PRESENZE}" == "2/1" ]]; then verde "restano due presenze, una sola aperta"; else rosso "presenze: ${PRESENZE} (attese 2/1)"; fi

TRACCE=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT id FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p === null ? 0 : (int) App\Core\Database::first("SELECT COUNT(*) n FROM tracce WHERE personaggio_id = ?", [$p["id"]])["n"];
' "${PGNOME}")
if [[ "${TRACCE}" -ge 2 ]]; then verde "il passaggio lascia tracce nei luoghi (${TRACCE})"; else rosso "nessuna traccia lasciata"; fi

# Un luogo chiuso a quest'ora di gioco, se ce n'e' uno raggiungibile: e' il
# caso che aveva fatto fallire la prova quando la meta era scelta a mano.
CHIUSO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT luogo FROM personaggi WHERE nome = ?", [$argv[1]]);
  if ($p === null) { echo ""; return; }
  foreach (App\Sim\Luoghi::uscite((string) $p["luogo"]) as $u) {
      if (!$u["aperto"]) { echo $u["a"]; return; }
  }
  echo "";
' "${PGNOME}")
if [[ -n "${CHIUSO}" ]]; then
  T=$(gettone "${BASE}/quartiere")
  curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" -d "verso=${CHIUSO}" "${BASE}/vai" >/dev/null
  ANCORA=$(php -r '
    require "src/autoload.php"; require "src/Support/helpers.php";
    $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
    $p = App\Core\Database::first("SELECT verso FROM personaggi WHERE nome = ?", [$argv[1]]);
    echo $p === null ? "assente" : ($p["verso"] ?? "fermo");
  ' "${PGNOME}")
  if [[ "${ANCORA}" == "fermo" ]]; then verde "non ci si incammina verso un posto chiuso (${CHIUSO})"; else rosso "partito lo stesso verso '${CHIUSO}'"; fi
else
  verde "(nessun luogo chiuso raggiungibile a quest'ora: controllo saltato)"
fi

titolo "Un personaggio per uno"
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/personaggio/nuovo")
if [[ "${DEST}" == *"/quartiere" ]]; then verde "chi ha gia' un personaggio non torna al modulo"; else rosso "il modulo si riapre (-> '${DEST}')"; fi

DOPPIO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT user_id FROM personaggi WHERE nome = ?", [$argv[1]]);
  if ($p === null) { echo "assente"; return; }
  $r = App\Game\Personaggio::crea((int) $p["user_id"], "Altro", "Ayukawa", "m", "superiori", 1, 3, 3, false);
  echo $r["ok"] ? "CREATO" : "rifiutato";
' "${PGNOME}")
if [[ "${DOPPIO}" == "rifiutato" ]]; then verde "il modello rifiuta il secondo personaggio"; else rosso "secondo personaggio: ${DOPPIO}"; fi

OMONIMO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  // L utente 999999 non esiste: se il controllo sugli omonimi non scattasse,
  // la INSERT fallirebbe sulla chiave esterna e si vedrebbe.
  try {
      $r = App\Game\Personaggio::crea(999999, $argv[1], "Kasuga", "f", "superiori", 2, 6, 15, false);
      echo $r["ok"] ? "CREATO" : "rifiutato";
  } catch (Throwable $e) { echo "ECCEZIONE"; }
' "${PGNOME}")
if [[ "${OMONIMO}" == "rifiutato" ]]; then verde "due omonimi nello stesso quartiere non si possono avere"; else rosso "omonimo: ${OMONIMO}"; fi

# --- Nome utente con spazi ---------------------------------------------------------------
titolo "Normalizzazione del nome"
SPAZI=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  echo App\Auth\Auth::normalizeUsername("  Kyosuke   Kasuga ");
')
if [[ "${SPAZI}" == "Kyosuke Kasuga" ]]; then verde "gli spazi doppi si normalizzano"; else rosso "normalizzazione: ottenuto '${SPAZI}'"; fi

# --- F6: il quartiere vivo -------------------------------------------------------------------
titolo "Il quartiere vivo"

# Le sei schermate nuove devono rendersi davvero, non solo rispondere 200:
# php -l non vede un metodo mancante, e una vista che esplode a meta' pagina
# manda 200 con dentro mezza pagina. Si cerca un pezzo di testo di ognuna.
if contiene "${BASE}/voci" "Quello che si dice"; then verde "le voci si aprono"; else rosso "/voci"; fi
if contiene "${BASE}/bacheca" "La bacheca"; then verde "la bacheca si apre"; else rosso "/bacheca"; fi
if contiene "${BASE}/biglietti" "I biglietti"; then verde "i biglietti si aprono"; else rosso "/biglietti"; fi
if contiene "${BASE}/club" "I club"; then verde "l'elenco dei club si apre"; else rosso "/club"; fi
if contiene "${BASE}/club/karate" "Club di karate"; then verde "la scheda di un club si apre"; else rosso "/club/karate"; fi
if contiene "${BASE}/calendario" "Il calendario"; then verde "il calendario si apre"; else rosso "/calendario"; fi

# E il quartiere deve linkarle, se no non le trova nessuno.
if contiene "${BASE}/quartiere" "quello che si dice"; then verde "il quartiere rimanda alle voci"; else rosso "manca il collegamento alle voci"; fi

# Un club inventato non deve dare un errore: rimanda all'elenco.
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/club/non_esiste")
if [[ "${DEST}" == *"/club" ]]; then verde "un club inventato riporta all'elenco"; else rosso "club inventato -> '${DEST}'"; fi

# Iscrizione vera a un club, e uscita.
T=$(gettone "${BASE}/club")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" -d "club=letteratura" "${BASE}/club/iscrivi" >/dev/null
if contiene "${BASE}/club/letteratura" "Lascia il club"; then verde "ci si iscrive a un club"; else rosso "iscrizione al club non riuscita"; fi
T=$(gettone "${BASE}/club")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" -d "club=letteratura" "${BASE}/club/esci" >/dev/null
if contiene "${BASE}/club/letteratura" "Iscriviti"; then verde "e si esce"; else rosso "uscita dal club non riuscita"; fi

# Un avviso in bacheca, appeso e riletto.
T=$(gettone "${BASE}/bacheca")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  -d "tipo=avviso" -d "titolo=Prova di bacheca" -d "testo=Un testo qualunque, scritto da una prova." \
  "${BASE}/bacheca/affiggi" >/dev/null
if contiene "${BASE}/bacheca" "Prova di bacheca"; then verde "un avviso appeso si rilegge"; else rosso "l'avviso non compare in bacheca"; fi

# --- F7: la rifinitura -----------------------------------------------------------------------
titolo "La rifinitura"

# Il diario, che e' un download e non una pagina.
INTESTAZIONI=$(curl -sS -o /dev/null -D- -b "${BISCOTTI}" "${BASE}/diario")
if grep -qi 'content-type: text/markdown' <<< "${INTESTAZIONI}"; then verde "il diario esce come markdown"; else rosso "tipo del diario sbagliato"; fi
if grep -qi 'content-disposition: attachment' <<< "${INTESTAZIONI}"; then verde "e si scarica invece di aprirsi"; else rosso "il diario non si scarica"; fi
if contiene "${BASE}/diario" "Izumi Matsumoto"; then verde "il diario porta l'attribuzione"; else rosso "il diario non attribuisce l'opera"; fi

# La PWA. Il manifesto e il service worker devono uscire col tipo giusto:
# col tipo sbagliato il browser li scarta e non lo dice a nessuno.
if curl -sS -o /dev/null -D- "${BASE}/manifest.webmanifest" | grep -qi 'content-type: application/manifest'; then
  verde "il manifesto esce col tipo giusto"; else rosso "tipo del manifesto sbagliato"; fi
if curl -sS -o /dev/null -D- "${BASE}/sw.js" | grep -qi 'content-type: text/javascript'; then
  verde "il service worker esce col tipo giusto"; else rosso "tipo del service worker sbagliato"; fi
if contiene "${BASE}/sw.js" "cento-gradini-v"; then verde "il service worker e' il nostro"; else rosso "/sw.js non e' il nostro"; fi
if contiene "${BASE}/quartiere" 'rel="manifest"'; then verde "la pagina dichiara il manifesto"; else rosso "manca il rel=manifest"; fi

# L'album illustrato. Serve un ricordo vero: su un album vuoto non c'e'
# niente da illustrare, e la prova direbbe il falso in tutte e due i versi.
php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $u = App\Core\Database::first("SELECT id FROM users WHERE username = ?", [$argv[1]]);
  if ($u === null) { exit; }
  $p = App\Core\Database::first("SELECT id FROM personaggi WHERE user_id = ?", [(int) $u["id"]]);
  if ($p === null) { exit; }
  App\Core\Database::run(
    "INSERT INTO ricordi (personaggio_id, user_id, titolo, testo, gts, luogo) VALUES (?, ?, ?, ?, ?, ?)",
    [(int) $p["id"], (int) $u["id"], "Un pomeriggio qualunque",
     "Non era successo niente, ed era proprio quello il punto.",
     App\Sim\Orologio::lineare(), "gradini"]);
' "${UTENTE}" >/dev/null 2>&1 || true

if contiene "${BASE}/ricordi" 'class="illustrazione"'; then verde "l'album e' illustrato"; else rosso "manca l'illustrazione nell'album"; fi
if contiene "${BASE}/ricordi" 'Un pomeriggio qualunque'; then verde "e il ricordo c'e'"; else rosso "il ricordo non compare"; fi

# --- La fotografia del personaggio -------------------------------------------
# Questa parte gira mentre l'utente di prova e' ancora un giocatore normale:
# serve a dimostrare che senza i galloni non si tocca la faccia di un altro.
titolo "La fotografia"

ritratto_di() {
  php -r '
    require "src/autoload.php"; require "src/Support/helpers.php";
    $GLOBALS["__project_root"] = getcwd();
    App\Core\Config::load(getcwd());
    $p = App\Core\Database::first("SELECT ritratto_file FROM personaggi WHERE nome = ?", [$argv[1]]);
    echo (string) ($p["ritratto_file"] ?? "");
  ' "$1"
}

if contiene "${BASE}/personaggio/profilo" 'data-ritaglio'; then
  verde "la pagina del profilo offre il riquadro di ritaglio"
else rosso "/personaggio/profilo non ha il riquadro"; fi
if contiene "${BASE}/personaggio/profilo" 'name="lato"'; then
  verde "e i campi del ritaglio ci sono anche senza JavaScript"
else rosso "mancano i campi nascosti del ritaglio"; fi

# Il riquadro mostra l'anteprima con `URL.createObjectURL()`, che produce un
# URL `blob:`. Se la CSP non lo ammette il browser blocca l'immagine e non lo
# dice a nessuno tranne che alla console: la pagina torna 200, il modulo
# funziona lo stesso, e il giocatore vede soltanto il messaggio di ripiego con
# il riquadro che non compare mai. Dal server un guasto cosi' e' invisibile —
# per questo si guarda l'intestazione, che e' l'unico punto in cui il
# problema e' ispezionabile senza un browser vero.
CSP=$(curl -sS -o /dev/null -D- -b "${BISCOTTI}" "${BASE}/personaggio/profilo" \
      | grep -i '^content-security-policy:')
if grep -qiE "img-src[^;]*blob:" <<< "${CSP}"; then
  verde "la CSP lascia passare l'anteprima del riquadro (img-src ... blob:)"
else rosso "la CSP blocca l'anteprima del riquadro: img-src senza blob:"; fi

# Si carica un file vero, con un multipart vero: e' l'unico modo di provare
# insieme is_uploaded_file, GD e la scrittura su disco. Un finto POST di soli
# campi passerebbe accanto a tutte e tre le cose.
FOTO="$(mktemp)".png
php -r '
  $im = imagecreatetruecolor(600, 400);
  imagefilledrectangle($im, 0, 0, 299, 399, imagecolorallocate($im, 210, 40, 40));
  imagefilledrectangle($im, 300, 0, 599, 399, imagecolorallocate($im, 40, 40, 210));
  imagepng($im, $argv[1]);
' "${FOTO}"

T=$(gettone "${BASE}/personaggio/profilo")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -F "_token=${T}" -F "foto=@${FOTO};type=image/png" \
  -F "sx=200" -F "sy=0" -F "lato=400" "${BASE}/personaggio/profilo/foto" >/dev/null
FILE=$(ritratto_di "${PGNOME}")
if [[ -n "${FILE}" ]]; then verde "la fotografia risulta assegnata al personaggio"
else rosso "dopo il caricamento il personaggio e' ancora senza fotografia"; fi
if [[ -n "${FILE}" && -f "${RADICE}/assets/img/ritratti/${FILE}" ]]; then
  verde "e il file sta dove ci si aspetta"
else rosso "il file ${FILE:-?} non c'e' in assets/img/ritratti/"; fi
if [[ -n "${FILE}" ]] && php -r '
    $m = getimagesize($argv[1]);
    exit($m !== false && $m[0] === 320 && $m[1] === 320 && $m[2] === IMAGETYPE_WEBP ? 0 : 1);
  ' "${RADICE}/assets/img/ritratti/${FILE}"; then
  verde "quello che si serve e' un WebP di 320 pixel, non il file caricato"
else rosso "il file servito non e' stato riscritto come ci aspettavamo"; fi
if contiene "${BASE}/personaggio" "img/ritratti/${FILE}"; then
  verde "e compare sulla scheda"
else rosso "la fotografia non compare sulla scheda"; fi

# Il pezzo che conta: un giocatore normale non modifica il profilo di un
# altro. Il gettone e' valido apposta — se fosse sbagliato il rifiuto
# arriverebbe dal CSRF e questa prova direbbe il falso.
ALTRO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT id FROM personaggi WHERE nome <> ? LIMIT 1", [$argv[1]]);
  echo $p === null ? 0 : (int) $p["id"];
' "${PGNOME}")
if [[ "${ALTRO}" != "0" ]]; then
  T=$(gettone "${BASE}/personaggio/profilo")
  CODICE=$(curl -sS -o /dev/null -w '%{http_code}' -b "${BISCOTTI}" -c "${BISCOTTI}" \
    -d "_token=${T}" -d "personaggio=${ALTRO}" "${BASE}/personaggio/profilo/foto/togli")
  if [[ "${CODICE}" == "403" ]]; then
    verde "un giocatore non tocca il profilo di un altro"
  else rosso "il profilo di un altro risponde ${CODICE} a un giocatore normale"; fi
else
  rosso "non ho trovato un secondo personaggio su cui provare il divieto"
fi

# E la si toglie: sparisce dalla riga e sparisce dal disco.
T=$(gettone "${BASE}/personaggio/profilo")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" "${BASE}/personaggio/profilo/foto/togli" >/dev/null
if [[ -z "$(ritratto_di "${PGNOME}")" ]]; then verde "togliere la fotografia svuota la riga"
else rosso "la fotografia risulta ancora assegnata"; fi
if [[ -n "${FILE}" && ! -f "${RADICE}/assets/img/ritratti/${FILE}" ]]; then
  verde "e porta via anche il file, che non serviva piu' a nessuno"
else rosso "il file e' rimasto sul disco"; fi
rm -f "${FOTO}"

# --- Il profilo di un'altra persona ------------------------------------------
titolo "Chi sono gli altri"

# Chi si trova qui adesso si puo' guardare. Chi non si e' mai incontrato no:
# non e' una rubrica, e un elenco di tutti gli abitanti consultabile dal
# divano racconterebbe un quartiere diverso da questo.
# Il personaggio di prova puo' benissimo essere solo dove si trova: per
# provare il profilo di un altro bisogna che un altro ci sia. Si sposta li' un
# abitante del quartiere — il motore lo rimettera' al suo giro da solo.
php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $io = App\Core\Database::first("SELECT id, luogo FROM personaggi WHERE nome = ?", [$argv[1]]);
  if ($io === null) { exit; }
  $png = App\Core\Database::first("SELECT id FROM personaggi WHERE png IS NOT NULL LIMIT 1");
  if ($png === null) { exit; }
  App\Core\Database::run(
    "UPDATE personaggi SET luogo = ?, verso = NULL, arrivato_gts = ? WHERE id = ?",
    [$io["luogo"], App\Sim\Orologio::lineare(), (int) $png["id"]]);
' "${PGNOME}" >/dev/null 2>&1 || true

VICINO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $io = App\Core\Database::first("SELECT id, luogo FROM personaggi WHERE nome = ?", [$argv[1]]);
  if ($io === null) { echo 0; exit; }
  $a = App\Core\Database::first(
    "SELECT id FROM personaggi WHERE luogo = ? AND id <> ? AND verso IS NULL AND stato = ? LIMIT 1",
    [$io["luogo"], (int) $io["id"], "attivo"]);
  echo $a === null ? 0 : (int) $a["id"];
' "${PGNOME}")

if [[ "${VICINO}" != "0" ]]; then
  if contiene "${BASE}/chi/${VICINO}" "Cosa provi"; then
    verde "il profilo di chi e' qui adesso si apre"
  else rosso "/chi/${VICINO} non si apre per una persona presente"; fi
  if contiene "${BASE}/chi/${VICINO}" "cosa puoi fare"; then
    verde "e porta alla pagina dei gesti"
  else rosso "il profilo non rimanda ai gesti"; fi
else
  rosso "non ho trovato nessuno nello stesso luogo su cui provare il profilo"
fi

# Uno sconosciuto, lontano e mai incontrato: non si apre.
LONTANO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $io = App\Core\Database::first("SELECT id, luogo FROM personaggi WHERE nome = ?", [$argv[1]]);
  if ($io === null) { echo 0; exit; }
  $a = App\Core\Database::first(
    "SELECT p.id FROM personaggi p
      WHERE p.luogo <> ? AND p.id <> ?
        AND NOT EXISTS (SELECT 1 FROM legami l
                        WHERE (l.da_id = p.id AND l.a_id = ?) OR (l.da_id = ? AND l.a_id = p.id))
      LIMIT 1",
    [$io["luogo"], (int) $io["id"], (int) $io["id"], (int) $io["id"]]);
  echo $a === null ? 0 : (int) $a["id"];
' "${PGNOME}")

if [[ "${LONTANO}" != "0" ]]; then
  DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/chi/${LONTANO}")
  if [[ "${DEST}" == *"/legami" ]]; then
    verde "il profilo di uno sconosciuto non si apre"
  else rosso "uno sconosciuto si apre lo stesso: '${DEST}'"; fi
else
  rosso "non ho trovato uno sconosciuto su cui provare il divieto"
fi

# La carta del giocatore NON deve dire chi e' mosso dal motore e chi no.
if curl -sS -b "${BISCOTTI}" "${BASE}/api/carta" | grep -q '"giocatori"'; then
  rosso "la carta del giocatore distingue i giocatori dagli abitanti"
else verde "la carta del giocatore non distingue giocatori e abitanti"; fi

# --- Le comunicazioni, lato giocatore ----------------------------------------
titolo "Le comunicazioni"

if contiene "${BASE}/comunicazioni" "la gestione del gioco"; then
  verde "la pagina delle comunicazioni dice chi sta parlando"
else rosso "/comunicazioni non si apre o non si presenta"; fi

CODICE=$(curl -sS -o /dev/null -w '%{http_code}' -b "${BISCOTTI}" "${BASE}/admin/comunicazioni")
if [[ "${CODICE}" == "403" ]]; then
  verde "e un giocatore non vede i fili degli altri"
else rosso "/admin/comunicazioni da' ${CODICE} a un giocatore normale"; fi

T=$(gettone "${BASE}/comunicazioni")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  -d "testo=Una domanda dalla prova automatica." "${BASE}/comunicazioni" >/dev/null
if contiene "${BASE}/comunicazioni" "Una domanda dalla prova automatica"; then
  verde "il giocatore scrive e si rilegge"
else rosso "il messaggio del giocatore non compare nel filo"; fi

# /admin: prima negato, poi concesso.
CODICE=$(curl -sS -o /dev/null -w '%{http_code}' -b "${BISCOTTI}" "${BASE}/admin")
if [[ "${CODICE}" == "403" ]]; then verde "/admin e' chiuso a chi non e' amministratore"; else rosso "/admin da' ${CODICE} a un giocatore normale"; fi
if contiene "${BASE}/quartiere" 'class="barra-admin"'; then
  rosso "un giocatore normale vede la barra admin"; else verde "e un giocatore normale non la vede nemmeno"; fi

php bin/console.php user:admin "${UTENTE}" >/dev/null 2>&1 || true
if contiene "${BASE}/admin" "Amministrazione"; then verde "/admin si apre per un amministratore"; else rosso "/admin non si apre nemmeno da amministratore"; fi

# La barra dell'amministrazione sta nella testata, quindi si raggiunge da
# qualunque pagina: prima esisteva solo dentro /admin e bisognava sapere
# l'indirizzo a memoria.
if contiene "${BASE}/quartiere" 'class="barra-admin"'; then
  verde "la barra admin c'e' anche mentre si gioca"; else rosso "la barra admin non compare fuori da /admin"; fi
if contiene "${BASE}/quartiere" "statistiche"; then
  verde "e porta alle sezioni"; else rosso "la barra admin non elenca le sezioni"; fi
if contiene "${BASE}/admin" "Le manopole del mondo"; then verde "e mostra le manopole"; else rosso "il pannello e' incompleto"; fi
# Mostrarle non basta: mostrava il titolo della sezione e dentro «Array» in
# tutte e cinquanta le righe, perche' GameConfig::all() restituisce
# ['chiave' => ['value' => …, 'type' => …]] e la vista lo trattava da scalare.
# La pagina rispondeva 200 e nessuna prova se ne accorgeva.
if contiene "${BASE}/admin" ">Array<"; then
  rosso "il cruscotto stampa «Array» al posto dei valori delle manopole"
else verde "e ne mostra i valori veri, non «Array»"; fi
if contiene "${BASE}/admin" "world.seed"; then
  verde "col nome della manopola accanto"; else rosso "le manopole non compaiono per nome"; fi
# La prova gira con la configurazione locale, che tiene il trasporto su «log»:
# il cruscotto DEVE dirlo. Una posta spenta ha l'aspetto di una posta sana —
# la coda resta vuota perche' Mailer in modalita' log risponde «riuscito» — ed
# e' cosi' che le conferme d'iscrizione sono finite in un file per tre giorni
# mentre chi si era iscritto aspettava.
if contiene "${BASE}/admin" "La posta non parte"; then
  verde "e avvisa che con questa configurazione la posta non parte"
else rosso "il cruscotto non avvisa che il trasporto della posta e' spento"; fi

# Le tre schermate profonde.
if contiene "${BASE}/admin/statistiche" "Il Segreto"; then verde "le statistiche si aprono"; else rosso "/admin/statistiche"; fi
if contiene "${BASE}/admin/statistiche" "degli usi viene notato"; then verde "e misurano se il Segreto morde"; else rosso "manca la quota di usi notati"; fi
if contiene "${BASE}/admin/statistiche" "passaggi medi"; then verde "e se le voci si propagano"; else rosso "mancano le statistiche delle voci"; fi
if contiene "${BASE}/admin/accessi" "Per indirizzo"; then verde "gli accessi si aprono"; else rosso "/admin/accessi"; fi
if contiene "${BASE}/admin/accessi" "Ultimi movimenti"; then verde "e mostrano il registro"; else rosso "manca il registro degli accessi"; fi
if contiene "${BASE}/admin/impostazioni" "Le leve del mondo"; then verde "le leve si aprono"; else rosso "/admin/impostazioni"; fi
if contiene "${BASE}/admin/impostazioni" "orologio del mondo"; then verde "e sono raggruppate per famiglia"; else rosso "le leve non sono raggruppate"; fi
if contiene "${BASE}/admin/impostazioni" "voci.durata_giorni"; then verde "e ci sono tutte"; else rosso "manca qualche leva"; fi

# Cambiare una manopola dal pannello deve cambiarla davvero.
PRIMA=$(php bin/console.php config:get voci.durata_giorni 2>/dev/null | tr -dc '0-9')
T=$(gettone "${BASE}/admin")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  -d "chiave=voci.durata_giorni" -d "valore=19" "${BASE}/admin/config" >/dev/null
DOPO=$(php bin/console.php config:get voci.durata_giorni 2>/dev/null | tr -dc '0-9')
if [[ "${DOPO}" == "19" ]]; then verde "una manopola si cambia dal pannello"; else rosso "la manopola vale '${DOPO}', attesi 19"; fi
php bin/console.php config:set voci.durata_giorni "${PRIMA:-21}" >/dev/null 2>&1 || true

# Le tre pagine nuove dell'amministrazione.
if contiene "${BASE}/admin/utenti" "Gli utenti"; then verde "l'elenco utenti si apre"; else rosso "/admin/utenti"; fi
if contiene "${BASE}/admin/utenti" "${UTENTE}"; then verde "e ci trova dentro l'utente di prova"; else rosso "l'utente di prova non compare in elenco"; fi
# Le tabelle del pannello sono da sei o sette colonne: in colonna da lettura
# (42rem) sbordano, e il riquadro ci mette una barra di scorrimento
# orizzontale — cioe' nasconde meta' dei dati dietro un gesto che nessuno fa.
# Misurato: l'elenco utenti chiede 673 pixel e ne aveva 548.
if contiene "${BASE}/admin/utenti" 'class="contenuto largo"'; then
  verde "il pannello usa la colonna larga"
else rosso "/admin/utenti sta ancora nella colonna da lettura"; fi
if contiene "${BASE}/admin/utenti" 'data-etichetta="personaggio"'; then
  verde "e le celle portano l'etichetta della colonna, per quando si impilano"
else rosso "le celle non hanno data-etichetta: impilate sarebbero mute"; fi
if contiene "${BASE}/quartiere" 'class="contenuto largo"'; then
  verde "la carta del quartiere ha ancora la sua"
else rosso "la pagina del quartiere ha perso la colonna larga"; fi
if contiene "${BASE}/personaggio" 'class="contenuto "'; then
  verde "e le pagine di lettura restano strette"
else rosso "una pagina di lettura si e' allargata"; fi
if contiene "${BASE}/admin/mappa" "La situazione"; then verde "la mappa della situazione si apre"; else rosso "/admin/mappa"; fi
if contiene "${BASE}/admin/mappa" "luogo per luogo"; then verde "e elenca i luoghi"; else rosso "la mappa non elenca i luoghi"; fi
if contiene "${BASE}/admin/mappa" 'data-api="[^"]*admin/api/carta"'; then
  verde "e adesso ha anche la carta disegnata"; else rosso "manca la tela sulla mappa admin"; fi
# La carta dell'amministrazione e' l'unica che puo' dire chi e' giocatore.
if curl -sS -b "${BISCOTTI}" "${BASE}/admin/api/carta" | grep -q '"giocatori"'; then
  verde "la carta admin distingue i giocatori dagli abitanti"
else rosso "la carta admin non distingue giocatori e abitanti"; fi


UID_PROVA=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $u = App\Core\Database::first("SELECT id FROM users WHERE username = ?", [$argv[1]]);
  echo $u === null ? 0 : (int) $u["id"];
' "${UTENTE}")
if contiene "${BASE}/admin/utente/${UID_PROVA}" "Le connessioni"; then verde "la scheda di un utente si apre"; else rosso "/admin/utente/${UID_PROVA}"; fi

# Il profilo di un giocatore si modifica da qui: e' la meta' che il divieto di
# poco fa non poteva provare — che con i galloni la stessa rotta funziona.
PGID=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $p = App\Core\Database::first("SELECT id FROM personaggi WHERE nome = ?", [$argv[1]]);
  echo $p === null ? 0 : (int) $p["id"];
' "${PGNOME}")
if contiene "${BASE}/admin/utente/${UID_PROVA}" "data-ritaglio"; then
  verde "e offre il riquadro per correggere la fotografia"
else rosso "la scheda dell'utente non ha il riquadro"; fi
if contiene "${BASE}/admin/utente/${UID_PROVA}" "name=\"personaggio\" value=\"${PGID}\""; then
  verde "puntato sul personaggio giusto"
else rosso "il riquadro admin non porta il numero del personaggio"; fi

FOTO="$(mktemp)".png
php -r '
  $im = imagecreatetruecolor(500, 500);
  imagefilledrectangle($im, 0, 0, 499, 499, imagecolorallocate($im, 90, 160, 120));
  imagepng($im, $argv[1]);
' "${FOTO}"
T=$(gettone "${BASE}/admin/utente/${UID_PROVA}")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -F "_token=${T}" -F "personaggio=${PGID}" -F "foto=@${FOTO};type=image/png" \
  -F "sx=0" -F "sy=0" -F "lato=500" "${BASE}/personaggio/profilo/foto" >/dev/null
FILE_ADMIN=$(ritratto_di "${PGNOME}")
if [[ -n "${FILE_ADMIN}" ]]; then verde "un amministratore puo' cambiare la fotografia di un giocatore"
else rosso "l'amministratore non e' riuscito a mettere la fotografia"; fi

# Il muro si guarda ADESSO, che la fotografia c'e': fra poco l'amministratore
# la toglie, ed e' un'altra prova.
if contiene "${BASE}/admin/fotografie" "Le fotografie"; then
  verde "il muro delle fotografie si apre"; else rosso "/admin/fotografie"; fi
if contiene "${BASE}/admin/fotografie" "img/ritratti/${FILE_ADMIN}"; then
  verde "e ci trova dentro quella appena caricata"
else rosso "la fotografia caricata non compare sul muro"; fi

T=$(gettone "${BASE}/admin/utente/${UID_PROVA}")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" -d "personaggio=${PGID}" -d "aspetto=Uno che nessuno nota mai" \
  "${BASE}/personaggio/profilo/aspetto" >/dev/null
if php -r '
    require "src/autoload.php"; require "src/Support/helpers.php";
    $GLOBALS["__project_root"] = getcwd();
    App\Core\Config::load(getcwd());
    $p = App\Core\Database::first("SELECT aspetto FROM personaggi WHERE id = ?", [(int) $argv[1]]);
    exit(($p["aspetto"] ?? "") === "Uno che nessuno nota mai" ? 0 : 1);
  ' "${PGID}"; then
  verde "e puo' correggere anche com'e' fatto"
else rosso "l'aspetto non e' stato scritto"; fi

T=$(gettone "${BASE}/admin/utente/${UID_PROVA}")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" \
  -d "_token=${T}" -d "personaggio=${PGID}" "${BASE}/personaggio/profilo/foto/togli" >/dev/null
if [[ -z "$(ritratto_di "${PGNOME}")" ]]; then verde "e puo' togliere la fotografia di un giocatore"
else rosso "l'amministratore non e' riuscito a togliere la fotografia"; fi
if php -r '
    require "src/autoload.php"; require "src/Support/helpers.php";
    $GLOBALS["__project_root"] = getcwd();
    App\Core\Config::load(getcwd());
    $n = App\Core\Database::first(
      "SELECT COUNT(*) n FROM audit_log WHERE action LIKE ? AND target_id = ?",
      ["admin.profilo.%", (int) $argv[1]]);
    exit(((int) ($n["n"] ?? 0)) > 0 ? 0 : 1);
  ' "${UID_PROVA}"; then
  rosso "nel registro c'e' una riga per quello che l'admin ha fatto a SE STESSO"
else
  verde "e quello che fa sul proprio personaggio non sporca il registro"
fi
rm -f "${FOTO}"

# La gestione risponde nello stesso filo, e il giocatore lo rilegge.
T=$(gettone "${BASE}/admin/comunicazioni/${UID_PROVA}")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  -d "utente=${UID_PROVA}" -d "testo=Risposta dalla gestione." "${BASE}/admin/comunicazioni" >/dev/null
if contiene "${BASE}/admin/comunicazioni/${UID_PROVA}" "Risposta dalla gestione"; then
  verde "la gestione risponde nel filo"; else rosso "la risposta non compare nel filo"; fi
if contiene "${BASE}/comunicazioni" "Risposta dalla gestione"; then
  verde "e il giocatore la trova dalla sua parte"
else rosso "il giocatore non vede la risposta"; fi
if contiene "${BASE}/admin/comunicazioni" "${UTENTE}"; then
  verde "il filo compare nell'elenco dei fili"; else rosso "l'elenco dei fili non lo mostra"; fi

# Un utente inventato non deve dare errore: rimanda all'elenco.
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/admin/utente/999999")
if [[ "${DEST}" == *"/admin/utenti" ]]; then verde "un utente inventato riporta all'elenco"; else rosso "utente inventato -> '${DEST}'"; fi

# --- Moderazione -----------------------------------------------------------
# L'utente di prova E' l'amministratore (lo abbiamo appena promosso), quindi
# serve un secondo account su cui provare i provvedimenti.
stato_di() {
  php -r '
    require "src/autoload.php"; require "src/Support/helpers.php";
    $GLOBALS["__project_root"] = getcwd();
    App\Core\Config::load(getcwd());
    $u = App\Core\Database::first("SELECT status, nota_admin FROM users WHERE id = ?", [(int) $argv[1]]);
    echo ($u["status"] ?? "?") . "|" . ($u["nota_admin"] ?? "");
  ' "$1"
}

# Un amministratore non deve potersi sospendere da solo: resterebbe chiuso
# fuori dal proprio pannello, e non c'e' modo di rientrare da web.
T=$(gettone "${BASE}/admin/utente/${UID_PROVA}")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  -d "utente=${UID_PROVA}" -d "azione=sospendi" -d "motivo=mi chiudo fuori" "${BASE}/admin/moderazione" >/dev/null
if [[ "$(stato_di "${UID_PROVA}")" == active* ]]; then
  verde "un amministratore non puo' sospendere se stesso"
else rosso "si e' sospeso da solo: $(stato_di "${UID_PROVA}")"; fi

VITTIMA="prova-mod-$$"
printf 'parolalungabastante\nparolalungabastante\n' \
  | php bin/console.php user:create "${VITTIMA}" "${VITTIMA}@example.invalid" >/dev/null 2>&1
UID_VITTIMA=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"] = getcwd();
  App\Core\Config::load(getcwd());
  $u = App\Core\Database::first("SELECT id FROM users WHERE username = ?", [$argv[1]]);
  echo $u === null ? 0 : (int) $u["id"];
' "${VITTIMA}")

if [[ "${UID_VITTIMA}" != "0" ]]; then
  # Senza motivo non passa: uno stato senza motivo, fra tre mesi, non si sa
  # piu' come interpretarlo.
  T=$(gettone "${BASE}/admin/utente/${UID_VITTIMA}")
  curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
    -d "utente=${UID_VITTIMA}" -d "azione=sospendi" -d "motivo=" "${BASE}/admin/moderazione" >/dev/null
  if [[ "$(stato_di "${UID_VITTIMA}")" == active* ]]; then
    verde "sospendere senza motivo non passa"
  else rosso "sospeso senza motivo: $(stato_di "${UID_VITTIMA}")"; fi

  T=$(gettone "${BASE}/admin/utente/${UID_VITTIMA}")
  curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
    -d "utente=${UID_VITTIMA}" -d "azione=sospendi" -d "motivo=prova automatica" "${BASE}/admin/moderazione" >/dev/null
  if [[ "$(stato_di "${UID_VITTIMA}")" == "suspended|prova automatica" ]]; then
    verde "con un motivo si sospende, e il motivo resta"
  else rosso "sospensione: $(stato_di "${UID_VITTIMA}")"; fi

  T=$(gettone "${BASE}/admin/utente/${UID_VITTIMA}")
  curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
    -d "utente=${UID_VITTIMA}" -d "azione=attiva" -d "motivo=" "${BASE}/admin/moderazione" >/dev/null
  if [[ "$(stato_di "${UID_VITTIMA}")" == active* ]]; then
    verde "e si riattiva"
  else rosso "riattivazione: $(stato_di "${UID_VITTIMA}")"; fi

  php bin/console.php user:delete "${VITTIMA}" >/dev/null 2>&1 <<< "SI" || true
else
  rosso "non sono riuscito a creare il secondo account di prova"
fi

# Una chiave inventata non si crea da web: le manopole nascono da una migrazione.
T=$(gettone "${BASE}/admin")
curl -sS -o /dev/null -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" \
  -d "chiave=chiave.inventata" -d "valore=7" "${BASE}/admin/config" >/dev/null
INVENTATA=$(php bin/console.php config:get chiave.inventata 2>&1 | head -1)
if grep -qiv '^7$' <<< "${INVENTATA}"; then verde "una chiave inventata non si crea dal pannello"; else rosso "il pannello ha creato una chiave nuova"; fi

# --- Uscita ---------------------------------------------------------------------------------
titolo "Uscita"
T=$(gettone "${BASE}/quartiere")
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" -c "${BISCOTTI}" -d "_token=${T}" "${BASE}/esci")
if [[ "${DEST}" == *"/" ]]; then verde "l'uscita riporta alla soglia"; else rosso "uscita -> '${DEST}'"; fi
DEST=$(curl -sS -o /dev/null -w '%{redirect_url}' -b "${BISCOTTI}" "${BASE}/quartiere")
if [[ "${DEST}" == *"/accesso" ]]; then verde "dopo l'uscita il quartiere e' di nuovo chiuso"; else rosso "sessione non chiusa (-> '${DEST}')"; fi

# --- Battito ------------------------------------------------------------------------------------
titolo "Battito"
if php bin/tick.php; then verde "bin/tick.php gira senza errori"; else rosso "bin/tick.php fallisce"; fi
BATTITO=$(php -r '
  require "src/autoload.php"; require "src/Support/helpers.php";
  $GLOBALS["__project_root"]=getcwd(); App\Core\Config::load(getcwd());
  $t = App\Core\Database::first("SELECT ok FROM tick_runs ORDER BY id DESC LIMIT 1");
  echo $t === null ? "mai" : (string) (int) $t["ok"];
')
if [[ "${BATTITO}" == "1" ]]; then verde "il battito si registra come riuscito"; else rosso "ultimo battito: '${BATTITO}'"; fi

printf '\n  %d passate, %d fallite\n\n' "${ok}" "${ko}"
[[ "${ko}" -eq 0 ]]
