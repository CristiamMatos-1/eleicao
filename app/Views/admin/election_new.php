<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nova Eleição</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Painel Administrativo';
    $navLinks = [
      ['label' => 'Início', 'href' => $baseUrl . '/admin'],
      ['label' => 'Nova eleição', 'href' => $baseUrl . '/admin/elections/new'],
    ];
    $activePath = '/admin/elections/new';
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
  <main class="wrap">
    <div class="toolbar">
      <div class="page-title">
        <h1>Nova Eleição</h1>
        <p class="muted" style="margin-top:2px;">Crie uma nova assembleia eleitoral</p>
      </div>
      <div class="page-actions">
        <a class="btn btn--secondary" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">
          ← Voltar
        </a>
      </div>
    </div>

    <?php if (!empty($flashMsg)): ?>
      <div class="alert <?= !empty($flashType) && $flashType === 'error' ? 'alert--error' : 'alert--info' ?>">
        <?= htmlspecialchars((string)$flashMsg, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

      <div class="cols-2" style="margin-bottom:18px;">
        <div class="card">
          <div class="card__header">
            <h3 style="margin:0;">Parâmetros da Eleição</h3>
            <span class="pill pill--blue">Obrigatório</span>
          </div>
          <div class="form-grid g-4">
            <div class="form-grid__cell form-grid__cell--2">
              <label>Tipo da Eleição</label>
              <select name="type" id="typeSelect" required>
                <option value="PASTOR">Pastor (Aprovação / Consentimento)</option>
                <option value="OFICIAIS">Presbíteros / Diáconos (Escolha Múltipla)</option>
                <option value="DIRETORIA">Diretoria / Deputados (Escolha Múltipla)</option>
                <option value="SOCIEDADES">Sociedades (Maioria Absoluta 50%+1)</option>
              </select>
            </div>

            <div class="form-grid__cell form-grid__cell--2">
              <label>Título</label>
              <input name="title" required maxlength="160" placeholder="Ex: Eleição Pastoral 2026 — Assembleia Extraordinária">
            </div>

            <div class="form-grid__cell">
              <label>Data</label>
              <input type="date" name="election_date" required>
            </div>

            <div class="form-grid__cell">
              <label>Eleitores Esperados</label>
              <input type="number" name="expected_voters" required min="1" placeholder="Ex: 200">
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card__header">
            <h3 style="margin:0;">Fluxo de Trabalho</h3>
            <span class="pill pill--amber">3 Fases</span>
          </div>
          <div style="display:flex; flex-direction:column; gap:12px;">
            <div class="workflow-banner wf--presence" style="margin:0; padding:12px 14px;">
              <strong style="display:block; margin-bottom:2px;">Fase 1 — Presença</strong>
              <span class="muted" style="font-size:13px;">Eleitores confirmam presença; voto bloqueado.</span>
            </div>
            <div class="workflow-banner wf--voting" style="margin:0; padding:12px 14px;">
              <strong style="display:block; margin-bottom:2px;">Fase 2 — Votação</strong>
              <span class="muted" style="font-size:13px;">Escrutínio(s) sucessivos até maioria absoluta.</span>
            </div>
            <div class="workflow-banner wf--closed" style="margin:0; padding:12px 14px;">
              <strong style="display:block; margin-bottom:2px;">Fase 3 — Encerrada</strong>
              <span class="muted" style="font-size:13px;">Relatórios PDF, apuração final e resultados.</span>
            </div>
          </div>
        </div>
      </div>

      <div id="officersFields" class="card" style="display:none; margin-bottom:18px;">
        <div class="card__header">
          <h3 style="margin:0;">Oficiais / Diretoria / Sociedades</h3>
          <span class="pill pill--teal">Múltiplos Candidatos</span>
        </div>
        <div class="form-grid g-4">
          <div class="form-grid__cell form-grid__cell--2">
            <label>Vagas em disputa</label>
            <input type="number" name="vacancies" id="vacanciesInput" min="1">
            <div class="muted" style="margin-top:4px; font-size:13px;">
              Para <strong>SOCIEDADES</strong>, o valor padrão é 1 vaga (escolha majoritária 50%+1).
            </div>
          </div>
          <div class="form-grid__cell form-grid__cell--2">
            <label>Candidatos <span class="muted" style="font-weight:400;">(um nome por linha)</span></label>
            <textarea name="candidates_bulk" rows="8" placeholder="João da Silva&#10;Maria Souza&#10;José Pereira"></textarea>
          </div>
        </div>
      </div>

      <div id="pastorFields" class="card" style="margin-bottom:18px;">
        <div class="card__header">
          <h3 style="margin:0;">Candidato a Pastor</h3>
          <span class="pill pill--green">Aprovação SIM / NÃO / BRANCO</span>
        </div>
        <div class="form-grid g-4">
          <div class="form-grid__cell form-grid__cell--2">
            <label>Nome Completo</label>
            <input name="pastor_full_name" maxlength="160" placeholder="Ex: Pr. João Carlos da Silva">
          </div>
          <div class="form-grid__cell">
            <label>Função / Cargo</label>
            <input name="pastor_role_title" maxlength="120" placeholder="Ex: Pastor Titular">
          </div>
          <div class="form-grid__cell">
            <label>Mandato (anos)</label>
            <select name="pastor_term_years">
              <option value="1">1 ano</option>
              <option value="2">2 anos</option>
              <option value="3" selected>3 anos</option>
              <option value="4">4 anos</option>
              <option value="5">5 anos</option>
            </select>
          </div>
          <div class="form-grid__cell form-grid__cell--2">
            <label>Foto do Candidato <span class="muted" style="font-weight:400;">(opcional)</span></label>
            <input type="file" name="pastor_photo" accept="image/*" class="file-input">
          </div>
        </div>
      </div>

      <div class="card">
        <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-start;">
          <button type="submit" class="btn btn--primary btn--lg" data-confirm="Criar esta nova eleição com os parâmetros informados?">
            Criar Eleição →
          </button>
          <a class="muted" style="text-decoration:none; font-size:13px;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">
            Cancelar e voltar para o painel
          </a>
        </div>
      </div>
    </form>

    <script>
      (function() {
        var type = document.getElementById('typeSelect');
        var officers = document.getElementById('officersFields');
        var pastor = document.getElementById('pastorFields');
        var vi = document.getElementById('vacanciesInput');

        function sync() {
          var v = type.value;
          if (v === 'OFICIAIS' || v === 'DIRETORIA') {
            officers.style.display = '';
            pastor.style.display = 'none';
            vi.required = true;
          } else if (v === 'SOCIEDADES') {
            officers.style.display = '';
            pastor.style.display = 'none';
            vi.required = true;
            vi.value = 1;
          } else {
            officers.style.display = 'none';
            pastor.style.display = '';
            vi.required = false;
          }
        }

        type.addEventListener('change', sync);
        sync();
      })();
    </script>
    <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
