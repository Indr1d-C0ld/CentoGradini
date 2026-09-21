<?php
/** @var list<array<string,mixed>> $utenti */
/** @var string $cerca */
/** @var int $ora */
use App\Sim\Luoghi;
use App\Sim\Scuola;

$quando = static function (?string $t, int $ora): string {
    if ($t === null) { return '—'; }
    $sec = $ora - strtotime($t);
    return match (true) {
        $sec < 120    => 'adesso',
        $sec < 3600   => intdiv($sec, 60) . ' min fa',
        $sec < 86400  => intdiv($sec, 3600) . ' ore fa',
        default       => intdiv($sec, 86400) . ' giorni fa',
    };
};
$online = static fn (?string $t, int $ora): bool => $t !== null && ($ora - strtotime($t)) < 600;
?>
<?= partial('nav_admin', ['qui' => '/admin/utenti']) ?>
<h1>Gli utenti</h1>
<p class="sommario">
  <?= count($utenti) ?> account.
  Chi è collegato negli ultimi dieci minuti ha il pallino acceso.
  Il personaggio sta sulla stessa riga dell'account, perché la domanda vera non è
  chi si è iscritto: è chi sta giocando, dove si trova e in che guaio è.
</p>

<form method="get" action="<?= e(url('/admin/utenti')) ?>" class="riga-config">
  <label>Cerca <input type="text" name="cerca" value="<?= e($cerca) ?>"
         placeholder="nome utente, e-mail, o nome del personaggio"></label>
  <button class="bottone secondario piccolo" type="submit">Cerca</button>
  <?php if ($cerca !== ''): ?>
    <a class="bottone secondario piccolo" href="<?= e(url('/admin/utenti')) ?>">Tutti</a>
  <?php endif; ?>
</form>

<div class="carta">
<div class="tabella-scorre">
<table class="tabella">
  <thead><tr>
    <th></th><th>utente</th><th>stato</th><th>personaggio</th><th>dove</th>
    <th>ultimo accesso</th><th>visto</th>
  </tr></thead>
  <tbody>
  <?php foreach ($utenti as $u): ?>
    <tr>
      <td><?= $online($u['last_seen_at'], $ora) ? '<span class="pallino acceso" title="collegato"></span>'
                                                : '<span class="pallino"></span>' ?></td>
      <td>
        <a href="<?= e(url('/admin/utente/' . (int) $u['id'])) ?>"><strong><?= e($u['username']) ?></strong></a>
        <?php if ((string) $u['role'] === 'admin'): ?><span class="pastiglia">admin</span><?php endif; ?>
        <br><span class="aiuto minuto"><?= e($u['email']) ?></span>
      </td>
      <td>
        <span class="stato stato-<?= e($u['status']) ?>"><?= e(match ((string) $u['status']) {
            'active' => 'attivo', 'pending' => 'da confermare',
            'suspended' => 'sospeso', 'banned' => 'bandito', default => (string) $u['status'],
        }) ?></span>
        <?php if ((string) $u['nota_admin'] !== ''): ?>
          <br><span class="aiuto"><?= e(mb_strimwidth((string) $u['nota_admin'], 0, 44, '…')) ?></span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($u['pg_id'] === null): ?>
          <span class="aiuto">nessuno</span>
        <?php else: ?>
          <?= e($u['nome'] . ' ' . $u['cognome']) ?>
          <br><span class="aiuto">
            <?= e(Scuola::nomeClasse((string) $u['sezione'], (int) $u['anno'])) ?>
            · <?= (int) $u['esper'] === 1 ? 'esper' : 'non esper' ?>
            <?php if ((string) $u['scheda'] !== 'completa'): ?> · <em>scheda da finire</em><?php endif; ?>
            <?php if ((string) $u['pg_stato'] !== 'attivo'): ?> · <strong>traslocato</strong><?php endif; ?>
          </span>
        <?php endif; ?>
      </td>
      <td><?= $u['luogo'] === null ? '<span class="aiuto">—</span>' : e(Luoghi::nome((string) $u['luogo'])) ?></td>
      <td class="minuto"><?= e($quando($u['last_login_at'], $ora)) ?></td>
      <td class="minuto"><?= e($quando($u['last_seen_at'], $ora)) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

<p class="aiuto">
  Creare account e cambiare password si fa dalla console, non da qui:
  <code>php bin/console.php user:create</code> e <code>user:password</code>. Una password
  digitata in un modulo web finisce nei log del browser e nella cronologia; la console la
  chiede senza mostrarla.
</p>
