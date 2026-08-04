<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Flash;

/** @var string $content */
$rotulosPapel = [
    'admin' => 'Administrador',
    'gerente' => 'Gerente de filial',
    'funcionario' => 'Funcionário',
];
$papel = Auth::papel();
$flash = Flash::pull();
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Comissão 360 · Farmácia Geremias</title>
<style>
  :root{
    --bg:#EEF2F1; --surface:#fff; --ink:#16232A; --ink-soft:#4C5E62; --ink-faint:#7C8C8F; --line:#D3DCD9;
    --primary:#1F6F63; --primary-ink:#0E4139; --primary-tint:#DCEBE7; --accent:#B8792E;
    --good:#3F8F5F; --bad:#AA4638; --good-tint:#E1F0E6; --bad-tint:#F6E4E0;
  }
  *{box-sizing:border-box}
  body{margin:0; background:var(--bg); color:var(--ink); font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;}
  header.app{background:var(--surface); border-bottom:1px solid var(--line); padding:.9rem 1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.6rem;}
  header.app .brand{font-weight:700; font-family:Georgia,serif; color:var(--ink); font-size:1.05rem;}
  header.app .brand span{color:var(--primary-ink)}
  header.app .top-right{display:flex; align-items:center; gap:1.2rem;}
  header.app .top-right a{color:var(--ink-soft); text-decoration:none; font-size:.9rem}
  header.app .top-right a:hover{color:var(--primary-ink)}
  nav.admin-nav{background:var(--surface); border-bottom:1px solid var(--line); padding:0 1.5rem; display:flex; gap:1.4rem; overflow-x:auto;}
  nav.admin-nav a{display:inline-block; padding:.7rem 0; color:var(--ink-soft); text-decoration:none; font-size:.88rem; border-bottom:2px solid transparent; white-space:nowrap;}
  nav.admin-nav a:hover{color:var(--ink)}
  nav.admin-nav a.active{color:var(--primary-ink); border-bottom-color:var(--primary); font-weight:600;}
  main{max-width:960px; margin:0 auto; padding:2rem 1.5rem;}
  h2{font-family:Georgia,serif; margin:0 0 .3rem;}
  .subtitle{color:var(--ink-soft); font-size:.92rem; margin:0 0 1.2rem;}
  .card{background:var(--surface); border:1px solid var(--line); border-radius:10px; padding:1.4rem 1.6rem; box-shadow:0 1px 2px rgba(16,27,30,.06); margin-top:1rem;}
  .pill{display:inline-block; padding:.15em .6em; border-radius:999px; font-size:.75rem; font-weight:600; background:var(--primary-tint); color:var(--primary-ink)}
  .pill.status-ativo{background:var(--good-tint); color:var(--good)}
  .pill.status-inativo{background:var(--bad-tint); color:var(--bad)}
  .toolbar{display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;}
  .btn{display:inline-block; padding:.55rem 1rem; border:none; border-radius:8px; background:var(--primary); color:#fff; font-weight:600; font-size:.88rem; cursor:pointer; text-decoration:none;}
  .btn:hover{background:var(--primary-ink)}
  .btn.secundario{background:var(--surface); color:var(--ink); border:1px solid var(--line);}
  .btn.secundario:hover{background:var(--bg)}
  .btn.pequeno{padding:.35rem .7rem; font-size:.8rem;}
  .btn.perigo{background:var(--bad)}
  .btn.perigo:hover{background:#8a372c}
  table.lista{width:100%; border-collapse:collapse; background:var(--surface); border:1px solid var(--line); border-radius:10px; overflow:hidden; font-size:.9rem;}
  table.lista th, table.lista td{padding:.65rem .9rem; text-align:left; border-bottom:1px solid var(--line); vertical-align:middle;}
  table.lista th{background:var(--bg); font-size:.72rem; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-soft);}
  table.lista tbody tr:last-child td{border-bottom:none}
  table.lista td.acoes{text-align:right; white-space:nowrap;}
  table.lista td.acoes form{display:inline-block; margin-left:.4rem;}
  form.form-padrao{max-width:520px;}
  form.form-padrao label{display:block; font-size:.82rem; color:var(--ink-soft); margin:1rem 0 .3rem;}
  form.form-padrao label:first-of-type{margin-top:0}
  form.form-padrao input[type=text], form.form-padrao input[type=email], form.form-padrao input[type=password], form.form-padrao input[type=number], form.form-padrao select{
    width:100%; padding:.55rem .7rem; border:1px solid var(--line); border-radius:8px; font-size:.92rem; background:#fff; color:var(--ink);
  }
  form.form-padrao input:focus, form.form-padrao select:focus{outline:2px solid var(--primary); outline-offset:1px; border-color:var(--primary);}
  form.form-padrao .ajuda{font-size:.78rem; color:var(--ink-faint); margin-top:.25rem;}
  form.form-padrao .acoes-form{margin-top:1.6rem; display:flex; gap:.7rem;}
  form.form-padrao fieldset{border:1px solid var(--line); border-radius:8px; margin-top:1.6rem; padding:1rem 1.1rem;}
  form.form-padrao legend{padding:0 .4rem; font-size:.85rem; font-weight:600; color:var(--ink);}
  .grade-checkbox{display:flex; flex-wrap:wrap; gap:.5rem 1.2rem;}
  .grade-checkbox label{display:flex; align-items:center; gap:.4rem; font-size:.88rem; color:var(--ink); margin:0;}
  table.faixas{width:100%; border-collapse:collapse; font-size:.86rem;}
  table.faixas th{text-align:left; font-size:.72rem; text-transform:uppercase; color:var(--ink-soft); padding:.3rem .4rem;}
  table.faixas td{padding:.3rem .4rem;}
  table.faixas input{width:100%; padding:.4rem .5rem; border:1px solid var(--line); border-radius:6px; font-size:.86rem;}
  .flash{padding:.7rem 1rem; border-radius:0 8px 8px 0; font-size:.88rem; margin-bottom:1.2rem; border-left:3px solid;}
  .flash.sucesso{background:var(--good-tint); color:var(--good); border-color:var(--good);}
  .flash.erro{background:var(--bad-tint); color:var(--bad); border-color:var(--bad);}
</style>
</head>
<body>
<header class="app">
  <div class="brand">💊 Comissão <span>360</span></div>
  <div class="top-right">
    <span class="pill"><?= htmlspecialchars($rotulosPapel[$papel] ?? $papel, ENT_QUOTES) ?></span>
    <a href="/dashboard">Dashboard</a>
    <a href="/logout">Sair</a>
  </div>
</header>
<?php $rota = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH); ?>
<?php if ($papel === 'admin'): ?>
<nav class="admin-nav">
  <a href="/filiais" class="<?= str_starts_with($rota, '/filiais') ? 'active' : '' ?>">Filiais</a>
  <a href="/usuarios" class="<?= str_starts_with($rota, '/usuarios') ? 'active' : '' ?>">Usuários</a>
  <a href="/categorias" class="<?= str_starts_with($rota, '/categorias') ? 'active' : '' ?>">Categorias</a>
  <a href="/metas" class="<?= str_starts_with($rota, '/metas') ? 'active' : '' ?>">Metas</a>
  <a href="/vendas" class="<?= str_starts_with($rota, '/vendas') ? 'active' : '' ?>">Vendas</a>
  <a href="/indicadores" class="<?= str_starts_with($rota, '/indicadores') ? 'active' : '' ?>">Indicadores</a>
  <a href="/fechamento" class="<?= str_starts_with($rota, '/fechamento') ? 'active' : '' ?>">Fechamento</a>
</nav>
<?php elseif ($papel === 'gerente'): ?>
<nav class="admin-nav">
  <a href="/metas" class="<?= str_starts_with($rota, '/metas') ? 'active' : '' ?>">Metas</a>
  <a href="/vendas" class="<?= str_starts_with($rota, '/vendas') ? 'active' : '' ?>">Lançar vendas</a>
  <a href="/indicadores" class="<?= str_starts_with($rota, '/indicadores') ? 'active' : '' ?>">Indicadores</a>
  <a href="/fechamento" class="<?= str_starts_with($rota, '/fechamento') ? 'active' : '' ?>">Fechamento</a>
</nav>
<?php endif; ?>
<main>
<?php if ($flash): ?>
  <div class="flash <?= htmlspecialchars($flash['tipo'], ENT_QUOTES) ?>"><?= htmlspecialchars($flash['mensagem'], ENT_QUOTES) ?></div>
<?php endif; ?>
<?= $content ?>
</main>
</body>
</html>
