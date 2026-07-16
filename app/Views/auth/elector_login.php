<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login do Eleitor</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Coninfoms Eleição';
    $navLinks = [
      ['label' => 'Cadastro', 'href' => $baseUrl . '/register'],
      ['label' => 'Admin', 'href' => $baseUrl . '/admin/login'],
      ['label' => 'Apuração', 'href' => $baseUrl . '/dashboard.php'],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap auth-wrap">
    <section class="card auth-card">
      <h1>Entrada do eleitor</h1>
      <p class="muted">Informe seu CPF para acessar a cédula da eleição disponível para sua igreja.</p>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" class="form" id="loginForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label for="cpfInput">CPF</label>
        <input name="cpf" id="cpfInput" inputmode="numeric" autocomplete="off" required maxlength="14" placeholder="000.000.000-00" data-cpf-input>
        <button type="submit">Entrar</button>
      </form>
      <div class="auth-links">
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/register">Não tem cadastro? Registre-se</a>
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php">Acompanhar apuração pública</a>
      </div>
    </section>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
  <script>
    function isValidCPF(cpf) {
      cpf = cpf.replace(/[^\d]+/g, '');
      if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
      let sum = 0;
      for (let i = 1; i <= 9; i++) sum += parseInt(cpf.substring(i - 1, i), 10) * (11 - i);
      let rest = (sum * 10) % 11;
      if (rest === 10 || rest === 11) rest = 0;
      if (rest !== parseInt(cpf.substring(9, 10), 10)) return false;
      sum = 0;
      for (let i = 1; i <= 10; i++) sum += parseInt(cpf.substring(i - 1, i), 10) * (12 - i);
      rest = (sum * 10) % 11;
      if (rest === 10 || rest === 11) rest = 0;
      return rest === parseInt(cpf.substring(10, 11), 10);
    }

    document.getElementById('loginForm').addEventListener('submit', function (e) {
      const cpfInput = document.getElementById('cpfInput').value;
      if (!isValidCPF(cpfInput)) {
        e.preventDefault();
        alert('Por favor, informe um CPF válido.');
      }
    });
  </script>
</body>
</html>