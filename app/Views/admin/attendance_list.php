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
    $activePath = '/admin/elections';
    ob_start();
  ?>
  <a
    class="btn btn--secondary"
    href="<?= htmlspecialchars($baseUrl . '/admin/elections/attendance/pdf?id=' . (int)$election['id'], ENT_QUOTES, 'UTF-8') ?>"
  >
    Exportar / Imprimir PDF
  </a>
  <?php
    $navActions = (string)ob_get_clean();
    require $this->services['config']['app']['base_path'] . '/app/Views/partials/top_nav.php';
  ?>

  <main class="wrap">
    <?php if (!empty($flashMsg)): ?>
      <div class="alert <?= !empty($flashType) && $flashType === 'success' ? 'alert--success' : 'alert--info' ?>">
        <?= htmlspecialchars((string)$flashMsg, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="page-title">
        <h1>Lista de Presença</h1>
        <p class="muted" style="margin-top:2px;">
          <?= htmlspecialchars($election['title'], ENT_QUOTES, 'UTF-8') ?>
          &nbsp;·&nbsp; Data: <?= date('d/m/Y', strtotime($election['election_date'])) ?>
          &nbsp;·&nbsp; Tipo: <?= htmlspecialchars($election['type'], ENT_QUOTES, 'UTF-8') ?>
        </p>
      </div>
      <div class="page-actions">
        <?php
          $status = (string)($election['status'] ?? '');
          $statusMap = [
            'aberta_para_presenca' => ['Aberta para Presença', 'pill--amber'],
            'aberta_para_votacao'  => ['Aberta para Votação',  'pill--green'],
            'encerrada'            => ['Encerrada',             'pill--red'],
            'OPEN'                 => ['Aberta (Legado)',       'pill--teal'],
            'CLOSED'               => ['Encerrada (Legado)',    'pill--red'],
          ];
          $statusInfo = $statusMap[$status] ?? null;
        ?>
        <?php if ($statusInfo): ?>
          <span class="pill <?= $statusInfo[1] ?>"><?= $statusInfo[0] ?></span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (isset($presentCount)): ?>
      <div class="cols-2 cols--kpi" style="margin-bottom:18px;">
        <div class="kpi">
          <div class="kpi__label">Presenças Registradas</div>
          <div class="kpi__value" style="color:var(--brand-success);"><?= (int)$presentCount ?></div>
          <div class="kpi__hint">Eleitores com presença confirmada</div>
        </div>
        <div class="kpi">
          <div class="kpi__label">Eleitores Habilitados</div>
          <div class="kpi__value" style="color:var(--brand-primary);"><?= count($voters) ?></div>
          <div class="kpi__hint">Total de eleitores cadastrados</div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:18px;">
      <div class="card__header">
        <h3>Registrar Presença Rapidamente</h3>
        <span class="muted">Informe CPF e/ou nome para credenciar um eleitor</span>
      </div>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/attendance/register" class="form-grid g-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
        <div class="form-grid__cell">
          <label for="cpf_reg">CPF</label>
          <input id="cpf_reg" type="text" name="cpf" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" data-mask="cpf">
        </div>
        <div class="form-grid__cell form-grid__cell--2">
          <label for="nome_reg">Nome (opcional)</label>
          <input id="nome_reg" type="text" name="nome" placeholder="Nome completo do eleitor">
        </div>
        <div class="form-grid__cell" style="display:flex; align-items:flex-end;">
          <button type="submit" class="btn btn--primary" style="width:100%;">Registrar Presença</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card__header">
        <h3>Relação de Eleitores</h3>
        <span class="muted"><?= count($voters) ?> registro(s)</span>
      </div>
      <div class="table-wrap">
        <table class="table">
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
                <td colspan="5" style="text-align: center; padding:28px 16px; color:var(--brand-muted);">
                  Nenhum eleitor habilitado para esta eleição.
                </td>
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
                  <td style="text-align:center; color:var(--brand-muted);"><?= $index + 1 ?></td>
                  <td style="font-weight:500;"><?= htmlspecialchars((string)($v['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="font-variant-numeric: tabular-nums;"><?= $cpfFmt ?></td>
                  <td style="text-align:center;">
                    <?php if ($present): ?>
                      <span class="pill pill--green">Presente</span>
                    <?php else: ?>
                      <span class="pill pill--gray">Ausente</span>
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
                        <button type="submit" class="btn btn--primary btn--sm" data-confirm="Confirmar presença de <?= htmlspecialchars((string)($v['name'] ?? 'este eleitor'), ENT_QUOTES, 'UTF-8') ?>?">
                          Confirmar
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:24px; padding:18px 20px; border:1px solid var(--brand-border); border-radius:var(--radius-lg); background:var(--brand-surface);">
        <div style="font-weight:600; margin-bottom:10px; color:var(--brand-text);">Legenda do Workflow</div>
        <div class="cols-3" style="gap:14px;">
          <div class="workflow-banner wf--presence" style="margin:0; padding:12px 14px;">
            <strong style="display:block; margin-bottom:2px;">Fase de Presença</strong>
            <span class="muted" style="font-size:13px;">Eleitores/administradores confirmam presença. Voto bloqueado.</span>
          </div>
          <div class="workflow-banner wf--voting" style="margin:0; padding:12px 14px;">
            <strong style="display:block; margin-bottom:2px;">Fase de Votação</strong>
            <span class="muted" style="font-size:13px;">Presença confirmada habilita o voto (backend valida).</span>
          </div>
          <div class="workflow-banner wf--closed" style="margin:0; padding:12px 14px;">
            <strong style="display:block; margin-bottom:2px;">Encerrada</strong>
            <span class="muted" style="font-size:13px;">Nenhuma ação permitida; relatórios e resultados finais.</span>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
