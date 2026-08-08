<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin - Configurações</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Super Admin';
    $navLinks = [
      ['label' => 'Igrejas', 'href' => $baseUrl . '/superadmin/churches'],
      ['label' => 'Configurações', 'href' => $baseUrl . '/superadmin/settings'],
      ['label' => 'Admin', 'href' => $baseUrl . '/admin'],
    ];
    $activePath = '/superadmin/settings';
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
<main class="wrap">
  <div class="toolbar">
    <div class="page-title">
      <h1>Configurações do Super Admin</h1>
      <p class="muted" style="margin-top:2px;">Perfil e gerenciamento de usuários do nível raiz</p>
    </div>
    <div class="page-actions">
      <span class="pill pill--teal"><?= count($superAdmins) ?> super admin(s)</span>
    </div>
  </div>

  <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert--success">Seus dados foram atualizados com sucesso!</div>
  <?php endif; ?>
  <?php if (isset($_GET['added'])): ?>
    <div class="alert alert--success">Novo Super Admin cadastrado com sucesso!</div>
  <?php endif; ?>

  <div class="cols-2" style="margin-bottom:18px;">
    <div class="card">
      <div class="card__header">
        <h3 style="margin:0;">Meu Perfil</h3>
        <span class="pill pill--blue">Você</span>
      </div>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/settings/update" class="form-grid g-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-grid__cell form-grid__cell--2">
          <label>Nome Completo</label>
          <input name="name" required maxlength="160" value="<?= htmlspecialchars($me['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-grid__cell form-grid__cell--2">
          <label>E-mail (Acesso)</label>
          <input name="email" type="email" required maxlength="190" value="<?= htmlspecialchars($me['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-grid__cell form-grid__cell--2">
          <label>Nova Senha <span class="muted" style="font-weight:400;">(deixe em branco para não alterar)</span></label>
          <input name="password" type="password" minlength="6" placeholder="Digite apenas se quiser alterar a senha">
        </div>

        <div class="form-grid__cell form-grid__cell--2" style="display:flex; align-items:flex-end;">
          <button type="submit" class="btn btn--primary" style="width:100%;">Salvar Alterações</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card__header">
        <h3 style="margin:0;">Cadastrar Novo Super Admin</h3>
        <span class="pill pill--amber">+ 1 Usuário</span>
      </div>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/users/add" class="form-grid g-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-grid__cell form-grid__cell--2">
          <label>Nome</label>
          <input name="name" required maxlength="160" placeholder="Nome completo do novo Super Admin">
        </div>

        <div class="form-grid__cell form-grid__cell--2">
          <label>E-mail</label>
          <input name="email" type="email" required maxlength="190" placeholder="email@dominio.com">
        </div>

        <div class="form-grid__cell form-grid__cell--2">
          <label>Senha</label>
          <input name="password" type="password" required minlength="6" placeholder="Defina uma senha segura (mínimo 6 caracteres)">
        </div>

        <div class="form-grid__cell form-grid__cell--2" style="display:flex; align-items:flex-end;">
          <button type="submit" class="btn btn--primary" style="width:100%;">Cadastrar Super Admin</button>
        </div>
      </form>

      <div style="margin-top:18px;">
        <a class="btn btn--secondary" style="width:100%; text-align:center; justify-content:center;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches">
          Voltar ao Gerenciador de Igrejas
        </a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__header">
      <h3 style="margin:0;">Super Admins do Sistema</h3>
      <span class="muted">Acesso raiz a todas as igrejas</span>
    </div>

    <?php if (empty($superAdmins)): ?>
      <div style="text-align:center; padding:36px 16px; color:var(--brand-muted);">
        Nenhum outro Super Admin cadastrado.
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th style="width:10%; text-align:center;">ID</th>
              <th style="width:45%;">Nome</th>
              <th style="width:35%;">E-mail</th>
              <th style="width:10%; text-align:center;">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($superAdmins as $u): ?>
              <?php $isMe = ((int)($u['id'] ?? 0) === (int)($userId ?? 0)); ?>
              <tr>
                <td style="text-align:center; color:var(--brand-muted); font-variant-numeric: tabular-nums;"><?= (int)$u['id'] ?></td>
                <td style="font-weight:600;">
                  <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                  <?php if ($isMe): ?>
                    <span class="pill pill--green" style="margin-left:8px; font-size:11px;">Você</span>
                  <?php endif; ?>
                </td>
                <td style="font-weight:500;"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="text-align:center;">
                  <?php if ($isMe): ?>
                    <span class="pill pill--green">Ativo</span>
                  <?php else: ?>
                    <span class="pill pill--gray">Ativo</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
