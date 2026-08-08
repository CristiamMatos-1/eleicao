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
    $activePath = '/admin/login';
    $brandLetter = 'C';
    $brandMarkClass = 'brand-mark brand-mark--alt';
    $navLinks = [
      ['label' => 'Login Eleitor', 'href' => $baseUrl . '/login'],
      ['label' => 'Cadastro', 'href' => $baseUrl . '/register'],
      ['label' => 'Apuração', 'href' => $baseUrl . '/dashboard.php', 'external' => false],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap auth-wrap">
    <div class="auth-card" style="width:100%;">
      <section class="hero-subtle" style="margin-bottom:14px;">
        <h1 style="margin:0 0 4px;">Acesso administrativo</h1>
        <p style="margin:0;">Use as credenciais de <strong>Administrador / Condutor</strong> para entrar no painel da igreja.</p>
      </section>

      <section class="card card--tinted">
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/login" class="form" novalidate>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

          <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" autocomplete="email" required placeholder="voce@suaigreja.com.br">
          </div>

          <div class="field">
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••">
          </div>

          <button type="submit" class="btn-block btn-lg">Acessar Painel</button>
        </form>

        <div class="auth-divider">outras opções</div>
        <div class="auth-links">
          <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">Entrar como eleitor (CPF)</a>
        </div>
      </section>

      <div class="footer-mini">© <?= date('Y') ?> Coninfoms Eleição. Acesso protegido por credencial.</div>
    </div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
