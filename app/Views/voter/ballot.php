<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cédula de Votação</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Área do Eleitor';
    $navLinks = [
      ['label' => 'Cédula', 'href' => $baseUrl . '/votar'],
      ['label' => 'Apuração', 'href' => $baseUrl . '/dashboard.php'],
    ];
    $activePath = '/votar';
    ob_start();
  ?>
  <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn--secondary btn--sm">Sair</button>
  </form>
  <?php
    $navActions = (string)ob_get_clean();
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
  <main class="wrap">
    <?php if (!empty($flashMsg)): ?>
      <div class="alert <?= !empty($flashType) && $flashType === 'error' ? 'alert--error' : 'alert--success' ?>">
        <?= htmlspecialchars((string)$flashMsg, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php
      $assemblyTypeRaw = (string)($election['assembly_type'] ?? 'ORDINARIA');
      $assemblyType = strtoupper($assemblyTypeRaw) === 'EXTRAORDINARIA' ? 'EXTRAORDINÁRIA' : 'ORDINÁRIA';
      $entityName   = trim((string)($election['entity_name'] ?? $election['church_legal_name'] ?? $election['church_name'] ?? ''));
      $rawDate = (string)($election['election_date'] ?? '');
      if ($rawDate !== '' && $rawDate !== '0000-00-00') {
          $dt = \DateTime::createFromFormat('Y-m-d', $rawDate);
          $formattedDate = $dt !== false ? $dt->format('d/m/Y') : '';
      } else {
          $formattedDate = '';
      }
    ?>
    <div class="ballot-header">
      <div>
        <div class="ballot-header__label">
          Assembleia Geral <?= htmlspecialchars($assemblyType, ENT_QUOTES, 'UTF-8') ?><?= $entityName !== '' ? ' do ' . htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8') : '' ?>
        </div>
        <h1 class="ballot-header__title"><?= htmlspecialchars((string)($election['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="ballot-header__meta">
          <?php if ($formattedDate !== ''): ?><?= htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') ?>&nbsp;·&nbsp;<?php endif; ?>
          Tipo: <?= htmlspecialchars((string)($election['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
      <?php if (!empty($myName)): ?>
        <div class="ballot-header__user">
          <div class="candidate-photo candidate-photo--sm" style="background:var(--brand-card); color:var(--brand-primary); border:2px solid rgba(255,255,255,.25);">
            <?php
              $initials = '';
              $nameClean = trim((string)$myName);
              if ($nameClean !== '') {
                  $parts = preg_split('/\s+/', $nameClean);
                  if (count($parts) >= 1) $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1));
                  if (count($parts) >= 2) $initials .= mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1));
              }
              echo htmlspecialchars($initials ?: 'U', ENT_QUOTES, 'UTF-8');
            ?>
          </div>
          <div style="line-height:1.25;">
            <div style="font-weight:600; color:#fff;"><?= htmlspecialchars((string)$myName, ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-size:12px; opacity:.85;">CPF: <?= htmlspecialchars((string)($myCpf ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php
      $workflowStatus = (string)($workflowStatus ?? ($election['status'] ?? ''));
      $isPresencaPhase = ($workflowStatus === 'aberta_para_presenca');
      $isVotacaoPhase  = ($workflowStatus === 'aberta_para_votacao' || $workflowStatus === 'OPEN');
      $isLocked        = !empty($workflowLocked);
      $presenceOk      = !empty($presenceRegistered);
    ?>

    <?php if ($isPresencaPhase): ?>
      <div class="workflow-banner wf--presence" style="margin-top:18px;">
        <strong>Fase de Presença</strong> — Aguardando abertura oficial da votação pela diretoria.
      </div>
    <?php elseif ($isVotacaoPhase): ?>
      <div class="workflow-banner wf--voting" style="margin-top:18px;">
        <strong>Escrutínio <?= (int)$scrutiny['number'] ?></strong> — Votação aberta. Vote com consciência.
      </div>
    <?php else: ?>
      <div class="workflow-banner wf--closed" style="margin-top:18px;">
        <strong>Eleição Encerrada</strong> — Nenhuma nova votação será aceita.
      </div>
    <?php endif; ?>

    <?php if ($isPresencaPhase): ?>
      <div class="card" style="margin-top:18px;">
        <?php if ($presenceOk): ?>
          <div class="alert alert--success" style="margin-bottom:14px;">
            Presença confirmada! Aguarde a abertura da votação.
          </div>
          <div class="muted" style="padding:4px 4px 10px 4px;">
            Olá, <strong><?= htmlspecialchars((string)($myName ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.
            Quando a assembleia abrir para votação, a tela de voto aparecerá automaticamente aqui.
            Você pode atualizar esta página em alguns instantes.
          </div>
        <?php else: ?>
          <div class="card__header">
            <h2 style="margin:0;">Confirmar Presença na Assembleia</h2>
          </div>
          <div class="muted" style="margin:6px 0 18px 0;">
            Antes de iniciar a votação, é necessário registrar sua presença.
            Clique no botão abaixo para confirmar sua participação.
          </div>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/votar/presenca">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn--primary btn--lg" style="width:100%; padding:18px 20px; font-size:17px;" data-confirm="Confirmar sua presença nesta assembleia?">
              Confirmar Presença na Assembleia
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php elseif ($isVotacaoPhase): ?>

      <?php if ($election['type'] === 'PASTOR'): ?>
        <?php $cand = $candidates[0] ?? null; ?>
        <div class="card" style="margin-top:18px;">
          <div class="card__header">
            <h2 style="margin:0;">Candidato</h2>
            <span class="pill pill--teal">Voto de Consentimento</span>
          </div>
          <div style="display:flex; align-items:center; gap:16px; padding:8px 4px 10px 4px;">
            <?php if (!empty($cand['photo_path'])): ?>
              <img
                class="candidate-photo candidate-photo--lg"
                src="<?= htmlspecialchars($baseUrl . '/' . ltrim($cand['photo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                alt="Foto de <?= htmlspecialchars((string)($cand['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              >
            <?php else: ?>
              <div class="candidate-photo candidate-photo--lg">
                <?php
                  $fn = trim((string)($cand['full_name'] ?? ''));
                  $ini = '';
                  if ($fn !== '') {
                      $p = preg_split('/\s+/', $fn);
                      if (count($p) >= 1) $ini .= mb_strtoupper(mb_substr($p[0], 0, 1));
                      if (count($p) >= 2) $ini .= mb_strtoupper(mb_substr($p[count($p) - 1], 0, 1));
                  }
                  echo htmlspecialchars($ini ?: 'SF', ENT_QUOTES, 'UTF-8');
                ?>
              </div>
            <?php endif; ?>
            <div style="flex:1; min-width:0;">
              <div style="font-size:20px; font-weight:700; color:var(--brand-text); line-height:1.2;">
                <?= htmlspecialchars((string)($cand['full_name'] ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div class="muted" style="margin-top:4px;">
                <?= htmlspecialchars((string)($cand['role_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </div>
            </div>
          </div>
        </div>

        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/votar/pastor" style="margin-top:18px;">
          <div class="card">
            <div class="card__header">
              <h2 style="margin:0;">Seu Voto</h2>
            </div>

            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="scrutiny_id" value="<?= (int)$scrutiny['id'] ?>">

            <div style="margin-bottom:18px;">
              <label for="cpf_voto_p">CPF (confirme para votar)</label>
              <input id="cpf_voto_p" type="text" name="cpf" required inputmode="numeric" maxlength="14" placeholder="000.000.000-00" data-mask="cpf" value="<?= htmlspecialchars((string)($myCpf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="muted" style="margin-bottom:12px; font-weight:500; color:var(--brand-text);">Selecione a sua opção:</div>
            <div class="cols-3" style="gap:12px;">
              <label class="candidate-card" style="cursor:pointer; text-align:center; padding:22px 14px;">
                <input type="radio" name="choice" value="SIM" required style="position:absolute; opacity:0; pointer-events:none;">
                <div style="font-size:28px; font-weight:800; color:var(--brand-success); letter-spacing:1px;">SIM</div>
                <div class="muted" style="margin-top:6px; font-size:13px;">Aprovar o candidato</div>
              </label>
              <label class="candidate-card" style="cursor:pointer; text-align:center; padding:22px 14px;">
                <input type="radio" name="choice" value="NAO" style="position:absolute; opacity:0; pointer-events:none;">
                <div style="font-size:28px; font-weight:800; color:var(--brand-danger); letter-spacing:1px;">NÃO</div>
                <div class="muted" style="margin-top:6px; font-size:13px;">Rejeitar o candidato</div>
              </label>
              <label class="candidate-card" style="cursor:pointer; text-align:center; padding:22px 14px;">
                <input type="radio" name="choice" value="BRANCO" style="position:absolute; opacity:0; pointer-events:none;">
                <div style="font-size:28px; font-weight:800; color:var(--brand-muted); letter-spacing:1px;">BRANCO</div>
                <div class="muted" style="margin-top:6px; font-size:13px;">Não opinar</div>
              </label>
            </div>

            <button type="submit" class="btn btn--primary btn--lg" style="width:100%; margin-top:22px; padding:16px 20px; font-size:16px;" data-confirm="Confirmar seu voto? Esta ação é irreversível.">
              Confirmar Voto
            </button>
          </div>
        </form>
      <?php else: ?>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/votar/oficiais" style="margin-top:18px;">
          <div class="card">
            <div class="card__header">
              <h2 style="margin:0;">Candidatos em Votação</h2>
              <span class="pill pill--blue">
                <?= $election['type'] === 'SOCIEDADES' ? 'Até 1 candidato' : 'Até ' . (int)$election['vacancies'] . ' candidato(s)' ?>
              </span>
            </div>

            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="scrutiny_id" value="<?= (int)$scrutiny['id'] ?>">

            <div style="margin-bottom:18px;">
              <label for="cpf_voto_o">CPF (confirme para votar)</label>
              <input id="cpf_voto_o" type="text" name="cpf" required inputmode="numeric" maxlength="14" placeholder="000.000.000-00" data-mask="cpf" value="<?= htmlspecialchars((string)($myCpf ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <?php if ($election['type'] === 'SOCIEDADES'): ?>
              <div class="alert alert--info" style="margin-bottom:16px;">
                Selecione apenas <strong>1</strong> candidato. O vencedor será aquele que atingir 50%+1 dos votos válidos.
              </div>
            <?php else: ?>
              <div class="alert alert--info" style="margin-bottom:16px;">
                Você pode escolher até <strong><?= (int)$election['vacancies'] ?></strong> candidato(s). Toque no cartão para marcar/desmarcar.
              </div>
            <?php endif; ?>

            <div id="officersList" style="display:grid; grid-template-columns:1fr; gap:10px;">
              <?php foreach ($candidates as $c): ?>
                <label class="candidate-card" style="cursor:pointer; display:flex; align-items:center; gap:14px; padding:14px 16px;">
                  <input
                    type="checkbox"
                    name="candidate_ids[]"
                    value="<?= (int)$c['id'] ?>"
                    class="candidate-cb"
                    style="width:22px; height:22px; flex-shrink:0; accent-color:var(--brand-primary);"
                  >
                  <?php if (!empty($c['photo_path'])): ?>
                    <img
                      class="candidate-photo candidate-photo--md"
                      src="<?= htmlspecialchars($baseUrl . '/' . ltrim($c['photo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                      alt="Foto de <?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                  <?php else: ?>
                    <div class="candidate-photo candidate-photo--md">
                      <?php
                        $fn2 = trim((string)($c['full_name'] ?? ''));
                        $ini2 = '';
                        if ($fn2 !== '') {
                            $p2 = preg_split('/\s+/', $fn2);
                            if (count($p2) >= 1) $ini2 .= mb_strtoupper(mb_substr($p2[0], 0, 1));
                            if (count($p2) >= 2) $ini2 .= mb_strtoupper(mb_substr($p2[count($p2) - 1], 0, 1));
                        }
                        echo htmlspecialchars($ini2 ?: 'SF', ENT_QUOTES, 'UTF-8');
                      ?>
                    </div>
                  <?php endif; ?>
                  <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; color:var(--brand-text); font-size:16px; line-height:1.25;">
                      <?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php if (!empty($c['role_title'])): ?>
                      <div class="muted" style="font-size:13px; margin-top:2px;">
                        <?= htmlspecialchars($c['role_title'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn--primary btn--lg" style="width:100%; margin-top:22px; padding:16px 20px; font-size:16px;" data-confirm="Confirmar seu voto? Esta ação é irreversível.">
              Confirmar Voto
            </button>
          </div>
        </form>

        <script>
          const maxVacancies = <?= (int)$election['vacancies'] ?>;
          const checkboxes = document.querySelectorAll('.candidate-cb');
          checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
              const checkedCount = document.querySelectorAll('.candidate-cb:checked').length;
              if (checkedCount > maxVacancies) {
                this.checked = false;
                if (maxVacancies === 1) {
                  alert('Você só pode selecionar 1 candidato nesta eleição.');
                } else {
                  alert('Você só pode selecionar até ' + maxVacancies + ' candidato(s). Desmarque alguém antes de escolher outro.');
                }
              }
            });
          });
        </script>
      <?php endif; ?>
    <?php else: ?>
      <div class="card" style="margin-top:18px;">
        <div class="card__header">
          <h2 style="margin:0;">Eleição Encerrada</h2>
        </div>
        <div class="muted">
          A votação desta assembleia foi encerrada. Acompanhe os resultados pelo painel de apuração.
        </div>
      </div>
    <?php endif; ?>

    <div style="margin-top:20px; text-align:center;">
      <a class="muted" style="text-decoration:none; font-size:14px;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php">
        Ver painel público de apuração →
      </a>
    </div>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
  <script>
    (function() {
      function fmt(v) {
        v = (v || '').replace(/\D/g, '').slice(0, 11);
        if (v.length >= 9) v = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{0,2})$/, '$1.$2.$3-$4');
        else if (v.length >= 6) v = v.replace(/^(\d{3})(\d{3})(\d*)$/, '$1.$2.$3');
        else if (v.length >= 3) v = v.replace(/^(\d{3})(\d*)$/, '$1.$2');
        return v;
      }
      const cpfInputs = document.querySelectorAll('input[name="cpf"]');
      cpfInputs.forEach(function(el) {
        el.value = fmt(el.value);
        el.addEventListener('input', function(e) { e.target.value = fmt(e.target.value); });
        el.addEventListener('paste', function(e) {
          setTimeout(function() { el.value = fmt(el.value); }, 0);
        });
      });
    })();
  </script>
</body>
</html>
