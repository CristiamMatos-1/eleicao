<?php

declare(strict_types=1);

$key = $_GET['key'] ?? '';
$key = is_string($key) ? $key : '';

// Retrieve base URL from config for assets
$config = require __DIR__ . '/../app/Config/config.php';
$baseUrl = rtrim((string)($config['app']['base_url'] ?? ''), '/');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel da Eleição</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/dashboard.css?v=<?= time() ?>">
</head>
<body>
  <main class="wrap" data-public-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card" style="margin-bottom:15px; border:none; box-shadow:none; padding:16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:15px; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0; color: var(--text);">Apuração ao Vivo</h2>
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" style="padding: 10px 16px; background: #E9ECEF; color: #495057; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; border: 1px solid #DEE2E6;">
          &larr; Voltar para a Página Principal
        </a>
      </div>
      <label class="muted" style="display:block; margin-bottom:8px; font-weight:600;">Selecione a Eleição:</label>
      <select id="electionSelect" style="width:100%; padding:14px; border-radius:12px; background:var(--bg); color:var(--text); border:1px solid var(--border); font-size:16px;">
        <option value="">Carregando eleições...</option>
      </select>
    </div>

    <?php if ($key !== ''): ?>
    <header class="card">
      <h1 id="title">Eleição</h1>
      <div class="meta">
        <span id="status" class="pill">Carregando</span>
        <span id="scrutiny" class="pill subtle"></span>
      </div>
    </header>

    <section class="grid">
      <article class="card">
        <h2>Andamento</h2>
        <div class="kpis">
          <div class="kpi">
            <div class="kpi-label">Votos</div>
            <div id="votes" class="kpi-value">0</div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Esperados</div>
            <div id="expected" class="kpi-value">0</div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Faltam</div>
            <div id="remaining" class="kpi-value">0</div>
          </div>
        </div>
        <div class="bar">
          <div id="barFill" class="bar-fill" style="width:0%"></div>
        </div>
        <div id="updatedAt" class="muted"></div>
      </article>

      <article class="card">
        <h2>Resultado</h2>
        <div id="result" class="result">
          <div class="muted">Aguardando apuração…</div>
        </div>
      </article>
    </section>
    <?php else: ?>
      <div class="card" style="text-align:center; padding:40px;">
        <h2>Selecione uma eleição acima para visualizar o painel</h2>
      </div>
    <?php endif; ?>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/dashboard.js"></script>
</body>
</html>