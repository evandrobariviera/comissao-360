<?php
/** @var array $periodo */
/** @var array $linhas */
/** @var float $total */
$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
?>
<div class="toolbar">
  <div>
    <h2>Minhas vendas</h2>
    <p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>. Total já vendido em seu nome no mês, por categoria — atualizado pelo seu gerente, consulta sem edição.</p>
  </div>
</div>

<table class="lista">
  <thead>
    <tr><th>Categoria</th><th>Valor</th></tr>
  </thead>
  <tbody>
    <?php foreach ($linhas as $l): ?>
    <tr>
      <td><?= htmlspecialchars($l['categoria'], ENT_QUOTES) ?></td>
      <td>R$ <?= number_format((float) $l['valor'], 2, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($linhas)): ?>
    <tr><td colspan="2" style="color:var(--ink-faint)">Nenhuma categoria ativa cadastrada.</td></tr>
    <?php endif; ?>
  </tbody>
  <tfoot>
    <tr><td><b>Total</b></td><td><b>R$ <?= number_format($total, 2, ',', '.') ?></b></td></tr>
  </tfoot>
</table>
