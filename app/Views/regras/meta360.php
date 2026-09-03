<?php
/**
 * Camada 2 — Meta 360: pisos/tetos de Qualidade + pesos dos pilares (globais da rede),
 * e override opcional por filial. Formulários postam para ParametroController.
 *
 * @var array      $parametros         chave => {chave, valor, descricao}
 * @var array      $parametrosGlobais  chave => valor
 * @var array      $filiais
 * @var int        $filialId
 * @var array|null $metaFilial
 * @var array|null $fechamento
 */
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
<div class="toolbar" style="margin-top:1.4rem">
  <div>
    <h3 style="margin:0">Boletim da Meta 360</h3>
    <p class="secao-sub" style="margin:.25rem 0 0">Régua da rede para o fator de desempenho (0 a 100 pts → multiplicador). Pisos/tetos de Qualidade valem pra todas as filiais; use o bloco de baixo pra sobrescrever numa filial específica.</p>
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

  <div class="acoes-form">
    <button type="submit" class="btn">Salvar régua da rede</button>
  </div>
</form>

<div class="toolbar" style="margin-top:2.5rem">
  <div>
    <h3 style="margin:0">Override por filial</h3>
    <p class="secao-sub" style="margin:.25rem 0 0">Só preencha se esta filial precisar de uma régua diferente da rede. Campo vazio = segue o padrão global acima.</p>
  </div>
</div>

<?php if (count($filiais) > 1): ?>
<form method="get" action="/regras" class="form-padrao" style="max-width:280px; margin-bottom:1rem;">
  <input type="hidden" name="aba" value="meta-360">
  <label for="filial_id">Filial</label>
  <select id="filial_id" name="filial_id" onchange="this.form.submit()">
    <?php foreach ($filiais as $f): ?>
      <option value="<?= (int) $f['id'] ?>" <?= (int) $f['id'] === $filialId ? 'selected' : '' ?>><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></option>
    <?php endforeach; ?>
  </select>
</form>
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
  <?php else: ?>
  <div class="callout dica"><span class="callout-label">Somente leitura</span>Esta filial já está fechada neste período — não é possível alterar overrides.</div>
  <?php endif; ?>
</form>
