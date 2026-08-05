<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Minha Conta</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Minha Conta';
    $navLinks = [
      ['label' => 'Painel', 'href' => $baseUrl . '/admin'],
      ['label' => 'Igrejas', 'href' => $baseUrl . '/superadmin/churches'],
    ];
    ob_start();
  ?>
  <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="secondary">Sair</button>
  </form>
  <?php
    $navActions = (string)ob_get_clean();
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
  <main class="wrap">
    <section class="card">
      <h1>Minha Conta</h1>
      <?php if (isset($_GET['updated'])): ?>
        <div class="pill">Perfil atualizado com sucesso.</div>
      <?php endif; ?>

      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/profile" class="form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <label>Nome</label>
        <input name="name" value="<?= htmlspecialchars((string)($me['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>

        <label>E-mail</label>
        <input name="email" type="email" value="<?= htmlspecialchars((string)($me['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Nova senha</label>
        <input name="password" type="password" minlength="6" placeholder="Deixe em branco para manter">

        <button type="submit">Salvar</button>
      </form>
    </section>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
