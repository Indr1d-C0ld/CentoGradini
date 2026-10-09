<?php
/**
 * Il pannello dei poteri, sul posto.
 *
 * Mostra prima di tutto il rischio, e solo dopo il pulsante: usare un potere
 * in mezzo alla gente dev'essere una decisione, non un clic distratto.
 *
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $dati
 */
use App\Game\Scheda;
use App\Game\Segreto;

if (!(bool) $pg['esper'] || $pg['verso'] !== null) {
    return;
}
$poteri = Scheda::poteri((int) $pg['id']);
if ($poteri === []) {
    return;
}
$testimoni = Segreto::testimoniPossibili($pg, (string) $pg['luogo']);
$folla     = (int) $dati['folla'];
$calore    = (int) ($dati['calore'] ?? 0);
$fattore   = \App\Game\Stati::ha((int) $pg['id'], 'invisibile') ? 0.2 : 1.0;
?>
<div class="carta">
  <p class="occhiello">Quello che potresti fare</p>

  <p class="rischio">
    <?php if ($testimoni === [] && $folla === 0): ?>
      <b>Qui non c'è nessuno.</b> È il momento in cui una cosa impossibile non costa niente.
    <?php else: ?>
      <b>Attento.</b>
      <?php if ($testimoni !== []): ?>
        Ci sono <?= count($testimoni) ?> <?= count($testimoni) === 1 ? 'persona che ti conosce' : 'persone che ti conoscono' ?> e non <?= count($testimoni) === 1 ? 'sa' : 'sanno' ?> niente di te<?= $folla > 0 ? ', e ' : '.' ?>
      <?php endif; ?>
      <?php if ($folla > 0): ?><?= e($dati['folla_dice']) ?> di passaggio.<?php endif; ?>
    <?php endif; ?>
  </p>

  <table class="tabellina poteri-qui">
    <thead><tr><th>Potere</th><th style="text-align:right">PP</th><th style="text-align:right">Rischio</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($poteri as $p):
      $costo = Scheda::costo($p);
      $puoi  = (int) $pg['pp'] >= $costo;
      // Il rischio mostrato è quello del testimone più attento: è il numero
      // che conta davvero, e mostrarne una media sarebbe rassicurare a torto.
      $peggio = 0;
      foreach ($testimoni as $t) {
          $peggio = max($peggio, Segreto::probabilitaNota(
              (int) $p['vistosita'], (int) $t['testa'], (int) $p['controllo'], null, $calore, $fattore));
      }
      $invisibile = (int) $p['vistosita'] === 0;
    ?>
      <tr<?= $puoi ? '' : ' class="spento"' ?>>
        <td>
          <b><?= e($p['nome']) ?></b>
          <?php if ((int) $p['primario'] === 1): ?><em class="marchio-primario">principale</em><?php endif; ?>
          <br><span class="tenue">controllo <?= (int) $p['controllo'] ?>%<?=
            in_array($p['pkey'], [...\App\Game\Poteri::CON_EFFETTO, 'sogni', 'blocco'], true) ? '' : ' · si vede, e basta' ?></span>
          <?php
            // La scelta (dove, su chi) sta sotto il nome e non accanto al
            // pulsante: su un telefono la colonna del pulsante e' troppo
            // stretta e la tendina mostrava una lettera sola. L'attributo
            // `form` la lega al modulo che sta nell'ultima colonna.
            $scelte = \App\Game\Poteri::scelte($pg, (string) $p['pkey']);
            $serve  = \App\Game\Poteri::parametro((string) $p['pkey']);
            $idForm = 'potere-' . $p['pkey'];
          ?>
          <?php if ($puoi && $serve !== null && $scelte !== []): ?>
            <select name="scelta" form="<?= e($idForm) ?>" class="scelta-potere"
                    aria-label="<?= $serve === 'luogo' ? 'Dove' : 'Su chi' ?>">
              <?php foreach ($scelte as $s): ?>
                <option value="<?= e($s['valore']) ?>"><?= e($s['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </td>
        <td style="text-align:right"><?= $costo ?></td>
        <td style="text-align:right">
          <?php if ($invisibile): ?>
            <span class="tenue">nessuno</span>
          <?php elseif ($testimoni === [] && $folla === 0): ?>
            <span class="tenue">—</span>
          <?php else: ?>
            <b class="<?= $peggio >= 50 ? 'rischio-alto' : ($peggio >= 20 ? 'rischio-medio' : '') ?>"><?= $peggio ?>%</b>
          <?php endif; ?>
        </td>
        <td style="text-align:right">
          <?php if ($p['pkey'] === 'sogni'): ?>
            <span class="tenue">arrivano di notte</span>
          <?php elseif ($puoi): ?>
            <?php if ($serve !== null && $scelte === []): ?>
              <span class="tenue"><?= $serve === 'luogo' ? 'nessun posto' : 'nessuno qui' ?></span>
            <?php else: ?>
            <form method="post" action="<?= e(url('/potere')) ?>" id="<?= e($idForm) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="potere" value="<?= e($p['pkey']) ?>">
              <button type="submit" class="secondario">usa</button>
            </form>
            <?php endif; ?>
          <?php else: ?>
            <span class="tenue">senza forze</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p class="aiuto">
    La percentuale è quella del testimone più attento, non una media: è il numero che decide.
    Il buio, la pioggia e un controllo alto la abbassano.
  </p>
</div>
