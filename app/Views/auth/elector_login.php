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
    $activePath = '/login';
    $brandLetter = 'C';
    $navLinks = [
      ['label' => 'Cadastro', 'href' => $baseUrl . '/register'],
      ['label' => 'Admin', 'href' => $baseUrl . '/admin/login'],
      ['label' => 'Apuração', 'href' => $baseUrl . '/dashboard.php', 'external' => false],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap auth-wrap">
    <div class="auth-card" style="width:100%;">

      <section class="hero-subtle" style="margin-bottom:14px;">
        <h1 style="margin:0 0 4px;">Entrada do eleitor</h1>
        <p style="margin:0;">Informe seu CPF para acessar a cédula da assembleia disponível para sua igreja.</p>
      </section>

      <section class="card card--tinted">
        <?php
        $render = function () use ($csrf, $baseUrl): void { ?>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" class="form" id="loginForm" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
              <label for="cpfInput">CPF</label>
              <input name="cpf" id="cpfInput" inputmode="numeric" autocomplete="off" required maxlength="14" placeholder="000.000.000-00" data-cpf-input>
              <span class="hint">Somente números serão enviados ao servidor.</span>
            </div>
            <button type="submit" class="btn-block btn-lg">Entrar na assembleia</button>
          </form>
        <?php };
        $render();
        ?>
        <div class="auth-divider">ou continue com</div>
        <div class="auth-links">
          <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/register">Não tem cadastro? Registre-se</a>
          <a class="link-muted" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php">Acompanhar apuração pública</a>
        </div>
      </section>

      <div class="footer-mini">© <?= date('Y') ?> Coninfoms Eleição. Segurança e transparência.</div>
    </div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
  <script>
    (function(){
      function isValidCPF(cpf) {
        cpf = cpf.replace(/[^\d]+/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        var sum = 0, rest, i;
        for (i = 1; i <= 9; i++) sum += parseInt(cpf.substring(i - 1, i), 10) * (11 - i);
        rest = (sum * 10) % 11;
        if (rest === 10 || rest === 11) rest = 0;
        if (rest !== parseInt(cpf.substring(9, 10), 10)) return false;
        sum = 0;
        for (i = 1; i <= 10; i++) sum += parseInt(cpf.substring(i - 1, i), 10) * (12 - i);
        rest = (sum * 10) % 11;
        if (rest === 10 || rest === 11) rest = 0;
        return rest === parseInt(cpf.substring(10, 11), 10);
      }
      var form = document.getElementById('loginForm');
      form && form.addEventListener('submit', function (e) {
        var cpfInput = document.getElementById('cpfInput').value;
        if (!isValidCPF(cpfInput)) {
          e.preventDefault();
          alert('Por favor, informe um CPF válido.');
        }
      });
    })();
  </script>
</body>
</html>
