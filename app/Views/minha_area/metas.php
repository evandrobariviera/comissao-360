<?php
/** @var array $periodo */
/** @var array $linhas */
/** @var float $metaTotal */
/** @var float $realizadoTotal */
use App\Core\Viz;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
?>
<div class="toolbar">
  <div>
    <h2>Minhas metas</h2>
    <p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>. Meta por categoria vs. o que já foi vendido em seu nome — consulta, sem edição.</p>
  </div>
</div>

<div class="card">
  <?= Viz::meterRow('Total (todas as categorias)', $realizadoTotal, $metaTotal) ?>
</div>

<div class="secao">
  <h3>Por categoria</h3>
  <div class="card">
    <?php foreach ($linhas as $l): ?>
      <?= Viz::meterRow($l['categoria'], $l['realizado'], $l['meta']) ?>
    <?php endforeach; ?>
    <?php if (empty($linhas)): ?>
      <p class="subtitle">Nenhuma categoria ativa cadastrada.</p>
    <?php endif; ?>
  </div>
</div>
