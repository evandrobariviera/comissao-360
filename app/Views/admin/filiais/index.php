<?php
/** @var array $filiais */
use App\Core\Csrf;
?>
<div class="toolbar">
  <div>
    <h2>Filiais</h2>
    <p class="subtitle">As 7 unidades da rede. Desativar preserva o histórico — nada é apagado.</p>
  </div>
  <a href="/filiais/novo" class="btn">+ Nova filial</a>
</div>

<table class="lista">
  <thead>
    <tr><th>Nome</th><th>Código</th><th>Status</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($filiais as $f): ?>
    <tr>
      <td><?= htmlspecialchars($f['nome'], ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars($f['codigo'], ENT_QUOTES) ?></td>
      <td><span class="pill <?= $f['ativo'] ? 'status-ativo' : 'status-inativo' ?>"><?= $f['ativo'] ? 'Ativa' : 'Inativa' ?></span></td>
      <td class="acoes">
        <a class="btn secundario pequeno" href="/filiais/<?= (int) $f['id'] ?>/editar">Editar</a>
        <form method="post" action="/filiais/<?= (int) $f['id'] ?>/status" onsubmit="return confirm('Confirma alternar o status desta filial?');">
          <?= Csrf::field() ?>
          <button type="submit" class="btn secundario pequeno"><?= $f['ativo'] ? 'Desativar' : 'Ativar' ?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($filiais)): ?>
    <tr><td colspan="4" style="color:var(--ink-faint)">Nenhuma filial cadastrada.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
