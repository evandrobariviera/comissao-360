<?php
/** @var array $usuarios */
use App\Core\Csrf;
$rotulosPapel = ['admin' => 'Administrador', 'gerente' => 'Gerente', 'funcionario' => 'Funcionário'];
?>
<div class="toolbar">
  <div>
    <h2>Usuários</h2>
    <p class="subtitle">Login e papel de acesso de cada pessoa. Desativar remove o acesso sem apagar o histórico.</p>
  </div>
  <a href="/usuarios/novo" class="btn">+ Novo usuário</a>
</div>

<table class="lista">
  <thead>
    <tr><th>Nome</th><th>Cargo</th><th>E-mail</th><th>Papel</th><th>Status</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($usuarios as $u): ?>
    <tr>
      <td><?= htmlspecialchars($u['nome'], ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars($u['cargo'] ?? '—', ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars($u['email'], ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars($rotulosPapel[$u['papel']] ?? $u['papel'], ENT_QUOTES) ?></td>
      <td><span class="pill <?= $u['ativo'] ? 'status-ativo' : 'status-inativo' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
      <td class="acoes">
        <a class="btn secundario pequeno" href="/usuarios/<?= (int) $u['id'] ?>/editar">Editar</a>
        <form method="post" action="/usuarios/<?= (int) $u['id'] ?>/status" onsubmit="return confirm('Confirma alternar o status deste usuário?');">
          <?= Csrf::field() ?>
          <button type="submit" class="btn secundario pequeno"><?= $u['ativo'] ? 'Desativar' : 'Ativar' ?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($usuarios)): ?>
    <tr><td colspan="6" style="color:var(--ink-faint)">Nenhum usuário cadastrado.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
