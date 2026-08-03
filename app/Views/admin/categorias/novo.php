<?php
/** @var array|null $categoria */
/** @var string|null $erro */
use App\Core\Csrf;
?>
<h2>Nova categoria</h2>
<p class="subtitle"><a href="/categorias">&larr; Voltar para categorias</a></p>

<div class="card">
  <?php if ($erro): ?>
    <div class="flash erro"><?= htmlspecialchars($erro, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <form class="form-padrao" method="post" action="/categorias">
    <?= Csrf::field() ?>

    <label for="nome">Nome da categoria</label>
    <input type="text" id="nome" name="nome" required maxlength="60" value="<?= htmlspecialchars($categoria['nome'] ?? '', ENT_QUOTES) ?>">

    <label for="ordem">Ordem de exibição</label>
    <input type="number" id="ordem" name="ordem" min="0" value="<?= htmlspecialchars((string) ($categoria['ordem'] ?? 0), ENT_QUOTES) ?>">

    <p class="ajuda">Depois de criar, você configura as faixas de comissão (e, se precisar, a composição com outra categoria) na tela de edição.</p>

    <div class="acoes-form">
      <button type="submit" class="btn">Criar e configurar faixas</button>
      <a href="/categorias" class="btn secundario">Cancelar</a>
    </div>
  </form>
</div>
