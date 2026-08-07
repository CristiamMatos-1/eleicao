<?php $baseUrl = rtrim((string)($this->services['config']['app']['base_url'] ?? ''), '/'); ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lista de Presença - <?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
  <?php
    $navTitle = 'Lista de Presença';
    $navLinks = [
      ['label' => 'Voltar', 'href' => $baseUrl . '/admin/elections/manage?id=' . (int)$election['id']],
    ];
    ob_start();
  ?>
  <div style="display:flex; gap:8px; flex-wrap:wrap;">
    <a
      class="secondary"
      style="padding:10px 14px; display:inline-block; text-decoration:none; border-radius:8px;"
      href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>"
    >
      Exportar / Imprimir PDF
    </a>
    <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/attendance/register" style="display:inline-flex; gap:8px; flex-wrap:wrap; align-items:flex-end;">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
      <div>
        <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:4px;">CPF</label>
        <input type="text" name="cpf" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" style="min-width:180px;">
      </div>
      <div>
        <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:4px;">Nome (opcional)</label>
        <input type="text" name="nome" placeholder="Nome completo" style="min-width:240px;">
      </div>
      <button type="submit" style="white-space:nowrap;">Registrar Presença</button>
    </form>
  </div>
  <?php
    $navActions = (string)ob_get_clean();
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap">
    <section class="card">
      <div class="row" style="align-items:flex-start;">
        <div>
          <h1>Lista de Presença</h1>
          <div class="muted" style="margin-top:4px;">
            <?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?>
            | Data: <?= date('d/m/Y', strtotime($election['election_date'])) ?>
            | Tipo: <?= htmlspecialchars($election['type'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
      </div>

      <?php
        $status = (string)($election['status'] ?? '');
        $statusLabel = [
          'aberta_para_presenca' => ['Aberta para Presença', 'background:#FFF3CD; color:#856404;'],
          'aberta_para_votacao'  => ['Aberta para Votação', 'background:#D4EDDA; color:#155724;'],
          'encerrada'            => ['Encerrada',          'background:#F8D7DA; color:#721C24;'],
          'OPEN'                 => ['Aberta (Legado)',     'background:#D1ECF1; color:#0C5460;'],
          'CLOSED'               => ['Encerrada (Legado)',  'background:#F8D7DA; color:#721C24;'],
        ][$status] ?? ['Status', ''];
      ?>
      <?php if ($statusLabel[0] !== 'Status'): ?>
        <div class="pill" style="<?= $statusLabel[1] ?> border:1px solid transparent; padding:8px 12px; border-radius:999px; display:inline-block; margin:10px 0;">
          Status: <?= htmlspecialchars($statusLabel[0], ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <?php if (isset($presentCount) && $presentCount > 0): ?>
        <div style="margin:10px 0 20px 0;">
          <strong>Presenças registradas:</strong> <?= (int)$presentCount ?>
          &nbsp;|&nbsp;
          <strong>Total de eleitores habilitados:</strong> <?= count($voters) ?>
        </div>
      <?php endif; ?>

      <div style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th style="width:5%; text-align:center;">#</th>
              <th style="width:40%;">Nome do Eleitor</th>
              <th style="width:22%;">CPF</th>
              <th style="width:13%; text-align:center;">Presença</th>
              <th style="width:20%; text-align:center;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($voters)): ?>
              <tr>
                <td colspan="5" style="text-align: center; padding:20px;">Nenhum eleitor habilitado para esta eleição.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($voters as $index => $v): ?>
                <?php
                  $cpf = is_string($v['cpf'] ?? null) ? $v['cpf'] : '';
                  $cpfDigits = preg_replace('/\D/', '', $cpf);
                  $cpfFmt = '';
                  if (strlen($cpfDigits) === 11) {
                      $cpfFmt = substr($cpfDigits, 0, 3) . '.' . substr($cpfDigits, 3, 3) . '.' . substr($cpfDigits, 6, 3) . '-' . substr($cpfDigits, 9, 2);
                  } else {
                      $cpfFmt = htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8');
                  }
                  $present = !empty($v['presente']);
                ?>
                <tr>
                  <td style="text-align:center;"><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars((string)($v['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= $cpfFmt ?></td>
                  <td style="text-align:center;">
                    <?php if ($present): ?>
                      <span style="display:inline-block; padding:4px 10px; border-radius:999px; background:#D4EDDA; color:#155724; font-weight:600;">Presente</span>
                    <?php else: ?>
                      <span style="display:inline-block; padding:4px 10px; border-radius:999px; background:#E9ECEF; color:#495057;">Ausente</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center;">
                    <?php if (!$present): ?>
                      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/attendance/register" style="display:inline-block;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= (int)($v['id'] ?? 0) ?>">
                        <input type="hidden" name="cpf" value="<?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="nome" value="<?= htmlspecialchars((string)($v['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="secondary" style="padding:6px 12px; font-size:14px;">Confirmar</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:24px; padding:16px; border:1px solid var(--border); border-radius:12px; background:var(--bg);">
        <div style="font-weight:600; margin-bottom:8px;">Legenda do Workflow</div>
        <div style="display:grid; gap:8px;">
          <div><strong>aberta_para_presença</strong> — Eleitores/administradores podem confirmar presença. Voto bloqueado.</div>
          <div><strong>aberta_para_votação</strong> — Presença confirmada habilita o voto (backend valida presença obrigatória).</div>
          <div><strong>encerrada</strong> — Nenhuma ação permitida; resultados e relatórios finais.</div>
        </div>
      </div>
    </section>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
