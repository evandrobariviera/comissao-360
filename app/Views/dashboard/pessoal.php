<?php
/** @var array $periodo */
/** @var string $nome */
/** @var array|null $linha */
/** @var int|null $posicao */
/** @var int $totalNaFilial */
/** @var float $metaIndividual */
/** @var float $realizadoIndividual */
use App\Core\Viz;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
?>
<h2>Meu desempenho</h2>
<p class="subtitle">Olá, <strong><?= htmlspecialchars($nome, ENT_QUOTES) ?></strong> — período <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>.</p>

<?php if ($linha === null): ?>
<div class="card"><p>Ainda não há cálculo disponível para você neste período.</p></div>
<?php else: $p = $linha['pontuacao']; ?>

<div class="kpi-row">
  <?= Viz::statTile('Comissão-base', Viz::money($linha['comissao_base']), 'Bloco 1 — por categoria') ?>
  <?= Viz::statTile('Pontuação 360', number_format($p['pontuacao_total'], 1, ',', '.') . ' pts', $p['nivel']) ?>
  <?= Viz::statTile('Multiplicador', number_format($p['multiplicador_protegido'], 2, ',', '.') . '×', 'modelo protegido') ?>
  <?= Viz::statTile('Total previsto no mês', Viz::money($linha['total']), 'comissão + prêmio de filial') ?>
</div>

<div class="secao">
  <h3>Pontuação por pilar</h3>
  <?= Viz::legendaPilares() ?>
  <div class="card">
    <?= Viz::pilaresBar($p) ?>
  </div>
</div>

<div class="secao">
  <h3>Minha meta individual</h3>
  <p class="secao-sub">Soma das metas por categoria vs. total vendido no mês.</p>
  <div class="card"><?= Viz::meterRow('Meta individual', $realizadoIndividual, $metaIndividual) ?></div>
</div>

<?php if ($posicao !== null): ?>
<div class="secao">
  <h3>Ranking na filial</h3>
  <div class="card"><p style="margin:0">Você está em <strong><?= $posicao ?>º lugar</strong> de <?= $totalNaFilial ?> na sua filial (por total do mês).</p></div>
</div>
<?php endif; ?>

<?php endif; ?>
