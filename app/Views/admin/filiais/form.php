<?php
/** @var array|null $filial */
/** @var string|null $erro */
use App\Core\Csrf;
$editando = $filial !== null && !empty($filial['id']);
?>
<h2><?= $editando ? 'Editar filial' : 'Nova filial' ?></h2>
<p class="subtitle"><a href="/filiais">&larr; Voltar para filiais</a></p>

<div class="card">
  <?php if ($erro): ?>
    <div class="flash erro"><?= htmlspecialchars($erro, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <form class="form-padrao" method="post" action="<?= $editando ? '/filiais/' . (int) $filial['id'] : '/filiais' ?>">
    <?= Csrf::field() ?>

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" required maxlength="80" value="<?= htmlspecialchars($filial['nome'] ?? '', ENT_QUOTES) ?>">

    <label for="codigo">Código</label>
    <input type="text" id="codigo" name="codigo" required maxlength="20" value="<?= htmlspecialchars($filial['codigo'] ?? '', ENT_QUOTES) ?>">
    <p class="ajuda">Identificador curto e único, usado internamente (ex.: "centro", "frai1").</p>

    <div class="acoes-form">
      <button type="submit" class="btn"><?= $editando ? 'Salvar alterações' : 'Cadastrar filial' ?></button>
      <a href="/filiais" class="btn secundario">Cancelar</a>
    </div>
  </form>
</div>
