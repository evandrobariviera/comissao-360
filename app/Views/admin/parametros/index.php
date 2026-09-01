<?php
/** @var array<string, array{chave:string, valor:string, descricao:?string}> $parametros */
/** @var array<string,string> $parametrosGlobais */
/** @var array $filiais */
/** @var int $filialId */
/** @var array $periodo */
/** @var array|null $metaFilial */
/** @var array|null $fechamento */
/** @var array $categorias */
use App\Core\Auth;
use App\Core\Csrf;

$fmt = static fn (string $chave, float $default = 0) => rtrim(rtrim(
    number_format((float) ($parametros[$chave]['valor'] ?? $default), 2, '.', ''), '0'), '.');

$campo = static function (string $chave, string $rotulo) use ($parametros, $fmt): string {
    $ajuda = htmlspecialchars($parametros[$chave]['descricao'] ?? '', ENT_QUOTES);
    return '<div>'
        . '<label for="p_' . $chave . '">' . htmlspecialchars($rotulo, ENT_QUOTES) . '</label>'
        . '<input type="text" id="p_' . $chave . '" name="valor[' . $chave . ']" value="' . $fmt($chave) . '">'
        . ($ajuda !== '' ? '<p class="ajuda">' . $ajuda . '</p>' : '')
        . '</div>';
};

$editavel = Auth::papel() === Auth::PAPEL_ADMIN && ($fechamento['status'] ?? 'aberto') === 'aberto';
$fmtVal = static fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

$filialNome = '';
foreach ($filiais as $f) {
    if ((int) $f['id'] === $filialId) {
        $filialNome = $f['nome'];
        break;
    }
}

// Campo de override opcional (aba de filial): valor salvo (se houver) ou vazio; placeholder mostra o padrão global.
$campoOverride = static function (string $chave, string $rotulo) use ($metaFilial, $parametrosGlobais, $editavel, $fmtVal): string {
    $valor = $metaFilial[$chave] ?? null;
    $global = $fmtVal($parametrosGlobais[$chave] ?? 0);
    if (!$editavel) {
        $texto = $valor !== null ? $fmtVal($valor) : "(padrão global: {$global})";
        return '<div><label>' . htmlspecialchars($rotulo, ENT_QUOTES) . '</label><p>' . htmlspecialchars($texto, ENT_QUOTES) . '</p></div>';
    }
    $val = $valor !== null ? htmlspecialchars($fmtVal($valor), ENT_QUOTES) : '';
    return '<div><label for="' . $chave . '">' . htmlspecialchars($rotulo, ENT_QUOTES) . '</label>'
        . '<input type="text" id="' . $chave . '" name="' . $chave . '" value="' . $val . '" placeholder="padrão: ' . $global . '"></div>';
};
?>
<div class="toolbar">
  <div>
    <h2>Parâmetros globais</h2>
    <p class="subtitle">"Parâmetros mãe" da Meta 360 — valem pra todas as filiais da rede. Selecione uma filial abaixo pra sobrescrever o piso/teto de Qualidade só nela; sem override, vale o padrão global.</p>
  </div>
</div>

<form class="form-padrao" method="post" action="/parametros" style="max-width:900px">
  <?= Csrf::field() ?>

  <fieldset>
    <legend>Qualidade — Desconto médio</legend>
    <p class="ajuda">Abaixo do piso: pontuação cheia. No teto ou acima: 0 pontos. Entre os dois: proporcional (desconto baixo é bom).</p>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0 1rem">
      <?= $campo('desconto_piso_pct', 'Piso — pontuação cheia (%)') ?>
      <?= $campo('desconto_teto_pct', 'Teto — zera a pontuação (%)') ?>
      <?= $campo('desconto_pts_max', 'Pontos máximos do sub-pilar') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Qualidade — Rentabilidade</legend>
    <p class="ajuda">Abaixo do piso: 0 pontos. No teto ou acima: pontuação cheia. Entre os dois: proporcional. Mesma régua usada pra rentabilidade da filial e do funcionário — só os pontos máximos de cada um são separados.</p>
    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0 1rem">
      <?= $campo('rentab_piso_pct', 'Piso — começa a pontuar (%)') ?>
      <?= $campo('rentab_teto_pct', 'Teto — pontuação cheia (%)') ?>
      <?= $campo('rentab_filial_pts', 'Pontos máximos — rentabilidade da filial') ?>
      <?= $campo('rentab_funcionario_pts', 'Pontos máximos — rentabilidade do funcionário') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Qualidade — Ticket médio</legend>
    <p class="ajuda">Abaixo do piso: 0 pontos. No teto ou acima: pontuação cheia. Entre os dois: proporcional (ticket alto é bom).</p>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0 1rem">
      <?= $campo('ticket_medio_piso', 'Piso — começa a pontuar (R$)') ?>
      <?= $campo('ticket_medio_teto', 'Teto — pontuação cheia (R$)') ?>
      <?= $campo('ticket_medio_pts_max', 'Pontos máximos do sub-pilar') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Pesos dos pilares (Meta 360, total 100 pts)</legend>
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:0 1rem">
      <?= $campo('peso_individual_max', 'Resultado Individual') ?>
      <?= $campo('peso_filial_max', 'Resultado da Filial') ?>
      <?= $campo('peso_qualidade_max', 'Qualidade/Rentabilidade') ?>
      <?= $campo('peso_equipe_max', 'Equipe') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Outros</legend>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0 1rem">
      <?= $campo('premio_filial_padrao', 'Prêmio de filial — valor de referência (R$)') ?>
    </div>
  </fieldset>

  <div class="acoes-form">
    <button type="submit" class="btn">Salvar parâmetros globais</button>
  </div>
