<?php
/** @var array|null $usuario */
/** @var array $filiais */
/** @var string|null $erro */
use App\Core\Csrf;
$editando = $usuario !== null && !empty($usuario['id']);
$filiaisVinculadas = $usuario['filiais'] ?? [];
$filialPrincipal = $usuario['filial_principal'] ?? ($filiaisVinculadas[0] ?? null);
$papeis = ['admin' => 'Administrador', 'gerente' => 'Gerente de filial', 'funcionario' => 'Funcionário'];
?>
<h2><?= $editando ? 'Editar usuário' : 'Novo usuário' ?></h2>
<p class="subtitle"><a href="/usuarios">&larr; Voltar para usuários</a></p>

<div class="card">
  <?php if ($erro): ?>
    <div class="flash erro"><?= htmlspecialchars($erro, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <form class="form-padrao" method="post" action="<?= $editando ? '/usuarios/' . (int) $usuario['id'] : '/usuarios' ?>">
    <?= Csrf::field() ?>

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" required maxlength="150" value="<?= htmlspecialchars($usuario['nome'] ?? '', ENT_QUOTES) ?>">

    <label for="cargo">Cargo <span style="font-weight:400">(opcional)</span></label>
    <input type="text" id="cargo" name="cargo" maxlength="80" value="<?= htmlspecialchars($usuario['cargo'] ?? '', ENT_QUOTES) ?>">

    <label for="email">E-mail de login</label>
    <input type="email" id="email" name="email" required maxlength="150" value="<?= htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES) ?>">

    <label for="papel">Papel de acesso</label>
    <select id="papel" name="papel" required>
      <option value="">Selecione…</option>
      <?php foreach ($papeis as $valor => $rotulo): ?>
        <option value="<?= $valor ?>" <?= ($usuario['papel'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
      <?php endforeach; ?>
    </select>

    <label for="senha"><?= $editando ? 'Nova senha' : 'Senha inicial' ?></label>
    <input type="password" id="senha" name="senha" <?= $editando ? '' : 'required' ?> minlength="8">
    <p class="ajuda"><?= $editando ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres. A pessoa pode trocar depois do primeiro acesso.' ?></p>

    <fieldset>
      <legend>Filiais vinculadas</legend>
      <table class="faixas">
        <thead><tr><th>Vinculada</th><th>Principal</th><th>Filial</th></tr></thead>
        <tbody>
          <?php foreach ($filiais as $f): $fid = (int) $f['id']; ?>
          <tr>
            <td><input type="checkbox" name="filiais[]" value="<?= $fid ?>" id="filial_<?= $fid ?>" <?= in_array($fid, $filiaisVinculadas, true) ? 'checked' : '' ?>></td>
            <td><input type="radio" name="filial_principal" value="<?= $fid ?>" <?= $filialPrincipal === $fid ? 'checked' : '' ?>
                  onclick="document.getElementById('filial_<?= $fid ?>').checked = true"></td>
            <td><label for="filial_<?= $fid ?>" style="margin:0"><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></label></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="ajuda">A filial principal decide de qual meta/rentabilidade a pessoa participa nos pilares de filial e no prêmio, quando vinculada a mais de uma.</p>
    </fieldset>

    <div class="acoes-form">
      <button type="submit" class="btn"><?= $editando ? 'Salvar alterações' : 'Cadastrar usuário' ?></button>
      <a href="/usuarios" class="btn secundario">Cancelar</a>
    </div>
  </form>
</div>
