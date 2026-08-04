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
use App\Core\Viz;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$atingimentoRede = $metaRede > 0 ? ($vendaRealizada / $metaRede) * 100 : 0.0;
?>
<h2>Painel da rede</h2>
<p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong> — visão consolidada das 7 filiais.</p>

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
  <h3>Ranking de funcionários</h3>
  <p class="secao-sub">Top 10 por total do mês (comissão ajustada + prêmio), rede toda.</p>
  <div class="card"><?= Viz::ranking($ranking) ?></div>
</div>

<div class="secao">
  <h3>Distribuição por nível</h3>
  <p class="secao-sub">Quantas pessoas em cada faixa da pontuação 360, da mais baixa para a mais alta.</p>
  <div class="card"><?= Viz::distribuicaoNiveis($distribuicao) ?></div>
</div>
