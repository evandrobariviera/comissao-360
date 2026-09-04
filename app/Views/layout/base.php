<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Funcionario;
use App\Models\Periodo;

/** @var string $content */
$rotulosPapel = [
    'admin' => 'Administrador',
    'gerente' => 'Gerente de filial',
    'funcionario' => 'Funcionário',
];
$rotulosStatusPeriodo = ['aberto' => 'Aberto', 'fechado' => 'Fechado', 'aprovado' => 'Aprovado'];
$nomesMesCurto = [1=>'jan',2=>'fev',3=>'mar',4=>'abr',5=>'mai',6=>'jun',7=>'jul',8=>'ago',9=>'set',10=>'out',11=>'nov',12=>'dez'];
$papel = Auth::papel();
$flash = Flash::pull();
$minhaConta = Funcionario::porUsuario((int) Auth::id());
$periodoAtivo = Periodo::ativo();
$periodosDisponiveis = Periodo::listar();
$urlAtual = $_SERVER['REQUEST_URI'] ?? '/dashboard';
$rota = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// O seletor global de período só aparece nas telas que de fato trabalham "por mês".
// Configuração (Regras, Filiais, Usuários), Corrida (tem seu próprio seletor de edição),
// Relatórios (tem intervalo de/até próprio), Ajuda e Minha conta não usam o período ativo.
$mostrarSeletorPeriodo = $rota === '/';
foreach (['/dashboard', '/painel-filial', '/metas', '/vendas', '/indicadores', '/fechamento', '/minhas-vendas', '/minhas-metas'] as $prefixoComPeriodo) {
    if (str_starts_with($rota, $prefixoComPeriodo)) {
        $mostrarSeletorPeriodo = true;
        break;
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Comissão 360 · Farmácia Geremias</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#F2F5F2; --surface:#fff; --ink:#141A14; --ink-soft:#4E5E52; --ink-faint:#8A9A8D; --line:#DDE4DE;
    --primary:#1A7A4A; --primary-ink:#124F31; --primary-tint:#EDF5F0; --accent:#B8792E;
    --good:#2CA863; --bad:#B83232; --good-tint:#E4F1EA; --bad-tint:#FDF0F0;
    --warn:#B07A10; --warn-tint:#FDF8EC; --accent-tint:#F3E4CE;
    --green-l:#2CA863; --green-xl:#7FDBA8;
    /* Casca escura estilo app — sidebar (desktop) e top-bar (mobile). */
    --dark:#0D1410; --dark-2:#1A2318;
    --sidebar-w:224px;
    /* Paleta categórica validada (CVD-safe) — só para o gráfico dos 4 pilares da pontuação 360. */
    --chart-individual:#2a78d6; --chart-filial:#eb6834; --chart-qualidade:#1baf7a; --chart-equipe:#eda100;
    /* Sistema de forma/elevação — consistente em cards, tabelas, botões e inputs. */
    --font:'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI Variable", "Segoe UI", system-ui, Roboto, Helvetica, Arial, sans-serif;
    --radius-sm:8px; --radius-md:14px; --radius-lg:18px;
    --shadow-sm:0 1px 2px rgba(16,27,30,.05);
    --shadow-md:0 1px 2px rgba(16,27,30,.04), 0 8px 20px rgba(16,27,30,.06);
  }
  *{box-sizing:border-box}
  body{margin:0; background:var(--bg); color:var(--ink); font-family:var(--font); line-height:1.5; -webkit-font-smoothing:antialiased;}
  a{transition:color .12s ease}

  /* ============================================================
     SIDEBAR (desktop) — casca escura fixa à esquerda.
     ============================================================ */
  .sidebar{
    position:fixed; top:0; left:0; bottom:0; width:var(--sidebar-w); z-index:20;
    background:var(--dark); display:flex; flex-direction:column;
    border-right:1px solid rgba(255,255,255,.06);
  }
  .sb-brand{ padding:22px 20px 18px; border-bottom:1px solid rgba(255,255,255,.07); }
  .sb-brand a{ text-decoration:none; display:block; }
  .sb-brand .sb-brand-label{ font-size:12px; font-weight:700; letter-spacing:.06em; color:var(--green-xl); }
  .sb-brand .sb-brand-sub{ font-size:10px; color:rgba(255,255,255,.32); margin-top:2px; }
  .sb-period{ padding:13px 20px; border-bottom:1px solid rgba(255,255,255,.07); }
  .sb-period .sb-period-label{ font-size:10px; color:rgba(255,255,255,.32); margin-bottom:5px; }
  .sb-period select{
    width:100%; padding:.42rem .6rem; border:1px solid rgba(255,255,255,.12); border-radius:var(--radius-sm);
    background:rgba(255,255,255,.06); color:rgba(255,255,255,.85); font-size:.8rem; font-weight:600; font-family:var(--font); cursor:pointer;
  }
  .sb-period select:focus{ outline:none; border-color:var(--green-l); }
  .sb-period .sb-period-pill{ display:inline-block; margin-top:.5rem; font-size:.68rem; font-weight:700; padding:.15em .6em; border-radius:999px; background:var(--warn-tint); color:var(--warn); }
  .sb-nav{ flex:1; padding:10px 0; overflow-y:auto; }
  .sb-section{ font-size:9px; font-weight:700; letter-spacing:.11em; text-transform:uppercase; color:rgba(255,255,255,.24); padding:14px 20px 5px; }
  .sb-item{
    display:flex; align-items:center; gap:11px; padding:9px 20px;
    color:rgba(255,255,255,.55); text-decoration:none; font-size:.85rem; font-weight:500;
    border-left:2px solid transparent; transition:color .12s ease, background .12s ease;
  }
  .sb-item:hover{ color:rgba(255,255,255,.9); background:rgba(255,255,255,.04); }
  .sb-item.active{ color:#fff; background:rgba(26,122,74,.18); border-left-color:var(--green-l); font-weight:600; }
  .sb-item svg{ width:17px; height:17px; flex:none; stroke:currentColor; fill:none; stroke-width:1.9; }
  .sb-footer{ border-top:1px solid rgba(255,255,255,.07); padding:12px 14px; }
  .sb-user{ display:flex; align-items:center; gap:10px; padding:8px 6px; text-decoration:none; border-radius:var(--radius-sm); }
  .sb-user:hover{ background:rgba(255,255,255,.04); }
  .sb-user .avatar-mini{ width:30px; height:30px; border-radius:999px; object-fit:cover; flex:none; }
  .sb-user .avatar-mini.placeholder{ background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; }
  .sb-user .sb-user-name{ font-size:.8rem; font-weight:600; color:rgba(255,255,255,.8); }
  .sb-user .sb-user-role{ font-size:.67rem; color:rgba(255,255,255,.32); }
  .sb-logout{ display:flex; align-items:center; gap:11px; padding:9px 12px; margin-top:2px; color:rgba(255,255,255,.4); text-decoration:none; font-size:.8rem; font-weight:500; border-radius:var(--radius-sm); }
  .sb-logout:hover{ color:var(--bad); background:rgba(255,255,255,.04); }
  .sb-logout svg{ width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:1.9; }

  main{ margin-left:var(--sidebar-w); padding:2.4rem 2.6rem 4rem; max-width:calc(var(--sidebar-w) + 1160px); }
  main.main-wide{ max-width:none; }

  h2{font-family:var(--font); font-weight:800; letter-spacing:-.015em; font-size:1.5rem; margin:0 0 .35rem; color:var(--ink);}
  .subtitle{color:var(--ink-soft); font-size:.92rem; margin:0 0 1.4rem; line-height:1.5;}
  .card{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); padding:1.5rem 1.7rem; box-shadow:var(--shadow-sm); margin-top:1.1rem;}
  .card.flush{padding:0; overflow:hidden;}
  .card-header{display:flex; align-items:center; justify-content:space-between; gap:1rem; margin:-.2rem 0 1rem;}
  .card-title{font-size:.9rem; font-weight:700; color:var(--ink);}
  .card-meta{font-size:.75rem; color:var(--ink-faint);}
  .pill{display:inline-block; padding:.2em .65em; border-radius:999px; font-size:.75rem; font-weight:600; background:var(--primary-tint); color:var(--primary-ink)}
  .pill.status-ativo{background:var(--good-tint); color:var(--good)}
  .pill.status-inativo{background:var(--bad-tint); color:var(--bad)}
  .pill.status-fechado, .pill.status-aprovado{background:var(--warn-tint); color:var(--warn);}
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
  td.num, th.num{text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap;}

  form.form-padrao{max-width:520px;}
  form.form-padrao label{display:block; font-size:.83rem; font-weight:600; color:var(--ink-soft); margin:1.1rem 0 .35rem;}
  form.form-padrao label:first-of-type{margin-top:0}
  form.form-padrao input[type=text], form.form-padrao input[type=email], form.form-padrao input[type=password], form.form-padrao input[type=number], form.form-padrao input[type=date], form.form-padrao select{
    width:100%; padding:.62rem .8rem; border:1px solid var(--line); border-radius:var(--radius-sm); font-size:.92rem; background:#fff; color:var(--ink); transition:border-color .12s ease, box-shadow .12s ease;
  }
  form.form-padrao input[type=date]{font-family:inherit;}
  form.form-padrao input:focus, form.form-padrao select:focus{outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-tint);}
  form.form-padrao .ajuda{font-size:.78rem; color:var(--ink-faint); margin-top:.3rem;}
  form.form-padrao .acoes-form{margin-top:1.8rem; display:flex; gap:.7rem;}
  form.form-padrao fieldset{border:1px solid var(--line); border-radius:var(--radius-sm); margin-top:1.8rem; padding:1.1rem 1.2rem;}
  form.form-padrao legend{padding:0 .4rem; font-size:.85rem; font-weight:700; color:var(--ink);}
  .grade-checkbox{display:flex; flex-wrap:wrap; gap:.5rem 1.2rem;}
  .grade-checkbox label{display:flex; align-items:center; gap:.4rem; font-size:.88rem; color:var(--ink); margin:0;}

  /* Campo solto fora de .form-padrao (filtros de relatório, grades da Corrida). */
  .campo-inline{padding:.5rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); font-size:.88rem; background:#fff; color:var(--ink); font-family:var(--font);}
  .campo-inline:focus{outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-tint);}
  .campo-rotulo{font-size:.75rem; font-weight:700; color:var(--ink-soft);}
  .grade-2, .grade-3, .grade-4{display:grid; gap:0 1rem;}
  .grade-2{grid-template-columns:repeat(2,1fr);}
  .grade-3{grid-template-columns:repeat(3,1fr);}
  .grade-4{grid-template-columns:repeat(4,1fr);}

  table.faixas{width:100%; border-collapse:collapse; font-size:.86rem;}
  table.faixas th{text-align:left; font-size:.72rem; text-transform:uppercase; color:var(--ink-soft); padding:.3rem .4rem;}
  table.faixas td{padding:.3rem .4rem;}
  table.faixas input{width:100%; padding:.4rem .5rem; border:1px solid var(--line); border-radius:6px; font-size:.86rem;}
  .scrollx{overflow-x:auto;}
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

  /* ---- dashboard: stat tiles — bloco único com divisórias, barrinha no rodapé ---- */
  .kpi-row{display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:0; margin:1.1rem 0 1.5rem; background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); overflow:hidden; box-shadow:var(--shadow-sm);}
  .stat-tile{padding:1.15rem 1.3rem; border-right:1px solid var(--line); position:relative; min-width:0;}
  .stat-tile:last-child{border-right:none;}
  .stat-tile .stat-label{display:block; font-size:.7rem; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-soft); font-weight:700; margin-bottom:.5rem;}
  .stat-tile .stat-value{display:block; font-size:1.55rem; font-weight:800; letter-spacing:-.01em; color:var(--ink); line-height:1.1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
  .stat-tile .stat-value.green{color:var(--primary);} .stat-tile .stat-value.red{color:var(--bad);} .stat-tile .stat-value.amber{color:var(--warn);}
  .stat-tile .stat-sub{display:block; font-size:.76rem; color:var(--ink-faint); margin-top:.4rem;}
  .stat-tile .stat-accent{position:absolute; left:0; right:0; bottom:0; height:3px; background:var(--line);}
  .stat-tile .stat-accent > span{display:block; height:100%;}
  .stat-bar-track{height:6px; border-radius:999px; overflow:hidden; margin-top:.55rem;}
  .stat-bar-fill{height:100%; border-radius:999px;}

  /* ---- dashboard: seção ---- */
  .secao{margin-top:2.2rem;}
  .secao h3{font-weight:800; letter-spacing:-.01em; font-size:1.1rem; margin:0 0 .25rem;}
  .secao .secao-sub{font-size:.82rem; color:var(--ink-faint); margin:0 0 1.1rem;}

  /* ---- filial-row: nome / trilha / % / badge (atingimento de meta) ---- */
  .filial-row{display:grid; grid-template-columns:minmax(84px,132px) 1fr 3.4rem 5rem; align-items:center; gap:.9rem; padding:.7rem 0; border-bottom:1px solid var(--line);}
  .filial-row:last-child{border-bottom:none;}
  .filial-name{font-size:.86rem; font-weight:500; color:var(--ink); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
  .progress-track{height:9px; border-radius:999px; background:var(--bg); overflow:hidden;}
  .progress-fill{height:100%; border-radius:999px;}
  .progress-fill.low{background:var(--bad);} .progress-fill.mid{background:var(--warn);} .progress-fill.good{background:var(--green-l);} .progress-fill.great{background:var(--primary);}
  .pct-val{font-size:.84rem; font-weight:700; text-align:right; font-variant-numeric:tabular-nums;}
  .pct-val.low{color:var(--bad);} .pct-val.mid{color:var(--warn);} .pct-val.good{color:var(--green-l);} .pct-val.great{color:var(--primary);}
  .status-badge{font-size:.68rem; font-weight:700; padding:.2rem .5rem; border-radius:999px; text-align:center; white-space:nowrap;}
  .status-badge.low{background:var(--bad-tint); color:var(--bad);} .status-badge.mid{background:var(--warn-tint); color:var(--warn);}
  .status-badge.good, .status-badge.great{background:var(--good-tint); color:var(--good);}
  .status-good{color:var(--good)} .status-warn{color:var(--warn)} .status-bad{color:var(--bad)}

  /* ---- meter-row (compat: ainda usado por alguns Viz) ---- */
  .meter-row{display:grid; grid-template-columns:1fr 3.4rem; align-items:center; gap:.7rem; padding:.35rem 0;}
  .meter-row .meter-nome{grid-column:1 / -1; font-size:.86rem; color:var(--ink); margin-bottom:.2rem; display:flex; justify-content:space-between; gap:.5rem;}
  .meter-row .meter-nome .status-tag{font-size:.74rem; font-weight:600;}
  .meter-track{height:10px; border-radius:999px; overflow:hidden;}
  .meter-fill{height:100%; border-radius:999px;}
  .meter-pct{text-align:right; font-size:.82rem; font-weight:600; font-variant-numeric:tabular-nums; color:var(--ink-soft);}

  /* ---- ranking: magnitude simples, uma cor só ---- */
  .rank-list{display:flex; flex-direction:column; gap:.55rem;}
  .rank-row{display:grid; grid-template-columns:7.5rem 1fr 5.5rem; align-items:center; gap:.7rem; font-size:.85rem;}
  .rank-name{color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
  .rank-track{height:10px; border-radius:999px; background:var(--bg); overflow:hidden;}
  .rank-fill{height:100%; border-radius:999px; background:var(--primary);}
  .rank-value{text-align:right; font-variant-numeric:tabular-nums; color:var(--ink-soft);}

  /* ---- ranking rico (posição + nome/filial + valor + pílula de nível) ---- */
  .rank-tabela{display:flex; flex-direction:column;}
  .rank-linha{display:grid; grid-template-columns:1.6rem 1fr auto; align-items:center; gap:.8rem; padding:.7rem 0; border-bottom:1px solid var(--line);}
  .rank-linha:last-child{border-bottom:none;}
  .rank-pos{font-size:.82rem; font-weight:700; color:var(--ink-faint); text-align:center;}
  .rank-pos.top{color:var(--primary);}
  .rank-quem{min-width:0;}
  .rank-quem .rank-nome{font-size:.86rem; font-weight:600; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
  .rank-quem .rank-sub{font-size:.72rem; color:var(--ink-faint);}
  .rank-fim{text-align:right; display:flex; flex-direction:column; align-items:flex-end; gap:.2rem;}
  .rank-fim .rank-val{font-size:.86rem; font-weight:700; color:var(--ink); font-variant-numeric:tabular-nums;}
  .nivel-pill{font-size:.62rem; font-weight:700; padding:.15rem .5rem; border-radius:999px; white-space:nowrap;}
  .nivel-ouro{background:#FEF9EC; color:#A07800;} .nivel-diamante{background:#EBF4FD; color:#1565A8;}
  .nivel-platina{background:var(--bg); color:var(--ink-soft);} .nivel-base{background:var(--bad-tint); color:var(--bad);}

  /* ---- Distribuição Meta 360: grade 2×2 ---- */
  .dist360{display:grid; grid-template-columns:1fr 1fr; gap:0;}
  .dist360 .d360{padding:1rem 1.15rem; border-right:1px solid var(--line); border-bottom:1px solid var(--line);}
  .dist360 .d360:nth-child(2n){border-right:none;} .dist360 .d360:nth-last-child(-n+2){border-bottom:none;}
  .dist360 .d360-label{font-size:.7rem; color:var(--ink-faint); margin-bottom:.35rem;}
  .dist360 .d360-val{font-size:1.7rem; font-weight:800; line-height:1;}
  .dist360 .d360-val.green{color:var(--primary);} .dist360 .d360-val.blue{color:#1565A8;} .dist360 .d360-val.red{color:var(--bad);}
  .dist360 .d360-sub{font-size:.7rem; color:var(--ink-faint); margin-top:.3rem;}

  /* ---- Corrida strip: card escuro ---- */
  .corrida-strip{background:var(--dark); border-radius:var(--radius-md); padding:1.3rem 1.4rem; margin-top:1.1rem; color:#fff;}
  .corrida-strip .cs-head{display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1rem;}
  .corrida-strip .cs-title{font-size:.9rem; font-weight:700;}
  .corrida-strip .cs-meta{font-size:.7rem; color:rgba(255,255,255,.4); margin-top:.15rem;}
  .corrida-strip .cs-dias{text-align:right; flex:none;}
  .corrida-strip .cs-dias-val{font-size:1.7rem; font-weight:800; color:var(--green-xl); line-height:1;}
  .corrida-strip .cs-dias-label{font-size:.66rem; color:rgba(255,255,255,.4);}
  .corrida-strip .cs-grupos{display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.1rem;}
  .corrida-strip .cs-grupo-label{font-size:.7rem; font-weight:600; color:rgba(255,255,255,.4); margin-bottom:.5rem;}
  .corrida-strip .cs-linha{display:flex; justify-content:space-between; gap:.6rem; padding:.32rem 0; border-bottom:1px solid rgba(255,255,255,.07); font-size:.78rem;}
  .corrida-strip .cs-linha:last-child{border-bottom:none;}
  .corrida-strip .cs-linha .cs-nome{color:rgba(255,255,255,.72);}
  .corrida-strip .cs-linha.top .cs-nome{color:#fff; font-weight:600;}
  .corrida-strip .cs-linha .cs-premio{color:var(--green-xl); font-weight:700; font-variant-numeric:tabular-nums;}

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

  /* ---- mix de categoria: grade categoria x filial, heatmap de status ---- */
  table.mix-grade{width:100%; border-collapse:collapse; font-size:.83rem; background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); overflow:hidden;}
  table.mix-grade th, table.mix-grade td{padding:.55rem .7rem; text-align:center; border-bottom:1px solid var(--line); white-space:nowrap;}
  table.mix-grade th{background:var(--bg); font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-soft); font-weight:700;}
  table.mix-grade td:first-child, table.mix-grade th:first-child{text-align:left; color:var(--ink); font-weight:600;}
  table.mix-grade tbody tr:last-child td{border-bottom:none;}
  table.mix-grade td.mix-meta-col{color:var(--ink-faint); font-variant-numeric:tabular-nums;}
  table.mix-grade td.mix-cell{font-variant-numeric:tabular-nums; font-weight:600;}
  table.mix-grade .mix-rede-col{border-left:2px solid var(--line);}

  /* ---- gauge: faixa piso→teto de um indicador individual ---- */
  .gauge-track{height:8px; border-radius:999px; overflow:hidden; margin-top:.6rem;}
  .gauge-fill{height:100%; border-radius:999px;}
  .gauge-extremos{display:flex; justify-content:space-between; font-size:.7rem; color:var(--ink-faint); margin-top:.3rem; font-variant-numeric:tabular-nums;}

  /* ---- checklist de equipe (binário, sem gráfico) ---- */
  ul.checklist-status{list-style:none; padding:0; margin:0; display:grid; gap:.5rem;}
  ul.checklist-status li{padding:.4rem .1rem .4rem 1.7rem; position:relative; font-size:.87rem; color:var(--ink);}
  ul.checklist-status li.ok::before{content:"✓"; position:absolute; left:0; color:var(--good); font-weight:700;}
  ul.checklist-status li.pendente{color:var(--ink-faint);}
  ul.checklist-status li.pendente::before{content:"·"; position:absolute; left:.3rem; color:var(--ink-faint); font-weight:700;}

  /* ---- ritmo diário: trajetória acumulada (SVG, sem lib de gráfico) ---- */
  .ritmo-chart{width:100%; height:auto; display:block;}
  .ritmo-chart .ritmo-eixo{font-size:9px; fill:var(--ink-faint);}
  .ritmo-chart circle.ritmo-marcador{fill:var(--surface); stroke-width:1.5;}

  /* ---- painel do funcionário: hero escuro + 2 colunas ---- */
  .balc-hero{background:var(--dark); border-radius:var(--radius-lg); padding:1.5rem 1.6rem; color:#fff; margin:1.1rem 0 .4rem;}
  .balc-hero .balc-saud{font-size:.8rem; color:rgba(255,255,255,.42);}
  .balc-hero .balc-nome{font-size:1.25rem; font-weight:700; margin:.1rem 0 1rem;}
  .balc-hero .balc-box{background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:var(--radius-md); padding:1.1rem 1.2rem;}
  .balc-hero .balc-box-label{font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--green-xl); margin-bottom:.4rem;}
  .balc-hero .balc-box-val{font-size:2.3rem; font-weight:800; line-height:1;}
  .balc-hero .balc-box-sub{font-size:.75rem; color:rgba(255,255,255,.42); margin-top:.4rem;}
  .balc-hero .balc-nivel{display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:.7rem; background:rgba(26,122,74,.22); border:1px solid rgba(44,168,99,.28); border-radius:var(--radius-sm); padding:.7rem 1rem;}
  .balc-hero .balc-nivel-label{font-size:.72rem; color:rgba(255,255,255,.5);}
  .balc-hero .balc-nivel-pts{font-size:.72rem; color:rgba(255,255,255,.4); margin-top:.1rem;}
  .balc-hero .balc-nivel-val{font-size:.95rem; font-weight:700; color:var(--green-xl); white-space:nowrap;}

  main.main-wide{max-width:none;}
  .dash-cols{display:grid; grid-template-columns:2fr 1fr; gap:0 2.2rem; align-items:start;}
  .dash-col-filial{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); padding:1.5rem 1.6rem; box-shadow:var(--shadow-sm); position:sticky; top:1.5rem;}
  .dash-col-filial .kpi-row{margin-bottom:0}
  @media (max-width: 1100px){
    .dash-cols{grid-template-columns:1fr;}
    .dash-col-filial{position:static; margin-top:2.2rem;}
  }

  /* ---- Simulador ---- */
  .simulador{background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); padding:1.3rem 1.4rem; margin-top:1.1rem; box-shadow:var(--shadow-sm);}
  .simulador h3{font-size:1rem; font-weight:700; margin:0 0 .2rem;}
  .simulador .sim-desc{font-size:.8rem; color:var(--ink-faint); margin:0 0 1rem;}
  .simulador .sim-row{display:flex; gap:.6rem; flex-wrap:wrap; align-items:center;}
  .simulador .sim-row select, .simulador .sim-row input{padding:.55rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); font-size:.9rem; font-family:var(--font); background:#fff; color:var(--ink);}
  .simulador .sim-row select{flex:1; min-width:140px;}
  .simulador .sim-row input{width:120px;}
  .simulador .sim-result{display:none; margin-top:1rem; background:var(--primary-tint); border:1px solid rgba(26,122,74,.2); border-radius:var(--radius-sm); padding:.9rem 1.1rem;}
  .simulador .sim-result.show{display:block;}
  .simulador .sim-result-label{font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--primary-ink); margin-bottom:.25rem;}
  .simulador .sim-result-val{font-size:1.5rem; font-weight:800; color:var(--primary-ink);}
  .simulador .sim-result-diff{font-size:.78rem; color:var(--ink-soft); margin-top:.3rem;}

  nav.tabs-filial{display:flex; gap:.4rem; overflow-x:auto; margin-bottom:1.4rem; border-bottom:1px solid var(--line); padding-bottom:0;}
  nav.tabs-filial a{display:inline-block; padding:.55rem 1rem; color:var(--ink-soft); text-decoration:none; font-size:.86rem; font-weight:600; white-space:nowrap; border-radius:var(--radius-sm) var(--radius-sm) 0 0; border:1px solid transparent; border-bottom:none; transition:color .12s ease, background .12s ease;}
  nav.tabs-filial a:hover{color:var(--ink); background:var(--bg);}
  nav.tabs-filial a.active{color:var(--primary-ink); background:var(--surface); border-color:var(--line); position:relative; top:1px;}

  /* ============================================================
     MOBILE / APP  (≤ 768px) — top-bar escura, barra inferior,
     folha "Mais", e ajustes de layout das telas.
     ============================================================ */
  .mobile-topbar, .mobile-bottomnav, .mais-sheet, .mais-backdrop{ display:none; }

  @media (max-width: 768px){
    .sidebar{ display:none; }
    main{ margin:0; max-width:100%; padding:1.3rem 1rem calc(5.6rem + env(safe-area-inset-bottom)); }
    main.main-wide{ max-width:100%; }
    html, body{ overflow-x:clip; }
    main{ overflow-x:clip; }

    /* top-bar escura estilo app */
    .mobile-topbar{
      display:flex; align-items:center; justify-content:space-between; gap:.6rem;
      position:sticky; top:0; z-index:30;
      background:var(--dark); border-bottom:1px solid rgba(255,255,255,.06);
      padding:.55rem 1rem; padding-top:calc(.55rem + env(safe-area-inset-top));
    }
    .mobile-topbar .brand{ display:flex; align-items:center; }
    .mobile-topbar .brand-label{ font-size:.72rem; font-weight:700; letter-spacing:.05em; color:var(--green-xl); }
    .mobile-topbar .tb-chip{
      display:inline-flex; align-items:center; gap:.3rem;
      font-family:var(--font); font-size:.75rem; font-weight:700; cursor:pointer;
      color:rgba(255,255,255,.75); background:rgba(255,255,255,.09);
      border:1px solid rgba(255,255,255,.12); border-radius:999px; padding:.3rem .75rem;
    }
    .mobile-topbar .tb-chip.status-aprovado, .mobile-topbar .tb-chip.status-fechado{ background:var(--warn-tint); color:var(--warn); border-color:transparent; }
    .mobile-topbar .tb-chip svg{ width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:2.2; }

    /* toggle Rede / Meu painel (gerente) */
    .view-tabs{ display:flex; gap:3px; background:rgba(255,255,255,.06); border-radius:9px; padding:3px; }
    .view-tabs a{ padding:.35rem .8rem; border-radius:7px; font-size:.72rem; font-weight:600; text-decoration:none; color:rgba(255,255,255,.45); }
    .view-tabs a.active{ background:var(--primary); color:#fff; }
    .mobile-topbar.has-toggle{ flex-wrap:wrap; }

    h2{ font-size:1.28rem; }
    .subtitle{ font-size:.9rem; margin-bottom:1rem; }
    .card{ padding:1.1rem 1.05rem; }

    .toolbar{ flex-direction:column; align-items:stretch; gap:.7rem; }
    .toolbar > *{ width:100%; min-width:0; }
    .toolbar .btn{ text-align:center; }

    form.form-padrao{ max-width:100%; }
    form.form-padrao fieldset{ min-width:0; padding:.9rem 1rem; }
    form.form-padrao .acoes-form{ flex-direction:column; }
    form.form-padrao .acoes-form .btn{ width:100%; text-align:center; }
    input, select, textarea{ font-size:16px; min-width:0; max-width:100%; }
    form.form-padrao div[style*="grid-template-columns"],
    .grade-2, .grade-3, .grade-4,
    [style*="grid-template-columns:repeat"], [style*="grid-template-columns: repeat"]{ grid-template-columns:1fr !important; }
    form.form-padrao div[style*="display:grid"] > div,
    form.form-padrao div[style*="display: grid"] > div{ min-width:0; }
    input[style*="min-width"], select[style*="min-width"]{ min-width:0 !important; width:100% !important; }

    table.lista, table.faixas, table.mix-grade{ display:block; overflow-x:auto; white-space:nowrap; -webkit-overflow-scrolling:touch; }
    .scrollx{ -webkit-overflow-scrolling:touch; max-width:100%; }
    ul.checklist-status li{ padding-left:1.35rem; }

    .kpi-row{ grid-template-columns:1fr 1fr; }
    .stat-tile{ padding:.9rem .95rem; border-bottom:1px solid var(--line); }
    .stat-tile:nth-child(2n){ border-right:none; }
    .stat-tile:nth-last-child(-n+2):nth-child(2n+1), .stat-tile:last-child{ border-bottom:none; }
    .stat-tile .stat-label{ white-space:normal; }
    .stat-tile .stat-value{ font-size:1.15rem; white-space:normal; overflow:visible; overflow-wrap:anywhere; }
    .secao{ margin-top:1.8rem; }

    .filial-row{ grid-template-columns:1fr auto; row-gap:.4rem; }
    .filial-row .filial-name{ grid-column:1; }
    .filial-row .pct-val{ grid-column:2; grid-row:1; }
    .filial-row .progress-track{ grid-column:1 / -1; grid-row:2; }
    .filial-row .status-badge{ grid-column:2; grid-row:2; justify-self:end; }
    .dist360{ grid-template-columns:1fr 1fr; }
    .corrida-strip .cs-grupos{ grid-template-columns:1fr; }

    .meter-row{ grid-template-columns:1fr auto; }
    .meter-row > *{ min-width:0; }
    .meter-row .meter-nome > span:first-child{ min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .meter-row .meter-nome .status-tag{ flex:none; }
    .rank-row{ grid-template-columns:5.4rem 1fr 4.4rem; gap:.5rem; font-size:.82rem; }
    .rank-row > *{ min-width:0; }
    .tier-row{ grid-template-columns:6.2rem 1fr 2rem; font-size:.82rem; }
    .tier-row > *{ min-width:0; }
    .pilares-bar, .oport-track, .gauge-track, .stat-bar-track, .meter-track, .rank-track{ max-width:100%; }

    nav.tabs-filial{ margin:0 -1rem 1.2rem; padding:0 1rem; }

    .mobile-bottomnav{
      display:flex; position:fixed; left:0; right:0; bottom:0; z-index:40;
      background:var(--surface); border-top:1px solid var(--line);
      padding-bottom:env(safe-area-inset-bottom);
      box-shadow:0 -2px 14px rgba(16,27,30,.07);
    }
    .mobile-bottomnav a, .mobile-bottomnav label{
      flex:1 1 0; min-width:0; display:flex; flex-direction:column; align-items:center; gap:.16rem;
      padding:.48rem .15rem .5rem; text-decoration:none; cursor:pointer;
      color:var(--ink-faint); font-size:.64rem; font-weight:600; line-height:1.15; text-align:center;
      -webkit-tap-highlight-color:transparent;
    }
    .mobile-bottomnav svg{ width:23px; height:23px; stroke:currentColor; fill:none; stroke-width:1.85; }
    .mobile-bottomnav a.active, .mobile-bottomnav label.active{ color:var(--primary); }
    .mobile-bottomnav .bn-label{ display:block; max-width:100%; overflow:hidden; text-overflow:ellipsis; }

    .mais-backdrop{
      display:block; position:fixed; inset:0; z-index:50; background:rgba(16,27,30,.42);
      opacity:0; pointer-events:none; transition:opacity .2s ease;
    }
    .mais-sheet{
      display:block; position:fixed; left:0; right:0; bottom:0; z-index:60;
      background:var(--surface); border-radius:var(--radius-lg) var(--radius-lg) 0 0;
      padding:.4rem 1.1rem calc(1.1rem + env(safe-area-inset-bottom));
      transform:translateY(115%); transition:transform .26s cubic-bezier(.22,1,.36,1);
      max-height:82vh; overflow-y:auto; box-shadow:0 -10px 34px rgba(16,27,30,.2);
    }
    .mais-toggle:checked ~ .mais-backdrop{ opacity:1; pointer-events:auto; }
    .mais-toggle:checked ~ .mais-sheet{ transform:translateY(0); }
    .mais-sheet .sheet-grip{ width:38px; height:4px; border-radius:999px; background:var(--line); margin:.45rem auto .7rem; }
    .mais-sheet .sheet-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem; }
    .mais-sheet .sheet-head strong{ font-size:1.02rem; }
    .mais-sheet .sheet-head label{ color:var(--ink-faint); font-size:.85rem; cursor:pointer; padding:.3rem; }
    .mais-sheet .sheet-pills{ display:flex; gap:.4rem; flex-wrap:wrap; margin:0 0 .8rem; }
    .mais-sheet .seletor-periodo{ display:block; margin:0 0 1rem; }
    .mais-sheet .seletor-periodo select{ width:100%; font-size:16px; padding:.6rem .7rem; border:1px solid var(--line); border-radius:var(--radius-sm); background:var(--surface); color:var(--ink); font-weight:600; font-family:var(--font); }
    .mais-sheet .sheet-links{ display:flex; flex-direction:column; }
    .mais-sheet .sheet-links a{
      display:flex; align-items:center; gap:.8rem; padding:.9rem .2rem;
      color:var(--ink); text-decoration:none; font-size:.96rem; font-weight:500;
      border-top:1px solid var(--line);
    }
    .mais-sheet .sheet-links a:first-child{ border-top:none; }
    .mais-sheet .sheet-links a.active{ color:var(--primary-ink); font-weight:700; }
    .mais-sheet .sheet-links a.sair{ color:var(--bad); }
    .mais-sheet .sheet-links svg{ width:20px; height:20px; stroke:currentColor; fill:none; stroke-width:1.85; flex:none; }

    :focus-visible{ outline:2px solid var(--primary); outline-offset:2px; }
  }
  @media (prefers-reduced-motion: reduce){
    .mais-sheet, .mais-backdrop{ transition:none; }
  }
</style>
</head>
<body>
<?php
/** Rota ativa? (aceita vários prefixos; '/' casa só com a raiz exata). */
$ehAtivo = static function (array $prefixos) use ($rota): bool {
    foreach ($prefixos as $p) {
        if ($p === '/' ? ($rota === '/') : str_starts_with($rota, $p)) {
            return true;
        }
    }
    return false;
};
/** Ícone do sprite SVG (definido logo abaixo). */
$ic = static fn (string $nome): string => '<svg aria-hidden="true"><use href="#ic-' . $nome . '"></use></svg>';

// Seletor de período reaproveitado na sidebar (desktop) e na folha "Mais" (mobile).
$renderSeletorPeriodo = static function (string $classe = 'seletor-periodo') use ($periodosDisponiveis, $periodoAtivo, $nomesMesCurto, $rotulosStatusPeriodo, $urlAtual): void {
    echo '<form method="post" action="/periodo/selecionar" class="' . htmlspecialchars($classe, ENT_QUOTES) . '">';
    echo Csrf::field();
    echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($urlAtual, ENT_QUOTES) . '">';
    echo '<select name="periodo" onchange="var p=this.value.split(\'-\'); this.form.ano.value=p[0]; this.form.mes.value=p[1]; this.form.submit()">';
    foreach ($periodosDisponiveis as $p) {
        $sel = ((int) $p['ano'] === (int) $periodoAtivo['ano'] && (int) $p['mes'] === (int) $periodoAtivo['mes']) ? ' selected' : '';
        $extra = $p['status'] !== 'aberto' ? ' · ' . $rotulosStatusPeriodo[$p['status']] : '';
        echo '<option value="' . (int) $p['ano'] . '-' . (int) $p['mes'] . '"' . $sel . '>'
            . htmlspecialchars($nomesMesCurto[(int) $p['mes']], ENT_QUOTES) . '/' . (int) $p['ano'] . htmlspecialchars($extra, ENT_QUOTES)
            . '</option>';
    }
    echo '</select>';
    echo '<input type="hidden" name="ano" value="' . (int) $periodoAtivo['ano'] . '">';
    echo '<input type="hidden" name="mes" value="' . (int) $periodoAtivo['mes'] . '">';
    echo '</form>';
};

// ---- Navegação da sidebar (desktop), por papel ---------------------------------
if ($papel === 'admin') {
    $navSidebar = [
        'Visão geral' => [
            ['/dashboard', 'Painel da rede', 'home', ['/', '/dashboard']],
            ['/metas', 'Metas', 'target', ['/metas']],
            ['/vendas', 'Vendas', 'cart', ['/vendas']],
            ['/indicadores', 'Qualidade', 'check', ['/indicadores']],
        ],
        'Fechamento' => [
            ['/fechamento', 'Fechamento', 'lock', ['/fechamento']],
            ['/relatorios', 'Relatórios', 'chart', ['/relatorios']],
        ],
        'Campanhas' => [
            ['/corrida', 'Corrida dos Campeões', 'trophy', ['/corrida']],
        ],
    ];
    $navRodape = [
        ['/config', 'Configuração', 'sliders', ['/config', '/filiais', '/usuarios', '/regras', '/categorias', '/parametros']],
        ['/ajuda', 'Ajuda', 'help', ['/ajuda']],
    ];
} elseif ($papel === 'gerente') {
    $navSidebar = [
        'Visão geral' => [
            ['/painel-filial', 'Painel da filial', 'home', ['/painel-filial']],
            ['/dashboard', 'Meu desempenho', 'user', ['/', '/dashboard']],
            ['/metas', 'Metas', 'target', ['/metas']],
            ['/vendas', 'Lançar vendas', 'cart', ['/vendas']],
            ['/indicadores', 'Qualidade', 'check', ['/indicadores']],
        ],
        'Fechamento' => [
            ['/fechamento', 'Fechamento', 'lock', ['/fechamento']],
            ['/relatorios', 'Relatórios', 'chart', ['/relatorios']],
        ],
        'Campanhas' => [
            ['/corrida', 'Corrida dos Campeões', 'trophy', ['/corrida']],
        ],
    ];
    $navRodape = [
        ['/ajuda', 'Ajuda', 'help', ['/ajuda']],
    ];
} else {
    $navSidebar = [
        'Visão geral' => [
            ['/dashboard', 'Meu painel', 'home', ['/', '/dashboard']],
            ['/minhas-vendas', 'Minhas vendas', 'cart', ['/minhas-vendas']],
            ['/minhas-metas', 'Minhas metas', 'target', ['/minhas-metas']],
        ],
        'Campanhas' => [
            ['/corrida', 'Corrida dos Campeões', 'trophy', ['/corrida']],
        ],
    ];
    $navRodape = [
        ['/ajuda', 'Ajuda', 'help', ['/ajuda']],
    ];
}

// ---- Navegação mobile por papel: [href, rótulo, ícone, [prefixos que marcam ativo]]
if ($papel === 'admin') {
    $navBottom = [
        ['/dashboard', 'Painel', 'home', ['/', '/dashboard']],
        ['/vendas', 'Vendas', 'cart', ['/vendas']],
        ['/indicadores', 'Qualidade', 'check', ['/indicadores']],
        ['/fechamento', 'Fechamento', 'lock', ['/fechamento']],
    ];
    $navMais = [
        ['/metas', 'Metas', 'target', ['/metas']],
        ['/relatorios', 'Relatórios', 'chart', ['/relatorios']],
        ['/corrida', 'Corrida dos Campeões', 'trophy', ['/corrida']],
        ['/config', 'Configuração', 'sliders', ['/config', '/filiais', '/usuarios', '/regras', '/categorias', '/parametros']],
        ['/ajuda', 'Ajuda', 'help', ['/ajuda']],
        ['/minha-conta', 'Minha conta', 'user', ['/minha-conta']],
    ];
} elseif ($papel === 'gerente') {
    $navBottom = [
        ['/painel-filial', 'Painel', 'home', ['/painel-filial']],
        ['/vendas', 'Vendas', 'cart', ['/vendas']],
        ['/indicadores', 'Qualidade', 'check', ['/indicadores']],
        ['/fechamento', 'Fechamento', 'lock', ['/fechamento']],
    ];
    $navMais = [
        ['/dashboard', 'Meu desempenho', 'user', ['/', '/dashboard']],
        ['/metas', 'Metas', 'target', ['/metas']],
        ['/relatorios', 'Relatórios', 'chart', ['/relatorios']],
        ['/corrida', 'Corrida dos Campeões', 'trophy', ['/corrida']],
        ['/ajuda', 'Ajuda', 'help', ['/ajuda']],
        ['/minha-conta', 'Minha conta', 'user', ['/minha-conta']],
    ];
} else {
    $navBottom = [
        ['/dashboard', 'Painel', 'home', ['/', '/dashboard']],
        ['/minhas-vendas', 'Vendas', 'cart', ['/minhas-vendas']],
        ['/minhas-metas', 'Metas', 'target', ['/minhas-metas']],
        ['/corrida', 'Corrida', 'trophy', ['/corrida']],
    ];
    $navMais = [
        ['/ajuda', 'Ajuda', 'help', ['/ajuda']],
        ['/minha-conta', 'Minha conta', 'user', ['/minha-conta']],
    ];
}
$maisAtivo = false;
foreach ($navMais as $m) {
    if ($ehAtivo($m[3])) { $maisAtivo = true; break; }
}

// Toggle Rede / Meu painel — só faz sentido pro gerente (tem os dois painéis).
$mostrarToggleGerente = $papel === 'gerente'
    && ($ehAtivo(['/painel-filial']) || $rota === '/' || str_starts_with($rota, '/dashboard'));

$inicial = mb_strtoupper(mb_substr($minhaConta['nome'] ?? '?', 0, 1));
?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
  <symbol id="ic-home" viewBox="0 0 24 24"><path d="M3 10.7 12 3l9 7.7"/><path d="M5.5 9.3V20a1 1 0 0 0 1 1H10v-6h4v6h3.5a1 1 0 0 0 1-1V9.3"/></symbol>
  <symbol id="ic-cart" viewBox="0 0 24 24"><path d="M2.5 3.5h2.2l2.3 11.2a1.6 1.6 0 0 0 1.6 1.3h8.8a1.6 1.6 0 0 0 1.6-1.3L21.5 7H6"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/></symbol>
  <symbol id="ic-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12.2 2.8 2.8L16.2 9"/></symbol>
  <symbol id="ic-lock" viewBox="0 0 24 24"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/><circle cx="12" cy="15.2" r="1.3"/></symbol>
  <symbol id="ic-target" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4"/></symbol>
  <symbol id="ic-chart" viewBox="0 0 24 24"><path d="M4 20h16"/><rect x="5.5" y="11" width="3.2" height="7" rx=".6"/><rect x="10.4" y="6" width="3.2" height="12" rx=".6"/><rect x="15.3" y="13.5" width="3.2" height="4.5" rx=".6"/></symbol>
  <symbol id="ic-trophy" viewBox="0 0 24 24"><path d="M8 4h8v4a4 4 0 0 1-8 0z"/><path d="M8 5H5.5a2.5 2.5 0 0 0 2.5 3M16 5h2.5a2.5 2.5 0 0 1-2.5 3"/><path d="M12 12v4M9.5 20h5M10.5 16h3"/></symbol>
  <symbol id="ic-sliders" viewBox="0 0 24 24"><path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/></symbol>
  <symbol id="ic-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 1 1 3.6 2.4c-.9.5-1.2 1-1.2 2"/><circle cx="12" cy="16.6" r="1.1" fill="currentColor" stroke="none"/></symbol>
  <symbol id="ic-user" viewBox="0 0 24 24"><circle cx="12" cy="8.5" r="3.8"/><path d="M5 20a7 7 0 0 1 14 0"/></symbol>
  <symbol id="ic-logout" viewBox="0 0 24 24"><path d="M9.5 20.5H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2h3.5"/><path d="m15.5 16.5 4.5-4.5-4.5-4.5"/><path d="M20 12H9.5"/></symbol>
  <symbol id="ic-dots" viewBox="0 0 24 24"><circle cx="5.5" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="18.5" cy="12" r="1.7" fill="currentColor" stroke="none"/></symbol>
  <symbol id="ic-calendar" viewBox="0 0 24 24"><rect x="4" y="5.5" width="16" height="15" rx="2"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/></symbol>
</defs></svg>

<!-- SIDEBAR (desktop) -->
<aside class="sidebar">
  <div class="sb-brand">
    <a href="<?= htmlspecialchars($navSidebar['Visão geral'][0][0], ENT_QUOTES) ?>">
      <div class="sb-brand-label">Comissão 360</div>
      <div class="sb-brand-sub">Farmácia Geremias</div>
    </a>
  </div>

  <?php if ($mostrarSeletorPeriodo): ?>
  <div class="sb-period">
    <div class="sb-period-label">Período ativo</div>
    <?php $renderSeletorPeriodo('sb-period-form'); ?>
    <?php if ($periodoAtivo['status'] !== 'aberto'): ?>
      <span class="sb-period-pill"><?= htmlspecialchars($rotulosStatusPeriodo[$periodoAtivo['status']], ENT_QUOTES) ?></span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <nav class="sb-nav">
    <?php foreach ($navSidebar as $secao => $itens): ?>
      <div class="sb-section"><?= htmlspecialchars($secao, ENT_QUOTES) ?></div>
      <?php foreach ($itens as [$href, $label, $icone, $prefixos]): ?>
        <a href="<?= htmlspecialchars($href, ENT_QUOTES) ?>" class="sb-item <?= $ehAtivo($prefixos) ? 'active' : '' ?>">
          <?= $ic($icone) ?><span><?= htmlspecialchars($label, ENT_QUOTES) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="sb-section">Ajustes</div>
    <?php foreach ($navRodape as [$href, $label, $icone, $prefixos]): ?>
      <a href="<?= htmlspecialchars($href, ENT_QUOTES) ?>" class="sb-item <?= $ehAtivo($prefixos) ? 'active' : '' ?>">
        <?= $ic($icone) ?><span><?= htmlspecialchars($label, ENT_QUOTES) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sb-footer">
    <a href="/minha-conta" class="sb-user">
      <?php if (!empty($minhaConta['avatar_path'])): ?>
        <img src="<?= htmlspecialchars($minhaConta['avatar_path'], ENT_QUOTES) ?>" alt="" class="avatar-mini">
      <?php else: ?>
        <span class="avatar-mini placeholder"><?= htmlspecialchars($inicial, ENT_QUOTES) ?></span>
      <?php endif; ?>
      <div>
        <div class="sb-user-name"><?= htmlspecialchars($minhaConta['nome'] ?? 'Minha conta', ENT_QUOTES) ?></div>
        <div class="sb-user-role"><?= htmlspecialchars($rotulosPapel[$papel] ?? $papel, ENT_QUOTES) ?></div>
      </div>
    </a>
    <a href="/logout" class="sb-logout"><?= $ic('logout') ?><span>Sair</span></a>
  </div>
</aside>

<!-- TOP-BAR (mobile) -->
<header class="mobile-topbar <?= $mostrarToggleGerente ? 'has-toggle' : '' ?>">
  <a href="<?= htmlspecialchars($navBottom[0][0], ENT_QUOTES) ?>" class="brand"><span class="brand-label">Comissão 360</span></a>
  <?php if ($mostrarSeletorPeriodo): ?>
    <label for="maismenu" class="tb-chip <?= $periodoAtivo['status'] !== 'aberto' ? 'status-' . htmlspecialchars($periodoAtivo['status'], ENT_QUOTES) : '' ?>">
      <?= $ic('calendar') ?><?= htmlspecialchars($nomesMesCurto[(int) $periodoAtivo['mes']], ENT_QUOTES) ?>/<?= (int) $periodoAtivo['ano'] ?>
    </label>
  <?php endif; ?>
  <?php if ($mostrarToggleGerente): ?>
    <nav class="view-tabs" style="flex-basis:100%">
      <a href="/painel-filial" class="<?= $ehAtivo(['/painel-filial']) ? 'active' : '' ?>">Rede</a>
      <a href="/dashboard" class="<?= ($rota === '/' || str_starts_with($rota, '/dashboard')) ? 'active' : '' ?>">Meu painel</a>
    </nav>
  <?php endif; ?>
</header>
<?php $mainWide = in_array($papel, ['funcionario', 'gerente'], true) && ($rota === '/dashboard' || $rota === '/'); ?>
<main class="<?= $mainWide ? 'main-wide' : '' ?>">
<?php if ($flash): ?>
  <div class="flash <?= htmlspecialchars($flash['tipo'], ENT_QUOTES) ?>"><?= htmlspecialchars($flash['mensagem'], ENT_QUOTES) ?></div>
<?php endif; ?>
<?= $content ?>
</main>

<input type="checkbox" id="maismenu" class="mais-toggle" hidden>

<nav class="mobile-bottomnav">
  <?php foreach ($navBottom as [$bHref, $bLabel, $bIcone, $bPrefixos]): ?>
    <a href="<?= htmlspecialchars($bHref, ENT_QUOTES) ?>" class="<?= $ehAtivo($bPrefixos) ? 'active' : '' ?>">
      <?= $ic($bIcone) ?><span class="bn-label"><?= htmlspecialchars($bLabel, ENT_QUOTES) ?></span>
    </a>
  <?php endforeach; ?>
  <label for="maismenu" class="<?= $maisAtivo ? 'active' : '' ?>">
    <?= $ic('dots') ?><span class="bn-label">Mais</span>
  </label>
</nav>

<label class="mais-backdrop" for="maismenu" aria-hidden="true"></label>
<div class="mais-sheet" role="dialog" aria-label="Mais opções">
  <span class="sheet-grip"></span>
  <div class="sheet-head">
    <strong>Mais</strong>
    <label for="maismenu">Fechar ✕</label>
  </div>
  <div class="sheet-pills">
    <span class="pill"><?= htmlspecialchars($rotulosPapel[$papel] ?? $papel, ENT_QUOTES) ?></span>
    <?php if ($periodoAtivo['status'] !== 'aberto'): ?>
      <span class="pill status-<?= htmlspecialchars($periodoAtivo['status'], ENT_QUOTES) ?>">Período <?= htmlspecialchars($rotulosStatusPeriodo[$periodoAtivo['status']], ENT_QUOTES) ?></span>
    <?php endif; ?>
  </div>
  <?php if ($mostrarSeletorPeriodo): ?>
    <?php $renderSeletorPeriodo('seletor-periodo'); ?>
  <?php endif; ?>
  <nav class="sheet-links">
    <?php foreach ($navMais as [$mHref, $mLabel, $mIcone, $mPrefixos]): ?>
      <a href="<?= htmlspecialchars($mHref, ENT_QUOTES) ?>" class="<?= $ehAtivo($mPrefixos) ? 'active' : '' ?>"><?= $ic($mIcone) ?><?= htmlspecialchars($mLabel, ENT_QUOTES) ?></a>
    <?php endforeach; ?>
    <a href="/logout" class="sair"><?= $ic('logout') ?>Sair</a>
  </nav>
</div>

</body>
</html>
