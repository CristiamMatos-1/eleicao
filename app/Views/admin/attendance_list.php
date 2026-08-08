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
        <span class="crumbs"><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" style="color:inherit; text-decoration:none;">Painel</a> · Lista de presença</span>
        <h1>Lista de Presença</h1>
        <p class="muted page-subtitle">
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
            'aberta_para_votacao'  => ['Aberta para Votação',  'pill--blue'],
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
      <div class="cols-2 cols--kpi layout-row">
        <div class="kpi kpi--green">
          <div class="kpi__label">Presenças Registradas</div>
          <div class="kpi__value"><?= (int)$presentCount ?></div>
          <div class="kpi__hint">Eleitores com presença confirmada</div>
        </div>
        <div class="kpi">
          <div class="kpi__label">Eleitores Habilitados</div>
          <div class="kpi__value"><?= count($voters) ?></div>
          <div class="kpi__hint">Total de eleitores cadastrados</div>
        </div>
      </div>
    <?php endif; ?>

    <section class="card layout-row">
      <div class="card__header">
        <div>
          <h3 class="card__title-sm">Registrar Presença Rapidamente</h3>
          <span class="card__hint">Informe CPF e/ou nome para credenciar um eleitor</span>
        </div>
      </div>
      <form method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/elections/attendance/register" class="form-grid g-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
        <div class="form-grid__cell form-grid__cell--3">
          <label for="cpf_reg">CPF</label>
          <input id="cpf_reg" type="text" name="cpf" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" data-mask="cpf">
        </div>
        <div class="form-grid__cell form-grid__cell--6">
          <label for="nome_reg">Nome <span class="hint-inline">(opcional, ajuda na busca)</span></label>
          <input id="nome_reg" type="text" name="nome" placeholder="Nome completo do eleitor">
        </div>
        <div class="form-grid__cell form-grid__cell--3 form-grid__cell--submit">
          <button type="submit" class="btn btn--primary">Registrar Presença</button>
        </div>
      </form>
    </section>

    <section class="card">
      <div class="card__header">
        <div>
          <h3 class="card__title-sm">Relação de Eleitores</h3>
          <span class="card__hint"><?= count($voters) ?> registro(s)</span>
        </div>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th style="width:5%; text-align:center;">#</th>
              <th>Nome do Eleitor</th>
              <th style="width:22%;">CPF</th>
              <th style="width:15%; text-align:center;">Presença</th>
              <th style="width:20%; text-align:center;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($voters)): ?>
              <tr>
                <td colspan="5" class="table-empty">
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
                  <td style="text-align:center;" class="cell-muted"><?= $index + 1 ?></td>
                  <td class="cell-name"><?= htmlspecialchars((string)($v['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-mono"><?= $cpfFmt ?></td>
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

      <div class="subcard subcard--soft legend-card">
        <h4 class="subcard__title">Legenda do Workflow</h4>
        <div class="cols-3">
          <div class="workflow-banner wf--presence wf-card">
            <div class="wf-title"><span class="wf-icon" aria-hidden="true">●</span> Fase de Presença</div>
            <p class="wf-sub">Eleitores e administradores confirmam presença; voto bloqueado.</p>
          </div>
          <div class="workflow-banner wf--voting wf-card">
            <div class="wf-title"><span class="wf-icon" aria-hidden="true">●</span> Fase de Votação</div>
            <p class="wf-sub">Presença confirmada habilita o voto (back-end valida).</p>
          </div>
          <div class="workflow-banner wf--closed wf-card">
            <div class="wf-title"><span class="wf-icon" aria-hidden="true">●</span> Encerrada</div>
            <p class="wf-sub">Nenhuma ação permitida; relatórios e resultados finais.</p>
          </div>
        </div>
      </div>
    </section>
  </main>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/app.js"></script>
</body>
</html>
