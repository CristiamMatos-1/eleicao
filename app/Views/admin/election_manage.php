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
    $brandLetter = 'C';
    $navLinks = [
      ['label' => 'Início', 'href' => $baseUrl . '/admin'],
      ['label' => 'Voltar', 'href' => $baseUrl . '/admin'],
    ];
    ob_start();
  ?>
    <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" class="btn secondary btn-sm">← Painel</a>
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
        <span class="crumbs"><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">Painel</a> · Assembleia</span>
        <h1 style="margin-top:4px;"><?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?></h1>
      </div>
      <div class="page-actions">
        <?php
          $status = (string)($election['status'] ?? '');
          $statusMap = [
            'aberta_para_presenca' => ['Fase de Presença', 'pill--amber'],
            'aberta_para_votacao'  => ['Votação Aberta', 'pill--blue'],
            'encerrada'            => ['Encerrada', 'pill--teal'],
            'OPEN'                 => ['Aberta (Legado)', 'pill--blue'],
            'CLOSED'               => ['Encerrada (Legado)', 'pill--gray'],
          ];
          [$statusLabel, $pillClass] = $statusMap[$status] ?? ['Status desconhecido', 'pill--gray'];
        ?>
        <span class="pill <?= $pillClass ?>"><span class="pill-dot" aria-hidden="true"></span> <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>

    <section class="card" style="margin-bottom:18px;">
      <div class="section-title">
        <h2>Workflow da Assembleia</h2>
        <span class="muted">Controle as fases de presença, votação e encerramento</span>
      </div>

      <div class="cols-3" style="margin-bottom:14px;">
        <div class="box box-warn">
          <div class="box-heading"><h4>Fase 1 · Presença</h4></div>
          <div class="muted" style="font-size:0.88rem; margin-bottom:10px;">
            Libere a entrada, confirme presença dos eleitores e automatize a abertura do cadastro.
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" data-confirm="Deseja abrir a fase de presença agora?">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="status" value="presenca">
            <button type="submit" class="btn btn-block <?= $status === 'aberta_para_presenca' ? 'warn' : 'secondary' ?>" <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'disabled' : '' ?>>
              <?= $status === 'aberta_para_presenca' ? 'Atualmente · Presença' : 'Abrir para Presença' ?>
            </button>
          </form>
        </div>
        <div class="box box-info">
          <div class="box-heading"><h4>Fase 2 · Votação</h4></div>
          <div class="muted" style="font-size:0.88rem; margin-bottom:10px;">
            Libere a cédula para todos os eleitores com presença confirmada.
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" data-confirm="Deseja abrir a votação agora?">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="status" value="votacao">
            <button type="submit" class="btn btn-block <?= $status === 'aberta_para_votacao' ? '' : 'secondary' ?>" <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'disabled' : '' ?>>
              <?= $status === 'aberta_para_votacao' ? 'Atualmente · Votação' : 'Abrir para Votação' ?>
            </button>
          </form>
        </div>
        <div class="box box-danger">
          <div class="box-heading"><h4>Fase 3 · Encerrar</h4></div>
          <div class="muted" style="font-size:0.88rem; margin-bottom:10px;">
            Encerra a assembleia, fecha todos os escrutínios abertos e trava presenças e votos.
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" data-confirm="Tem certeza que deseja encerrar esta eleição? Após encerrada, presenças e votos não podem ser mais inseridos.">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="status" value="encerrada">
            <button type="submit" class="btn btn-block <?= ($status === 'encerrada' || $status === 'CLOSED') ? '' : 'danger secondary' ?>" <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'disabled' : '' ?>>
              <?= ($status === 'encerrada' || $status === 'CLOSED') ? 'Assembleia Encerrada' : 'Encerrar Eleição' ?>
            </button>
          </form>
        </div>
      </div>

      <div class="cols-2" style="margin-bottom:10px;">
        <div class="box box-info">
          <div class="box-heading"><h4>Lista de Presença</h4></div>
          <div class="muted" style="font-size:0.88rem; margin-bottom:10px;">
            Veja quem confirmou presença, registre manualmente ou imprima.
          </div>
          <div class="row row--tight">
            <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn">Ver Lista Completa</a>
            <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn secondary">Exportar PDF ↓</a>
          </div>
        </div>
        <div class="box box-soft">
          <div class="box-heading"><h4>Painel Público de Apuração</h4></div>
          <div class="muted" style="font-size:0.88rem; margin-bottom:8px;">Compartilhe este link com a comunidade:</div>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <code style="flex:1; min-width:240px; background:#F3F4F6; border:1px solid var(--brand-border); padding:10px 12px; border-radius:var(--radius-sm); font-size:0.82rem; word-break:break-all; color:var(--brand-text);"><?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars($election['public_key'], ENT_QUOTES, 'UTF-8') ?></code>
            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars($election['public_key'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn ghost">Abrir ↗</a>
          </div>
        </div>
      </div>

      <?php if (in_array($status, ['OPEN','aberta_para_presenca','aberta_para_votacao'], true)): ?>
        <div class="box">
          <div class="box-heading"><h4>Parâmetros de encerramento automático</h4></div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/config/edit" class="form" style="margin-top:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <div class="row" style="align-items:flex-end; gap:12px;">
              <div class="field" style="flex:1; min-width:220px;">
                <label>Número de eleitores esperados</label>
                <input type="number" name="expected_voters" value="<?= (int)$election['expected_voters'] ?>" min="1" required style="max-width:180px;">
                <span class="hint">Usado para cálculo de quórum e encerramento automático.</span>
              </div>
              <button type="submit" class="btn">Atualizar</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($election['type'] === 'DIRETORIA'): ?>
      <section class="card" style="margin-bottom:18px;">
        <div class="section-title">
          <h2>Credenciamento de Deputados</h2>
          <span class="muted">Eleitores e candidatos autorizados</span>
        </div>
        <div class="cols-2">
          <div class="box box-success">
            <div class="box-heading">
              <h4>Credenciados <span class="pill pill--green" style="margin-left:8px;"><?= count($accreditedVoters) ?></span></h4>
              <div>
                <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn ghost btn-sm">Lista de Presença</a>
                <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn secondary btn-sm">PDF</a>
              </div>
            </div>
            <div style="max-height: 240px; overflow-y:auto;">
              <?php if (empty($accreditedVoters)): ?>
                <div class="muted">Nenhum eleitor credenciado.</div>
              <?php else: ?>
                <div class="list">
                  <?php foreach($accreditedVoters as $v): ?>
                    <div class="row itemRow" style="padding:8px 10px; border-radius:var(--radius-md);">
                      <div style="min-width:0; flex:1;">
                        <div style="font-weight:600; font-size:0.94rem;"><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/accreditation/toggle" style="margin:0;" data-confirm="Remover credenciamento deste eleitor?">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$v['id'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn secondary btn-sm" style="color:var(--brand-danger); border-color:rgba(220,38,38,0.28);">Remover</button>
                      </form>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="box">
            <div class="box-heading">
              <h4>Não credenciados <span class="pill pill--gray" style="margin-left:8px;"><?= count($unaccreditedVoters) ?></span></h4>
            </div>
            <div style="max-height: 240px; overflow-y:auto;">
              <?php if (empty($unaccreditedVoters)): ?>
                <div class="muted">Todos os eleitores ativos estão credenciados.</div>
              <?php else: ?>
                <div class="list">
                  <?php foreach($unaccreditedVoters as $v): ?>
                    <div class="row itemRow" style="padding:8px 10px; border-radius:var(--radius-md);">
                      <div style="min-width:0; flex:1;">
                        <div style="font-weight:500; font-size:0.94rem; color:var(--brand-muted);"><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/accreditation/toggle" style="margin:0;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$v['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn success btn-sm">Credenciar</button>
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

    <section class="card" style="margin-bottom:18px;">
      <div class="section-title">
        <h2>Escrutínios e Apuração Atual</h2>
        <?php if ($election['status'] === 'OPEN' && in_array($election['type'], ['OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true) && (!empty($scrutiniums) && $scrutiniums[0]['status'] === 'CLOSED')): ?>
          <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/next?id=<?= (int)$election['id'] ?>" class="btn">+ Abrir Próximo Escrutínio</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($voteCounts)):
        $currentScrutinyExpected = !empty($scrutiniums) ? (int)$scrutiniums[0]['expected_voters'] : (int)$election['expected_voters'];
        $quorum = intdiv($currentScrutinyExpected, 2) + 1;
      ?>
        <div class="box box-info" style="margin-bottom:12px;">
          <div class="box-heading">
            <h4>Apuração do Escrutínio Atual</h4>
            <span class="pill pill--blue">Quórum: <?= (int)$quorum ?> voto(s)</span>
          </div>
          <div class="cols-2">
            <?php foreach($voteCounts as $vc):
              $isElectedLive = ($vc['name'] !== 'BRANCOS' && (int)$vc['votes'] >= $quorum);
            ?>
              <div class="card-flat" style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 14px; border-radius:var(--radius-md);">
                <div style="min-width:0;">
                  <div style="font-weight:700;"><?= htmlspecialchars((string)$vc['name'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if($isElectedLive): ?>
                    <span class="pill pill--green" style="margin-top:4px; font-size:0.72rem;"><span class="pill-dot"></span> ELEITO</span>
                  <?php endif; ?>
                </div>
                <div class="big" style="color:var(--brand-primary);"><?= (int)$vc['votes'] ?> voto(s)</div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Escrutínio</th>
              <th>Status</th>
              <th style="width:180px;">Votos / Esperados</th>
              <th style="width:160px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($scrutiniums as $s): ?>
              <tr>
                <td style="font-weight:700;">#<?= (int)$s['number'] ?></td>
                <td>
                  <?php
                    $sPill = ($s['status'] ?? '') === 'OPEN' ? 'pill--green' : 'pill--gray';
                  ?>
                  <span class="pill <?= $sPill ?>"><span class="pill-dot" aria-hidden="true"></span> <?= htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td><strong><?= (int)$s['vote_count'] ?></strong> / <?= (int)$s['expected_voters'] ?></td>
                <td style="text-align:right;">
                  <?php if ($s['status'] === 'OPEN'): ?>
                    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/reset" data-confirm="ATENÇÃO! Deseja zerar todos os votos deste escrutínio?">
                      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="scrutiny_id" value="<?= (int)$s['id'] ?>">
                      <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                      <button type="submit" class="btn secondary btn-sm" style="color:var(--brand-danger); border-color:rgba(220,38,38,0.28);">Zerar Votos</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card" style="margin-bottom:18px;">
      <div class="section-title">
        <h2>Candidatos</h2>
        <span class="muted">Editar, excluir ou adicionar novos</span>
      </div>
      <div class="list">
        <?php foreach ($candidates as $c):
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
          <div class="itemRow" style="margin-bottom:0;">
            <div class="row--start" style="display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap;">
              <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:240px;">
                <?php if (!empty($c['photo_path'])): ?>
                  <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($c['photo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?>" style="width:44px; height:44px; border-radius:var(--radius-md); object-fit:cover;">
                <?php else: ?>
                  <div class="candidate-photo" style="width:44px; height:44px; border-radius:var(--radius-md); font-size:0.95rem;"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div style="min-width:0;">
                  <div class="big" style="font-size:1rem;"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="row row--tight" style="margin-top:4px;">
                    <span class="pill" style="margin:0;"><?= htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                </div>
              </div>
              <div class="row row--tight" style="justify-content:flex-end; flex-wrap:wrap;">
                <button type="button" class="btn secondary btn-sm" data-toggle-inline="#editForm<?= (int)$c['id'] ?>">Editar</button>
                <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/delete" data-confirm="Excluir este candidato?" style="margin:0;">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="candidate_id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                  <button type="submit" class="btn secondary btn-sm" style="color:var(--brand-danger); border-color:rgba(220,38,38,0.28);">Excluir</button>
                </form>
              </div>
            </div>

            <div id="editForm<?= (int)$c['id'] ?>" class="box box-soft" style="display:none; margin-top:10px; border-radius:var(--radius-md);">
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/edit" enctype="multipart/form-data" class="form" style="margin-top:0;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="candidate_id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                <div class="form-grid">
                  <div class="g-6 field">
                    <label>Nome</label>
                    <input name="full_name" value="<?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="160">
                  </div>
                  <div class="g-6 field">
                    <label>Nova Foto (opcional)</label>
                    <input type="file" name="photo" accept="image/*" style="background:transparent; border:1px dashed var(--brand-border); padding:8px 10px;">
                  </div>
                  <div class="g-12" style="grid-column: span 12; display:flex; gap:8px; justify-content:flex-end;">
                    <button type="button" class="btn secondary" data-toggle-inline="#editForm<?= (int)$c['id'] ?>">Cancelar</button>
                    <button type="submit" class="btn">Salvar Alterações</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (in_array($election['type'], ['OFICIAIS', 'SOCIEDADES'], true)): ?>
        <div class="box box-info" style="margin-top:16px;">
          <div class="box-heading"><h4>Adicionar candidato</h4></div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/add" class="form" enctype="multipart/form-data" style="margin-top:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <div class="form-grid">
              <div class="g-6 field">
                <label>Nome completo</label>
                <input name="full_name" required maxlength="160" placeholder="Nome do candidato">
              </div>
              <div class="g-6 field">
                <label>Foto (Opcional)</label>
                <input type="file" name="photo" accept="image/*" style="background:transparent; border:1px dashed var(--brand-border); padding:8px 10px;">
              </div>
              <div class="g-12" style="grid-column: span 12; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn">Adicionar candidato</button>
              </div>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </section>

    <section class="card" style="margin-bottom:18px;">
      <div class="section-title">
        <h2>Eleitos</h2>
        <span class="muted">Resultados oficiais registrados</span>
      </div>
      <?php if (!$elected): ?>
        <div class="box box-soft">
          <div class="muted" style="font-weight:600;">Nenhum eleito registrado ainda.</div>
          <div style="font-size:0.9rem; color:var(--brand-muted); margin-top:4px;">Continue a apuração e os eleitos aparecerão aqui automaticamente.</div>
        </div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($elected as $e):
            $rulePill = ($e['rule'] ?? '') === 'MAIORIA' ? 'pill--blue' : 'pill--teal';
          ?>
            <div class="row itemRow">
              <div style="min-width:0; flex:1;">
                <div class="big"><?= htmlspecialchars($e['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted">
                  Escrutínio: <?= (int)$e['elected_in_scrutiny'] ?> · Votos: <strong><?= (int)($e['votes'] ?? 0) ?></strong>
                </div>
              </div>
              <span class="pill <?= $rulePill ?>"><?= htmlspecialchars($e['rule'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if (!empty($pendingVoters)): ?>
      <section class="card">
        <div class="section-title">
          <h2>Ainda Não Votaram</h2>
          <span class="pill pill--amber"><?= count($pendingVoters) ?> pendentes</span>
        </div>
        <div class="box">
          <ul style="margin:0; padding-left:20px; columns: 2; column-gap: 24px; font-size:0.92rem; color:var(--brand-muted);">
            <?php foreach($pendingVoters as $name): ?>
              <li style="padding:3px 0; break-inside: avoid;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    <?php endif; ?>

    <div class="footer-mini">Gerenciamento de assembleia · Coninfoms Eleição · <?= date('Y') ?></div>
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
          if (el.style.display === "none" || !el.style.display) el.style.display = "block";
          else el.style.display = "none";
        });
      });
    })();
  </script>
</body>
</html>
