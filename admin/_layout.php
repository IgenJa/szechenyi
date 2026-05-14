<?php
/**
 * Közös admin layout. Használat:
 *
 *   $pageTitle = 'Kategóriák';
 *   $activeNav = 'categories';   // 'dashboard' | 'categories' | 'gallery'
 *   include __DIR__ . '/_layout.php';
 *   sz_admin_header();
 *   // ... oldal tartalom ...
 *   sz_admin_footer();
 */

require_once __DIR__ . '/auth.php';
sz_require_login();

function sz_admin_header(string $pageTitle = 'Admin', string $activeNav = ''): void {
    $user = sz_current_user();
    ?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sz_e($pageTitle) ?> — Admin</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" type="image/svg+xml" href="../favicon.svg">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="assets/admin.css">
<script defer src="assets/admin.js"></script>
</head>
<body class="admin-body">
  <header class="admin-header">
    <a href="index.php" class="admin-header__brand">
      <span class="admin-header__brand-mark">SM</span>
      <span class="admin-header__brand-text">Admin</span>
    </a>
    <nav class="admin-nav" aria-label="Admin navigáció">
      <a href="index.php"      class="admin-nav__link <?= $activeNav==='dashboard'?'is-active':'' ?>">Áttekintés</a>
      <a href="categories.php" class="admin-nav__link <?= $activeNav==='categories'?'is-active':'' ?>">Kategóriák</a>
      <a href="gallery.php"    class="admin-nav__link <?= $activeNav==='gallery'?'is-active':'' ?>">Galéria</a>
    </nav>
    <div class="admin-header__right">
      <a class="admin-nav__link" href="../galeria.php" target="_blank" rel="noopener">Oldal megtekintése ↗</a>
      <span class="admin-header__user"><?= sz_e($user['username'] ?? '') ?></span>
      <a class="admin-btn admin-btn--ghost admin-btn--sm" href="logout.php">Kilépés</a>
    </div>
  </header>

  <main class="admin-shell">
<?php
}

function sz_admin_footer(): void {
    ?>
  </main>
</body>
</html>
<?php
}
