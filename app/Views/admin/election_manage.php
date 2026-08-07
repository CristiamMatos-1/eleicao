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
    $navLinks = [
      ['label' => 'Início', 'href' => $baseUrl . '/admin'],
      ['label' => 'Voltar', 'href' => $baseUrl . '/admin'],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
  <main class="wrap">
    <section class="card">
      <div class="row">
        <h1><?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">Voltar</a>
      </div>

      <div class="box">
        <div class="row" style="align-items:flex-start; flex-wrap:wrap; gap:16px;">
          <div>
            <div class="muted">Status do Workflow</div>
            <?php
              $status = (string)($election['status'] ?? '');
              $statusMap = [
                'aberta_para_presenca' => ['Aberta para Presença', 'background:#FFF3CD; color:#856404;'],
                'aberta_para_votacao'  => ['Aberta para Votação', 'background:#D4EDDA; color:#155724;'],
                'encerrada'            => ['Encerrada',          'background:#F8D7DA; color:#721C24;'],
                'OPEN'                 => ['Aberta (Legado)',     'background:#D1ECF1; color:#0C5460;'],
                'CLOSED'               => ['Encerrada (Legado)',  'background:#F8D7DA; color:#721C24;'],
              ];
              [$statusLabel, $statusStyle] = $statusMap[$status] ?? ['Status desconhecido', ''];
            ?>
            <div class="big" style="<?= $statusStyle ?> display:inline-block; padding:6px 12px; border-radius:999px; margin-top:4px;">
              <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <?php if ($status !== 'encerrada' && $status !== 'CLOSED'): ?>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" onsubmit="return confirm('Deseja abrir a fase de presença?');" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                <input type="hidden" name="status" value="presenca">
                <button type="submit" class="secondary" style="white-space:nowrap;">Abrir para Presença</button>
              </form>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" onsubmit="return confirm('Deseja abrir a votação agora?');" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                <input type="hidden" name="status" value="votacao">
                <button type="submit" class="secondary" style="white-space:nowrap;">Abrir para Votação</button>
              </form>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/workflow/status" onsubmit="return confirm('Tem certeza que deseja encerrar esta eleição? Após encerrada, presenças e votos não podem ser mais inseridos.');" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                <input type="hidden" name="status" value="encerrada">
                <button type="submit" class="secondary" style="color:var(--danger); border-color:rgba(255,77,79,0.3); white-space:nowrap;">Encerrar Eleição</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:16px; flex-wrap:wrap;">
          <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" class="secondary" style="display:inline-block; text-decoration:none; padding:10px 14px; border:1px solid var(--accent); border-radius:8px; color:var(--accent); font-weight:bold;">Lista de Presença</a>
          <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" class="secondary" style="display:inline-block; text-decoration:none; padding:10px 14px; border:1px solid var(--border); border-radius:8px;">Exportar / Imprimir PDF</a>
        </div>

        <div class="muted" style="margin-top:14px">Painel público</div>
        <div class="row">
          <div class="big" style="font-size:14px;word-break:break-all">
            <?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=<?= htmlspecialchars($election['public_key'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
        
        <?php if (in_array($status, ['OPEN','aberta_para_presenca','aberta_para_votacao'], true)): ?>
          <hr style="border:0; border-top:1px solid var(--border); margin:15px 0;">
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/config/edit" class="form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <label>Número de eleitores esperados (para encerramento automático)</label>
            <div style="display:flex; gap:10px;">
              <input type="number" name="expected_voters" value="<?= (int)$election['expected_voters'] ?>" min="1" required style="max-width:150px;">
              <button type="submit" class="secondary">Atualizar</button>
            </div>
          </form>
        <?php endif; ?>
      </div>

      <?php if ($election['type'] === 'DIRETORIA'): ?>
        <h2>Credenciamento de Deputados (Eleitores & Candidatos)</h2>
        
        <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px;">
          <!-- Credenciados -->
          <div class="box" style="flex:1; min-width:300px;">
            <h3>Credenciados (<?= count($accreditedVoters) ?>)</h3>
            <div style="max-height: 200px; overflow-y: auto;">
              <?php if (empty($accreditedVoters)): ?>
                <div class="muted">Nenhum eleitor credenciado.</div>
              <?php else: ?>
                <ul style="margin:0; padding-left:20px; font-size:14px; color:var(--text);">
                  <?php foreach($accreditedVoters as $v): ?>
                    <li style="margin-bottom:5px;">
                      <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/accreditation/toggle" style="margin:0;">
                          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                          <input type="hidden" name="user_id" value="<?= (int)$v['id'] ?>">
                          <input type="hidden" name="action" value="remove">
                          <button type="submit" class="secondary" style="padding:2px 6px; font-size:11px; color:var(--danger); border-color:rgba(255,77,79,0.3);">Remover</button>
                        </form>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
            <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
              <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="secondary" style="display:inline-block; text-decoration:none; padding:8px 12px; border:1px solid var(--accent); border-radius:8px; color:var(--accent); font-weight:bold; font-size:13px;">Lista de Presença</a>
              <a href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="secondary" style="display:inline-block; text-decoration:none; padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px;">Exportar PDF</a>
            </div>
          </div>

          <!-- Não Credenciados -->
          <div class="box" style="flex:1; min-width:300px;">
            <h3>Não Credenciados (<?= count($unaccreditedVoters) ?>)</h3>
            <div style="max-height: 200px; overflow-y: auto;">
              <?php if (empty($unaccreditedVoters)): ?>
                <div class="muted">Todos os eleitores ativos estão credenciados.</div>
              <?php else: ?>
                <ul style="margin:0; padding-left:20px; font-size:14px; color:var(--muted);">
                  <?php foreach($unaccreditedVoters as $v): ?>
                    <li style="margin-bottom:5px;">
                      <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/accreditation/toggle" style="margin:0;">
                          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                          <input type="hidden" name="user_id" value="<?= (int)$v['id'] ?>">
                          <input type="hidden" name="action" value="add">
                          <button type="submit" style="padding:2px 6px; font-size:11px;">Credenciar</button>
                        </form>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <h2>Escrutínios e Apuração Atual</h2>
      <?php if ($election['status'] === 'OPEN' && in_array($election['type'], ['OFICIAIS', 'DIRETORIA', 'SOCIEDADES'], true) && (!empty($scrutiniums) && $scrutiniums[0]['status'] === 'CLOSED')): ?>
        <div style="margin-bottom:15px;">
          <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/next?id=<?= (int)$election['id'] ?>" class="secondary" style="display:inline-block; text-decoration:none; padding:10px 15px; border:1px solid var(--accent); border-radius:8px; color:var(--accent); font-weight:bold;">Abrir Próximo Escrutínio</a>
        </div>
      <?php endif; ?>

      <?php if (!empty($voteCounts)): ?>
        <?php 
          // Find the expected voters for the current scrutiny
          $currentScrutinyExpected = !empty($scrutiniums) ? (int)$scrutiniums[0]['expected_voters'] : (int)$election['expected_voters'];
          $quorum = intdiv($currentScrutinyExpected, 2) + 1; 
        ?>
        <div class="box" style="margin-bottom:15px; background:rgba(255,255,255,0.05); border-color:var(--accent);">
          <div class="muted">Apuração do Escrutínio Atual (Quórum: <strong><?= $quorum ?></strong> votos):</div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px;">
            <?php foreach($voteCounts as $vc): ?>
              <?php $isElectedLive = ($vc['name'] !== 'BRANCOS' && (int)$vc['votes'] >= $quorum); ?>
              <div style="padding:10px; background:rgba(0,0,0,0.2); border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                <span>
                  <?= htmlspecialchars((string)$vc['name'], ENT_QUOTES, 'UTF-8') ?>
                  <?php if($isElectedLive): ?>
                    <span style="font-size:10px; color:var(--ok); margin-left:5px; font-weight:bold;">[ELEITO]</span>
                  <?php endif; ?>
                </span>
                <strong><?= (int)$vc['votes'] ?> voto(s)</strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="list">
        <?php foreach ($scrutiniums as $s): ?>
          <div class="itemRow row">
            <div>
              <div class="big">#<?= (int)$s['number'] ?> — <?= htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="muted">Votos: <?= (int)$s['vote_count'] ?> / <?= (int)$s['expected_voters'] ?></div>
            </div>
            <?php if ($s['status'] === 'OPEN'): ?>
              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/reset" onsubmit="return confirm('ATENÇÃO! Deseja zerar todos os votos deste escrutínio?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="scrutiny_id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                <button type="submit" class="secondary" style="padding:4px 8px; font-size:12px; color:var(--danger); border-color:rgba(255,77,79,0.3);">Zerar Votos</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <h2>Candidatos (Editar / Excluir)</h2>
      <div class="list">
        <?php foreach ($candidates as $c): ?>
          <div class="itemRow row">
            <div style="display:flex; align-items:center; gap:10px;">
              <?php if (!empty($c['photo_path'])): ?>
                <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($c['photo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
              <?php else: ?>
                <div style="width:30px; height:30px; border-radius:50%; background:rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center; font-size:10px;">SF</div>
              <?php endif; ?>
              <div class="big"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
              <div class="pill" style="margin:0;"><?= htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8') ?></div>
              
              <button type="button" class="secondary" style="padding:4px 8px; font-size:12px;" onclick="document.getElementById('editForm<?= $c['id'] ?>').style.display='block';">Editar</button>

              <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/delete" onsubmit="return confirm('Excluir este candidato?');" style="margin:0;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="candidate_id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                <button type="submit" class="secondary" style="padding:4px 8px; font-size:12px; color:var(--danger); border-color:rgba(255,77,79,0.3);">Excluir</button>
              </form>
            </div>
          </div>
          
          <div id="editForm<?= $c['id'] ?>" class="box" style="display:none; margin-top:-10px; border-top-left-radius:0; border-top-right-radius:0; background:rgba(0,0,0,0.2);">
            <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/edit" enctype="multipart/form-data" class="form">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="candidate_id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; align-items:end;">
                <div>
                  <label>Nome</label>
                  <input name="full_name" value="<?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="160">
                </div>
                <div>
                  <label>Nova Foto (opcional)</label>
                  <input type="file" name="photo" accept="image/*" style="background:transparent; border:none; padding:0;">
                </div>
                <div style="grid-column: span 2; display:flex; gap:10px;">
                  <button type="submit" style="padding:6px 12px;">Salvar Alterações</button>
                  <button type="button" class="secondary" style="padding:6px 12px;" onclick="document.getElementById('editForm<?= $c['id'] ?>').style.display='none';">Cancelar</button>
                </div>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (in_array($election['type'], ['OFICIAIS', 'SOCIEDADES'], true)): ?>
        <h2>Adicionar candidato</h2>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/candidate/add" class="form" enctype="multipart/form-data">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
          <label>Nome completo</label>
          <input name="full_name" required maxlength="160">
          <label>Foto (Opcional)</label>
          <input type="file" name="photo" accept="image/*" style="background:transparent; border:none; padding:0;">
          <button type="submit">Adicionar</button>
        </form>
      <?php endif; ?>

      <h2>Eleitos</h2>
      <?php if (!$elected): ?>
        <div class="muted">Nenhum eleito registrado ainda.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($elected as $e): ?>
            <div class="itemRow row">
              <div>
                <div class="big"><?= htmlspecialchars($e['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted">
                  Escrutínio: <?= (int)$e['elected_in_scrutiny'] ?> | Votos: <?= (int)($e['votes'] ?? 0) ?>
                </div>
              </div>
              <div class="pill"><?= htmlspecialchars($e['rule'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($pendingVoters)): ?>
        <h2>Ainda Não Votaram no Escrutínio Atual</h2>
        <div class="box" style="max-height: 200px; overflow-y: auto;">
          <ul style="margin:0; padding-left:20px; font-size:14px; color:var(--muted);">
            <?php foreach($pendingVoters as $name): ?>
              <li><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

    </section>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>