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
  <main class="wrap">
    <section class="card">
      <div class="row">
        <h1>Nova Eleição</h1>
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin">Voltar</a>
      </div>

      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections" class="form" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <label>Tipo</label>
        <select name="type" id="typeSelect" required>
            <option value="PASTOR">Pastor</option>
            <option value="OFICIAIS">Presbíteros/Diáconos</option>
            <option value="DIRETORIA">Diretoria / Deputados</option>
            <option value="SOCIEDADES">Sociedades</option>
          </select>

        <label>Título</label>
        <input name="title" required maxlength="160" placeholder="Ex: Eleição Pastoral 2026">

        <label>Data</label>
        <input type="date" name="election_date" required>

        <label>Eleitores Esperados</label>
        <input type="number" name="expected_voters" required min="1" placeholder="Ex: 200">

        <div id="officersFields" style="display:none">
          <label>Vagas em disputa</label>
          <input type="number" name="vacancies" id="vacanciesInput" min="1">
          <div class="muted" style="margin-top:5px; font-size:12px;">Para SOCIEDADES, o valor padrão é 1 vaga.</div>

          <label>Candidatos (um por linha)</label>
          <textarea name="candidates_bulk" rows="8" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.03);color:#e9eefb"></textarea>
        </div>

        <div id="pastorFields">
          <label>Nome do candidato (Pastor)</label>
          <input name="pastor_full_name" maxlength="160">

          <label>Foto do Candidato (Opcional)</label>
          <input type="file" name="pastor_photo" accept="image/*" style="background:transparent; border:none; padding:0;">

          <label>Função</label>
          <input name="pastor_role_title" maxlength="120" placeholder="Ex: Pastor Titular">

          <label>Mandato (anos)</label>
          <select name="pastor_term_years">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
          </select>
        </div>

        <button type="submit" style="margin-top:20px;">Criar Eleição</button>
      </form>
    </section>
  </main>

  <script>
    const type = document.getElementById('typeSelect');
    const officers = document.getElementById('officersFields');
    const pastor = document.getElementById('pastorFields');
    const vi = document.getElementById('vacanciesInput');

    function sync() {
      const v = type.value;
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
  </script>
</body>
</html>