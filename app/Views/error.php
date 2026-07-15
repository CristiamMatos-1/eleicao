<?php
// Tenta buscar a baseUrl injetada via App.php, caso contrário faz fallback dinâmico seguro
$baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/');
if (empty($baseUrl)) {
    $baseUrl = '/voto'; // fallback caso seja chamado de forma muito isolada
}
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
  <main class="wrap" style="display:flex; justify-content:center; align-items:center; min-height:100vh;">
    <section class="card" style="max-width:500px; width:100%; text-align:center;">
      <h2 style="color: var(--danger); margin-bottom: 20px;">Aviso do Sistema</h2>
      
      <div class="box" style="margin-bottom: 24px; padding: 24px; background: #FFFFFF; border: 1px solid var(--border);">
        <p class="big" style="margin: 0; color: var(--text);">
          <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
      </div>

      <div style="display:flex; flex-direction:column; gap:12px;">
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php" style="padding: 14px 20px; background: var(--accent); color: #FFF; text-decoration: none; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);">
          Acompanhar Apuração em Tempo Real
        </a>
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" style="padding: 14px 20px; background: #F8F9FA; color: #495057; text-decoration: none; border-radius: 12px; font-weight: 600; border: 1px solid #DEE2E6;">
          Voltar para a Página de Login
        </a>
      </div>
    </section>
  </main>
</body>
</html>