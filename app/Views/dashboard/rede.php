<?php
/** @var array $periodo */
/** @var float $comissaoTotal */
/** @var float $vendaRealizada */
/** @var float $metaRede */
/** @var int $totalFuncionarios */
/** @var int $filiaisBateram */
/** @var int $totalFiliais */
/** @var array $filiaisComMeta */
/** @var array $ranking */
/** @var array $distribuicao */
/** @var array $oportunidades */
/** @var array<int,string> $mixNomesFiliais */
/** @var array $mixLinhas */
/** @var array|null $corrida */
use App\Core\Viz;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$atingimentoRede = $metaRede > 0 ? ($vendaRealizada / $metaRede) * 100 : 0.0;
?>
<h2>Painel da rede</h2>
<p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong> — visão consolidada das <?= (int) $totalFiliais ?> filiais.</p>

<?php if ($totalFiliais > 0 && $filiaisBateram === 0): ?>
<div class="callout dica" style="margin-top:0">
  <span class="callout-label">Nenhuma filial na meta</span>
  Atingimento médio da rede: <strong><?= Viz::pct($atingimentoRede) ?></strong>.
</div>
<?php endif; ?>

<div class="kpi-row">
  <?= Viz::statTile('Comissão prevista (rede)', Viz::money($comissaoTotal), $totalFuncionarios . ' funcionários') ?>
  <?= Viz::statTile('Vendas realizadas', Viz::money($vendaRealizada), 'meta ' . Viz::money($metaRede)) ?>
  <?= Viz::statTile('Atingimento da rede', Viz::pct($atingimentoRede), 'venda ÷ meta') ?>
  <?= Viz::statTile('Filiais na meta', $filiaisBateram . ' de ' . $totalFiliais, Viz::pct($totalFiliais > 0 ? ($filiaisBateram / $totalFiliais) * 100 : 0) . ' da rede') ?>
</div>

<div class="secao">
  <h3>Atingimento de meta por filial</h3>
  <p class="secao-sub">Venda realizada no mês sobre a meta cadastrada em Metas.</p>
  <div class="card">
    <?php foreach ($filiaisComMeta as $f): ?>
      <?= Viz::meterRow($f['nome'], $f['realizado'], $f['meta']) ?>
    <?php endforeach; ?>
  </div>
</div>

<div class="secao">
  <h3>Distribuição Meta 360</h3>
  <p class="secao-sub">Quantas pessoas em cada faixa de multiplicador da pontuação 360.</p>
  <div class="card flush"><?= Viz::distribuicao360Grid($distribuicao) ?></div>
</div>

<div class="secao">
  <h3>Top comissionados</h3>
  <p class="secao-sub">Top 10 por total do mês (comissão ajustada + prêmio), rede toda.</p>
  <div class="card"><?= Viz::ranking($ranking) ?></div>
</div>

<?php if ($corrida !== null): ?>
<div class="secao">
  <h3>Corrida dos Campeões</h3>
  <p class="secao-sub">Ranking ao vivo por grupo premiado — os mesmos números da tela da Corrida.</p>
  <?= Viz::corridaStrip($corrida['edicao'], $corrida['grupos'], $corrida['diasRestantes']) ?>
</div>
<?php endif; ?>

<?php if (!empty($mixLinhas) && $vendaRealizada > 0): ?>
<div class="secao">
  <h3>Mix de vendas por categoria</h3>
  <p class="secao-sub">Realizado x meta de cada categoria, por filial e na rede — configurado em Parâmetros.</p>
  <div class="card"><?= Viz::mixGrade($mixNomesFiliais, $mixLinhas) ?></div>
</div>
<?php endif; ?>

<div class="secao">
  <h3>Maiores oportunidades da rede</h3>
  <p class="secao-sub">As 5 faixas mais próximas de virar em toda a rede — onde um empurrão hoje rende mais.</p>
  <div class="card"><?= Viz::oportunidadesEquipe($oportunidades) ?></div>
</div>
