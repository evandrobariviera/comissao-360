<?php
/** Landing de Configuração — três atalhos para os cadastros que mudam pouco. */
$itens = [
    ['/filiais', 'Filiais', 'As lojas da rede — nome e código. Tudo (usuário, meta, venda, indicador) fica amarrado a uma filial, então é o primeiro cadastro.'],
    ['/usuarios', 'Usuários', 'Quem acessa o sistema — admin, gerente ou funcionário — e a quais filiais cada pessoa está vinculada.'],
    ['/regras?aba=faixas', 'Regras de comissão', 'As três camadas da metodologia: faixas de comissão por categoria, régua da Meta 360 (pesos e pisos/tetos) e prêmio de filial + mix.'],
];
?>
<div class="toolbar">
  <div>
    <h2>Configuração</h2>
    <p class="subtitle">Cadastros e regras que mudam pouco ao longo do ano. O trabalho do mês a mês fica na barra principal.</p>
  </div>
</div>

<div style="display:grid; gap:1rem; margin-top:.4rem; max-width:640px">
  <?php foreach ($itens as [$href, $titulo, $desc]): ?>
    <a class="card" href="<?= htmlspecialchars($href, ENT_QUOTES) ?>" style="display:block; text-decoration:none; color:inherit; margin-top:0; transition:border-color .12s ease, box-shadow .12s ease">
      <strong style="display:block; color:var(--primary-ink); font-size:1.06rem; margin-bottom:.3rem"><?= htmlspecialchars($titulo, ENT_QUOTES) ?> &rarr;</strong>
      <span style="color:var(--ink-soft); font-size:.9rem; line-height:1.5"><?= htmlspecialchars($desc, ENT_QUOTES) ?></span>
    </a>
  <?php endforeach; ?>
</div>
