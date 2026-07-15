<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <main class="wrap">
    <section class="card">
      <div class="row">
        <h1>Admin</h1>
        <div style="display:flex; gap:10px; align-items:center;">
          <?php if (($_SESSION[\App\Core\Auth::SESS_ROLE] ?? '') === 'SUPER_ADMIN'): ?>
            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches" class="secondary" style="padding: 6px 12px; text-decoration: none; border-radius: 8px; border: 1px solid var(--accent); color: var(--accent);">Gerenciar Igrejas</a>
          <?php endif; ?>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="secondary">Sair</button>
          </form>
        </div>
      </div>

      <div class="box">
        <div class="row">
          <div>
            <div class="muted">Cadastros</div>
            <div class="big"><?= $registration_open ? 'Abertos' : 'Fechados' ?></div>
          </div>
          <div class="row">
            <?php if (!$registration_open): ?>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/registrations/open">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Abrir</button>
              </form>
            <?php else: ?>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/registrations/close">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="secondary">Fechar</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <h2>Eleições</h2>
      <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new">Criar nova eleição</a>

      <?php if (empty($elections)): ?>
        <div class="muted">Nenhuma eleição cadastrada.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($elections as $e): ?>
            <div class="itemRow row">
              <div>
                <div class="big"><?= htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted">
                  <?= htmlspecialchars($e['type'], ENT_QUOTES, 'UTF-8') ?>
                  · <?= htmlspecialchars((string)$e['election_date'], ENT_QUOTES, 'UTF-8') ?>
                  · Esperados: <?= (int)$e['expected_voters'] ?>
                  <?php if (!empty($e['vacancies'])): ?>
                    · Vagas: <?= (int)$e['vacancies'] ?>
                  <?php endif; ?>
                </div>
                <div class="muted" style="word-break:break-all">
                  /dashboard.php?key=<?= htmlspecialchars($e['public_key'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                <div class="pill"><?= htmlspecialchars($e['status'], ENT_QUOTES, 'UTF-8') ?></div>
                <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/manage?id=<?= (int)$e['id'] ?>">Gerenciar</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2>Gerenciar Eleitores</h2>
      <div class="box">
        <h3>Adicionar Eleitor Manualmente</h3>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/add" class="form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <div class="row" style="align-items:flex-end;">
            <div style="flex:1;">
              <label>Nome Completo</label>
              <input name="name" required maxlength="160">
            </div>
            <div style="flex:1;">
              <label>CPF</label>
              <input name="cpf" inputmode="numeric" required maxlength="14" placeholder="Somente números">
            </div>
            <button type="submit" style="margin-bottom:2px;">Cadastrar</button>
          </div>
        </form>
      </div>

      <?php if (!empty($approvedElectors)): ?>
        <h3 style="margin-top:16px;">Eleitores Aprovados</h3>
        <div class="list">
          <?php foreach ($approvedElectors as $u): ?>
            <div class="row itemRow">
              <div>
                <div class="big"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted">CPF: <?= htmlspecialchars((string)$u['cpf'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/delete" onsubmit="return confirm('Deseja realmente excluir este eleitor?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="secondary" style="color:var(--danger); border-color:rgba(255,77,79,0.3);">Excluir</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <hr style="border:0; border-top:1px solid var(--border); margin:30px 0;">

      <h2>Gerenciar Usuários (Admin / Condutor)</h2>
      <div class="box">
        <h3>Adicionar Novo Usuário</h3>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/add" class="form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          
          <label>Nome Completo</label>
          <input name="name" required maxlength="160">
          
          <label>E-mail</label>
          <input name="email" type="email" required maxlength="190">
          
          <div class="row" style="align-items:flex-end;">
            <div style="flex:1;">
              <label>Senha</label>
              <input name="password" type="password" required>
            </div>
            <div style="flex:1;">
              <label>Papel</label>
              <select name="role" required>
                <option value="ADMIN">Administrador</option>
                <option value="CONDUTOR">Condutor</option>
              </select>
            </div>
            <button type="submit" style="margin-bottom:2px;">Cadastrar</button>
          </div>
        </form>
      </div>

      <?php if (!empty($systemUsers)): ?>
        <h3 style="margin-top:16px;">Usuários do Sistema</h3>
        <div class="list">
          <?php foreach ($systemUsers as $u): ?>
            <div class="row itemRow">
              <div>
                <div class="big"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted">
                  E-mail: <?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?>
                  <span class="pill" style="margin:0 0 0 10px; padding:2px 8px; font-size:10px;"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              </div>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/delete" onsubmit="return confirm('Deseja realmente excluir este usuário?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="secondary" style="color:var(--danger); border-color:rgba(255,77,79,0.3);">Excluir</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <hr style="border:0; border-top:1px solid var(--border); margin:30px 0;">

      <h2>Pendentes de Aprovação</h2>
      <?php if (!$pending): ?>
        <div class="muted">Nenhum cadastro pendente.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($pending as $u): ?>
            <div class="row itemRow">
              <div>
                <div class="big"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted">CPF: <?= htmlspecialchars((string)$u['cpf'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/approve">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit">Aprovar</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>