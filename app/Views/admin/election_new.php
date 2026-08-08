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
        <span class="crumbs"><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" style="color:inherit; text-decoration:none;">Painel</a> · Nova assembleia</span>
        <h1>Nova Eleição</h1>
        <p class="muted page-subtitle">Crie uma nova assembleia eleitoral com seus parâmetros e candidatos</p>
      </div>
      <div class="page-actions">
        <a class="btn btn--secondary" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">
          &larr; Voltar
        </a>
      </div>
    </div>

    <?php if (!empty($flashMsg)): ?>
      <div class="alert <?= !empty($flashType) && $flashType === 'error' ? 'alert--error' : 'alert--info' ?>">
        <?= htmlspecialchars((string)$flashMsg, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections" enctype="multipart/form-data" class="form-stacked">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

      <div class="cols-2 layout-row">
        <section class="card">
          <div class="card__header">
            <div>
              <h3 class="card__title-sm">Parâmetros da Eleição</h3>
              <span class="card__hint">Dados obrigatórios da assembleia</span>
            </div>
            <span class="pill pill--blue">Obrigatório</span>
          </div>
          <div class="form-grid g-4">
            <div class="form-grid__cell form-grid__cell--6">
              <label for="typeSelect">Tipo da Eleição</label>
              <select name="type" id="typeSelect" required>
                <option value="PASTOR">Pastor (Aprovação / Consentimento)</option>
                <option value="OFICIAIS">Presbíteros / Diáconos (Escolha Múltipla)</option>
                <option value="DIRETORIA">Diretoria / Deputados (Escolha Múltipla)</option>
                <option value="SOCIEDADES">Sociedades (Maioria Absoluta 50%+1)</option>
              </select>
            </div>

            <div class="form-grid__cell form-grid__cell--6">
              <label for="titleInput">Título</label>
              <input id="titleInput" name="title" required maxlength="160" placeholder="Ex: Eleição Pastoral 2026 — Assembleia Extraordinária">
            </div>

            <div class="form-grid__cell form-grid__cell--3">
              <label for="assemblyTypeSelect">Natureza da Assembleia</label>
              <select name="assembly_type" id="assemblyTypeSelect" required>
                <option value="ORDINARIA" selected>Ordinária</option>
                <option value="EXTRAORDINARIA">Extraordinária</option>
              </select>
            </div>

            <div class="form-grid__cell form-grid__cell--3">
              <label for="dateInput">Data</label>
              <input id="dateInput" type="date" name="election_date" required>
            </div>

            <div class="form-grid__cell form-grid__cell--3">
              <label for="entityInput">Nome da Entidade / Condomínio</label>
              <?php
                $churchName = (string)($church['name'] ?? '');
                $legalName  = (string)($church['legal_name'] ?? '');
                $defaultEntity = $legalName !== '' ? $legalName : $churchName;
              ?>
              <input id="entityInput" name="entity_name" value="<?= htmlspecialchars($defaultEntity, ENT_QUOTES, 'UTF-8') ?>" maxlength="255" placeholder="Ex: Igreja Batista da Esperança EIRELI - ME">
              <span class="hint">Este nome aparecerá no cabeçalho do PDF de Lista de Presença.</span>
            </div>

            <div class="form-grid__cell form-grid__cell--3">
              <label for="expectedInput">Eleitores Esperados</label>
              <input id="expectedInput" type="number" name="expected_voters" required min="1" placeholder="Ex: 200">
            </div>
          </div>
        </section>

        <aside class="card card-workflow">
          <div class="card__header">
            <div>
              <h3 class="card__title-sm">Fluxo de Trabalho</h3>
              <span class="card__hint">3 fases sequenciais</span>
            </div>
            <span class="pill pill--amber">3 Fases</span>
          </div>
          <div class="workflow-stack">
            <div class="workflow-banner wf--presence wf-card">
              <div class="wf-title"><span class="wf-icon">1</span>Fase 1 — Presença</div>
              <p class="wf-sub">Eleitores confirmam presença; voto bloqueado.</p>
            </div>
            <div class="workflow-banner wf--voting wf-card">
              <div class="wf-title"><span class="wf-icon">2</span>Fase 2 — Votação</div>
              <p class="wf-sub">Escrutínio(s) sucessivos até maioria absoluta.</p>
            </div>
            <div class="workflow-banner wf--closed wf-card">
              <div class="wf-title"><span class="wf-icon">3</span>Fase 3 — Encerrada</div>
              <p class="wf-sub">Relatórios PDF, apuração final e resultados.</p>
            </div>
          </div>
        </aside>
      </div>

      <section id="officersFields" class="card hidden">
        <div class="card__header">
          <div>
            <h3 class="card__title-sm">Oficiais / Diretoria / Sociedades</h3>
            <span class="card__hint">Vagas e candidatos em disputa</span>
          </div>
          <span class="pill pill--teal">Múltiplos Candidatos</span>
        </div>
        <div class="form-grid g-4">
          <div class="form-grid__cell form-grid__cell--4">
            <label for="vacanciesInput">Vagas em disputa</label>
            <input type="number" name="vacancies" id="vacanciesInput" min="1">
            <span class="hint">Para <strong>SOCIEDADES</strong>, o valor padrão é 1 vaga (escolha majoritária 50%+1).</span>
          </div>
          <div class="form-grid__cell form-grid__cell--8">
            <label for="candidatesBulk">Candidatos <span class="hint-inline">(um nome por linha)</span></label>
            <textarea id="candidatesBulk" name="candidates_bulk" rows="8" placeholder="João da Silva&#10;Maria Souza&#10;José Pereira"></textarea>
          </div>
        </div>
      </section>

      <section id="pastorFields" class="card">
        <div class="card__header">
          <div>
            <h3 class="card__title-sm">Candidato a Pastor</h3>
            <span class="card__hint">Cédula de aprovação SIM / NÃO / BRANCO</span>
          </div>
          <span class="pill pill--green">Aprovação</span>
        </div>
        <div class="form-grid g-4">
          <div class="form-grid__cell form-grid__cell--5">
            <label for="pastorName">Nome Completo</label>
            <input id="pastorName" name="pastor_full_name" maxlength="160" placeholder="Ex: Pr. João Carlos da Silva">
          </div>
          <div class="form-grid__cell form-grid__cell--3">
            <label for="pastorRole">Função / Cargo</label>
            <input id="pastorRole" name="pastor_role_title" maxlength="120" placeholder="Ex: Pastor Titular">
          </div>
          <div class="form-grid__cell form-grid__cell--2">
            <label for="pastorTerm">Mandato (anos)</label>
            <select id="pastorTerm" name="pastor_term_years">
              <option value="1">1 ano</option>
              <option value="2">2 anos</option>
              <option value="3" selected>3 anos</option>
              <option value="4">4 anos</option>
              <option value="5">5 anos</option>
            </select>
          </div>
          <div class="form-grid__cell form-grid__cell--2">
            <label for="pastorPhoto">Foto <span class="hint-inline">(opcional)</span></label>
            <input id="pastorPhoto" type="file" name="pastor_photo" accept="image/*" class="file-input">
          </div>
        </div>
      </section>

      <section class="card card-actions">
        <div class="actions-row">
          <button type="submit" class="btn btn--primary btn--lg" data-confirm="Criar esta nova eleição com os parâmetros informados?">
            Criar Eleição &rarr;
          </button>
          <a class="btn btn--ghost-sm" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">
            Cancelar e voltar para o painel
          </a>
        </div>
      </section>
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
            officers.classList.remove('hidden');
            pastor.classList.add('hidden');
            vi.required = true;
          } else if (v === 'SOCIEDADES') {
            officers.classList.remove('hidden');
            pastor.classList.add('hidden');
            vi.required = true;
            vi.value = 1;
          } else {
            officers.classList.add('hidden');
            pastor.classList.remove('hidden');
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
