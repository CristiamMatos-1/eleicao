<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Eleitor</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <main class="wrap">
    <section class="card">
      <h1>Entrar (Eleitor)</h1>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" class="form" id="loginForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <label>CPF</label>
        <input name="cpf" id="cpfInput" inputmode="numeric" autocomplete="off" required maxlength="14" placeholder="Somente números">
        <button type="submit">Entrar</button>
      </form>
      <div style="margin-top:15px; display:flex; flex-direction:column; gap:8px;">
        <a class="link" style="margin-top:0" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php">Acompanhar Apuração em Tempo Real</a>
        <a class="link" style="margin-top:0" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/register">Não tem cadastro? Registre-se aqui</a>
        <a class="link" style="margin-top:0" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/login">Área Administrativa</a>
      </div>

      <script>
        function isValidCPF(cpf) {
          cpf = cpf.replace(/[^\d]+/g, '');
          if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
          let sum = 0, rest;
          for (let i = 1; i <= 9; i++) sum = sum + parseInt(cpf.substring(i-1, i)) * (11 - i);
          rest = (sum * 10) % 11;
          if ((rest === 10) || (rest === 11)) rest = 0;
          if (rest !== parseInt(cpf.substring(9, 10))) return false;
          sum = 0;
          for (let i = 1; i <= 10; i++) sum = sum + parseInt(cpf.substring(i-1, i)) * (12 - i);
          rest = (sum * 10) % 11;
          if ((rest === 10) || (rest === 11)) rest = 0;
          if (rest !== parseInt(cpf.substring(10, 11))) return false;
          return true;
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
          const cpfInput = document.getElementById('cpfInput').value;
          if (!isValidCPF(cpfInput)) {
            e.preventDefault();
            alert('Por favor, informe um CPF válido.');
          }
        });
      </script>
    </section>
  </main>
</body>
</html>