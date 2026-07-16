<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin - Igrejas</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
  <style>
    /* Usando as classes do dashboard.css para manter consistência */
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
      <h2>Gerenciar Igrejas (Super Admin)</h2>
      
          <div style="margin-bottom:30px;" class="box">
            <h3>Cadastrar Nova Igreja</h3>
            <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches/add" class="form">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              
              <label>Nome da Igreja / Sociedade</label>
              <input name="name" required maxlength="255" placeholder="Ex: Igreja Central">
              
              <label>E-mail do Administrador da Igreja</label>
              <input name="admin_email" type="email" required maxlength="120" placeholder="admin@igreja.com">
              
              <label>Senha do Administrador</label>
              <input name="admin_password" type="password" required minlength="6" placeholder="Defina uma senha de acesso">

              <button type="submit" style="margin-top: 10px;">Cadastrar Igreja e Admin</button>
            </form>
          </div>

      <h3>Igrejas Cadastradas</h3>
      <?php if (empty($churches)): ?>
        <div class="muted">Nenhuma igreja cadastrada.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($churches as $c): ?>
            <div class="itemRow">
              <div style="flex-grow: 1;">
                <div class="big"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted" style="margin-top: 5px;">
                  ID: <?= (int)$c['id'] ?> | Slug: <?= htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8') ?><br>
                  Admin: <strong><?= htmlspecialchars($c['admin_email'] ?? 'Nenhum admin', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
              </div>
              <div style="display:flex; gap:10px; flex-wrap: wrap;">
                <button type="button" onclick="editChurch(<?= (int)$c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name']), ENT_QUOTES, 'UTF-8') ?>')" style="background:var(--warn); color:#000; padding:8px 12px; font-size:14px; border:none; border-radius:8px; cursor:pointer;">Editar</button>
                <?php if ((int)$c['id'] !== 1): ?>
                  <button type="button" onclick="deleteChurch(<?= (int)$c['id'] ?>)" style="background:var(--danger); color:#fff; padding:8px 12px; font-size:14px; border:none; border-radius:8px; cursor:pointer;">Excluir</button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Formulários ocultos para JS -->
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
            let newName = prompt("Digite o novo nome da Igreja:", currentName);
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

      <div style="margin-top:30px; text-align:center; display:flex; justify-content:center; gap:15px; flex-wrap: wrap;">
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" style="padding:10px 20px; border:1px solid var(--accent); border-radius:8px;">Voltar ao Painel Admin</a>
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/settings" style="padding:10px 20px; border:1px solid var(--warn); color:var(--warn); border-radius:8px;">Configurações Super Admin</a>
      </div>
    </section>
  </main>
</div>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
