<?php
/** @var array $periodo */
/** @var array $filiaisPermitidas */
/** @var int $filialId */
/** @var float $metaVenda */
/** @var float $metaRentab */
/** @var float $rentabRealizada */
/** @var float $realizado */
/** @var float $comissaoTotal */
/** @var int $totalFuncionarios */
/** @var array $ranking */
/** @var array $oportunidades */
/** @var array $ritmo */
/** @var array{desconto_medio: ?float, ticket_medio: ?float} $mediasFilial */
use App\Core\Viz;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$nomeFilial = '';
foreach ($filiaisPermitidas as $f) { if ((int) $f['id'] === $filialId) { $nomeFilial = $f['nome']; } }
?>
<h2>Painel da filial</h2>
<p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong></p>

<?php if (count($filiaisPermitidas) > 1): ?>
<form method="get" action="/dashboard" class="form-padrao" style="max-width:280px; margin-bottom:1rem;">
  <label for="filial_id">Filial</label>
  <select id="filial_id" name="filial_id" onchange="this.form.submit()">
    <?php foreach ($filiaisPermitidas as $f): ?>
      <option value="<?= (int) $f['id'] ?>" <?= (int) $f['id'] === $filialId ? 'selected' : '' ?>><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php else: ?>
<p class="subtitle">Filial: <strong><?= htmlspecialchars($nomeFilial, ENT_QUOTES) ?></strong></p>
<?php endif; ?>

<div class="kpi-row">
  <?= Viz::statTile('Comissão prevista', Viz::money($comissaoTotal), $totalFuncionarios . ' funcionários') ?>
  <?= Viz::statTile('Vendas realizadas', Viz::money($realizado), 'meta ' . Viz::money($metaVenda)) ?>
  <?= Viz::statTilePct('Rentabilidade da filial', $rentabRealizada, $metaRentab) ?>
</div>

<div class="secao">
  <h3>Atingimento de meta</h3>
  <div class="card"><?= Viz::meterRow($nomeFilial ?: 'Filial', $realizado, $metaVenda) ?></div>
</div>

<div class="secao">
  <h3>Ritmo diário</h3>
  <p class="secao-sub">Quanto falta vender por dia útil (sem domingo) pra bater a meta do mês, e a trajetória até agora.</p>
  <div class="kpi-row" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
    <?= Viz::statTile('Meta de hoje', Viz::money($ritmo['meta_hoje']), $ritmo['dias_uteis_restantes'] . ' dia(s) útil(eis) restante(s)') ?>
    <?= Viz::statTile('Falta no mês', Viz::money($ritmo['meta_restante']), 'de ' . Viz::money($ritmo['meta_venda']) . ' de meta') ?>
  </div>
  <div class="card"><?= Viz::ritmoDiarioChart($ritmo) ?></div>
</div>

<div class="secao">
  <h3>Indicadores da equipe</h3>
  <p class="secao-sub">Média entre quem já tem indicador lançado no período.</p>
  <div class="kpi-row" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
    <?= Viz::statTile('Desconto médio (equipe)', $mediasFilial['desconto_medio'] !== null ? Viz::pct($mediasFilial['desconto_medio']) : '—') ?>
    <?= Viz::statTile('Ticket médio (equipe)', $mediasFilial['ticket_medio'] !== null ? Viz::money($mediasFilial['ticket_medio']) : '—') ?>
  </div>
</div>

<div class="secao">
  <h3>Oportunidades da equipe</h3>
  <p class="secao-sub">Quem está mais perto de virar a próxima faixa — bom ponto de partida para uma conversa hoje.</p>
  <div class="card"><?= Viz::oportunidadesEquipe($oportunidades) ?></div>
</div>

<div class="secao">
  <h3>Ranking da filial</h3>
  <p class="secao-sub">Total do mês (comissão ajustada + prêmio) por funcionário.</p>
  <div class="card"><?= Viz::ranking($ranking) ?></div>
</div>
