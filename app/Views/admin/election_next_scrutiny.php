<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Abrir Próximo Escrutínio</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Painel Administrativo';
    $navLinks = [
      ['label' => 'Início', 'href' => $baseUrl . '/admin'],
      ['label' => 'Gerenciar eleição', 'href' => $baseUrl . '/admin/elections/manage?id=' . (int)$election['id']],
    ];
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
  <main class="wrap">
    <section class="card">
      <div class="row">
        <h1>Abrir Próximo Escrutínio</h1>
        <a class="link" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/manage?id=<?= (int)$election['id'] ?>">Cancelar</a>
      </div>

      <p class="muted">Selecione os candidatos que irão concorrer no próximo escrutínio. Os candidatos não selecionados serão desclassificados (ELIMINATED).</p>

      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/next" class="form" style="margin-top:20px;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">

        <div class="list">
          <?php foreach ($candidates as $c): ?>
            <label class="item" style="cursor:pointer; display:flex; align-items:center;">
              <input type="checkbox" name="candidate_ids[]" value="<?= (int)$c['id'] ?>" style="width:auto; flex-shrink:0; margin-right:10px;" checked>
              <span style="font-weight:500; font-size:16px; word-break:break-word; flex:1; line-height:1.2;"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <?php if (empty($candidates)): ?>
          <div class="muted">Nenhum candidato ativo disponível para o próximo escrutínio.</div>
        <?php endif; ?>

        <button type="submit" style="margin-top:20px;">Abrir Novo Escrutínio</button>
      </form>
    </section>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>