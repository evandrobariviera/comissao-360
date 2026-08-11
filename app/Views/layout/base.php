<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Flash;
use App\Models\Funcionario;

/** @var string $content */
$rotulosPapel = [
    'admin' => 'Administrador',
    'gerente' => 'Gerente de filial',
    'funcionario' => 'Funcionário',
];
$papel = Auth::papel();
$flash = Flash::pull();
$minhaConta = Funcionario::porUsuario((int) Auth::id());
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
    --warn:#B8862E; --warn-tint:#F3E9D2; --accent-tint:#F3E4CE;
    /* Paleta categórica validada (CVD-safe) — só para o gráfico dos 4 pilares da pontuação 360. */
    --chart-individual:#2a78d6; --chart-filial:#eb6834; --chart-qualidade:#1baf7a; --chart-equipe:#eda100;
    /* Sistema de forma/elevação — consistente em cards, tabelas, botões e inputs. */
    --font: -apple-system, BlinkMacSystemFont, "Segoe UI Variable", "Segoe UI", "Inter", system-ui, Roboto, Helvetica, Arial, sans-serif;
    --radius-sm:8px; --radius-md:12px; --radius-lg:16px;
    --shadow-sm:0 1px 2px rgba(16,27,30,.05);
    --shadow-md:0 1px 2px rgba(16,27,30,.04), 0 8px 20px rgba(16,27,30,.06);
  }
  *{box-sizing:border-box}
  body{margin:0; background:var(--bg); color:var(--ink); font-family:var(--font); line-height:1.5; -webkit-font-smoothing:antialiased;}
  a{transition:color .12s ease}
  header.app{background:var(--surface); border-bottom:1px solid var(--line); padding:1.05rem 1.75rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.6rem;}
  header.app .brand{font-weight:700; color:var(--ink); font-size:1.05rem;}
  header.app .brand span{color:var(--primary-ink)}
  header.app .brand img{display:block; height:36px; width:auto;}
  header.app .top-right{display:flex; align-items:center; gap:1.5rem;}
  header.app .top-right a{color:var(--ink-soft); text-decoration:none; font-size:.89rem; font-weight:500;}
  header.app .top-right a:hover{color:var(--primary-ink)}
  header.app .avatar-link{display:flex; align-items:center; gap:.5rem;}
  header.app .avatar-mini{width:27px; height:27px; border-radius:999px; object-fit:cover; border:1px solid var(--line);}
  header.app .avatar-mini.placeholder{background:var(--primary-tint); color:var(--primary-ink); display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700;}
  nav.admin-nav{background:var(--surface); border-bottom:1px solid var(--line); padding:0 1.75rem; display:flex; gap:.3rem; overflow-x:auto;}
  nav.admin-nav a{display:inline-block; padding:.75rem .65rem; color:var(--ink-soft); text-decoration:none; font-size:.87rem; font-weight:500; border-bottom:2px solid transparent; white-space:nowrap; transition:color .12s ease, border-color .12s ease;}
  nav.admin-nav a:hover{color:var(--ink)}
  nav.admin-nav a.active{color:var(--primary-ink); border-bottom-color:var(--primary); font-weight:700;}
  main{max-width:980px; margin:0 auto; padding:2.5rem 1.75rem 4rem;}
  h2{font-family:var(--font); font-weight:800; letter-spacing:-.015em; font-size:1.55rem; margin:0 0 .35rem; color:var(--ink);}
  .subtitle{color:var(--ink-soft); font-size:.94rem; margin:0 0 1.4rem; line-height:1.5;}
  .card{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); padding:1.6rem 1.8rem; box-shadow:var(--shadow-sm); margin-top:1.1rem;}
  .pill{display:inline-block; padding:.2em .65em; border-radius:999px; font-size:.75rem; font-weight:600; background:var(--primary-tint); color:var(--primary-ink)}
  .pill.status-ativo{background:var(--good-tint); color:var(--good)}
  .pill.status-inativo{background:var(--bad-tint); color:var(--bad)}
  .toolbar{display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1.2rem; flex-wrap:wrap;}
  .btn{display:inline-block; padding:.62rem 1.15rem; border:none; border-radius:var(--radius-sm); background:var(--primary); color:#fff; font-weight:600; font-size:.88rem; cursor:pointer; text-decoration:none; transition:background .12s ease, box-shadow .12s ease, transform .12s ease;}
  .btn:hover{background:var(--primary-ink); box-shadow:var(--shadow-sm); transform:translateY(-1px);}
  .btn.secundario{background:var(--surface); color:var(--ink); border:1px solid var(--line);}
  .btn.secundario:hover{background:var(--bg); box-shadow:none; transform:none;}
  .btn.pequeno{padding:.4rem .8rem; font-size:.8rem;}
  .btn.perigo{background:var(--bad)}
  .btn.perigo:hover{background:#8a372c}
  table.lista{width:100%; border-collapse:collapse; background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); overflow:hidden; font-size:.9rem; box-shadow:var(--shadow-sm);}
  table.lista th, table.lista td{padding:.75rem 1rem; text-align:left; border-bottom:1px solid var(--line); vertical-align:middle;}
  table.lista th{background:var(--bg); font-size:.7rem; letter-spacing:.05em; text-transform:uppercase; color:var(--ink-soft); font-weight:700;}
  table.lista tbody tr{transition:background .1s ease;}
  table.lista tbody tr:hover{background:var(--bg);}
  table.lista tbody tr:last-child td{border-bottom:none}
  table.lista td.acoes{text-align:right; white-space:nowrap;}
  table.lista td.acoes form{display:inline-block; margin-left:.4rem;}
  form.form-padrao{max-width:520px;}
  form.form-padrao label{display:block; font-size:.83rem; font-weight:600; color:var(--ink-soft); margin:1.1rem 0 .35rem;}
  form.form-padrao label:first-of-type{margin-top:0}
  form.form-padrao input[type=text], form.form-padrao input[type=email], form.form-padrao input[type=password], form.form-padrao input[type=number], form.form-padrao select{
    width:100%; padding:.62rem .8rem; border:1px solid var(--line); border-radius:var(--radius-sm); font-size:.92rem; background:#fff; color:var(--ink); transition:border-color .12s ease, box-shadow .12s ease;
  }
  form.form-padrao input:focus, form.form-padrao select:focus{outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-tint);}
  form.form-padrao .ajuda{font-size:.78rem; color:var(--ink-faint); margin-top:.3rem;}
  form.form-padrao .acoes-form{margin-top:1.8rem; display:flex; gap:.7rem;}
  form.form-padrao fieldset{border:1px solid var(--line); border-radius:var(--radius-sm); margin-top:1.8rem; padding:1.1rem 1.2rem;}
  form.form-padrao legend{padding:0 .4rem; font-size:.85rem; font-weight:700; color:var(--ink);}
  .grade-checkbox{display:flex; flex-wrap:wrap; gap:.5rem 1.2rem;}
  .grade-checkbox label{display:flex; align-items:center; gap:.4rem; font-size:.88rem; color:var(--ink); margin:0;}
  table.faixas{width:100%; border-collapse:collapse; font-size:.86rem;}
  table.faixas th{text-align:left; font-size:.72rem; text-transform:uppercase; color:var(--ink-soft); padding:.3rem .4rem;}
  table.faixas td{padding:.3rem .4rem;}
  table.faixas input{width:100%; padding:.4rem .5rem; border:1px solid var(--line); border-radius:6px; font-size:.86rem;}
  .flash{padding:.75rem 1.05rem; border-radius:0 var(--radius-sm) var(--radius-sm) 0; font-size:.88rem; margin-bottom:1.3rem; border-left:3px solid;}
  .flash.sucesso{background:var(--good-tint); color:var(--good); border-color:var(--good);}
  .flash.erro{background:var(--bad-tint); color:var(--bad); border-color:var(--bad);}

  /* ---- ajuda: callouts, fórmula e exemplo (reaproveitável fora da tela de Ajuda também) ---- */
  .callout{padding:.95rem 1.15rem; border-radius:0 var(--radius-sm) var(--radius-sm) 0; border-left:3px solid; margin:1.1rem 0; font-size:.9rem;}
  .callout .callout-label{display:block; font-weight:700; font-size:.82rem; margin-bottom:.35rem;}
  .callout.armadilha{background:var(--bad-tint); border-color:var(--bad);}
  .callout.armadilha .callout-label{color:var(--bad);}
  .callout.dica{background:var(--primary-tint); border-color:var(--primary);}
  .callout.dica .callout-label{color:var(--primary-ink);}
  .callout.critico{background:var(--bad-tint); border-color:var(--bad); border-left-width:5px; padding:1.15rem 1.35rem;}
  .callout.critico .callout-label{color:var(--bad); font-size:.92rem;}
  .formula{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); padding:1.1rem 1.4rem; font-weight:700; letter-spacing:-.01em; text-align:center; color:var(--primary-ink); margin:1.1rem 0; box-shadow:var(--shadow-sm);}
  .formula .formula-label{display:block; font-weight:600; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-faint); margin-bottom:.5rem;}
  .exemplo{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); padding:1.1rem 1.3rem; margin:1.1rem 0; box-shadow:var(--shadow-sm);}
  .exemplo h4{margin:0 0 .5rem; font-size:.85rem; text-transform:uppercase; letter-spacing:.04em; color:var(--accent);}
  .exemplo .resultado{font-weight:700; color:var(--good);}
  .badge-papel{display:inline-block; padding:.1em .5em; border-radius:6px; font-size:.72rem; font-weight:700; background:var(--primary-tint); color:var(--primary-ink);}
  .ajuda-toc{list-style:none; padding:0; margin:1rem 0; display:grid; gap:.35rem;}
  .ajuda-toc a{color:var(--ink-soft); text-decoration:none; font-size:.9rem; font-weight:500;}
  .ajuda-toc a:hover{color:var(--primary-ink)}
  .ajuda h3{font-weight:800; letter-spacing:-.01em; font-size:1.15rem; color:var(--primary-ink); margin:1.9rem 0 .55rem;}
  .ajuda section{scroll-margin-top:1rem; padding-top:1.7rem; border-top:1px solid var(--line);}
  .ajuda section:first-of-type{padding-top:0; border-top:none;}
  .ajuda table{width:100%; border-collapse:collapse; font-size:.87rem; margin:.8rem 0;}
  .ajuda table th{background:var(--bg); text-align:left; padding:.55rem .75rem; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-soft); font-weight:700;}
  .ajuda table td{padding:.55rem .75rem; border-top:1px solid var(--line);}
  .ajuda .tabela-wrap{overflow-x:auto; border:1px solid var(--line); border-radius:var(--radius-sm);}
  .ajuda ul.checklist{list-style:none; padding:0; margin:.7rem 0;}
  .ajuda ul.checklist li{padding:.28rem 0 .28rem 1.6rem; position:relative;}
  .ajuda ul.checklist li::before{content:"☐"; position:absolute; left:0; color:var(--primary);}

  /* ---- dashboard: stat tiles ---- */
  .kpi-row{display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:1rem; margin:1.1rem 0 1.5rem;}
  .stat-tile{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); padding:1.2rem 1.3rem; box-shadow:var(--shadow-sm);}
  .stat-tile .stat-label{display:block; font-size:.7rem; letter-spacing:.05em; text-transform:uppercase; color:var(--ink-soft); font-weight:700; margin-bottom:.5rem;}
  .stat-tile .stat-value{display:block; font-size:1.65rem; font-weight:800; letter-spacing:-.01em; color:var(--ink); line-height:1.1;}
  .stat-tile .stat-sub{display:block; font-size:.78rem; color:var(--ink-faint); margin-top:.4rem;}

  /* ---- dashboard: seção ---- */
  .secao{margin-top:2.2rem;}
  .secao h3{font-weight:800; letter-spacing:-.01em; font-size:1.1rem; margin:0 0 .25rem;}
  .secao .secao-sub{font-size:.82rem; color:var(--ink-faint); margin:0 0 1.1rem;}

  /* ---- meter: uma razão contra um limite (ex.: atingimento de meta) ---- */
  .meter-row{display:grid; grid-template-columns:1fr 3.4rem; align-items:center; gap:.7rem; padding:.35rem 0;}
  .meter-row .meter-nome{grid-column:1 / -1; font-size:.86rem; color:var(--ink); margin-bottom:.2rem; display:flex; justify-content:space-between; gap:.5rem;}
  .meter-row .meter-nome .status-tag{font-size:.74rem; font-weight:600;}
  .meter-track{height:10px; border-radius:999px; overflow:hidden;}
  .meter-fill{height:100%; border-radius:999px;}
  .meter-pct{text-align:right; font-size:.82rem; font-weight:600; font-variant-numeric:tabular-nums; color:var(--ink-soft);}
  .status-good{color:var(--good)} .status-warn{color:var(--warn)} .status-bad{color:var(--bad)}

  /* ---- ranking: magnitude simples, uma cor só ---- */
  .rank-list{display:flex; flex-direction:column; gap:.55rem;}
  .rank-row{display:grid; grid-template-columns:7.5rem 1fr 5.5rem; align-items:center; gap:.7rem; font-size:.85rem;}
  .rank-name{color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
  .rank-track{height:10px; border-radius:999px; background:var(--bg); overflow:hidden;}
  .rank-fill{height:100%; border-radius:999px; background:var(--primary);}
  .rank-value{text-align:right; font-variant-numeric:tabular-nums; color:var(--ink-soft);}

  /* ---- legenda (paleta categórica) ---- */
  .legend{display:flex; flex-wrap:wrap; gap:.9rem 1.1rem; font-size:.78rem; color:var(--ink-soft); margin:0 0 1rem;}
  .legend .sw{display:inline-block; width:.62rem; height:.62rem; border-radius:2px; margin-right:.4rem; vertical-align:middle;}

  /* ---- barra empilhada: 4 pilares da pontuação 360 ---- */
  .pilares-item{margin:.7rem 0;}
  .pilares-item .pilares-topo{display:flex; justify-content:space-between; font-size:.85rem; color:var(--ink); margin-bottom:.3rem;}
  .pilares-bar{display:flex; height:12px; border-radius:6px; overflow:hidden; background:var(--bg);}
  .pilares-bar .seg{height:100%;}
  .pilares-bar .seg + .seg{margin-left:2px;}

  /* ---- oportunidade: faltam R$X para a próxima faixa ---- */
  .oport-row{padding:.65rem 0; border-bottom:1px solid var(--line);}
  .oport-row:last-child{border-bottom:none;}
  .oport-topo{display:flex; justify-content:space-between; align-items:baseline; gap:.6rem; flex-wrap:wrap;}
  .oport-nome{font-size:.88rem; font-weight:600; color:var(--ink);}
  .oport-quem{font-size:.78rem; color:var(--ink-faint); font-weight:400;}
  .oport-ganho{font-size:.85rem; font-weight:700; color:var(--accent); white-space:nowrap;}
  .oport-texto{margin:.3rem 0 0; font-size:.82rem; color:var(--ink-soft);}
  .oport-texto strong{color:var(--ink);}
  .oport-track{height:6px; border-radius:999px; background:var(--accent-tint); margin-top:.5rem; overflow:hidden;}
  .oport-fill{height:100%; border-radius:999px; background:var(--accent);}

  /* ---- distribuição de níveis (ordinal, uma cor) ---- */
  .tier-row{display:grid; grid-template-columns:9.5rem 1fr 2.2rem; align-items:center; gap:.7rem; font-size:.85rem; padding:.3rem 0;}
  .tier-track{height:10px; border-radius:999px; background:var(--bg); overflow:hidden;}
  .tier-fill{height:100%; border-radius:999px; background:var(--primary);}
  .tier-count{text-align:right; font-variant-numeric:tabular-nums; color:var(--ink-soft);}
</style>
</head>
<body>
<header class="app">
  <div class="brand"><img src="/assets/img/logo.jpg" alt="Farmácia Geremias · Comissão 360"></div>
  <div class="top-right">
    <span class="pill"><?= htmlspecialchars($rotulosPapel[$papel] ?? $papel, ENT_QUOTES) ?></span>
    <a href="/dashboard">Dashboard</a>
    <a href="/ajuda">Ajuda</a>
    <a href="/minha-conta" class="avatar-link">
      <?php if (!empty($minhaConta['avatar_path'])): ?>
        <img src="<?= htmlspecialchars($minhaConta['avatar_path'], ENT_QUOTES) ?>" alt="" class="avatar-mini">
      <?php else: ?>
        <span class="avatar-mini placeholder"><?= htmlspecialchars(mb_strtoupper(mb_substr($minhaConta['nome'] ?? '?', 0, 1)), ENT_QUOTES) ?></span>
      <?php endif; ?>
      Minha conta
    </a>
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
<?php elseif ($papel === 'funcionario'): ?>
<nav class="admin-nav">
  <a href="/minhas-vendas" class="<?= str_starts_with($rota, '/minhas-vendas') ? 'active' : '' ?>">Minhas vendas</a>
  <a href="/minhas-metas" class="<?= str_starts_with($rota, '/minhas-metas') ? 'active' : '' ?>">Minhas metas</a>
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
