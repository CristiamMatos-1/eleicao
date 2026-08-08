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
    $activePath = '/admin/elections';
    $navActions = '';
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>
  <main class="wrap">
    <div class="toolbar">
      <div class="page-title">
        <h1>Abrir Próximo Escrutínio</h1>
        <p class="muted" style="margin-top:2px;">
          <?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?>
        </p>
      </div>
      <div class="page-actions">
        <a class="btn btn--secondary" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/manage?id=<?= (int)$election['id'] ?>">
          ← Cancelar
        </a>
      </div>
    </div>

    <div class="alert alert--warn" style="margin-bottom:18px;">
      <strong>Atenção:</strong> Selecione apenas os candidatos que <strong>permanecem</strong> disputando no próximo escrutínio.
      Candidatos <strong>NÃO selecionados</strong> serão automaticamente desclassificados (ELIMINATED) e não poderão mais receber votos.
    </div>

    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/scrutiny/next">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">

      <div class="card" style="margin-bottom:18px;">
        <div class="card__header">
          <h3 style="margin:0;">Candidatos para o Próximo Escrutínio</h3>
          <span class="pill pill--amber">
            Todos estão marcados — desmarque quem deve ser eliminado
          </span>
        </div>

        <?php if (empty($candidates)): ?>
          <div style="text-align:center; padding:36px 16px; color:var(--brand-muted);">
            Nenhum candidato ativo disponível para o próximo escrutínio.
          </div>
        <?php else: ?>
          <div class="candidates-list candidates-list--2col">
            <?php foreach ($candidates as $c): ?>
              <label class="candidate-card">
                <input
                  type="checkbox"
                  name="candidate_ids[]"
                  value="<?= (int)$c['id'] ?>"
                  class="candidate-cb"
                  checked
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
                      $fn = trim((string)($c['full_name'] ?? ''));
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
                <div class="candidate-body">
                  <div style="font-weight:700; color:var(--brand-text); font-size:1rem; line-height:1.25;">
                    <?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <?php if (!empty($c['role_title'])): ?>
                    <div class="candidate-meta">
                      <?= htmlspecialchars($c['role_title'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  <?php endif; ?>
                </div>
                <span class="pill pill--blue">Permanece</span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($candidates)): ?>
        <div class="card">
          <div style="display:flex; flex-direction:column; gap:10px;">
            <button
              type="submit"
              class="btn btn--primary btn--lg"
              data-confirm="Confirma os candidatos selecionados para o próximo escrutínio? Os não selecionados serão ELIMINADOS permanentemente desta eleição."
              style="width:100%;"
            >
              Abrir Novo Escrutínio →
            </button>
            <a class="btn btn--secondary" style="width:100%; text-align:center; justify-content:center;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/manage?id=<?= (int)$election['id'] ?>">
              Cancelar
            </a>
          </div>
        </div>
      <?php endif; ?>
    </form>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
