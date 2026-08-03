<?php
/** @var array $filiaisPermitidas */
/** @var int $filialId */
/** @var array $periodo */
/** @var array $funcionarios */
/** @var array $categorias */
/** @var array $vendas */
use App\Core\Csrf;
$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
?>
<div class="toolbar">
  <div>
    <h2>Lançamento de vendas</h2>
    <p class="subtitle">Período aberto: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>. Lance por funcionário e categoria — o quanto antes melhor, para o Bloco 1 refletir a realidade do mês.</p>
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

<div class="card">
  <?php if (empty($funcionarios)): ?>
    <p>Nenhum funcionário ativo vinculado a esta filial ainda. Cadastre em <a href="/usuarios">Usuários</a>.</p>
  <?php else: ?>
  <form class="form-padrao" method="post" action="/vendas" style="max-width:640px">
    <?= Csrf::field() ?>
    <input type="hidden" name="filial_id" value="<?= $filialId ?>">

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 1rem;">
      <div>
        <label for="funcionario_id">Funcionário</label>
        <select id="funcionario_id" name="funcionario_id" required>
          <option value="">Selecione…</option>
          <?php foreach ($funcionarios as $f): ?>
            <option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="categoria_id">Categoria</label>
        <select id="categoria_id" name="categoria_id" required>
          <option value="">Selecione…</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nome'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="data">Data da venda</label>
        <input type="date" id="data" name="data" required value="<?= date('Y-m-d') ?>" min="<?= sprintf('%04d-%02d-01', $periodo['ano'], $periodo['mes']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <div>
        <label for="valor">Valor (R$)</label>
        <input type="text" id="valor" name="valor" required placeholder="0,00">
      </div>
    </div>

    <label style="display:flex; align-items:center; gap:.5rem; margin-top:1rem;">
      <input type="checkbox" name="eh_sn" value="1" style="width:auto">
      Venda sem nota (S/N) — só marque para Manipulação
    </label>

    <div class="acoes-form">
      <button type="submit" class="btn">Lançar venda</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<h3 style="margin-top:2rem">Lançamentos recentes</h3>
<table class="lista">
  <thead>
    <tr><th>Data</th><th>Funcionário</th><th>Categoria</th><th>Valor</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($vendas as $v): ?>
    <tr>
      <td><?= (new DateTime($v['data']))->format('d/m/Y') ?></td>
      <td><?= htmlspecialchars($v['funcionario_nome'], ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars($v['categoria_nome'], ENT_QUOTES) ?><?= $v['eh_sn'] ? ' <span class="pill">S/N</span>' : '' ?></td>
      <td>R$ <?= number_format((float) $v['valor'], 2, ',', '.') ?></td>
      <td class="acoes">
        <form method="post" action="/vendas/<?= (int) $v['id'] ?>/excluir" onsubmit="return confirm('Excluir este lançamento?');">
          <?= Csrf::field() ?>
          <button type="submit" class="btn perigo pequeno">Excluir</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($vendas)): ?>
    <tr><td colspan="5" style="color:var(--ink-faint)">Nenhuma venda lançada neste período ainda.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
