<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin - Configurações</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
  <style>
    /* Usando as classes do dashboard.css para manter consistência */
    .success-msg { background: rgba(40, 199, 111, 0.12); color: var(--ok); border: 1px solid rgba(40, 199, 111, 0.4); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
  </style>
</head>
<body>
  <?php
    $navTitle = 'Super Admin';
    $navLinks = [
      ['label' => 'Igrejas', 'href' => $baseUrl . '/superadmin/churches'],
      ['label' => 'Configurações', 'href' => $baseUrl . '/superadmin/settings'],
      ['label' => 'Admin', 'href' => $baseUrl . '/admin'],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
<div class="wrap">
  <main>
    <section class="card">
      <h2>Configurações do Super Admin</h2>

      <?php if (isset($_GET['updated'])): ?>
        <div class="success-msg">Seus dados foram atualizados com sucesso!</div>
      <?php endif; ?>
      <?php if (isset($_GET['added'])): ?>
        <div class="success-msg">Novo Super Admin cadastrado com sucesso!</div>
      <?php endif; ?>
      
      <div class="box" style="margin-bottom:30px;">
        <h3>Meu Perfil</h3>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/settings/update" class="form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          
          <label>Nome Completo</label>
          <input name="name" required maxlength="160" value="<?= htmlspecialchars($me['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          
          <label>E-mail (Acesso)</label>
          <input name="email" type="email" required maxlength="190" value="<?= htmlspecialchars($me['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          
          <label>Nova Senha (deixe em branco para não alterar)</label>
          <input name="password" type="password" minlength="6" placeholder="Digite apenas se quiser alterar">

          <button type="submit" style="margin-top: 10px;">Salvar Alterações</button>
        </form>
      </div>

      <div class="box" style="margin-bottom:30px;">
        <h3>Cadastrar Novo Super Admin</h3>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/users/add" class="form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          
          <label>Nome</label>
          <input name="name" required maxlength="160" placeholder="Nome do novo Super Admin">
          
          <label>E-mail</label>
          <input name="email" type="email" required maxlength="190" placeholder="email@dominio.com">
          
          <label>Senha</label>
          <input name="password" type="password" required minlength="6" placeholder="Defina uma senha segura">

          <button type="submit" style="margin-top: 10px; background: var(--warn); color:#000; border:none; box-shadow:none;">Cadastrar Super Admin</button>
        </form>
      </div>

      <h3>Super Admins do Sistema</h3>
      <?php if (empty($superAdmins)): ?>
        <div class="muted">Nenhum outro Super Admin cadastrado.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($superAdmins as $u): ?>
            <div class="itemRow">
              <div>
                <div class="big"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted">
                  ID: <?= (int)$u['id'] ?> | E-mail: <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>
                  <?php if ($u['id'] == ($userId ?? 0)): ?>
                    <span style="color:#2ecc71; margin-left:10px; font-weight:bold;">(Você)</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div style="margin-top:30px; text-align:center; display:flex; justify-content:center;">
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches" style="padding:10px 20px; border:1px solid var(--accent); border-radius:8px; display:inline-block;">Voltar ao Gerenciador de Igrejas</a>
      </div>
    </section>
  </main>
</div>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>