<?php
/** @var array $periodo */
/** @var array $vendas */
$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
?>
<div class="toolbar">
  <div>
    <h2>Minhas vendas</h2>
    <p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>. Vendas lançadas pelo seu gerente em seu nome — consulta, sem edição.</p>
  </div>
</div>

<table class="lista">
  <thead>
    <tr><th>Data</th><th>Categoria</th><th>Valor</th></tr>
  </thead>
  <tbody>
    <?php foreach ($vendas as $v): ?>
    <tr>
      <td><?= (new DateTime($v['data']))->format('d/m/Y') ?></td>
      <td><?= htmlspecialchars($v['categoria_nome'], ENT_QUOTES) ?><?= $v['eh_sn'] ? ' <span class="pill">S/N</span>' : '' ?></td>
      <td>R$ <?= number_format((float) $v['valor'], 2, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($vendas)): ?>
    <tr><td colspan="3" style="color:var(--ink-faint)">Nenhuma venda lançada neste período ainda.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
