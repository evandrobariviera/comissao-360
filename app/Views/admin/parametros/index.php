<?php
/** @var array<string, array{chave:string, valor:string, descricao:?string}> $parametros */
use App\Core\Csrf;

$fmt = static fn (string $chave, float $default = 0) => rtrim(rtrim(
    number_format((float) ($parametros[$chave]['valor'] ?? $default), 2, '.', ''), '0'), '.');

$campo = static function (string $chave, string $rotulo, string $sufixo = '') use ($parametros, $fmt): string {
    $ajuda = htmlspecialchars($parametros[$chave]['descricao'] ?? '', ENT_QUOTES);
    return '<div>'
        . '<label for="p_' . $chave . '">' . htmlspecialchars($rotulo, ENT_QUOTES) . '</label>'
        . '<input type="text" id="p_' . $chave . '" name="valor[' . $chave . ']" value="' . $fmt($chave) . '">'
        . ($sufixo !== '' ? '<span class="ajuda-inline">' . htmlspecialchars($sufixo, ENT_QUOTES) . '</span>' : '')
        . ($ajuda !== '' ? '<p class="ajuda">' . $ajuda . '</p>' : '')
        . '</div>';
};
?>
<div class="toolbar">
  <div>
    <h2>Parâmetros globais</h2>
    <p class="subtitle">"Parâmetros mãe" da Meta 360 — valem pra todas as filiais da rede. Uma filial específica pode sobrescrever os piso/teto de Qualidade em <a href="/metas">Metas</a>; sem override, vale o que está aqui.</p>
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
    <button type="submit" class="btn">Salvar parâmetros</button>
  </div>
</form>
