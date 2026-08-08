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
        <span class="crumbs"><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" style="color:inherit; text-decoration:none;">Painel</a> · Visão geral</span>
        <h1>Olá, <?= htmlspecialchars((string)($authUserName !== '' ? $authUserName : 'Admin'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="muted page-subtitle">Controle assembleias, eleitores, usuários e acompanhe apurações</p>
      </div>
      <div class="page-actions">
        <?php if (($authUserRole ?? '') === 'SUPER_ADMIN'): ?>
          <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/superadmin/churches" class="btn btn--secondary btn--sm">Gerenciar Igrejas</a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php" class="btn btn--secondary btn--sm" target="_blank" rel="noopener noreferrer">Apuração pública &nearr;</a>
      </div>
    </div>

    <div class="cols-4 layout-row">
      <div class="kpi <?= $registration_open ? 'kpi--green' : '' ?>">
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

    <div class="cols-2 layout-row">

      <section class="card card-stack">
        <div class="card__header">
          <div>
            <h2 class="card__title-sm">Cadastros</h2>
            <span class="card__hint">Ajuste o período e gerencie eleitores</span>
          </div>
        </div>

        <div class="subcard subcard--soft">
          <h4 class="subcard__title">Período de cadastro de eleitores</h4>
          <div class="period-row">
            <div class="period-info">
              <div class="period-label">Situação atual</div>
              <div class="period-status <?= $registration_open ? 'is-open' : 'is-closed' ?>">
                <?= $registration_open ? 'Cadastros abertos' : 'Cadastros fechados' ?>
              </div>
            </div>
            <div class="period-actions">
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

        <div class="subcard">
          <h4 class="subcard__title">Adicionar eleitor manualmente</h4>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/add">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid g-4">
              <div class="form-grid__cell form-grid__cell--5">
                <label for="electorName">Nome Completo</label>
                <input id="electorName" name="name" required maxlength="160" placeholder="Nome do eleitor">
              </div>
              <div class="form-grid__cell form-grid__cell--3">
                <label for="electorCpf">CPF</label>
                <input id="electorCpf" name="cpf" inputmode="numeric" required maxlength="14" placeholder="000.000.000-00" data-mask="cpf">
              </div>
              <div class="form-grid__cell form-grid__cell--4 form-grid__cell--submit">
                <button type="submit" class="btn btn--primary btn--sm">Cadastrar eleitor</button>
              </div>
            </div>
          </form>
        </div>

        <?php if (!empty($approvedElectors)): ?>
          <div class="section-head">
            <h3 class="section-head__title">Eleitores aprovados <span class="muted section-head__count">(<?= count($approvedElectors) ?>)</span></h3>
          </div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th style="width:200px;">CPF</th>
                  <th style="width:140px; text-align:center;">Ação</th>
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
                      <div class="cell-name"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="cell-sub">Aprovado em <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td class="cell-mono"><?= $fmt ?></td>
                    <td style="text-align:center;">
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/delete" data-confirm="Deseja realmente excluir este eleitor?">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn btn--danger-outline btn--sm">Excluir</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <div class="col-stack">
        <section class="card">
          <div class="card__header">
            <h2 class="card__title-sm">Assembleias / Eleições</h2>
            <a class="btn btn--primary btn--sm" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new">+ Nova</a>
          </div>
          <?php if (empty($elections)): ?>
            <div class="empty-state empty-state--blue">
              <div class="empty-state__title">Nenhuma assembleia cadastrada.</div>
              <a class="empty-state__cta" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/new">Criar primeira assembleia &rarr;</a>
            </div>
          <?php else: ?>
            <div class="list-stack">
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
                <article class="list-item">
                  <div class="list-item__info">
                    <div class="list-item__title-row">
                      <h3 class="list-item__title"><?= htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                      <span class="pill <?= $pillClass ?>"><span class="pill__dot" aria-hidden="true"></span> <?= htmlspecialchars($statusPretty, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="list-item__meta">
                      <?= htmlspecialchars($e['type'], ENT_QUOTES, 'UTF-8') ?>
                      &nbsp;·&nbsp; <?= date('d/m/Y', strtotime($e['election_date'])) ?>
                      &nbsp;·&nbsp; Esperados: <strong><?= (int)$e['expected_voters'] ?></strong>
                      <?php if (!empty($e['vacancies'])): ?>
                        &nbsp;·&nbsp; Vagas: <strong><?= (int)$e['vacancies'] ?></strong>
                      <?php endif; ?>
                    </div>
                    <div class="list-item__link">
                      <span class="list-item__link-label">Link apuração:</span>
                      /dashboard.php?key=<?= htmlspecialchars($e['public_key'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </div>
                  <div class="list-item__actions">
                    <a class="btn btn--primary btn--sm" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/manage?id=<?= (int)$e['id'] ?>">Gerenciar</a>
                    <a class="btn btn--secondary btn--sm" target="_blank" rel="noopener noreferrer"
                       href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars($e['public_key'], ENT_QUOTES, 'UTF-8') ?>">Apuração &nearr;</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="card">
          <div class="card__header">
            <h2 class="card__title-sm">Pendentes de aprovação</h2>
            <span class="pill pill--amber"><span class="pill__dot" aria-hidden="true"></span> <?= count($pending) ?> pendente(s)</span>
          </div>
          <?php if (!$pending): ?>
            <div class="empty-state empty-state--green">
              <div class="empty-state__title">Nenhum cadastro pendente.</div>
              <div class="empty-state__subtitle">Todos os novos eleitores já foram revisados.</div>
            </div>
          <?php else: ?>
            <div class="list-stack">
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
                <article class="list-item list-item--pending">
                  <div class="list-item__info">
                    <div class="list-item__name"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="list-item__meta list-item__meta--sm">
                      CPF: <?= $fmt ?>&nbsp;·&nbsp; cadastrado em <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </div>
                  <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elector/approve">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn--success btn--sm">Aprovar</button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="card">
          <div class="card__header">
            <div>
              <h2 class="card__title-sm">Usuários do sistema</h2>
              <span class="card__hint">Administradores e condutores</span>
            </div>
          </div>

          <div class="subcard">
            <h4 class="subcard__title">Novo usuário</h4>
            <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/add">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <div class="form-grid g-4">
                <div class="form-grid__cell form-grid__cell--4">
                  <label for="uName">Nome Completo</label>
                  <input id="uName" name="name" required maxlength="160" placeholder="Nome">
                </div>
                <div class="form-grid__cell form-grid__cell--4">
                  <label for="uEmail">E-mail</label>
                  <input id="uEmail" name="email" type="email" required maxlength="190" placeholder="nome@empresa.com.br">
                </div>
                <div class="form-grid__cell form-grid__cell--2">
                  <label for="uPass">Senha</label>
                  <input id="uPass" name="password" type="password" required autocomplete="new-password">
                </div>
                <div class="form-grid__cell form-grid__cell--2">
                  <label for="uRole">Papel</label>
                  <select id="uRole" name="role" required>
                    <option value="ADMIN">Administrador</option>
                    <option value="CONDUTOR">Condutor</option>
                  </select>
                </div>
                <div class="form-grid__cell form-grid__cell--12 form-grid__cell--submit-end">
                  <button type="submit" class="btn btn--primary btn--sm">Cadastrar usuário</button>
                </div>
              </div>
            </form>
          </div>

          <?php if (!empty($systemUsers)): ?>
            <div class="table-wrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th style="width:260px;">E-mail</th>
                    <th style="width:160px;">Papel</th>
                    <th style="width:140px; text-align:center;">Ação</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($systemUsers as $u):
                    $pillRole = ($u['role'] ?? '') === 'ADMIN' ? 'pill--blue' : 'pill--teal';
                  ?>
                    <tr>
                      <td>
                        <div class="cell-name"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="cell-sub">Desde <?= htmlspecialchars((string)($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                      </td>
                      <td><?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><span class="pill <?= $pillRole ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                      <td style="text-align:center;">
                        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/user/delete" data-confirm="Deseja realmente excluir este usuário?">
                          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                          <button type="submit" class="btn btn--danger-outline btn--sm">Excluir</button>
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

    <div class="app-footer">Painel administrativo Coninfoms Eleição · <?= date('Y') ?></div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
