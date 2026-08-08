<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gerenciar Eleição</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Painel Administrativo';
    $activePath = '/admin/elections/manage';
    $navLinks = [
      ['label' => 'Início', 'href' => $baseUrl . '/admin'],
      ['label' => 'Voltar', 'href' => $baseUrl . '/admin'],
    ];
    ob_start();
  ?>
    <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" class="btn btn--secondary btn--sm">← Painel</a>
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
        <span class="crumbs"><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" style="color:inherit; text-decoration:none;">Painel</a> · Assembleia</span>
        <h1 style="margin-top:4px;"><?= htmlspecialchars((string)($election['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
      </div>
      <div class="page-actions">
        <?php
          $status = (string)($election['status'] ?? '');
          $statusMap = [
            'aberta_para_presenca' => ['Fase de Presença', 'pill--amber'],
            'aberta_para_votacao'  => ['Aberta para Votação',  'pill--blue'],
            'encerrada'            => ['Encerrada',             'pill--teal'],
            'OPEN'                 => ['Aberta (Legado)',       'pill--blue'],
            'CLOSED'               => ['Encerrada (Legado)',    'pill--gray'],
          ];
          [$statusLabel, $pillClass] = $statusMap[$status] ?? ['Status desconhecido', 'pill--gray'];
        ?>
        <span class="pill <?= $pillClass ?>"><span class="pill__dot" aria-hidden="true"></span> <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>

    <section class="card layout-row">
      <div class="card__header">
        <div>
          <h2 class="card__title-sm">Workflow da Assembleia</h2>
          <span class="card__hint">Controle as fases de presença, votação e encerramento</span>
        </div>
      </div>

      <div class="cols-3" style="margin-bottom:14px;">
        <div class="card card--tinted card--warn">
          <div class="card__header">
          <h4 style="margin:0;">Fase 1 · Presença</h4>
          </div>
          <div class="card__hint">
            Libere a entrada, confirme presença dos eleitores e automatize a abertura do cadastro.
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" data-confirm="Deseja abrir a fase de presença agora?">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="status" value="presenca">
            <button type="submit" class="btn btn--warning btn-block <?= ($status === 'encerrada' || $status === 'CLOSED') ? '' : '' ?>" <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'disabled' : '' ?>>
              <?= $status === 'aberta_para_presenca' ? 'Atualmente · Presença' : 'Abrir para Presença' ?>
            </button>
          </form>
        </div>
        <div class="card card--tinted card--info">
          <div class="card__header">
            <h4 style="margin:0;">Fase 2 · Votação</h4>
          </div>
          <div class="card__hint">
            Libere a cédula para todos os eleitores com presença confirmada.
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" data-confirm="Deseja abrir a votação agora?">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="status" value="votacao">
            <button type="submit" class="btn btn--primary btn-block" <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'disabled' : '' ?>>
              <?= $status === 'aberta_para_votacao' ? 'Atualmente · Votação' : 'Abrir para Votação' ?>
            </button>
          </form>
        </div>
        <div class="card card--tinted card--danger">
          <div class="card__header">
            <h4 style="margin:0;">Fase 3 · Encerrar</h4>
          </div>
          <div class="card__hint">
            Encerra a assembleia, fecha todos os escrutínios abertos e trava presenças e votos.
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" data-confirm="Tem certeza que deseja encerrar esta eleição? Após encerrada, presenças e votos não podem ser mais inseridos.">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="status" value="encerrada">
            <button type="submit" class="btn btn--danger btn-block" <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'disabled' : '' ?>>
              <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'Assembleia Encerrada' : 'Encerrar Eleição' ?>
            </button>
          </form>
        </div>
      </div>

      <div class="cols-2" style="margin-bottom:10px;">
        <div class="card card--tinted card--info">
          <div class="card__header">
            <h4 style="margin:0;">Lista de Presença</h4>
          </div>
          <div class="card__hint">
            Veja quem confirmou presença, registre manualmente ou imprima.
          </div>
          <div class="actions-row">
            <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn--primary">Ver Lista Completa</a>
            <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--secondary">Exportar PDF ↓</a>
          </div>
        </div>
        <div class="card card--tinted card--info" style="border-style:dashed;">
          <div class="card__header">
            <h4 style="margin:0;">Painel Público de Apuração</h4>
          </div>
          <div class="card__hint">Compartilhe este link com a comunidade:</div>
          <div class="share-url">
            <code><?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars((string)($election['public_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars((string)($election['public_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--secondary">Abrir ↗</a>
          </div>
        </div>
      </div>

      <?php if (in_array($status, ['OPEN','aberta_para_presenca','aberta_para_votacao'], true)):
        $entityName = (string)(($election['entity_name'] ?? '') !== '' ? $election['entity_name'] : ($election['church_legal_name'] ?? ($election['church_name'] ?? '')));
        $assemblyTypeRaw = strtoupper((string)($election['assembly_type'] ?? 'ORDINARIA'));
        $electionDateVal = (string)($election['election_date'] ?? date('Y-m-d'));
      ?>
        <div class="card card--tinted card--success">
          <div class="card__header">
            <h4 style="margin:0;">Parâmetros da Assembleia</h4>
            <span class="pill pill--teal">Editável</span>
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/config/edit" style="margin-top:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <div class="form-grid g-4">
              <div class="form-grid__cell form-grid__cell--6">
                <label>Título da Assembleia</label>
                <input name="title" value="<?= htmlspecialchars((string)($election['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required maxlength="190">
              </div>
              <div class="form-grid__cell form-grid__cell--2">
                <label>Natureza da Assembleia</label>
                <select name="assembly_type" required>
                  <option value="ORDINARIA" <?= $assemblyTypeRaw !== 'EXTRAORDINARIA' ? 'selected' : '' ?>>Ordinária</option>
                  <option value="EXTRAORDINARIA" <?= $assemblyTypeRaw === 'EXTRAORDINARIA' ? 'selected' : '' ?>>Extraordinária</option>
                </select>
              </div>
              <div class="form-grid__cell form-grid__cell--2">
                <label>Data da Assembleia</label>
                <input type="date" name="election_date" value="<?= htmlspecialchars($electionDateVal, ENT_QUOTES, 'UTF-8') ?>" required>
              </div>
              <div class="form-grid__cell form-grid__cell--2">
                <label>Eleitores Esperados</label>
                <input type="number" name="expected_voters" value="<?= (int)($election['expected_voters'] ?? 0) ?>" min="1" required>
              </div>
              <div class="form-grid__cell form-grid__cell--8">
                <label>Nome da Entidade / Condomínio <span class="hint-inline">(Razão Social p/ PDF)</label>
                <input name="entity_name" value="<?= htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8') ?>" maxlength="255" placeholder="Ex: Igreja Batista da Esperança EIRELI - ME">
              </div>
              <div class="form-grid__cell form-grid__cell--4 form-grid__cell--submit-end">
                <button type="submit" class="btn btn--primary btn--lg">Salvar Parâmetros →</button>
              </div>
              <div class="form-grid__cell form-grid__cell--6">
                <span class="card__hint">
                  Usado para cálculo de quórum, encerramento automático e cabeçalho do PDF de Lista de Presença.
                </span>
              </div>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </section>

    <?php if (($election['type'] ?? '') === 'DIRETORIA'): ?>
      <section class="card layout-row">
        <div class="card__header">
          <div>
            <h2 class="card__title-sm">Credenciamento de Deputados</h2>
            <span class="card__hint">Eleitores e candidatos autorizados</span>
          </div>
        </div>
        <div class="cols-2">
          <div class="card card--tinted card--success">
            <div class="card__header">
              <h4 style="margin:0;">Credenciados <span class="pill pill--green" style="margin-left:8px;"><?= count($accreditedVoters ?? []) ?></span></h4>
              <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn--secondary btn--sm">Lista de Presença</a>
                <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn--secondary btn--sm">PDF</a>
              </div>
            </div>
            <div class="accredited-list">
              <?php if (empty($accreditedVoters)): ?>
                <div class="muted">Nenhum eleitor credenciado.</div>
              <?php else: ?>
                <div>
                  <?php foreach(($accreditedVoters ?? []) as $v): ?>
                    <div class="accredited-row">
                      <div class="accredited-row__info">
                        <div class="accredited-row__name"><?= htmlspecialchars((string)($v['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/accreditation/toggle" style="margin:0;" data-confirm="Remover credenciamento deste eleitor?">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= (int)($v['id'] ?? 0) ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn btn--danger-outline btn--sm">Remover</button>
                      </form>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card card--tinted">
            <div class="card__header">
              <h4 style="margin:0;">Não credenciados <span class="pill pill--gray" style="margin-left:8px;"><?= count($unaccreditedVoters ?? []) ?></span></h4>
            </div>
            <div class="accredited-list">
              <?php if (empty($unaccreditedVoters)): ?>
                <div class="muted">Todos os eleitores ativos estão credenciados.</div>
              <?php else: ?>
                <div>
                  <?php foreach(($unaccreditedVoters ?? []) as $v): ?>
                    <div class="accredited-row accredited-row--gray">
                      <div class="accredited-row__info">
                        <div class="accredited-row__name accredited-row__name--muted"><?= htmlspecialchars((string)($v['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/accreditation/toggle" style="margin:0;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= (int)($v['id'] ?? 0) ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn btn--success btn--sm">Credenciar</button>
                      </form>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <section class="card layout-row">
      <div class="card__header">
        <div>
          <h2 class="card__title-sm">Escrutínios e Apuração Atual</h2>
        </div>
        <?php if (in_array($status, ['OPEN','aberta_para_presenca','aberta_para_votacao'], true) && in_array(($election['type'] ?? ''), ['OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true) && (!empty($scrutiniums) && ($scrutiniums[0]['status'] ?? '') === 'CLOSED')): ?>
          <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/next?id=<?= (int)$election['id'] ?>" class="btn btn--primary">+ Abrir Próximo Escrutínio</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($voteCounts)):
        $currentScrutinyExpected = !empty($scrutiniums) ? (int)($scrutiniums[0]['expected_voters'] ?? 0) : (int)($election['expected_voters'] ?? 0);
        $quorum = intdiv($currentScrutinyExpected, 2) + 1;
      ?>
        <div class="card card--tinted scrutiny-card scrutiny-card--blue">
          <div class="card__header">
            <h4 class="card__title-sm">Apuração do Escrutínio Atual</h4>
            <span class="pill pill--blue">Quórum: <?= (int)$quorum ?> voto(s)</span>
          </div>
          <div class="cols-2">
            <?php foreach($voteCounts as $vc):
              $isElectedLive = (($vc['name'] ?? '') !== 'BRANCOS' && ((int)($vc['votes'] ?? 0) >= $quorum));
            ?>
              <article class="scrutiny-row <?= $isElectedLive ? 'scrutiny-row--elected' : '' ?>">
                <div class="scrutiny-row__info">
                  <div class="scrutiny-row__name"><?= htmlspecialchars((string)($vc['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if($isElectedLive): ?>
                    <span class="pill pill--green scrutiny-row__badge"><span class="pill__dot" aria-hidden="true"></span> ELEITO</span>
                  <?php endif; ?>
                </div>
                <div class="scrutiny-row__votes"><?= (int)($vc['votes'] ?? 0) ?> voto(s)</div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Escrutínio</th>
              <th>Status</th>
              <th style="width:180px;">Votos / Esperados</th>
              <th style="width:160px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($scrutiniums ?? []) as $s): ?>
              <tr>
                <td style="font-weight:700;">#<?= (int)($s['number'] ?? 0) ?></td>
                <td>
                  <?php
                    $sPill = ($s['status'] ?? '') === 'OPEN' ? 'pill--green' : 'pill--gray';
                  ?>
                  <span class="pill <?= $sPill ?>"><span class="pill__dot" aria-hidden="true"></span> <?= htmlspecialchars((string)($s['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td><strong><?= (int)($s['vote_count'] ?? 0) ?></strong> / <?= (int)($s['expected_voters'] ?? 0) ?></td>
                <td style="text-align:right;">
                  <?php if (($s['status'] ?? '') === 'OPEN'): ?>
                    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/reset" data-confirm="ATENÇÃO! Deseja zerar todos os votos deste escrutínio?">
                      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="scrutiny_id" value="<?= (int)($s['id'] ?? 0) ?>">
                      <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                      <button type="submit" class="btn btn--danger-outline btn--sm">Zerar Votos</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card layout-row">
      <div class="card__header">
        <div>
          <h2 class="card__title-sm">Candidatos</h2>
          <span class="card__hint">Editar, excluir ou adicionar novos</span>
        </div>
      </div>
      <div>
        <?php foreach (($candidates ?? []) as $c):
          $initials = '';
          $nameForInitials = trim((string)($c['full_name'] ?? ''));
          if ($nameForInitials !== '') {
            $parts = preg_split('/\s+/', $nameForInitials);
            if (count($parts) >= 2) {
              $initials = mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1);
            } else {
              $initials = mb_substr($nameForInitials, 0, 2);
            }
          }
          $initials = mb_strtoupper($initials);
        ?>
          <div class="candidate-row">
            <div class="candidate-row__body">
              <div class="candidate-row__main">
                <?php if (!empty($c['photo_path'])): ?>
                  <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim((string)$c['photo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($c['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:44px; height:44px; border-radius:10px; object-fit:cover;">
                <?php else: ?>
                  <div class="candidate-photo candidate-photo--sm"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div class="candidate-row__meta">
                  <div class="candidate-row__name"><?= htmlspecialchars((string)($c['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="candidate-row__pills">
                    <span class="pill" style="margin:0;"><?= htmlspecialchars((string)($c['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                </div>
              </div>
              <div class="candidate-row__actions">
                <button type="button" class="btn btn--secondary btn--sm" data-toggle-inline="#editForm<?= (int)($c['id'] ?? 0) ?>">Editar</button>
                <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/delete" data-confirm="Excluir este candidato?" style="margin:0;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="candidate_id" value="<?= (int)($c['id'] ?? 0) ?>">
                  <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                  <button type="submit" class="btn btn--danger-outline btn--sm">Excluir</button>
                </form>
              </div>
            </div>

            <div id="editForm<?= (int)($c['id'] ?? 0) ?>" class="inline-edit hidden">
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/edit" enctype="multipart/form-data" style="margin-top:0;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="candidate_id" value="<?= (int)($c['id'] ?? 0) ?>">
                <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                <div class="form-grid g-4">
                  <div class="form-grid__cell form-grid__cell--2">
                    <label>Nome</label>
                    <input name="full_name" value="<?= htmlspecialchars((string)($c['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required maxlength="160">
                  </div>
                  <div class="form-grid__cell form-grid__cell--2">
                    <label>Nova Foto <span class="hint-inline">(opcional)</span></label>
                    <input type="file" name="photo" accept="image/*" class="file-input--dashed">
                  </div>
                  <div class="form-grid__cell form-grid__cell--4 form-grid__cell--submit-end">
                    <button type="button" class="btn btn--secondary" data-toggle-inline="#editForm<?= (int)($c['id'] ?? 0) ?>">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Salvar Alterações →</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (in_array(($election['type'] ?? ''), ['OFICIAIS', 'SOCIEDADES'], true)): ?>
        <div class="card card--tinted card--info" style="margin-top:16px;">
          <div class="card__header">
            <h4 style="margin:0;">Adicionar candidato</h4>
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/add" enctype="multipart/form-data" style="margin-top:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <div class="form-grid g-4">
              <div class="form-grid__cell form-grid__cell--2">
                <label>Nome completo</label>
                <input name="full_name" required maxlength="160" placeholder="Nome do candidato">
              </div>
              <div class="form-grid__cell form-grid__cell--2">
                <label>Foto <span class="hint-inline">(opcional)</span></label>
                <input type="file" name="photo" accept="image/*" class="file-input--dashed">
              </div>
              <div class="form-grid__cell form-grid__cell--4 form-grid__cell--submit-end">
                <button type="submit" class="btn btn--primary">Adicionar candidato →</button>
              </div>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </section>

    <section class="card layout-row">
      <div class="card__header">
        <div>
          <h2 class="card__title-sm">Eleitos</h2>
          <span class="card__hint">Resultados oficiais registrados</span>
        </div>
      </div>
      <?php if (empty($elected)): ?>
        <div class="card card--tinted">
          <div style="font-weight:600;">Nenhum eleito registrado ainda.</div>
          <div class="card__hint" style="margin-top:4px;">Continue a apuração e os eleitos aparecerão aqui automaticamente.</div>
        </div>
      <?php else: ?>
        <div>
          <?php foreach (($elected ?? []) as $e):
            $rulePill = ($e['rule'] ?? '') === 'MAIORIA' ? 'pill--blue' : 'pill--teal';
          ?>
            <div class="elected-row">
              <div class="elected-row__info">
                <div class="elected-row__name"><?= htmlspecialchars((string)($e['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="elected-row__meta">
                  Escrutínio: <?= (int)($e['elected_in_scrutiny'] ?? 0) ?> · Votos: <strong><?= (int)($e['votes'] ?? 0) ?></strong>
                </div>
              </div>
              <span class="pill <?= $rulePill ?>"><?= htmlspecialchars((string)($e['rule'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if (!empty($pendingVoters)): ?>
      <section class="card">
        <div class="card__header">
          <div>
            <h2 class="card__title-sm">Ainda Não Votaram</h2>
          </div>
          <span class="pill pill--amber"><?= count($pendingVoters) ?> pendentes</span>
        </div>
        <div class="card card--tinted">
          <ul class="pending-voters">
            <?php foreach($pendingVoters as $name): ?>
              <li><?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    <?php endif; ?>

    <div class="app-footer">Gerenciamento de assembleia · Coninfoms Eleição · <?= date('Y') ?></div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
  <script>
    (function(){
      document.querySelectorAll("[data-toggle-inline]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var sel = btn.getAttribute("data-toggle-inline");
          if (!sel) return;
          var el = document.querySelector(sel);
          if (!el) return;
          if (el.classList.contains('hidden')) el.classList.remove('hidden');
          else el.classList.add('hidden');
        });
      });
    })();
  </script>
</body>
</html>
