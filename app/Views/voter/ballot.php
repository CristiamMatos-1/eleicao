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
  <main class="wrap">
    <section class="card">
      <div class="row">
        <h1><?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="secondary">Sair</button>
        </form>
      </div>

      <div class="pill">Escrutínio <?= (int)$scrutiny['number'] ?></div>

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

          <div class="muted">Selecione o seu voto:</div>
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

          <?php if ($election['type'] === 'SOCIEDADES'): ?>
            <div class="muted">Selecione apenas <strong>1</strong> candidato. O vencedor será aquele que atingir 50%+1 dos votos.</div>
          <?php else: ?>
            <div class="muted">Você pode escolher até <strong><?= (int)$election['vacancies'] ?></strong> candidato(s).</div>
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

      <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/dashboard.php?key=">Painel público (cole a chave)</a>
    </section>
  </main>
</body>
</html>