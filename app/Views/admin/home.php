<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel Administrativo</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Painel Administrativo';
    $activePath = '/admin';
    $brandLetter = 'C';
    $navLinks = [
      ['label' => 'Início', 'href' => $baseUrl . '/admin'],
      ['label' => 'Nova eleição', 'href' => $baseUrl . '/admin/elections/new'],
    ];
    if (($authUserRole ?? '') === 'SUPER_ADMIN') {
      $navLinks[] = ['label' => 'Igrejas', 'href' => $baseUrl . '/superadmin/churches'];
    }
    ob_start();
  ?>
    <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new" class="btn btn--primary btn--sm">+ Nova assembleia</a>
    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout" style="margin:0;">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="btn btn--secondary btn--sm">Sair</button>
    </form>
  <?php
    $navActions = (string)ob_get_clean();
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap">

    <div class="toolbar">
      <div class="page-title">
        <span class="muted" style="font-size:13px; letter-spacing:.4px;"><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" style="color:inherit; text-decoration:none;">Painel</a> · Visão geral</span>
        <h1>Olá, <?= htmlspecialchars((string)($authUserName !== '' ? $authUserName : 'Admin'), ENT_QUOTES, 'UTF-8') ?></h1>
      </div>
      <div class="page-actions">
        <?php if (($authUserRole ?? '') === 'SUPER_ADMIN'): ?>
          <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches" class="btn btn--secondary btn--sm">Gerenciar Igrejas</a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php" class="btn btn--secondary btn--sm" target="_blank" rel="noopener noreferrer">Apuração pública ↗</a>
      </div>
    </div>

    <div class="cols-4" style="margin-bottom:20px;">
      <div class="kpi">
        <div class="kpi__label">Cadastros</div>
        <div class="kpi__value"><?= $registration_open ? 'Abertos' : 'Fechados' ?></div>
        <div class="kpi__hint">
          <?php if (!empty($registration_open_by_attendance) && empty($registration_open_by_flag)): ?>
            <span class="pill pill--amber"><span class="pill__dot" aria-hidden="true"></span> Aberto automaticamente · fase de presença</span>
          <?php elseif ($registration_open): ?>
            <span class="pill pill--green"><span class="pill__dot" aria-hidden="true"></span> Liberados para novos eleitores</span>
          <?php else: ?>
            <span class="pill pill--gray"><span class="pill__dot" aria-hidden="true"></span> Acesso fechado</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="kpi kpi--amber">
        <div class="kpi__label">Eleitores aprovados</div>
        <div class="kpi__value"><?= count($approvedElectors) ?></div>
        <div class="kpi__hint">Ativos e prontos para votar</div>
      </div>
      <div class="kpi kpi--green">
        <div class="kpi__label">Usuários do sistema</div>
        <div class="kpi__value"><?= count($systemUsers) ?></div>
        <div class="kpi__hint">Administradores e condutores</div>
      </div>
      <div class="kpi kpi--red">
        <div class="kpi__label">Pendentes de aprovação</div>
        <div class="kpi__value"><?= count($pending) ?></div>
        <div class="kpi__hint">Novos cadastros aguardando</div>
      </div>
    </div>

    <div class="cols-2">

      <section class="card" style="display:flex; flex-direction:column; gap:12px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:14px; flex-wrap:wrap;">
          <div>
            <h2 style="margin:0; color:var(--brand-secondary);">Cadastros</h2>
            <span class="muted" style="font-size:13px;">Ajuste o período e gerencie eleitores</span>
          </div>
        </div>
        <div class="card card--tinted" style="padding:16px 18px;">
          <div style="font-weight:600; margin-bottom:10px;">Período de cadastro de eleitores</div>
          <div style="display:flex; flex-direction:column; gap:12px;">
            <div>
              <div class="muted" style="font-size:13px;">Situação atual</div>
              <div style="margin-top:4px; font-size:17px; font-weight:700;">
                <?= $registration_open ? '<span style="color:var(--brand-success);">Cadastros abertos</span>' : '<span style="color:var(--brand-muted);">Cadastros fechados</span>' ?>
              </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <?php if (!$registration_open): ?>
                <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/registrations/open" data-confirm="Abrir período de cadastro de eleitores?">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="btn btn--primary btn--sm">Abrir cadastros</button>
                </form>
              <?php else: ?>
                <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/registrations/close" data-confirm="Fechar o período de cadastro agora?">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="btn btn--secondary btn--sm">Fechar cadastros</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="card" style="border:1px solid var(--brand-border); padding:16px 18px; margin-top:4px;">
          <div style="font-weight:600; margin-bottom:12px;">Adicionar eleitor manualmente</div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/add">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid g-4">
              <div class="form-grid__cell form-grid__cell--2">
                <label>Nome Completo</label>
                <input name="name" required maxlength="160" placeholder="Nome do eleitor">
              </div>
              <div class="form-grid__cell form-grid__cell--1">
                <label>CPF</label>
                <input name="cpf" inputmode="numeric" required maxlength="14" placeholder="000.000.000-00" data-mask="cpf">
              </div>
              <div class="form-grid__cell form-grid__cell--3" style="display:flex; align-items:flex-end; justify-content:flex-end;">
                <button type="submit" class="btn btn--primary btn--sm">Cadastrar eleitor</button>
              </div>
            </div>
          </form>
        </div>

        <?php if (!empty($approvedElectors)): ?>
          <div style="margin-top:6px;">
            <h3 style="margin:0 0 12px 0;">Eleitores aprovados <span class="muted" style="font-weight:500;">(<?= count($approvedElectors) ?>)</span></h3>
          </div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th style="width:200px;">CPF</th>
                  <th style="width:120px; text-align:center;">Ação</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($approvedElectors as $u):
                  $cpf = is_string($u['cpf'] ?? null) ? $u['cpf'] : '';
                  $d = preg_replace('/\D/', '', $cpf);
                  $fmt = '';
                  if (strlen($d) === 11) {
                      $fmt = substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6,3).'-'.substr($d,9,2);
                  } else {
                      $fmt = htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8');
                  }
                ?>
                  <tr>
                    <td>
                      <div style="font-weight:600;"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="muted" style="font-size:12px;">Aprovado em <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td style="font-variant-numeric: tabular-nums;"><?= $fmt ?></td>
                    <td style="text-align:center;">
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/delete" data-confirm="Deseja realmente excluir este eleitor?">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn btn--secondary btn--sm" style="color:var(--brand-danger); border-color:rgba(220,38,38,.3);">Excluir</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <div style="display:flex; flex-direction:column; gap:16px;">
        <section class="card">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
            <h2 style="margin:0;">Assembleias / Eleições</h2>
            <a class="btn btn--primary btn--sm" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new">+ Nova</a>
          </div>
          <?php if (empty($elections)): ?>
            <div style="padding:18px; background:var(--brand-soft); border:1px solid #BFDBFE; border-radius:var(--radius-lg);">
              <div class="muted" style="font-weight:600; color:#1E3A8A;">Nenhuma assembleia cadastrada.</div>
              <div style="margin-top:6px;">
                <a style="color:var(--brand-primary); font-weight:600;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new">Criar primeira assembleia →</a>
              </div>
            </div>
          <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
              <?php foreach ($elections as $e):
                $statusLabel = $e['status'];
                $pillClass = 'pill--gray';
                if (in_array($statusLabel, ['aberta_para_presenca','OPEN'], true)) $pillClass = 'pill--amber';
                if (in_array($statusLabel, ['aberta_para_votacao'], true)) $pillClass = 'pill--blue';
                if (in_array($statusLabel, ['encerrada','CLOSED'], true)) $pillClass = 'pill--teal';
                $statusPretty = $statusLabel;
                if ($statusLabel === 'aberta_para_presenca') $statusPretty = 'Fase de presença';
                elseif ($statusLabel === 'aberta_para_votacao') $statusPretty = 'Votação aberta';
                elseif ($statusLabel === 'encerrada') $statusPretty = 'Encerrada';
                elseif ($statusLabel === 'OPEN') $statusPretty = 'Aberta (legado)';
                elseif ($statusLabel === 'CLOSED') $statusPretty = 'Fechada (legado)';
                ?>
                <div style="padding:16px 18px; border:1px solid var(--brand-border); border-radius:var(--radius-lg); background:var(--brand-card); box-shadow:var(--shadow-sm);">
                  <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:220px;">
                      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span style="font-size:16px; font-weight:700; color:var(--brand-text);"><?= htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="pill <?= $pillClass ?>"><span class="pill__dot" aria-hidden="true"></span> <?= htmlspecialchars($statusPretty, ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                      <div class="muted" style="margin-top:6px; font-size:13px;">
                        <?= htmlspecialchars($e['type'], ENT_QUOTES, 'UTF-8') ?>
                        · <?= date('d/m/Y', strtotime($e['election_date'])) ?>
                        · Esperados: <strong><?= (int)$e['expected_voters'] ?></strong>
                        <?php if (!empty($e['vacancies'])): ?>
                          · Vagas: <strong><?= (int)$e['vacancies'] ?></strong>
                        <?php endif; ?>
                      </div>
                      <div class="muted" style="font-size:12px; margin-top:6px; word-break:break-all;">
                        <span style="font-weight:600;">Link apuração:</span> /dashboard.php?key=<?= htmlspecialchars($e['public_key'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                      <a class="btn btn--primary btn--sm" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/manage?id=<?= (int)$e['id'] ?>">Gerenciar</a>
                      <a class="btn btn--secondary btn--sm" target="_blank" rel="noopener noreferrer"
                         href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars($e['public_key'], ENT_QUOTES, 'UTF-8') ?>">Apuração ↗</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="card">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
            <h2 style="margin:0;">Pendentes de aprovação</h2>
            <span class="pill pill--amber"><span class="pill__dot" aria-hidden="true"></span> <?= count($pending) ?> pendente(s)</span>
          </div>
          <?php if (!$pending): ?>
            <div style="padding:18px; background:#DCFCE7; border:1px solid #BBF7D0; border-radius:var(--radius-lg);">
              <div style="font-weight:600; color:#166534;">Nenhum cadastro pendente.</div>
              <div style="font-size:14px; color:var(--brand-muted); margin-top:4px;">Todos os novos eleitores já foram revisados.</div>
            </div>
          <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:10px;">
              <?php foreach ($pending as $u):
                $cpf = is_string($u['cpf'] ?? null) ? $u['cpf'] : '';
                $d = preg_replace('/\D/', '', $cpf);
                $fmt = '';
                if (strlen($d) === 11) {
                    $fmt = substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6,3).'-'.substr($d,9,2);
                } else {
                    $fmt = htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8');
                }
              ?>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:14px 16px; border:1px solid var(--brand-border); border-radius:var(--radius-lg); background:var(--brand-card);">
                  <div style="flex:1; min-width:200px;">
                    <div style="font-weight:600; color:var(--brand-text); font-size:15px;"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="muted" style="font-size:13px;">
                      CPF: <?= $fmt ?> · cadastrado em <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </div>
                  <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/approve">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn--success btn--sm">Aprovar</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="card">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
            <h2 style="margin:0;">Usuários do sistema</h2>
            <span class="muted" style="font-size:13px;">Admin / Condutor</span>
          </div>
          <div style="padding:14px 16px; border:1px solid var(--brand-border); border-radius:var(--radius-lg);">
            <div style="font-weight:600; margin-bottom:12px;">Novo usuário</div>
            <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/add">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <div class="form-grid g-4">
                <div class="form-grid__cell form-grid__cell--2">
                  <label>Nome Completo</label>
                  <input name="name" required maxlength="160" placeholder="Nome">
                </div>
                <div class="form-grid__cell form-grid__cell--2">
                  <label>E-mail</label>
                  <input name="email" type="email" required maxlength="190" placeholder="nome@empresa.com.br">
                </div>
                <div class="form-grid__cell form-grid__cell--1">
                  <label>Senha</label>
                  <input name="password" type="password" required autocomplete="new-password">
                </div>
                <div class="form-grid__cell form-grid__cell--1">
                  <label>Papel</label>
                  <select name="role" required>
                    <option value="ADMIN">Administrador</option>
                    <option value="CONDUTOR">Condutor</option>
                  </select>
                </div>
                <div class="form-grid__cell form-grid__cell--4" style="display:flex; justify-content:flex-end; align-items:flex-end;">
                  <button type="submit" class="btn btn--primary btn--sm">Cadastrar usuário</button>
                </div>
              </div>
            </form>
          </div>

          <?php if (!empty($systemUsers)): ?>
            <div class="table-wrap" style="margin-top:14px;">
              <table class="table">
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th style="width:240px;">E-mail</th>
                    <th style="width:140px;">Papel</th>
                    <th style="width:120px; text-align:center;">Ação</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($systemUsers as $u):
                    $pillRole = ($u['role'] ?? '') === 'ADMIN' ? 'pill--blue' : 'pill--teal';
                  ?>
                    <tr>
                      <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="muted" style="font-size:12px;">Desde <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                      </td>
                      <td><?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><span class="pill <?= $pillRole ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                      <td style="text-align:center;">
                        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/delete" data-confirm="Deseja realmente excluir este usuário?">
                          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                          <button type="submit" class="btn btn--secondary btn--sm" style="color:var(--brand-danger); border-color:rgba(220,38,38,.3);">Excluir</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </div>

    <div style="margin-top:28px; text-align:center; color:var(--brand-muted); font-size:13px; padding:0 16px 20px 16px;">Painel administrativo Coninfoms Eleição · <?= date('Y') ?></div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
