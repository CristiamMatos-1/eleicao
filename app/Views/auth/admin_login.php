<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Administrativo</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Coninfoms Eleição';
    $navLinks = [
      ['label' => 'Login Eleitor', 'href' => $baseUrl . '/login'],
      ['label' => 'Cadastro', 'href' => $baseUrl . '/register'],
      ['label' => 'Apuração', 'href' => $baseUrl . '/dashboard.php'],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap auth-wrap">
    <section class="card auth-card">
      <h1>Acesso administrativo</h1>
      <p class="muted">Use as credenciais de administrador para entrar no painel da igreja.</p>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/login" class="form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" required>

        <label for="password">Senha</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Acessar Painel</button>
      </form>
      <div class="auth-links">
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">Voltar para login de eleitor</a>
      </div>
    </section>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>