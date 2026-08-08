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
    $activePath = '/register';
    $brandLetter = 'C';
    $navLinks = [
      ['label' => 'Login Eleitor', 'href' => $baseUrl . '/login'],
      ['label' => 'Admin', 'href' => $baseUrl . '/admin/login'],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap auth-wrap">
    <div class="auth-card" style="width:100%;">

      <section class="hero-subtle" style="margin-bottom:14px;">
        <h1 style="margin:0 0 4px;">Registro de Eleitor</h1>
        <p style="margin:0;">Preencha seus dados para aprovação no processo eleitoral da sua igreja.</p>
      </section>

      <section class="card card--tinted">
        <?php if (!$isOpen): ?>
          <div class="alert alert--warn" style="margin-top:0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            <div>
              <strong>Atenção</strong>
              O período de cadastro está encerrado no momento.
              <div style="margin-top:4px;"><a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">← Voltar para o login</a></div>
            </div>
          </div>
        <?php else: ?>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/register" class="form" id="registerForm" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
              <label>Igreja / Sociedade</label>
              <select name="church_id" required>
                <option value="">Selecione sua Igreja...</option>
                <?php foreach ($churches as $c): ?>
                  <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Nome Completo</label>
              <input name="name" required maxlength="160" placeholder="Digite seu nome completo">
            </div>

            <div class="field">
              <label>CPF</label>
              <input name="cpf" id="cpfInput" inputmode="numeric" autocomplete="off" required maxlength="14" placeholder="000.000.000-00" data-cpf-input>
              <span class="hint">Seu CPF é validado por algoritmo e sigilizado no armazenamento.</span>
            </div>

            <button type="submit" class="btn-block btn-lg">Cadastrar meu perfil</button>
          </form>

          <div class="auth-divider">já sou cadastrado</div>
          <div class="auth-links">
            <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">Entrar com meu CPF</a>
          </div>

          <script>
            (function(){
              function isValidCPF(cpf) {
                cpf = cpf.replace(/[^\d]+/g, '');
                if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
                var sum = 0, rest, i;
                for (i = 1; i <= 9; i++) sum = sum + parseInt(cpf.substring(i-1, i), 10) * (11 - i);
                rest = (sum * 10) % 11;
                if ((rest === 10) || (rest === 11)) rest = 0;
                if (rest !== parseInt(cpf.substring(9, 10), 10)) return false;
                sum = 0;
                for (i = 1; i <= 10; i++) sum = sum + parseInt(cpf.substring(i-1, i), 10) * (12 - i);
                rest = (sum * 10) % 11;
                if ((rest === 10) || (rest === 11)) rest = 0;
                return rest === parseInt(cpf.substring(10, 11), 10);
              }
              var form = document.getElementById('registerForm');
              form && form.addEventListener('submit', function(e) {
                var cpfInput = document.getElementById('cpfInput').value;
                if (!isValidCPF(cpfInput)) {
                  e.preventDefault();
                  alert('Por favor, informe um CPF válido.');
                }
              });
            })();
          </script>
        <?php endif; ?>
      </section>

      <div class="footer-mini">© <?= date('Y') ?> Coninfoms Eleição. Seguro, privado e auditável.</div>
    </div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
