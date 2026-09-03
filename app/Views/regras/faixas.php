<?php
/**
 * Camada 1 — Faixas por categoria. Lista de categorias; a edição de faixas e composição
 * fica na tela dedicada (/categorias/{id}/editar).
 *
 * @var array $todasCategorias
 */
use App\Core\Csrf;
?>
<div class="toolbar" style="margin-top:1.4rem">
  <div>
    <h3 style="margin:0">Faixas de comissão por categoria</h3>
    <p class="secao-sub" style="margin:.25rem 0 0">O percentual da faixa alcançada incide sobre o valor <strong>total</strong> vendido na categoria no mês — não é progressivo. Categoria ativa sem faixa gera 0% de comissão, sem aviso.</p>
  </div>
  <a href="/categorias/novo" class="btn">+ Nova categoria</a>
</div>

<div class="scrollx">
<table class="lista">
  <thead>
    <tr><th>Ordem</th><th>Categoria</th><th>Status</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($todasCategorias as $c): ?>
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
    <?php if (empty($todasCategorias)): ?>
    <tr><td colspan="4" style="color:var(--ink-faint)">Nenhuma categoria cadastrada.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>
