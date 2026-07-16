<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro Eleitor</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Coninfoms Eleição';
    $navLinks = [
      ['label' => 'Login Eleitor', 'href' => $baseUrl . '/login'],
      ['label' => 'Admin', 'href' => $baseUrl . '/admin/login'],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap auth-wrap">
    <section class="card auth-card">
      <h1>Registro de Eleitor</h1>
      <p class="muted">Preencha seus dados para aprovação no processo eleitoral da sua igreja.</p>
      
      <?php if (!$isOpen): ?>
        <div class="box">
          <div class="muted">Atenção</div>
          <div style="color:var(--danger); font-weight:bold; margin-top:5px;">O período de cadastro está encerrado no momento.</div>
        </div>
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">Voltar ao Login</a>
      <?php else: ?>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/register" class="form" id="registerForm">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

          <label>Igreja / Sociedade</label>
          <select name="church_id" required>
            <option value="">Selecione sua Igreja...</option>
            <?php foreach ($churches as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>

          <label>Nome Completo</label>
          <input name="name" required maxlength="160" placeholder="Digite seu nome completo">
          
          <label>CPF</label>
          <input name="cpf" id="cpfInput" inputmode="numeric" autocomplete="off" required maxlength="14" placeholder="000.000.000-00" data-cpf-input>
          
          <button type="submit">Cadastrar</button>
        </form>
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">Já possui cadastro? Entre aqui</a>

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

          document.getElementById('registerForm').addEventListener('submit', function(e) {
            const cpfInput = document.getElementById('cpfInput').value;
            if (!isValidCPF(cpfInput)) {
              e.preventDefault();
              alert('Por favor, informe um CPF válido.');
            }
          });
        </script>
      <?php endif; ?>
    </section>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>