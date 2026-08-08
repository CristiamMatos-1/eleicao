<?php

declare(strict_types=1);

$key = $_GET['key'] ?? '';
$key = is_string($key) ? $key : '';

$config = require __DIR__ . '/../app/Config/config.php';
$baseUrl = rtrim((string)($config['app']['base_url'] ?? ''), '/');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel de Apuração — Coninfoms</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/dashboard.css?v=<?= time() ?>">
</head>
<body>
  <main
    class="wrap"
    data-public-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
    data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>"
  >
    <section class="card toolbar--top" style="margin-bottom:16px; padding:18px 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:14px; flex-wrap: wrap; gap: 12px;">
        <div>
          <h2 style="margin: 0; color: var(--brand-secondary); font-size:22px;">Apuração ao Vivo</h2>
          <p class="muted" style="margin:4px 0 0 0;">Resultados atualizados automaticamente a cada 8 segundos</p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" class="link-voltar">
          &larr; Voltar para a Página Principal
        </a>
      </div>
      <label style="display:block; margin-bottom:8px; font-weight:600; color:var(--brand-text); font-size:14px;">
        Selecione a Eleição:
      </label>
      <select id="electionSelect">
        <option value="">Carregando eleições…</option>
      </select>
    </section>

    <?php if ($key !== ''): ?>
    <header class="ballot-header">
      <div>
        <div class="ballot-header__label">Eleição</div>
        <h1 id="title" class="ballot-header__title">Carregando dados…</h1>
      </div>
      <div class="meta">
        <span id="status" class="pill">Carregando</span>
        <span id="scrutiny" class="pill subtle"></span>
      </div>
    </header>

    <section class="grid">
      <article class="card">
        <h2>Andamento da Votação</h2>
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
        <div class="bar" aria-hidden="true">
          <div id="barFill" class="bar-fill" style="width:0%"></div>
        </div>
        <div id="updatedAt" class="muted">Aguardando primeira atualização…</div>
      </article>

      <article class="card">
        <h2>Resultado Parcial</h2>
        <div id="result" class="result">
          <div class="muted" style="padding:16px 4px; text-align:center;">
            Aguardando apuração…
          </div>
        </div>
      </article>
    </section>
    <?php else: ?>
      <section class="card" style="text-align:center; padding:44px 20px;">
        <h2 style="margin:0 0 10px 0; color:var(--brand-secondary);">Bem-vindo ao Painel Público</h2>
        <p class="muted" style="margin:0 0 18px 0;">Selecione uma eleição acima para visualizar a apuração em tempo real.</p>
        <a class="btn btn--primary btn--lg" style="justify-content:center; display:inline-flex;" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">
          Acessar o Sistema de Votação →
        </a>
      </section>
    <?php endif; ?>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/dashboard.js"></script>
</body>
</html>
