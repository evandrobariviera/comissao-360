<?php
/** @var array $categoria */
/** @var array $faixas */
/** @var array $composicoes */
/** @var array $outrasCategorias */
use App\Core\Csrf;
use App\Models\Categoria;

$totalFaixas = 6;
$totalComposicoes = 2;

$faixasLinhas = array_pad(array_map(static fn ($f) => [
    'limite_ate' => $f['limite_ate'] !== null ? rtrim(rtrim(number_format((float) $f['limite_ate'], 2, '.', ''), '0'), '.') : '',
    'percentual' => rtrim(rtrim(number_format((float) $f['percentual'], 2, '.', ''), '0'), '.'),
], $faixas), $totalFaixas, ['limite_ate' => '', 'percentual' => '']);

$composicoesLinhas = array_pad(array_map(static fn ($c) => [
    'fonte_tipo' => $c['fonte_tipo'],
    'fonte_categoria_id' => $c['fonte_categoria_id'],
    'fonte_flag' => $c['fonte_flag'],
], $composicoes), $totalComposicoes, ['fonte_tipo' => '', 'fonte_categoria_id' => null, 'fonte_flag' => null]);
?>
<h2>Editar categoria — <?= htmlspecialchars($categoria['nome'], ENT_QUOTES) ?></h2>
<p class="subtitle"><a href="/regras?aba=faixas">&larr; Voltar para Regras de comissão</a></p>

<form class="form-padrao" method="post" action="/categorias/<?= (int) $categoria['id'] ?>" style="max-width:680px">
  <?= Csrf::field() ?>

  <div class="card">
    <label for="nome">Nome da categoria</label>
    <input type="text" id="nome" name="nome" required maxlength="60" value="<?= htmlspecialchars($categoria['nome'], ENT_QUOTES) ?>">

    <label for="ordem">Ordem de exibição</label>
    <input type="number" id="ordem" name="ordem" min="0" value="<?= (int) $categoria['ordem'] ?>">
  </div>

  <fieldset>
    <legend>Faixas de comissão (Nível 1–2)</legend>
    <p class="ajuda" style="margin-top:0">O percentual da faixa incide sobre o valor total vendido na categoria (não é progressivo por degrau). Deixe "limite até" em branco só na última linha usada — significa "acima de".</p>
    <table class="faixas">
      <thead><tr><th style="width:2rem">#</th><th>Limite até (R$)</th><th style="width:8rem">Percentual (%)</th></tr></thead>
      <tbody>
        <?php foreach ($faixasLinhas as $i => $linha): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><input type="text" name="faixa_limite[]" placeholder="em branco = acima de" value="<?= htmlspecialchars((string) $linha['limite_ate'], ENT_QUOTES) ?>"></td>
          <td><input type="text" name="faixa_percentual[]" value="<?= htmlspecialchars((string) $linha['percentual'], ENT_QUOTES) ?>"></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </fieldset>

  <fieldset>
    <legend>Composição de categoria (Nível 3)</legend>
    <p class="ajuda" style="margin-top:0">Opcional. Faz a faixa acima incidir também sobre o valor de outra categoria ou de uma marcação de venda — sem precisar de código (ex.: Manipulação + S/N).</p>
    <table class="faixas">
      <thead><tr><th style="width:2rem">#</th><th>Fonte extra</th><th>Categoria de origem</th><th>Marcação de venda</th></tr></thead>
      <tbody>
        <?php foreach ($composicoesLinhas as $i => $linha): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td>
            <select name="composicao_tipo[]">
              <option value="" <?= $linha['fonte_tipo'] === '' ? 'selected' : '' ?>>— nenhuma —</option>
              <option value="categoria" <?= $linha['fonte_tipo'] === 'categoria' ? 'selected' : '' ?>>Outra categoria</option>
              <option value="flag_venda" <?= $linha['fonte_tipo'] === 'flag_venda' ? 'selected' : '' ?>>Marcação de venda</option>
            </select>
          </td>
          <td>
            <select name="composicao_categoria[]">
              <option value="">—</option>
              <?php foreach ($outrasCategorias as $oc): ?>
                <option value="<?= (int) $oc['id'] ?>" <?= (int) ($linha['fonte_categoria_id'] ?? 0) === (int) $oc['id'] ? 'selected' : '' ?>><?= htmlspecialchars($oc['nome'], ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <select name="composicao_flag[]">
              <option value="">—</option>
              <?php foreach (Categoria::FLAGS_VENDA as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= $linha['fonte_flag'] === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo, ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="ajuda">Preencha só a coluna correspondente ao tipo escolhido (categoria OU marcação).</p>
  </fieldset>

  <div class="acoes-form">
    <button type="submit" class="btn">Salvar categoria</button>
    <a href="/regras?aba=faixas" class="btn secundario">Cancelar</a>
  </div>
</form>
