<?php
$baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aviso do Sistema</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <main class="wrap" style="display:flex; justify-content:center; align-items:center; min-height:100vh; padding:24px 16px;">
    <section class="card" style="max-width:520px; width:100%;">
      <div style="display:flex; flex-direction:column; align-items:center; text-align:center; padding:12px 4px 6px 4px;">
        <div class="candidate-photo" style="width:72px; height:72px; background:var(--brand-soft); color:var(--brand-primary); border:2px solid var(--brand-primary); font-weight:800; font-size:28px; margin-bottom:16px;">
          !
        </div>
        <h2 style="margin:0 0 8px 0; color:var(--brand-text);">Aviso do Sistema</h2>
      </div>

      <div class="alert alert--info" style="margin:8px 0 22px 0; padding:18px 16px; text-align:center;">
        <div style="font-weight:500; color:var(--brand-text); font-size:15px; line-height:1.55;">
          <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>

      <div style="display:flex; flex-direction:column; gap:10px;">
        <a
          class="btn btn--primary btn--lg"
          style="justify-content:center; text-align:center;"
          href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php"
        >
          Acompanhar Apuração ao Vivo
        </a>
        <a
          class="btn btn--secondary"
          style="justify-content:center; text-align:center;"
          href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login"
        >
          Voltar para a Página de Login
        </a>
      </div>
    </section>
  </main>
</body>
</html>
