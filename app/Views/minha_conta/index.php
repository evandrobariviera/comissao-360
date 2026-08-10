<?php
/** @var array $funcionario */
use App\Core\Csrf;
?>
<h2>Minha conta</h2>
<p class="subtitle"><?= htmlspecialchars($funcionario['nome'], ENT_QUOTES) ?> · <?= htmlspecialchars($funcionario['email'], ENT_QUOTES) ?></p>

<div class="card" style="max-width:480px">
  <h3 style="margin-top:0">Foto de perfil</h3>
  <div style="display:flex; align-items:center; gap:1.2rem; margin-bottom:1rem;">
    <?php if (!empty($funcionario['avatar_path'])): ?>
      <img src="<?= htmlspecialchars($funcionario['avatar_path'], ENT_QUOTES) ?>" alt="Foto de perfil" style="width:72px; height:72px; border-radius:999px; object-fit:cover; border:1px solid var(--line);">
    <?php else: ?>
      <div style="width:72px; height:72px; border-radius:999px; background:var(--primary-tint); color:var(--primary-ink); display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:700;">
        <?= htmlspecialchars(mb_strtoupper(mb_substr($funcionario['nome'], 0, 1)), ENT_QUOTES) ?>
      </div>
    <?php endif; ?>
  </div>
  <form class="form-padrao" method="post" action="/minha-conta/avatar" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <label for="avatar">Trocar foto (JPG, PNG ou WEBP, até 2MB)</label>
    <input type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png,.webp" required>
    <div class="acoes-form">
      <button type="submit" class="btn">Enviar foto</button>
    </div>
  </form>
</div>

<div class="card" style="max-width:480px">
  <h3 style="margin-top:0">Trocar senha</h3>
  <form class="form-padrao" method="post" action="/minha-conta/senha">
    <?= Csrf::field() ?>
    <label for="senha_atual">Senha atual</label>
    <input type="password" id="senha_atual" name="senha_atual" required>
    <label for="nova_senha">Nova senha</label>
    <input type="password" id="nova_senha" name="nova_senha" required minlength="8">
    <p class="ajuda">Pelo menos 8 caracteres.</p>
    <label for="confirmacao">Confirmar nova senha</label>
    <input type="password" id="confirmacao" name="confirmacao" required minlength="8">
    <div class="acoes-form">
      <button type="submit" class="btn">Atualizar senha</button>
    </div>
  </form>
</div>
