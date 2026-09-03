<?php
/**
 * Partial compartilhado pelo painel (admin) e pela vitrine (gerente/funcionário).
 *
 * @var array       $edicao
 * @var array       $gruposRank   list de ['grupo'=>..., 'linhas'=>ranking do CorridaCalculator]
 * @var array       $rankingGeral ranking geral já calculado da aba selecionada
 * @var string      $abaGeral     'trimestre' | 'semestre' | 'ano'
 * @var array       $nomesFiliais [filial_id => nome]
 */
use App\Core\Viz;

$destaqueFuncionarioId = $destaqueFuncionarioId ?? null;
$edicaoId = (int) $edicao['id'];
$abas = ['trimestre' => 'Trimestre', 'semestre' => 'Semestre', 'ano' => 'Ano'];
$ordinal = static fn (int $c): string => $c . 'º';
?>
<div class="secao">
  <h3>Ranking por grupo</h3>
  <p class="secao-sub">Os 5 primeiros de cada grupo recebem o prêmio. Rateio do prêmio bruto por pesos <strong>5-4-3-2-1</strong> (1º = 5/15, 2º = 4/15, … 5º = 1/15); posição sem ninguém não é paga.</p>

  <?php if (empty($gruposRank)): ?>
    <div class="card"><p class="subtitle" style="margin:0">Nenhum grupo cadastrado nesta edição ainda.</p></div>
  <?php else: ?>
    <?php foreach ($gruposRank as $bloco): $grupo = $bloco['grupo']; $linhas = $bloco['linhas']; ?>
      <div class="card">
        <div class="toolbar" style="margin-bottom:.6rem">
          <div>
            <h3 style="font-size:1rem; margin:0"><?= htmlspecialchars($grupo['nome'], ENT_QUOTES) ?></h3>
            <p class="secao-sub" style="margin:.2rem 0 0">Prêmio bruto <strong><?= Viz::money((float) $grupo['premio_bruto']) ?></strong></p>
          </div>
        </div>
        <?php if (empty($linhas)): ?>
          <p class="subtitle" style="margin:0">Sem valor lançado neste grupo ainda.</p>
        <?php else: ?>
        <div class="scrollx">
          <table class="lista">
            <thead>
              <tr><th>Pos.</th><th>Funcionário</th><th>Filial</th><th style="text-align:right">Vendido</th><th style="text-align:right">Prêmio</th></tr>
            </thead>
            <tbody>
              <?php foreach ($linhas as $l):
                $eu = $destaqueFuncionarioId !== null && $l['funcionario_id'] === $destaqueFuncionarioId;
              ?>
              <tr<?= $eu ? ' style="background:var(--primary-tint)"' : '' ?>>
                <td><strong><?= $ordinal((int) $l['colocacao']) ?></strong><?= $l['empate'] ? ' <span class="pill" style="font-size:.62rem">empate</span>' : '' ?></td>
                <td><?= htmlspecialchars($l['nome'], ENT_QUOTES) ?><?= $eu ? ' <span class="badge-papel">você</span>' : '' ?></td>
                <td><?= htmlspecialchars($nomesFiliais[$l['filial_id']] ?? '—', ENT_QUOTES) ?></td>
                <td style="text-align:right; font-variant-numeric:tabular-nums"><?= Viz::money((float) $l['valor_vendido']) ?></td>
                <td style="text-align:right; font-variant-numeric:tabular-nums"><?= $l['premio'] > 0 ? '<strong>' . Viz::money((float) $l['premio']) . '</strong>' : '—' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="secao">
  <h3>Ranking geral acumulado</h3>
  <p class="secao-sub">Soma do valor vendido em todos os grupos. Só classificação — não distribui prêmio.</p>

  <nav class="tabs-filial">
    <?php foreach ($abas as $chave => $rotulo): ?>
      <a href="/corrida?edicao=<?= $edicaoId ?>&amp;rg=<?= $chave ?>" class="<?= $abaGeral === $chave ? 'active' : '' ?>"><?= $rotulo ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if (empty($rankingGeral)): ?>
    <div class="card"><p class="subtitle" style="margin:0">Nenhum valor lançado no período desta aba ainda.</p></div>
  <?php else: ?>
  <div class="scrollx">
    <table class="lista">
      <thead>
        <tr><th>Pos.</th><th>Funcionário</th><th>Filial</th><th style="text-align:right">Total acumulado</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rankingGeral as $l):
          $eu = $destaqueFuncionarioId !== null && $l['funcionario_id'] === $destaqueFuncionarioId;
        ?>
        <tr<?= $eu ? ' style="background:var(--primary-tint)"' : '' ?>>
          <td><strong><?= $ordinal((int) $l['colocacao']) ?></strong></td>
          <td><?= htmlspecialchars($l['nome'], ENT_QUOTES) ?><?= $eu ? ' <span class="badge-papel">você</span>' : '' ?></td>
          <td><?= htmlspecialchars($nomesFiliais[$l['filial_id']] ?? '—', ENT_QUOTES) ?></td>
          <td style="text-align:right; font-variant-numeric:tabular-nums"><?= Viz::money((float) $l['total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
