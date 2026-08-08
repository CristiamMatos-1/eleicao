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
    if (($_SESSION[\App\Core\Auth::SESS_ROLE] ?? '') === 'SUPER_ADMIN') {
      $navLinks[] = ['label' => 'Igrejas', 'href' => $baseUrl . '/superadmin/churches'];
    }
    ob_start();
  ?>
    <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new" class="btn btn-sm">+ Nova assembleia</a>
    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout" style="margin:0;">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="btn secondary btn-sm">Sair</button>
    </form>
  <?php
    $navActions = (string)ob_get_clean();
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap">

    <div class="toolbar">
      <div class="page-title">
        <span class="crumbs"><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">Painel</a> · Visão geral</span>
        <h1>Olá, <?= htmlspecialchars((string)($_SESSION[\App\Core\Auth::SESS_NAME] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></h1>
      </div>
      <div class="page-actions">
        <?php if (($_SESSION[\App\Core\Auth::SESS_ROLE] ?? '') === 'SUPER_ADMIN'): ?>
          <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches" class="btn secondary">Gerenciar Igrejas</a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php" class="btn ghost" target="_blank" rel="noopener noreferrer">Apuração pública ↗</a>
      </div>
    </div>

    <div class="cols-4" style="margin-bottom:20px;">
      <div class="kpi">
        <div class="kpi-label">Cadastros</div>
        <div class="kpi-value"><?= $registration_open ? 'Abertos' : 'Fechados' ?></div>
        <div class="kpi-foot">
          <?php if (!empty($registration_open_by_attendance) && empty($registration_open_by_flag)): ?>
            <span class="pill pill--amber"><span class="pill-dot" aria-hidden="true"></span> Aberto automaticamente · fase de presença</span>
          <?php elseif ($registration_open): ?>
            <span class="pill pill--green"><span class="pill-dot" aria-hidden="true"></span> Liberados para novos eleitores</span>
          <?php else: ?>
            <span class="pill pill--gray"><span class="pill-dot" aria-hidden="true"></span> Acesso fechado</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="kpi kpi--amber">
        <div class="kpi-label">Eleitores aprovados</div>
        <div class="kpi-value"><?= count($approvedElectors) ?></div>
        <div class="kpi-foot">Ativos e prontos para votar</div>
      </div>
      <div class="kpi kpi--green">
        <div class="kpi-label">Usuários do sistema</div>
        <div class="kpi-value"><?= count($systemUsers) ?></div>
        <div class="kpi-foot">Administradores e condutores</div>
      </div>
      <div class="kpi kpi--red">
        <div class="kpi-label">Pendentes de aprovação</div>
        <div class="kpi-value"><?= count($pending) ?></div>
        <div class="kpi-foot">Novos cadastros aguardando</div>
      </div>
    </div>

    <div class="cols-2">

      <section class="card" style="display:flex; flex-direction:column; gap:12px;">
        <div class="section-title">
          <h2>Cadastros</h2>
          <span class="muted">Ajuste o período</span>
        </div>
        <div class="box box-info">
          <div class="box-heading">
            <h4>Período de cadastro de eleitores</h4>
          </div>
          <div class="row row--start" style="gap:16px; align-items:center;">
            <div style="flex:1; min-width:220px;">
              <div class="muted" style="font-size:0.85rem;">Situação atual</div>
              <div class="big" style="margin-top:4px;">
                <?= $registration_open ? '<span style="color:var(--brand-success);">Cadastros abertos</span>' : '<span style="color:var(--brand-muted);">Cadastros fechados</span>' ?>
              </div>
            </div>
            <div class="row" style="gap:8px;">
              <?php if (!$registration_open): ?>
                <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/registrations/open" data-confirm="Abrir período de cadastro de eleitores?">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="btn">Abrir cadastros</button>
                </form>
              <?php else: ?>
                <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/registrations/close" data-confirm="Fechar o período de cadastro agora?">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="btn secondary">Fechar cadastros</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="box">
          <div class="box-heading">
            <h4>Adicionar eleitor manualmente</h4>
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/add" class="form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid">
              <div class="g-6 field">
                <label>Nome Completo</label>
                <input name="name" required maxlength="160" placeholder="Nome do eleitor">
              </div>
              <div class="g-4 field">
                <label>CPF</label>
                <input name="cpf" inputmode="numeric" required maxlength="14" placeholder="000.000.000-00" data-cpf-input>
              </div>
              <div class="g-2" style="grid-column: span 12; display:flex; align-items:flex-end; justify-content:flex-end; gap:10px;">
                <button type="submit" class="btn">Cadastrar eleitor</button>
              </div>
            </div>
          </form>
        </div>

        <?php if (!empty($approvedElectors)): ?>
          <div class="section-title" style="margin-top:6px;">
            <h3>Eleitores aprovados <span class="muted" style="font-weight:500;">(<?= count($approvedElectors) ?>)</span></h3>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Nome</th>
                  <th style="width:200px;">CPF</th>
                  <th style="width:100px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($approvedElectors as $u): ?>
                  <tr>
                    <td>
                      <div style="font-weight:600;"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="muted" style="font-size:0.8rem;">Aprovado em <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td><?= htmlspecialchars((string)$u['cpf'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;">
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/delete" data-confirm="Deseja realmente excluir este eleitor?">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn secondary btn-sm" style="color:var(--brand-danger); border-color:rgba(220,38,38,0.3);">Excluir</button>
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
          <div class="section-title">
            <h2>Assembleias / Eleições</h2>
            <a class="btn btn-sm" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new">+ Nova</a>
          </div>
          <?php if (empty($elections)): ?>
            <div class="box box-soft">
              <div class="muted" style="font-weight:600;">Nenhuma assembleia cadastrada.</div>
              <div style="margin-top:6px;">
                <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new">Criar primeira assembleia →</a>
              </div>
            </div>
          <?php else: ?>
            <div class="list">
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
                <div class="itemRow">
                  <div class="row--start" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:220px;">
                      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="big" style="font-size:1rem;"><?= htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="pill <?= $pillClass ?>"><span class="pill-dot" aria-hidden="true"></span> <?= htmlspecialchars($statusPretty, ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                      <div class="item-meta" style="margin-top:6px;">
                        <?= htmlspecialchars($e['type'], ENT_QUOTES, 'UTF-8') ?>
                        · <?= htmlspecialchars((string)$e['election_date'], ENT_QUOTES, 'UTF-8') ?>
                        · Esperados: <strong><?= (int)$e['expected_voters'] ?></strong>
                        <?php if (!empty($e['vacancies'])): ?>
                          · Vagas: <strong><?= (int)$e['vacancies'] ?></strong>
                        <?php endif; ?>
                      </div>
                      <div class="muted" style="font-size:0.8rem; margin-top:6px; word-break:break-all;">
                        <span style="font-weight:600;">Link apuração:</span> /dashboard.php?key=<?= htmlspecialchars($e['public_key'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                      <a class="btn" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/manage?id=<?= (int)$e['id'] ?>">Gerenciar</a>
                      <a class="btn ghost btn-sm" target="_blank" rel="noopener noreferrer"
                         href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars($e['public_key'], ENT_QUOTES, 'UTF-8') ?>">Apuração ↗</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="card">
          <div class="section-title">
            <h2>Pendentes de aprovação</h2>
            <span class="pill pill--amber"><span class="pill-dot" aria-hidden="true"></span> <?= count($pending) ?> pendente(s)</span>
          </div>
          <?php if (!$pending): ?>
            <div class="box box-success">
              <div class="muted" style="font-weight:600;">Nenhum cadastro pendente.</div>
              <div style="font-size:0.9rem; color:var(--brand-muted); margin-top:4px;">Todos os novos eleitores já foram revisados.</div>
            </div>
          <?php else: ?>
            <div class="list">
              <?php foreach ($pending as $u): ?>
                <div class="row itemRow">
                  <div style="flex:1; min-width:200px;">
                    <div class="big"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="muted" style="font-size:0.85rem;">
                      CPF: <?= htmlspecialchars((string)$u['cpf'], ENT_QUOTES, 'UTF-8') ?>
                      · cadastrado em <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </div>
                  <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/approve">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn success btn-sm">Aprovar</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="card">
          <div class="section-title">
            <h2>Usuários do sistema</h2>
            <span class="muted">Admin / Condutor</span>
          </div>
          <div class="box">
            <div class="box-heading"><h4>Novo usuário</h4></div>
            <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/add" class="form">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <div class="form-grid">
                <div class="g-6 field">
                  <label>Nome Completo</label>
                  <input name="name" required maxlength="160" placeholder="Nome">
                </div>
                <div class="g-6 field">
                  <label>E-mail</label>
                  <input name="email" type="email" required maxlength="190" placeholder="nome@empresa.com.br">
                </div>
                <div class="g-6 field">
                  <label>Senha</label>
                  <input name="password" type="password" required autocomplete="new-password">
                </div>
                <div class="g-4 field">
                  <label>Papel</label>
                  <select name="role" required>
                    <option value="ADMIN">Administrador</option>
                    <option value="CONDUTOR">Condutor</option>
                  </select>
                </div>
                <div class="g-2" style="grid-column: span 12; display:flex; justify-content:flex-end; align-items:flex-end;">
                  <button type="submit" class="btn">Cadastrar usuário</button>
                </div>
              </div>
            </form>
          </div>

          <?php if (!empty($systemUsers)): ?>
            <div class="table-wrap" style="margin-top:14px;">
              <table>
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th style="width:240px;">E-mail</th>
                    <th style="width:140px;">Papel</th>
                    <th style="width:100px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($systemUsers as $u):
                    $pillRole = ($u['role'] ?? '') === 'ADMIN' ? 'pill--blue' : 'pill--teal';
                  ?>
                    <tr>
                      <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="muted" style="font-size:0.78rem;">Desde <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                      </td>
                      <td><?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><span class="pill <?= $pillRole ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                      <td style="text-align:right;">
                        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/delete" data-confirm="Deseja realmente excluir este usuário?">
                          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                          <button type="submit" class="btn secondary btn-sm" style="color:var(--brand-danger); border-color:rgba(220,38,38,0.3);">Excluir</button>
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

    <div class="footer-mini">Painel administrativo Coninfoms Eleição · <?= date('Y') ?></div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
