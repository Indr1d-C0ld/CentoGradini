<?php
/** @var array<string,mixed> $u */
/** @var array<string,mixed>|null $pg */
/** @var array<string,mixed> $dettagli */
/** @var list<array<string,mixed>> $registro */
/** @var int $ora */
use App\Game\Scheda;
use App\Sim\Luoghi;
use App\Sim\Scuola;
?>
<h1><?= e($u['username']) ?>
  <?php if ((string) $u['role'] === 'admin'): ?><span class="pastiglia">admin</span><?php endif; ?>
</h1>
<p class="sommario">
  <span class="stato stato-<?= e($u['status']) ?>"><?= e(match ((string) $u['status']) {
      'active' => 'attivo', 'pending' => 'da confermare',
      'suspended' => 'sospeso', 'banned' => 'bandito', default => (string) $u['status'],
  }) ?></span>
  · <?= e($u['email']) ?>
  · iscritto il <?= e(substr((string) $u['created_at'], 0, 10)) ?>
</p>

<?php if ((string) $u['nota_admin'] !== ''): ?>
  <div class="avviso attenzione">
    <strong>Nota:</strong> <?= e($u['nota_admin']) ?>
    <?php if ($u['nota_admin_at'] !== null): ?>
      <span class="aiuto">— <?= e(substr((string) $u['nota_admin_at'], 0, 16)) ?></span>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="carta">
  <h2>Le connessioni</h2>
  <p>
    Ultimo accesso: <strong><?= e((string) ($u['last_login_at'] ?? '—')) ?></strong><?php
      if ($u['last_login_ip'] !== null): ?>
      da <code><?= e((string) @inet_ntop($u['last_login_ip']) ?: '?') ?></code><?php
      endif; ?>.
    Ultima attività: <strong><?= e((string) ($u['last_seen_at'] ?? '—')) ?></strong>.
  </p>
  <p class="aiuto">
    Indirizzo confermato:
    <?= $u['email_verified_at'] === null ? 'no' : e(substr((string) $u['email_verified_at'], 0, 16)) ?>
    · inviti di conferma spediti: <?= (int) $u['verify_count'] ?>
  </p>
</div>

<?php if ($pg === null): ?>
  <div class="carta"><p>Non ha ancora creato un personaggio.</p></div>
<?php else: ?>
<div class="carta">
  <h2><?= e($pg['nome'] . ' ' . $pg['cognome']) ?></h2>
  <p class="occhiello">
    <?= e(Scuola::nomeClasse((string) $pg['sezione'], (int) $pg['anno'])) ?>
    · nato il <?= (int) $pg['nato_giorno'] ?>/<?= (int) $pg['nato_mese'] ?>/<?= (int) $pg['anno_nascita'] ?>
    · <?= (int) $pg['esper'] === 1 ? 'esper' : 'non esper' ?>
    · <?= e(Luoghi::nome((string) $pg['luogo'])) ?>
    <?php if ((string) $pg['stato'] !== 'attivo'): ?> · <strong>traslocato</strong><?php endif; ?>
  </p>
  <p><?= e(Scheda::riga($pg)) ?></p>

  <div class="griglia-numeri">
    <?php foreach ([
        'incidenti'   => $dettagli['incidenti'] ?? 0,
        'chi ha capito' => $dettagli['sanno'] ?? 0,
        'confidato a' => $dettagli['confidato'] ?? 0,
        'legami'      => $dettagli['legami'] ?? 0,
        'voci sentite'=> $dettagli['voci'] ?? 0,
        'ricordi'     => $dettagli['ricordi'] ?? 0,
    ] as $nome => $n): ?>
      <div class="numero">
        <span class="numero-valore"><?= (int) $n ?></span>
        <span class="numero-nome"><?= e($nome) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (($dettagli['poteri'] ?? []) !== []): ?>
    <p><strong>Poteri:</strong>
    <?php foreach ($dettagli['poteri'] as $i => $p): ?>
      <?= $i > 0 ? ' · ' : '' ?><?= e($p['nome']) ?><?php
        if ((int) $p['primario'] === 1): ?> <span class="aiuto">(primario)</span><?php endif; ?>
      <span class="aiuto">c.<?= (int) $p['controllo'] ?>, usi <?= (int) $p['usi'] ?></span>
    <?php endforeach; ?>
    </p>
  <?php endif; ?>
  <?php if (($dettagli['tratti'] ?? []) !== []): ?>
    <p><strong>Tratti:</strong> <?= e(implode(' · ', array_column($dettagli['tratti'], 'nome'))) ?></p>
  <?php endif; ?>
  <?php if (($dettagli['club'] ?? []) !== []): ?>
    <p><strong>Club:</strong> <?= e(implode(' · ', array_column($dettagli['club'], 'nome'))) ?></p>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="carta">
  <h2>Moderazione</h2>
  <p class="aiuto">
    Ogni provvedimento vuole un motivo scritto, e non per burocrazia: uno stato senza motivo,
    fra tre mesi, non si sa più come interpretarlo — e nel dubbio non si riattiva nessuno.
    Tutto finisce anche nel registro.
  </p>
  <form method="post" action="<?= e(url('/admin/moderazione')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="utente" value="<?= (int) $u['id'] ?>">
    <label>Motivo <input type="text" name="motivo" maxlength="255"
           value="<?= e((string) $u['nota_admin']) ?>"></label>
    <div class="bottoni">
      <?php
        $azioni = [
          'attiva'    => ['Riattiva',  'bottone'],
          'sospendi'  => ['Sospendi',  'bottone secondario'],
          'bandisci'  => ['Bandisci',  'bottone secondario'],
          'nota'      => ['Solo la nota', 'bottone secondario'],
        ];
        if ((string) $u['role'] !== 'admin') {
            $azioni['promuovi'] = ['Promuovi ad admin', 'bottone secondario'];
        } else {
            $azioni['retrocedi'] = ['Togli admin', 'bottone secondario'];
        }
      ?>
      <?php foreach ($azioni as $k => [$etichetta, $classe]): ?>
        <button class="<?= e($classe) ?> piccolo" type="submit" name="azione" value="<?= e($k) ?>">
          <?= e($etichetta) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<?php if ($registro !== []): ?>
<div class="carta">
  <h2>Il registro</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>quando</th><th>cosa</th><th>dettagli</th></tr></thead>
    <tbody>
    <?php foreach ($registro as $r): ?>
      <tr>
        <td class="minuto"><?= e(substr((string) $r['created_at'], 5, 14)) ?></td>
        <td class="minuto"><?= e($r['action']) ?></td>
        <td class="aiuto"><?= e(mb_strimwidth((string) ($r['meta'] ?? ''), 0, 80, '…')) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>
