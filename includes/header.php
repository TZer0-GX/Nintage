<?php $pageTitle = $pageTitle ?? 'Nin-tage'; $flashMessage = take_flash(); ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=e($pageTitle)?> - Nin-tage</title>
  <link rel="stylesheet" href="<?=e(url('assets/css/main.css'))?>">
  <link rel="stylesheet" href="<?=e(url('assets/css/dynamic.css'))?>">
</head>
<body>
<div class="site-shell">
  <header class="topbar">
    <a class="brand" href="<?=e(url('index.php'))?>"><span class="brand-mark">N</span><span class="brand-name">NIN-TAGE</span><span class="brand-dot">RETRO</span></a>
    <nav class="platform-nav" aria-label="Navigation principale">
      <a class="platform-btn" href="<?=e(url('index.php'))?>">Bibliothèque</a>
      <a class="platform-btn" href="<?=e(url('index.php#about'))?>">À propos</a>
    </nav>
    <div class="account-zone">
      <div class="user-zone">
        <?php if (current_user()): ?>
          <span>👤 <?=e(current_user()['username'])?></span>
          <a class="tiny-btn" href="<?=e(url('account.php'))?>">Compte</a>
          <?php if (current_user()['role'] === 'admin'): ?><a class="tiny-btn" href="<?=e(url('admin/games.php'))?>">Admin</a><?php endif; ?>
          <form method="post" action="<?=e(url('logout.php'))?>"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="tiny-btn" type="submit">Sortir</button></form>
        <?php else: ?>
          <a class="pixel-btn" href="<?=e(url('login.php'))?>">Connexion</a>
          <a class="pixel-btn accent" href="<?=e(url('register.php'))?>">Créer un compte</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <?php if ($flashMessage): ?><div class="flash <?=e($flashMessage['type'])?>"><?=e($flashMessage['message'])?></div><?php endif; ?>
  <main class="dynamic-main">
