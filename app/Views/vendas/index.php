<?php
/** @var array $filiaisPermitidas */
/** @var int $filialId */
/** @var array $periodo */
/** @var array $funcionarios */
/** @var array $categorias */
/** @var array|null $metaFilial */
/** @var array $grid */
/** @var array $gridSn */
/** @var array $ajustes */
use App\Core\Csrf;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$editavel = $periodo['status'] === 'aberto';

$fmt = static fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

$categoriaManipulacaoId = 0;
foreach ($categorias as $c) {
    if ($c['nome'] === 'Manipulação') {
        $categoriaManipulacaoId = (int) $c['id'];
    }
}
?>
<div class="toolbar">
  <div>
    <h2>Vendas</h2>
    <p class="subtitle">Período aberto: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>. Atualize o total já vendido no mês — não é preciso lançar venda por venda, só manter o acumulado em dia.</p>
  </div>
</div>

<?php if (count($filiaisPermitidas) > 1): ?>
<form method="get" action="/vendas" class="form-padrao" style="max-width:280px; margin-bottom:1rem;">
  <label for="filial_id">Filial</label>
  <select id="filial_id" name="filial_id" onchange="this.form.submit()">
    <?php foreach ($filiaisPermitidas as $f): ?>
      <option value="<?= (int) $f['id'] ?>" <?= (int) $f['id'] === $filialId ? 'selected' : '' ?>><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php else: ?>
<p class="subtitle">Filial: <strong><?= htmlspecialchars($filiaisPermitidas[0]['nome'], ENT_QUOTES) ?></strong></p>
<?php endif; ?>

<?php if (!$editavel): ?>
<div class="callout dica"><span class="callout-label">Somente leitura</span>Este período já foi fechado — as telas abaixo são somente leitura.</div>
<?php endif; ?>

<form class="form-padrao" method="post" action="/vendas/bruta" style="max-width:340px">
  <?= Csrf::field() ?>
  <input type="hidden" name="filial_id" value="<?= $filialId ?>">
  <fieldset>
    <legend>Venda bruta da filial</legend>
    <p class="ajuda" style="margin-top:0">Total vendido no mês pela filial inteira, incluindo vendas que não passam pela grade abaixo. Usado na meta da filial e no prêmio de filial.</p>
    <label for="venda_bruta">Venda bruta realizada (R$)</label>
    <?php if ($editavel): ?>
      <input type="text" id="venda_bruta" name="venda_bruta" value="<?= $fmt($metaFilial['venda_bruta_realizada'] ?? 0) ?>">
    <?php else: ?>
      <p><?= $fmt($metaFilial['venda_bruta_realizada'] ?? 0) ?></p>
    <?php endif; ?>
    <?php if (!empty($metaFilial['venda_bruta_atualizado_em'])): ?>
      <p class="ajuda">Última atualização: <?= (new DateTime($metaFilial['venda_bruta_atualizado_em']))->format('d/m/Y H:i') ?></p>
    <?php endif; ?>
  </fieldset>
  <?php if ($editavel): ?>
  <div class="acoes-form">
    <button type="submit" class="btn">Salvar venda bruta</button>
  </div>
  <?php endif; ?>
</form>

<?php if (empty($funcionarios)): ?>
  <div class="card" style="margin-top:1rem"><p>Nenhum funcionário ativo vinculado a esta filial ainda.</p></div>
<?php else: ?>
<form class="form-padrao" method="post" action="/vendas" style="max-width:100%; margin-top:1.8rem">
  <?= Csrf::field() ?>
  <input type="hidden" name="filial_id" value="<?= $filialId ?>">
  <fieldset>
    <legend>Realizado por funcionário/categoria (R$)</legend>
    <p class="ajuda" style="margin-top:0">Total já vendido no mês por cada funcionário — sobrescreva o valor quando tiver um número mais atualizado.</p>
    <div class="scrollx">
    <table class="faixas">
      <thead>
        <tr>
          <th>Funcionário</th>
          <?php foreach ($categorias as $c): $categoriaId = (int) $c['id']; ?>
            <th><?= htmlspecialchars($c['nome'], ENT_QUOTES) ?></th>
            <?php if ($categoriaId === $categoriaManipulacaoId): ?>
              <th>Manip. S/N</th>
            <?php endif; ?>
          <?php endforeach; ?>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($funcionarios as $f):
          $funcionarioId = (int) $f['id'];
          $totalLinha = array_sum($grid[$funcionarioId] ?? []);
        ?>
        <tr>
          <td><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></td>
          <?php foreach ($categorias as $c): $categoriaId = (int) $c['id']; $v = $grid[$funcionarioId][$categoriaId] ?? 0; ?>
            <td>
              <?php if ($editavel): ?>
                <input type="text" name="total[<?= $funcionarioId ?>][<?= $categoriaId ?>]" value="<?= $fmt($v) ?>" style="width:6.5rem">
              <?php else: ?>
                <?= $fmt($v) ?>
              <?php endif; ?>
            </td>
            <?php if ($categoriaId === $categoriaManipulacaoId): $sn = $gridSn[$funcionarioId] ?? 0; ?>
              <td>
                <?php if ($editavel): ?>
                  <input type="text" name="sn[<?= $funcionarioId ?>]" value="<?= $fmt($sn) ?>" style="width:6.5rem">
                <?php else: ?>
                  <?= $fmt($sn) ?>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          <?php endforeach; ?>
          <td><b><?= $fmt($totalLinha) ?></b></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </fieldset>
  <?php if ($editavel): ?>
  <div class="acoes-form">
    <button type="submit" class="btn">Salvar vendas</button>
  </div>
  <?php endif; ?>
</form>
<?php endif; ?>

<h3 style="margin-top:2rem">Ajustes recentes</h3>
<p class="subtitle">Histórico das diferenças gravadas a cada atualização da grade acima — não é lançamento de venda individual.</p>
<table class="lista">
  <thead>
    <tr><th>Data</th><th>Funcionário</th><th>Categoria</th><th>Valor</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($ajustes as $v): ?>
    <tr>
      <td><?= (new DateTime($v['data']))->format('d/m/Y') ?></td>
      <td><?= htmlspecialchars($v['funcionario_nome'], ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars($v['categoria_nome'], ENT_QUOTES) ?><?= $v['eh_sn'] ? ' <span class="pill">S/N</span>' : '' ?></td>
      <td>R$ <?= number_format((float) $v['valor'], 2, ',', '.') ?></td>
      <td class="acoes">
        <?php if ($editavel): ?>
        <form method="post" action="/vendas/<?= (int) $v['id'] ?>/excluir" onsubmit="return confirm('Excluir este ajuste? Isso muda o total acumulado do mês.');">
          <?= Csrf::field() ?>
          <button type="submit" class="btn perigo pequeno">Excluir</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($ajustes)): ?>
    <tr><td colspan="5" style="color:var(--ink-faint)">Nenhum ajuste registrado neste período ainda.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
