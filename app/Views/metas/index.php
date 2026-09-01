<?php
/** @var array $filiaisPermitidas */
/** @var int $filialId */
/** @var array $periodo */
/** @var array $funcionarios */
/** @var array $categorias */
/** @var array|null $metaFilial */
/** @var array $grid */
/** @var array $fechamento */
use App\Core\Auth;
use App\Core\Csrf;

$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];
$filialFechada = $fechamento['status'] !== 'aberto';
$editavel = Auth::papel() === Auth::PAPEL_ADMIN && !$filialFechada;

$fmt = static fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
?>
<div class="toolbar">
  <div>
    <h2>Metas</h2>
    <p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>. Meta da filial e meta individual por categoria — usadas nos pilares 1 e 2 da pontuação 360.</p>
  </div>
</div>

<?php if (count($filiaisPermitidas) > 1): ?>
<form method="get" action="/metas" class="form-padrao" style="max-width:280px; margin-bottom:1rem;">
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

<?php if ($filialFechada): ?>
<div class="callout dica"><span class="callout-label">Somente leitura</span>O fechamento desta filial já foi aprovado neste período — esta tela é somente leitura.</div>
<?php elseif (!$editavel && Auth::papel() !== Auth::PAPEL_ADMIN): ?>
<div class="callout dica"><span class="callout-label">Somente leitura</span>Só o administrador edita metas. Esta tela é somente leitura para o seu papel.</div>
<?php endif; ?>

<form class="form-padrao" method="post" action="/metas" style="max-width:100%">
  <?= Csrf::field() ?>
  <input type="hidden" name="filial_id" value="<?= $filialId ?>">

  <fieldset>
    <legend>Meta da filial</legend>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0 1rem; max-width:640px">
      <div>
        <label for="meta_venda_filial">Meta de venda (R$)</label>
        <?php if ($editavel): ?>
          <input type="text" id="meta_venda_filial" name="meta_venda_filial" value="<?= $fmt($metaFilial['meta_venda'] ?? 0) ?>">
        <?php else: ?>
          <p><?= $fmt($metaFilial['meta_venda'] ?? 0) ?></p>
        <?php endif; ?>
      </div>
      <div>
        <label for="meta_rentabilidade_filial">Meta de rentabilidade (%) — comparativo do dashboard</label>
        <?php if ($editavel): ?>
          <input type="text" id="meta_rentabilidade_filial" name="meta_rentabilidade_filial" value="<?= $fmt($metaFilial['meta_rentabilidade'] ?? 0) ?>">
        <?php else: ?>
          <p><?= $fmt($metaFilial['meta_rentabilidade'] ?? 0) ?></p>
        <?php endif; ?>
      </div>
      <div>
        <label for="valor_premio">Prêmio de filial (R$)</label>
        <?php if ($editavel): ?>
          <input type="text" id="valor_premio" name="valor_premio" value="<?= $fmt($metaFilial['valor_premio'] ?? 250) ?>">
        <?php else: ?>
          <p><?= $fmt($metaFilial['valor_premio'] ?? 250) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <p class="ajuda">Meta de rentabilidade acima é só o valor mostrado no comparativo do dashboard da filial. O piso/teto usados na pontuação da Meta 360 (desconto, rentabilidade, ticket médio) ficam em <a href="/parametros">Parâmetros</a>.</p>
  </fieldset>

  <?php if (empty($funcionarios)): ?>
    <div class="card" style="margin-top:1rem"><p>Nenhum funcionário ativo vinculado a esta filial ainda.</p></div>
  <?php else: ?>
  <fieldset>
    <legend>Meta individual por categoria (R$)</legend>
    <div class="scrollx">
    <table class="faixas">
      <thead>
        <tr>
          <th>Funcionário</th>
          <?php foreach ($categorias as $c): ?><th><?= htmlspecialchars($c['nome'], ENT_QUOTES) ?></th><?php endforeach; ?>
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
                <input type="text" name="meta[<?= $funcionarioId ?>][<?= $categoriaId ?>]" value="<?= $fmt($v) ?>" style="width:6.5rem">
              <?php else: ?>
                <?= $fmt($v) ?>
              <?php endif; ?>
            </td>
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
    <button type="submit" class="btn">Salvar metas</button>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</form>
