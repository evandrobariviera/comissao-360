<?php
/**
 * Casca de "Regras de comissão" — cabeçalho + abas das 3 camadas + inclusão do painel da aba.
 *
 * @var string $aba
 * @var array  $todasCategorias
 * @var array  $categoriasMix
 * @var array  $parametros
 * @var array  $parametrosGlobais
 * @var array  $filiais
 * @var int    $filialId
 * @var array  $periodo
 * @var array|null $fechamento
 * @var array|null $metaFilial
 */
$abas = [
    'faixas' => '1 · Faixas por categoria',
    'meta-360' => '2 · Meta 360',
    'premio-mix' => '3 · Prêmio de filial e mix',
];
$partial = ['faixas' => 'regras/faixas', 'meta-360' => 'regras/meta360', 'premio-mix' => 'regras/premio_mix'][$aba];
?>
<div class="toolbar">
  <div>
    <h2>Regras de comissão</h2>
    <p class="subtitle">As três camadas que definem quanto cada pessoa recebe: a comissão base pelas vendas, o fator de desempenho da Meta 360 e o prêmio coletivo da filial. Configuração — muda pouco ao longo do ano.</p>
  </div>
</div>

<nav class="tabs-filial">
  <?php foreach ($abas as $chave => $rotulo): ?>
    <a href="/regras?aba=<?= $chave ?>" class="<?= $aba === $chave ? 'active' : '' ?>"><?= htmlspecialchars($rotulo, ENT_QUOTES) ?></a>
  <?php endforeach; ?>
</nav>

<?php require APP_DIR . '/Views/' . $partial . '.php'; ?>
