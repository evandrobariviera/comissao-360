<?php
/** @var string|null $erro */
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar · Comissão 360</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{--bg:#F2F5F2; --surface:#fff; --ink:#141A14; --ink-soft:#4E5E52; --line:#DDE4DE; --primary:#1A7A4A; --primary-ink:#124F31; --bad:#B83232;}
  *{box-sizing:border-box}
  body{margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--bg); font-family:'Inter',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; color:var(--ink);}
  form{background:var(--surface); border:1px solid var(--line); border-radius:12px; padding:2.2rem 2rem; width:100%; max-width:340px; box-shadow:0 6px 20px rgba(16,27,30,.08);}
  .login-logo{display:block; width:100%; max-width:220px; height:auto; margin:0 auto 1.2rem;}
  h1{font-family:Georgia,serif; font-size:1.3rem; margin:0 0 .2rem; text-align:center;}
  p.sub{color:var(--ink-soft); font-size:.85rem; margin:0 0 1.4rem; text-align:center;}
  label{display:block; font-size:.82rem; color:var(--ink-soft); margin:.9rem 0 .3rem;}
  input{width:100%; padding:.6rem .7rem; border:1px solid var(--line); border-radius:8px; font-size:.95rem;}
  input:focus{outline:2px solid var(--primary); outline-offset:1px; border-color:var(--primary);}
  button{width:100%; margin-top:1.4rem; padding:.7rem; border:none; border-radius:8px; background:var(--primary); color:#fff; font-weight:600; font-size:.95rem; cursor:pointer;}
  button:hover{background:var(--primary-ink);}
  .erro{margin-top:1rem; padding:.6rem .8rem; background:#F3E4CE; border-left:3px solid var(--bad); border-radius:0 8px 8px 0; font-size:.85rem; color:var(--bad);}
</style>
</head>
<body>
<form method="post" action="/login" novalidate>
  <img src="/assets/img/logo.jpg" alt="Farmácia Geremias" class="login-logo">
  <h1>Comissão 360</h1>
  <p class="sub">Farmácia Geremias</p>
  <?php if (!empty($erro)): ?>
    <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES) ?></div>
  <?php endif; ?>
  <label for="email">E-mail</label>
  <input type="email" id="email" name="email" required autofocus>
  <label for="senha">Senha</label>
  <input type="password" id="senha" name="senha" required>
  <?= \App\Core\Csrf::field() ?>
  <button type="submit">Entrar</button>
</form>
</body>
</html>
