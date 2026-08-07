<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cédula</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Área do Eleitor';
    $navLinks = [
      ['label' => 'Cédula', 'href' => $baseUrl . '/votar'],
      ['label' => 'Apuração', 'href' => $baseUrl . '/dashboard.php'],
    ];
    ob_start();
  ?>
  <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="secondary">Sair</button>
  </form>
  <?php
    $navActions = (string)ob_get_clean();
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
  <main class="wrap">
    <section class="card">
      <div class="row" style="flex-wrap:wrap; gap:10px;">
        <h1><?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="secondary">Sair</button>
        </form>
      </div>

      <?php
        $workflowStatus = (string)($workflowStatus ?? ($election['status'] ?? ''));
        $isPresencaPhase = ($workflowStatus === 'aberta_para_presenca');
        $isVotacaoPhase  = ($workflowStatus === 'aberta_para_votacao' || $workflowStatus === 'OPEN');
        $isLocked        = !empty($workflowLocked);
        $presenceOk      = !empty($presenceRegistered);
      ?>

      <?php if ($isPresencaPhase): ?>
        <div class="pill" style="background:#FFF3CD; color:#856404; margin-top:8px;">Fase de Presença — Aguardando abertura da votação</div>
      <?php elseif ($isVotacaoPhase): ?>
        <div class="pill" style="background:#D4EDDA; color:#155724; margin-top:8px;">Escrutínio <?= (int)$scrutiny['number'] ?> — Votação Aberta</div>
      <?php else: ?>
        <div class="pill" style="background:#F8D7DA; color:#721C24; margin-top:8px;">Eleição Encerrada</div>
      <?php endif; ?>

      <?php if ($isPresencaPhase): ?>
        <div class="box" style="margin-top:16px;">
          <?php if ($presenceOk): ?>
            <div style="padding:14px; background:#D4EDDA; color:#155724; border-radius:10px; font-weight:600; margin-bottom:10px;">
              Presença confirmada! Aguarde a abertura da votação.
            </div>
            <div class="muted">
              Olá, <strong><?= htmlspecialchars((string)($myName ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.
              CPF: <strong><?= htmlspecialchars((string)($myCpf ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.
              Quando a assembleia abrir para votação, a tela de voto aparecerá automaticamente aqui.
            </div>
          <?php else: ?>
            <h2 style="margin-top:0;">Confirmar Presença na Assembleia</h2>
            <div class="muted" style="margin-bottom:16px;">
              Antes de iniciar a votação, é necessário registrar sua presença. Clique no botão abaixo para confirmar.
            </div>
            <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/votar/presenca" class="form">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" style="padding:16px 20px; font-size:18px;">Confirmar Presença na Assembleia</button>
            </form>
          <?php endif; ?>
        </div>
      <?php elseif ($isVotacaoPhase): ?>

        <?php if ($election['type'] === 'PASTOR'): ?>
          <?php $cand = $candidates[0] ?? null; ?>
          <div class="box">
            <div class="muted">Candidato</div>
            <div style="display:flex; align-items:center; gap:15px; margin-top:10px;">
              <?php if (!empty($cand['photo_path'])): ?>
                <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($cand['photo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="Foto" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid var(--border);">
              <?php else: ?>
                <div style="width:80px; height:80px; border-radius:50%; background:var(--bg); display:flex; align-items:center; justify-content:center; border:1px solid var(--border); font-size:12px; color:var(--muted);">Sem Foto</div>
              <?php endif; ?>
              <div>
                <div class="big"><?= htmlspecialchars((string)($cand['full_name'] ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="muted"><?= htmlspecialchars((string)($cand['role_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
          </div>

          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/votar/pastor" class="form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="scrutiny_id" value="<?= (int)$scrutiny['id'] ?>">

            <label for="cpf_voto_p">CPF (confirme para votar)</label>
            <input id="cpf_voto_p" name="cpf" required inputmode="numeric" maxlength="14" placeholder="000.000.000-00" value="<?= htmlspecialchars((string)($myCpf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <div class="muted" style="margin-top:10px;">Selecione o seu voto:</div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin:10px 0;">
              <label style="cursor:pointer; text-align:center; padding:15px; border-radius:12px; border:1px solid var(--border); background:var(--card); display:block;">
                <input type="radio" name="choice" value="SIM" required style="display:none;">
                <span style="font-weight:700; font-size:16px;">SIM</span>
              </label>
              <label style="cursor:pointer; text-align:center; padding:15px; border-radius:12px; border:1px solid var(--border); background:var(--card); display:block;">
                <input type="radio" name="choice" value="NAO" style="display:none;">
                <span style="font-weight:700; font-size:16px;">NÃO</span>
              </label>
              <label style="cursor:pointer; text-align:center; padding:15px; border-radius:12px; border:1px solid var(--border); background:var(--card); display:block;">
                <input type="radio" name="choice" value="BRANCO" style="display:none;">
                <span style="font-weight:700; font-size:16px;">BRANCO</span>
              </label>
            </div>

            <style>
              input[type="radio"]:checked + span { color: var(--accent); }
              label:has(input[type="radio"]:checked) { border-color: var(--accent); background: rgba(13, 110, 253, 0.05) !important; }
            </style>

            <button type="submit" style="margin-top:15px;">Confirmar Voto</button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/votar/oficiais" class="form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
            <input type="hidden" name="scrutiny_id" value="<?= (int)$scrutiny['id'] ?>">

            <label for="cpf_voto_o">CPF (confirme para votar)</label>
            <input id="cpf_voto_o" name="cpf" required inputmode="numeric" maxlength="14" placeholder="000.000.000-00" value="<?= htmlspecialchars((string)($myCpf ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <?php if ($election['type'] === 'SOCIEDADES'): ?>
              <div class="muted" style="margin-top:8px;">Selecione apenas <strong>1</strong> candidato. O vencedor será aquele que atingir 50%+1 dos votos.</div>
            <?php else: ?>
              <div class="muted" style="margin-top:8px;">Você pode escolher até <strong><?= (int)$election['vacancies'] ?></strong> candidato(s).</div>
            <?php endif; ?>
            
            <div class="list" id="officersList">
              <?php foreach ($candidates as $c): ?>
                <label class="item" style="cursor:pointer; display:flex; align-items:center;">
                  <input type="checkbox" name="candidate_ids[]" value="<?= (int)$c['id'] ?>" class="candidate-cb" style="width:auto; flex-shrink:0;">
                  <?php if (!empty($c['photo_path'])): ?>
                    <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($c['photo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="Foto" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid var(--border); flex-shrink:0;">
                  <?php else: ?>
                    <div style="width:40px; height:40px; border-radius:50%; background:var(--bg); display:flex; align-items:center; justify-content:center; border:1px solid var(--border); font-size:10px; color:var(--muted); flex-shrink:0;">SF</div>
                  <?php endif; ?>
                  <span style="font-weight:500; font-size:16px; word-break:break-word; flex:1; line-height:1.2;"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                </label>
              <?php endforeach; ?>
            </div>

            <button type="submit" style="margin-top:15px;">Confirmar Voto</button>
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
        <div class="box" style="margin-top:16px; padding:18px; border:1px solid var(--border); border-radius:12px;">
          <h2 style="margin-top:0;">Eleição Encerrada</h2>
          <div class="muted">
            A votação desta assembleia foi encerrada. Acompanhe os resultados pelo painel de apuração.
          </div>
        </div>
      <?php endif; ?>

      <a class="link" style="margin-top:20px; display:inline-block;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=">Painel público (cole a chave)</a>
    </section>
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
