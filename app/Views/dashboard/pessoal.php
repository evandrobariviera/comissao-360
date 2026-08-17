<?php
/** @var array $periodo */
/** @var string $nome */
/** @var array|null $linha */
/** @var int|null $posicao */
/** @var int $totalNaFilial */
/** @var float $metaIndividual */
/** @var float $realizadoIndividual */
/** @var float $metaVendaFilial */
/** @var float $realizadoFilial */
/** @var float $metaRentabFilial */
/** @var float $rentabFilialRealizada */
/** @var array $ritmo */
/** @var array|null $checklist */
use App\Core\Viz;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
?>
<h2>Minha performance</h2>
<p class="subtitle">Olá, <strong><?= htmlspecialchars($nome, ENT_QUOTES) ?></strong> — período <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>.</p>

<?php if ($linha === null): ?>
<div class="card"><p>Ainda não há cálculo disponível para você neste período.</p></div>
<?php else: $p = $linha['pontuacao']; $dq = $p['detalhe_qualidade']; ?>

<div class="dash-cols">
  <div class="dash-col-principal">

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
      <h3>Meus indicadores</h3>
      <p class="secao-sub">Piso e teto vigentes (parâmetro global, ou override desta filial) — é a régua que define quantos pontos cada indicador vale no pilar Qualidade.</p>
      <div class="kpi-row">
        <?= Viz::faixaGauge('Desconto médio', $dq['desconto']['valor'], $dq['desconto']['piso'], $dq['desconto']['teto'], $dq['desconto']['maior_melhor']) ?>
        <?= Viz::faixaGauge('Minha rentabilidade', $dq['rentab_funcionario']['valor'], $dq['rentab_funcionario']['piso'], $dq['rentab_funcionario']['teto'], $dq['rentab_funcionario']['maior_melhor']) ?>
        <?= Viz::faixaGauge('Ticket médio', $dq['ticket_medio']['valor'], $dq['ticket_medio']['piso'], $dq['ticket_medio']['teto'], $dq['ticket_medio']['maior_melhor'], '', 'R$ ') ?>
      </div>
    </div>

    <?php if ($checklist !== null): ?>
    <div class="secao">
      <h3>Checklist de equipe da filial</h3>
      <p class="secao-sub">Itens coletivos do pilar Equipe — valem pra todo mundo da filial igual, inclusive você.</p>
      <div class="card"><?= Viz::checklistList($checklist) ?></div>
    </div>
    <?php endif; ?>

    <div class="secao">
      <h3>Próxima faixa</h3>
      <p class="secao-sub">Quanto falta vender em cada categoria para pular de faixa — a alíquota nova vale sobre o total, então vale a pena empurrar.</p>
      <div class="card"><?= Viz::oportunidadesFuncionario($linha['detalhe_categorias']) ?></div>
    </div>

    <div class="secao">
      <h3>Minhas vendas por categoria</h3>
      <p class="secao-sub">Tudo que você vendeu no mês, a faixa aplicada e a comissão gerada — categoria por categoria.</p>
      <div class="card">
        <table class="lista">
          <thead><tr><th>Categoria</th><th>Vendido</th><th>Faixa aplicada</th><th>Comissão</th></tr></thead>
          <tbody>
            <?php foreach ($linha['detalhe_categorias'] as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['categoria'], ENT_QUOTES) ?></td>
              <td><?= Viz::money($d['valor']) ?></td>
              <td><?= Viz::pct($d['percentual']) ?></td>
              <td><?= Viz::money($d['comissao']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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

  </div>

  <div class="dash-col-filial">
    <h2 style="font-size:1.25rem">Painel da filial</h2>
    <p class="subtitle" style="margin-bottom:1.1rem">Panorama da loja inteira — não só a sua parte.</p>

    <div class="secao" style="margin-top:0">
      <h3>Ritmo diário</h3>
      <p class="secao-sub">Quanto falta vender por dia útil (sem domingo) pra bater a meta do mês.</p>
      <div class="kpi-row" style="grid-template-columns:1fr">
        <?= Viz::statTile('Meta de hoje', Viz::money($ritmo['meta_hoje']), $ritmo['dias_uteis_restantes'] . ' dia(s) útil(eis) restante(s)') ?>
        <?= Viz::statTile('Falta no mês', Viz::money($ritmo['meta_restante']), 'de ' . Viz::money($ritmo['meta_venda']) . ' de meta') ?>
      </div>
      <div class="card"><?= Viz::ritmoDiarioChart($ritmo) ?></div>
    </div>

    <div class="secao">
      <h3>Meta de venda</h3>
      <div class="card"><?= Viz::meterRow('Venda bruta da filial', $realizadoFilial, $metaVendaFilial) ?></div>
    </div>

    <div class="secao">
      <h3>Rentabilidade</h3>
      <div class="kpi-row" style="grid-template-columns:1fr">
        <?= Viz::statTilePct('Rentabilidade da filial', $rentabFilialRealizada, $metaRentabFilial) ?>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>
