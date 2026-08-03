<?php
/** @var array $categorias */
use App\Core\Csrf;
?>
<div class="toolbar">
  <div>
    <h2>Categorias</h2>
    <p class="subtitle">Linhas de produto, faixas de comissão e composição especial (Nível 1–3 de parametrização).</p>
  </div>
  <a href="/categorias/novo" class="btn">+ Nova categoria</a>
</div>

<table class="lista">
  <thead>
    <tr><th>Ordem</th><th>Categoria</th><th>Status</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($categorias as $c): ?>
    <tr>
      <td><?= (int) $c['ordem'] ?></td>
      <td><?= htmlspecialchars($c['nome'], ENT_QUOTES) ?></td>
      <td><span class="pill <?= $c['ativo'] ? 'status-ativo' : 'status-inativo' ?>"><?= $c['ativo'] ? 'Ativa' : 'Inativa' ?></span></td>
      <td class="acoes">
        <a class="btn secundario pequeno" href="/categorias/<?= (int) $c['id'] ?>/editar">Editar faixas</a>
        <form method="post" action="/categorias/<?= (int) $c['id'] ?>/status" onsubmit="return confirm('Confirma alternar o status desta categoria?');">
          <?= Csrf::field() ?>
          <button type="submit" class="btn secundario pequeno"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($categorias)): ?>
    <tr><td colspan="4" style="color:var(--ink-faint)">Nenhuma categoria cadastrada.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
