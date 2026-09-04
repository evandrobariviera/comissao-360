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
/** @var array<int,string> $mixNomesFiliais */
/** @var array $mixLinhas */
use App\Core\Auth;
use App\Core\Viz;

/** @var array $simulador */
$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$simulador = $simulador ?? [];
?>

<?php if ($linha === null): ?>
<h2>Minha performance</h2>
<p class="subtitle">Olá, <strong><?= htmlspecialchars($nome, ENT_QUOTES) ?></strong> — período <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>.</p>
<div class="card"><p>Ainda não há cálculo disponível para você neste período.</p></div>
<?php else: $p = $linha['pontuacao']; $dq = $p['detalhe_qualidade']; ?>

<div class="balc-hero">
  <div class="balc-saud">Olá,</div>
  <div class="balc-nome"><?= htmlspecialchars($nome, ENT_QUOTES) ?></div>
  <div class="balc-box">
    <div class="balc-box-label">Comissão projetada no mês</div>
    <div class="balc-box-val"><?= Viz::money($linha['total']) ?></div>
    <div class="balc-box-sub">comissão ajustada + prêmio de filial · <?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></div>
  </div>
  <div class="balc-nivel">
    <div>
      <div class="balc-nivel-label">Nível Meta 360</div>
      <div class="balc-nivel-pts"><?= number_format($p['pontuacao_total'], 1, ',', '.') ?> pontos</div>
    </div>
    <div class="balc-nivel-val"><?= htmlspecialchars($p['nivel'], ENT_QUOTES) ?> · <?= number_format($p['multiplicador_protegido'], 2, ',', '.') ?>×</div>
  </div>
</div>

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
      <h3>Minha qualidade</h3>
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

    <?php if (!empty($simulador)): ?>
    <div class="secao">
      <h3>Simulador</h3>
      <p class="secao-sub">Se eu vender mais numa categoria, quanto a comissão sobe?</p>
      <div class="simulador" id="simulador" data-cats='<?= htmlspecialchars(json_encode($simulador, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS), ENT_QUOTES) ?>'>
        <p class="sim-desc">A alíquota da faixa incide sobre o total vendido na categoria — pular de faixa recalcula tudo.</p>
        <div class="sim-row">
          <select id="sim-cat">
            <?php foreach ($simulador as $s): ?>
              <option><?= htmlspecialchars($s['nome'], ENT_QUOTES) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="number" id="sim-val" min="0" step="50" inputmode="numeric" placeholder="R$ 500">
          <button type="button" class="btn" id="sim-btn">Ver</button>
        </div>
        <div class="sim-result" id="sim-result">
          <div class="sim-result-label">Comissão adicional estimada</div>
          <div class="sim-result-val" id="sim-out">+ R$ 0,00</div>
          <div class="sim-result-diff" id="sim-diff"></div>
        </div>
      </div>
    </div>
    <script>
    (function(){
      var box = document.getElementById('simulador');
      if (!box) return;
      var cats = JSON.parse(box.dataset.cats || '[]');
      var fmtPct = function(v){ return (Math.round(v * 100) / 100).toString().replace('.', ','); };
      var brl = function(v){ return 'R$ ' + v.toFixed(2).replace('.', ','); };
      var aliquota = function(valor, faixas){
        for (var i = 0; i < faixas.length; i++){
          if (faixas[i].ate === null || valor <= faixas[i].ate) return faixas[i].pct;
        }
        return 0;
      };
      var calcular = function(){
        var cat = cats.find(function(c){ return c.nome === document.getElementById('sim-cat').value; });
        var add = parseFloat(document.getElementById('sim-val').value) || 0;
        var res = document.getElementById('sim-result');
        if (!cat || add <= 0){ res.classList.remove('show'); return; }
        var atual = cat.valor_atual;
        var faAtual = aliquota(atual, cat.faixas);
        var faNovo = aliquota(atual + add, cat.faixas);
        var ganho = (atual + add) * faNovo / 100 - atual * faAtual / 100;
        document.getElementById('sim-out').textContent = '+ ' + brl(Math.max(0, ganho));
        document.getElementById('sim-diff').textContent = faNovo > faAtual
          ? 'Sobe da faixa de ' + fmtPct(faAtual) + '% para ' + fmtPct(faNovo) + '% — aplicada sobre o total da categoria.'
          : 'Continua na faixa de ' + fmtPct(faAtual) + '%.';
        res.classList.add('show');
      };
      document.getElementById('sim-btn').addEventListener('click', calcular);
      document.getElementById('sim-val').addEventListener('keydown', function(e){ if (e.key === 'Enter') calcular(); });
    })();
    </script>
    <?php endif; ?>

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

    <?php if (!empty($mixLinhas) && $realizadoFilial > 0): ?>
    <div class="secao">
      <h3>Mix de vendas por categoria</h3>
      <p class="secao-sub">Realizado x meta da sua filial, comparado à rede.</p>
      <div class="card"><?= Viz::mixGrade($mixNomesFiliais, $mixLinhas) ?></div>
    </div>
    <?php endif; ?>

    <?php if (Auth::papel() === Auth::PAPEL_GERENTE): ?>
    <div class="secao">
      <a href="/painel-filial" class="btn secundario" style="width:100%; text-align:center; box-sizing:border-box">Ver painel completo da filial →</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>
