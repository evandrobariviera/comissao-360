<?php
/**
 * Vitrine somente-leitura da Corrida dos Campeões (gerente e funcionário).
 *
 * @var array|null $edicao
 * @var array      $edicoes
 * @var array      $gruposRank
 * @var array      $rankingGeral
 * @var string     $abaGeral
 * @var array      $nomesFiliais
 * @var int|null   $destaqueFuncionarioId
 * @var float      $totalPremiado
 * @var float      $totalBonus
 * @var array      $produtosEdicao
 * @var array      $bonus
 */
use App\Core\Viz;

$dataBr = static fn (string $d) => $d ? implode('/', array_reverse(explode('-', $d))) : '';
$fechada = $edicao !== null && $edicao['status'] === 'fechada';
?>
<div class="toolbar">
  <div>
    <h2>Corrida dos Campeões</h2>
    <p class="subtitle" style="margin:0">Bonificação trimestral. Todos os funcionários da rede competem juntos.</p>
  </div>
  <?php if (count($edicoes) > 1): ?>
  <form method="get" action="/corrida">
    <select name="edicao" onchange="this.form.submit()" style="padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); font-weight:600;">
      <?php foreach ($edicoes as $e): ?>
        <option value="<?= (int) $e['id'] ?>" <?= $edicao !== null && (int) $e['id'] === (int) $edicao['id'] ? 'selected' : '' ?>>
          <?= (int) $e['trimestre'] ?>º tri / <?= (int) $e['ano'] ?><?= $e['nome'] ? ' — ' . htmlspecialchars($e['nome'], ENT_QUOTES) : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>
</div>

<?php if ($edicao === null): ?>
  <div class="card"><p class="subtitle" style="margin:0">Nenhuma corrida cadastrada ainda.</p></div>
<?php else: ?>

<div class="card">
  <h3 style="margin:0"><?= (int) $edicao['trimestre'] ?>º trimestre / <?= (int) $edicao['ano'] ?><?= $edicao['nome'] ? ' — ' . htmlspecialchars($edicao['nome'], ENT_QUOTES) : '' ?></h3>
  <p class="secao-sub" style="margin:.25rem 0 0">
    <?= $dataBr((string) $edicao['data_inicio']) ?> a <?= $dataBr((string) $edicao['data_fim']) ?> ·
    <span class="pill <?= $fechada ? 'status-aprovado' : 'status-ativo' ?>"><?= $fechada ? 'Encerrada' : 'Em andamento' ?></span>
    <?php if ($fechada): ?> · total pago: <strong><?= Viz::money($totalPremiado + $totalBonus) ?></strong> (rateio <?= Viz::money($totalPremiado) ?> + bônus <?= Viz::money($totalBonus) ?>)<?php endif; ?>
  </p>
</div>

<?php require APP_DIR . '/Views/corrida/_rankings.php'; ?>

<?php endif; ?>
