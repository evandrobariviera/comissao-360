<?php
/** @var array $filiaisPermitidas */
/** @var int $filialId */
/** @var array $periodo */
/** @var array $funcionarios */
/** @var array|null $rentabFilial */
/** @var array|null $checklist */
use App\Core\Csrf;
$nomesMes = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$rotuloPeriodo = $nomesMes[(int) $periodo['mes']] . '/' . $periodo['ano'];

$fmt = static function ($valor): string {
    return $valor === null ? '' : rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
};

$check = static function (?array $checklist, string $col): string {
    return !empty($checklist[$col]) ? 'checked' : '';
};
?>
<div class="toolbar">
  <div>
    <h2>Indicadores do fechamento</h2>
    <p class="subtitle">Período: <strong><?= htmlspecialchars($rotuloPeriodo, ENT_QUOTES) ?></strong>. Lançamento único por funcionário/filial — pode reeditar quantas vezes precisar até o período fechar.</p>
  </div>
</div>

<?php if (count($filiaisPermitidas) > 1): ?>
<form method="get" action="/indicadores" class="form-padrao" style="max-width:280px; margin-bottom:1rem;">
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

<?php if (empty($funcionarios)): ?>
<div class="card"><p>Nenhum funcionário ativo vinculado a esta filial ainda. Cadastre em <a href="/usuarios">Usuários</a>.</p></div>
<?php else: ?>
<form class="form-padrao" method="post" action="/indicadores" style="max-width:680px">
  <?= Csrf::field() ?>
  <input type="hidden" name="filial_id" value="<?= $filialId ?>">

  <fieldset>
    <legend>Indicadores por funcionário</legend>
    <p class="ajuda" style="margin-top:0">Preencha desconto médio, rentabilidade e ticket médio juntos (ou deixe os três em branco para não lançar ainda).</p>
    <table class="faixas">
      <thead><tr><th>Funcionário</th><th style="width:8rem">Desconto médio (%)</th><th style="width:8rem">Rentabilidade (%)</th><th style="width:8rem">Ticket médio (R$)</th></tr></thead>
      <tbody>
        <?php foreach ($funcionarios as $f): ?>
        <tr>
          <td><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></td>
          <td><input type="text" name="desconto[<?= (int) $f['id'] ?>]" value="<?= $fmt($f['desconto_medio']) ?>"></td>
          <td><input type="text" name="rentab[<?= (int) $f['id'] ?>]" value="<?= $fmt($f['rentabilidade_pct']) ?>"></td>
          <td><input type="text" name="ticket[<?= (int) $f['id'] ?>]" value="<?= $fmt($f['ticket_medio']) ?>"></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </fieldset>

  <fieldset>
    <legend>Rentabilidade da filial</legend>
    <label for="rentabilidade_filial">Rentabilidade realizada no mês (%)</label>
    <input type="text" id="rentabilidade_filial" name="rentabilidade_filial" style="max-width:10rem" value="<?= $fmt($rentabFilial['rentabilidade_pct'] ?? null) ?>">
  </fieldset>

  <fieldset>
    <legend>Checklist de equipe</legend>
    <div style="display:grid; gap:.6rem;">
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c1" style="width:auto" <?= $check($checklist, 'c1_sem_falta_injustificada') ?>> Sem falta injustificada</label>
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c2" style="width:auto" <?= $check($checklist, 'c2_cumpriu_escala') ?>> Cumpriu escala / cobriu colegas</label>
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c3" style="width:auto" <?= $check($checklist, 'c3_setor_organizado') ?>> Setor organizado / sem ruptura</label>
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c4" style="width:auto" <?= $check($checklist, 'c4_ajudou_treinou_colega') ?>> Ajudou ou treinou colega</label>
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c5" style="width:auto" <?= $check($checklist, 'c5_loja_bateu_meta_coletiva') ?>> Loja bateu a meta coletiva</label>
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c6" style="width:auto" <?= $check($checklist, 'c6_venda_5_catalogos') ?>> Venda 5 catálogos</label>
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c7" style="width:auto" <?= $check($checklist, 'c7_venda_30_a_vencer') ?>> Venda 30 a vencer</label>
      <label style="display:flex; align-items:center; gap:.5rem;"><input type="checkbox" name="c8" style="width:auto" <?= $check($checklist, 'c8_venda_30_linha_propria') ?>> Venda 30 linha própria</label>
    </div>
  </fieldset>

  <div class="acoes-form">
    <button type="submit" class="btn">Salvar indicadores</button>
  </div>
</form>
<?php endif; ?>
