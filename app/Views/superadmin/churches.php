<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin - Igrejas</title>
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
    $activePath = '/superadmin/churches';
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
<main class="wrap">
  <div class="toolbar">
    <div class="page-title">
      <h1>Gerenciar Igrejas</h1>
      <p class="muted" style="margin-top:2px;">Igrejas e sociedades cadastradas no sistema</p>
    </div>
    <div class="page-actions">
      <span class="pill pill--blue"><?= count($churches) ?> cadastrada(s)</span>
    </div>
  </div>

  <?php if (!empty($flashMsg)): ?>
    <div class="alert <?= !empty($flashType) && $flashType === 'success' ? 'alert--success' : 'alert--info' ?>">
      <?= htmlspecialchars((string)$flashMsg, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="cols-2" style="margin-bottom:18px;">
    <div class="card">
      <div class="card__header">
        <h3 style="margin:0;">Cadastrar Nova Igreja</h3>
        <span class="pill pill--teal">+ 1 Igreja</span>
      </div>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches/add" class="form-grid g-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-grid__cell form-grid__cell--2">
          <label>Nome da Igreja / Sociedade</label>
          <input name="name" required maxlength="255" placeholder="Ex: Igreja Central de Comunidade">
        </div>

        <div class="form-grid__cell">
          <label>E-mail do Administrador</label>
          <input name="admin_email" type="email" required maxlength="120" placeholder="admin@igreja.com">
        </div>

        <div class="form-grid__cell">
          <label>Senha do Administrador</label>
          <input name="admin_password" type="password" required minlength="6" placeholder="Mínimo 6 caracteres">
        </div>

        <div class="form-grid__cell form-grid__cell--2" style="display:flex; align-items:flex-end;">
          <button type="submit" class="btn btn--primary" style="width:100%;">Cadastrar Igreja e Admin</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card__header">
        <h3 style="margin:0;">Atalhos</h3>
        <span class="muted">Navegação rápida</span>
      </div>
      <div style="display:flex; flex-direction:column; gap:10px;">
        <a class="btn btn--secondary" style="text-align:center; justify-content:center;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">
          Painel Admin Geral
        </a>
        <a class="btn btn--secondary" style="text-align:center; justify-content:center;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/settings">
          Configurações Super Admin
        </a>
      </div>
      <div style="margin-top:18px; padding:14px 16px; border:1px solid var(--brand-border); border-radius:var(--radius-lg); background:var(--brand-surface);">
        <div style="font-weight:600; margin-bottom:4px; color:var(--brand-warn);">Dica</div>
        <div class="muted" style="font-size:13px;">
          Cada igreja cadastrada recebe automaticamente seu próprio usuário administrador, isolado dos demais.
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__header">
      <h3 style="margin:0;">Igrejas Cadastradas</h3>
      <span class="muted"><?= count($churches) ?> registro(s)</span>
    </div>

    <?php if (empty($churches)): ?>
      <div style="text-align:center; padding:36px 16px; color:var(--brand-muted);">
        Nenhuma igreja cadastrada. Use o formulário ao lado para começar.
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th style="width:5%; text-align:center;">ID</th>
              <th style="width:35%;">Nome</th>
              <th style="width:20%;">Slug</th>
              <th style="width:25%;">Admin</th>
              <th style="width:15%; text-align:center;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($churches as $c): ?>
              <tr>
                <td style="text-align:center; color:var(--brand-muted); font-variant-numeric: tabular-nums;"><?= (int)$c['id'] ?></td>
                <td style="font-weight:600;">
                  <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                  <?php if ((int)$c['id'] === 1): ?>
                    <span class="pill pill--amber" style="margin-left:8px; font-size:11px;">Principal</span>
                  <?php endif; ?>
                </td>
                <td style="font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:13px; color:var(--brand-muted);">
                  <?= htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                  <?php if (!empty($c['admin_email'])): ?>
                    <span style="font-weight:500;"><?= htmlspecialchars($c['admin_email'], ENT_QUOTES, 'UTF-8') ?></span>
                  <?php else: ?>
                    <span class="pill pill--gray">Sem admin</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;">
                  <div style="display:inline-flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                    <button
                      type="button"
                      class="btn btn--secondary btn--sm"
                      onclick="editChurch(<?= (int)$c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name']), ENT_QUOTES, 'UTF-8') ?>')"
                    >
                      Editar
                    </button>
                    <?php if ((int)$c['id'] !== 1): ?>
                      <button
                        type="button"
                        class="btn btn--sm"
                        style="background:var(--brand-danger); color:#fff; border-color:var(--brand-danger);"
                        onclick="deleteChurch(<?= (int)$c['id'] ?>)"
                      >
                        Excluir
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <form id="form_edit" method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches/edit" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="church_id" id="edit_church_id">
    <input type="hidden" name="name" id="edit_name">
  </form>

  <form id="form_del" method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches/delete" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="church_id" id="del_church_id">
  </form>

  <script>
    function editChurch(id, currentName) {
        var newName = prompt("Digite o novo nome da Igreja:", currentName);
        if (newName && newName.trim() !== "" && newName !== currentName) {
            document.getElementById('edit_church_id').value = id;
            document.getElementById('edit_name').value = newName;
            document.getElementById('form_edit').submit();
        }
    }
    function deleteChurch(id) {
        if (confirm("ATENÇÃO: Excluir esta igreja apagará TODAS as eleições, eleitores e votos vinculados a ela!\n\nDeseja realmente excluir?")) {
            document.getElementById('del_church_id').value = id;
            document.getElementById('form_del').submit();
        }
    }
  </script>
</main>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
