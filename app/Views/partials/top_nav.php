<?php
$navTitle = $navTitle ?? 'Sistema de Eleição';
$navLinks = $navLinks ?? [];
$navActions = $navActions ?? '';
?>
<header class="topbar">
  <div class="topbar-inner">
    <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login" class="brand">
      <span class="brand-dot"></span>
      <span><?= htmlspecialchars($navTitle, ENT_QUOTES, 'UTF-8') ?></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mainMenu">Menu</button>
    <nav id="mainMenu" class="main-nav">
      <?php foreach ($navLinks as $item): ?>
        <a href="<?= htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endforeach; ?>
      <?php if ($navActions !== ''): ?>
        <div class="main-nav-actions"><?= $navActions ?></div>
      <?php endif; ?>
    </nav>
  </div>
</header>

