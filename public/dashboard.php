<?php

declare(strict_types=1);

$key = $_GET['key'] ?? '';
$key = is_string($key) ? $key : '';

$config = require __DIR__ . '/../app/Config/config.php';
$baseUrlCfg = rtrim((string)($config['app']['base_url'] ?? ''), '/');

$resolveBaseUrl = function () use ($baseUrlCfg): string {
    if ($baseUrlCfg !== '') {
        return $baseUrlCfg;
    }
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === '') {
        return '';
    }
    $dir = dirname($script);
    if ($dir === '.' || $dir === '\\') {
        $dir = '';
    }
    return rtrim($dir, '/\\');
};
$baseUrl = $resolveBaseUrl();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Apuração ao Vivo — Coninfoms</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/dashboard.css?v=<?= time() ?>">
</head>
<body>
  <main
    class="wrap"
    data-public-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
    data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>"
  >
    <section class="card toolbar--top dashboard__toolbar">
      <div class="card__header">
        <div>
          <h2 class="card__title">Apuração ao Vivo</h2>
          <span class="muted card__hint">Resultados atualizados automaticamente a cada 8 segundos</span>
        </div>
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/" class="btn btn--secondary btn--sm">
          &larr; Voltar para a Página Principal
        </a>
      </div>
      <div class="dashboard__select">
        <label class="form-label">Selecione a Eleição:</label>
        <select id="electionSelect">
          <option value="">Carregando eleições…</option>
        </select>
      </div>
    </section>

    <?php if ($key !== ''): ?>
    <header class="ballot-header dashboard__ballot">
      <div>
        <div class="ballot-header__label">Eleição</div>
        <h1 id="title" class="ballot-header__title">Carregando dados…</h1>
      </div>
      <div class="meta">
        <span id="status" class="pill pill--blue">Carregando</span>
        <span id="scrutiny" class="pill pill--gray"></span>
      </div>
    </header>

    <section class="cols-2 dashboard__cols">
      <article class="card">
        <div class="card__header">
          <h2 class="card__title-sm">Andamento da Votação</h2>
        </div>
        <div class="kpis">
          <div class="kpi">
            <div class="kpi__label">Votos</div>
            <div id="votes" class="kpi__value">0</div>
          </div>
          <div class="kpi">
            <div class="kpi__label">Esperados</div>
            <div id="expected" class="kpi__value">0</div>
          </div>
          <div class="kpi">
            <div class="kpi__label">Faltam</div>
            <div id="remaining" class="kpi__value">0</div>
          </div>
        </div>
        <div class="bar" aria-hidden="true">
          <div id="barFill" class="bar-fill" style="width:0%"></div>
        </div>
        <div id="updatedAt" class="muted updated-at">Aguardando primeira atualização…</div>
      </article>

      <article class="card">
        <div class="card__header">
          <h2 class="card__title-sm">Resultado Parcial</h2>
        </div>
        <div id="result" class="result">
          <div class="muted result-empty">
            Aguardando apuração…
          </div>
        </div>
      </article>
    </section>
    <?php else: ?>
      <section class="card dashboard__empty">
        <h2 class="card__title center">Bem-vindo ao Painel Público</h2>
        <p class="muted center">Selecione uma eleição acima para visualizar a apuração em tempo real.</p>
        <a class="btn btn--primary btn--lg dashboard__cta" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/">
          Acessar o Sistema de Votação →
        </a>
      </section>
    <?php endif; ?>

    <div class="app-footer">
      Painel de Apuração Pública · Coninfoms Eleição · <?= date('Y') ?>
    </div>
  </main>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/dashboard.js"></script>
</body>
</html>
