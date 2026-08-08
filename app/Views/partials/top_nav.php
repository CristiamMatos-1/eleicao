<?php
$navTitle = $navTitle ?? 'Sistema de Eleição';
$navLinks = $navLinks ?? [];
$navActions = $navActions ?? '';
$brandLetter = $brandLetter ?? 'V';
$brandMarkClass = $brandMarkClass ?? 'brand-mark';
$activePath = $activePath ?? null;
$baseUrl = $baseUrl ?? '';

$isActive = static function (string $href) use ($activePath, $baseUrl): bool {
    if ($activePath === null) {
        return false;
    }
    $rel = $href;
    if (str_starts_with($rel, $baseUrl . '/')) {
        $rel = substr($rel, strlen($baseUrl));
    }
    if ($rel === '' || $rel === '/') {
        return $activePath === '/' || $activePath === '' || $activePath === '/login';
    }
    if ($rel === $activePath) {
        return true;
    }
    if (str_starts_with($activePath, rtrim($rel, '/') . '/')) {
        return true;
    }
    return false;
};
?>
<header class="topbar">
  <div class="topbar-inner">
    <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" class="brand" aria-label="Ir para o início">
      <span class="<?= htmlspecialchars($brandMarkClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($brandLetter, ENT_QUOTES, 'UTF-8') ?></span>
      <span><?= htmlspecialchars($navTitle, ENT_QUOTES, 'UTF-8') ?></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mainMenu" data-nav-toggle="mainMenu">
      <span aria-hidden="true">☰</span>
      <span style="margin-left:6px;">Menu</span>
    </button>
    <nav id="mainMenu" class="main-nav" aria-label="Navegação principal">
      <?php foreach ($navLinks as $item):
        $href = (string)($item['href'] ?? '#');
        $active = !empty($item['active']) || $isActive($href);
        $target = !empty($item['external']) ? '_blank' : null;
        $extra = '';
        if ($target !== null) {
            $extra .= ' target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"';
        }
        ?>
        <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"<?= $active ? ' class="is-active"' : '' ?><?= $extra ?>>
          <?= htmlspecialchars((string)($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endforeach; ?>
      <?php if ($navActions !== ''): ?>
        <div class="main-nav-actions"><?= $navActions ?></div>
      <?php endif; ?>
    </nav>
  </div>
</header>