</form>

<div class="toolbar" style="margin-top:2.5rem">
  <div>
    <h2 style="font-size:1.25rem">Meta de mix de vendas por categoria</h2>
    <p class="subtitle">Percentual ideal do total vendido pela rede em cada categoria (ex.: Similar 30%, RX 15%). Não afeta comissão nem a pontuação da Meta 360 — é só referência pra relatório de mix realizado x meta (ver Painel da rede). Categoria com meta aqui passa a pedir o lançamento do dia também por categoria em Vendas → Venda bruta da filial; deixe vazio pra não rastrear. <strong>Tipo:</strong> piso = quanto mais alto melhor (meta mínima); teto = quanto mais baixo melhor (meta máxima, ex. RX).</p>
  </div>
</div>

<form class="form-padrao" method="post" action="/parametros/mix" style="max-width:640px">
  <?= Csrf::field() ?>
  <fieldset>
    <legend>Meta de mix (%)</legend>
    <table class="lista">
      <thead>
        <tr><th>Categoria</th><th>Meta (%)</th><th>Tipo</th></tr>
      </thead>
      <tbody>
        <?php foreach ($categorias as $c): $catId = (int) $c['id']; ?>
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
  </fieldset>
  <div class="acoes-form">
    <button type="submit" class="btn">Salvar meta de mix</button>
  </div>
</form>

<div class="toolbar" style="margin-top:2.5rem">
  <div>
    <h2 style="font-size:1.25rem">Override por filial</h2>
    <p class="subtitle">Só preencha aqui se esta filial precisar de uma régua diferente da rede. Campo vazio = segue o padrão global acima.</p>
  </div>
</div>

<nav class="tabs-filial">
  <?php foreach ($filiais as $f): ?>
    <a href="/parametros?filial_id=<?= (int) $f['id'] ?>" class="<?= (int) $f['id'] === $filialId ? 'active' : '' ?>"><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$editavel && Auth::papel() !== Auth::PAPEL_ADMIN): ?>
<div class="callout dica"><span class="callout-label">Somente leitura</span>Só o administrador edita overrides.</div>
<?php endif; ?>

<form class="form-padrao" method="post" action="/parametros/filial" style="max-width:900px">
  <?= Csrf::field() ?>
  <input type="hidden" name="filial_id" value="<?= $filialId ?>">

  <fieldset>
    <legend>Desconto médio — <?= htmlspecialchars($filialNome, ENT_QUOTES) ?></legend>
    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0 1rem">
      <?= $campoOverride('desconto_piso_pct', 'Piso (%)') ?>
      <?= $campoOverride('desconto_teto_pct', 'Teto (%)') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Rentabilidade</legend>
    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0 1rem">
      <?= $campoOverride('rentab_piso_pct', 'Piso (%)') ?>
      <?= $campoOverride('rentab_teto_pct', 'Teto (%)') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Ticket médio</legend>
    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0 1rem">
      <?= $campoOverride('ticket_medio_piso', 'Piso (R$)') ?>
      <?= $campoOverride('ticket_medio_teto', 'Teto (R$)') ?>
    </div>
  </fieldset>

  <?php if ($editavel): ?>
  <div class="acoes-form">
    <button type="submit" class="btn">Salvar override desta filial</button>
  </div>
  <?php endif; ?>
</form>
