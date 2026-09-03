<?php
/**
 * Camada 3 — Prêmio de filial (valor de referência global) + meta de mix por categoria.
 * Formulários postam para ParametroController::salvar e ::salvarMix.
 *
 * @var array $parametros     chave => {chave, valor, descricao}
 * @var array $categoriasMix  categorias ativas
 */
use App\Core\Csrf;

$fmt = static fn (string $chave, float $default = 0) => rtrim(rtrim(
    number_format((float) ($parametros[$chave]['valor'] ?? $default), 2, '.', ''), '0'), '.');
$fmtVal = static fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
?>
<div class="toolbar" style="margin-top:1.4rem">
  <div>
    <h3 style="margin:0">Prêmio de filial — valor de referência</h3>
    <p class="secao-sub" style="margin:.25rem 0 0">O valor padrão sugerido quando você cria as metas do mês. O valor que de fato vale para cada filial é definido em <a href="/metas">Metas</a>, e o prêmio é tudo-ou-nada: a filial bate a meta de venda, todo mundo dela recebe; não bate, ninguém recebe.</p>
  </div>
</div>

<form class="form-padrao" method="post" action="/parametros" style="max-width:420px">
  <?= Csrf::field() ?>
  <fieldset>
    <legend>Valor de referência</legend>
    <div>
      <label for="p_premio_filial_padrao">Prêmio de filial (R$)</label>
      <input type="text" id="p_premio_filial_padrao" name="valor[premio_filial_padrao]" value="<?= $fmt('premio_filial_padrao') ?>">
      <p class="ajuda"><?= htmlspecialchars($parametros['premio_filial_padrao']['descricao'] ?? '', ENT_QUOTES) ?></p>
    </div>
  </fieldset>
  <div class="acoes-form"><button type="submit" class="btn">Salvar valor de referência</button></div>
</form>

<div class="toolbar" style="margin-top:2.5rem">
  <div>
    <h3 style="margin:0">Meta de mix de vendas por categoria</h3>
    <p class="secao-sub" style="margin:.25rem 0 0">Percentual ideal do total vendido pela rede em cada categoria. <strong>Não afeta comissão nem a pontuação da Meta 360</strong> — é referência para o relatório de mix realizado × meta. Categoria com meta aqui passa a pedir o lançamento do dia também por categoria em Vendas; deixe vazio para não rastrear. <strong>Tipo:</strong> piso = quanto mais alto melhor; teto = quanto mais baixo melhor (ex.: RX).</p>
  </div>
</div>

<form class="form-padrao" method="post" action="/parametros/mix" style="max-width:640px">
  <?= Csrf::field() ?>
  <fieldset>
    <legend>Meta de mix (%)</legend>
    <div class="scrollx">
    <table class="lista">
      <thead>
        <tr><th>Categoria</th><th>Meta (%)</th><th>Tipo</th></tr>
      </thead>
      <tbody>
        <?php foreach ($categoriasMix as $c): $catId = (int) $c['id']; ?>
        <tr>
          <td><?= htmlspecialchars($c['nome'], ENT_QUOTES) ?></td>
          <td>
            <input type="text" id="mix_<?= $catId ?>" name="mix[<?= $catId ?>]" style="width:6.5rem"
                   value="<?= $c['meta_percentual_pct'] !== null ? $fmtVal($c['meta_percentual_pct']) : '' ?>"
                   placeholder="não rastreada">
          </td>
          <td>
            <select name="mix_tipo[<?= $catId ?>]" style="width:auto">
              <option value="piso" <?= ($c['meta_percentual_tipo'] ?? 'piso') === 'piso' ? 'selected' : '' ?>>Piso (mínimo desejado)</option>
              <option value="teto" <?= ($c['meta_percentual_tipo'] ?? 'piso') === 'teto' ? 'selected' : '' ?>>Teto (máximo desejado)</option>
            </select>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </fieldset>
  <div class="acoes-form">
    <button type="submit" class="btn">Salvar meta de mix</button>
  </div>
</form>
