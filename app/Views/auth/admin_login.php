<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Admin</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <main class="wrap">
    <section class="card">
      <h1>Área Administrativa</h1>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/login" class="form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <label>E-mail</label>
        <input name="email" type="email" required>

        <label>Senha</label>
        <input name="password" type="password" required>

        <button type="submit">Acessar Painel</button>
      </form>
      <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">Voltar</a>
    </section>
  </main>
</body>
</html>