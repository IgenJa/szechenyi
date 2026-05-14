<?php
require_once __DIR__ . '/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!sz_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Lejárt vagy érvénytelen kérés. Töltsd újra az oldalt.';
    } elseif ($username === '' || $password === '') {
        $error = 'Felhasználónév és jelszó megadása kötelező.';
    } elseif (sz_login($username, $password)) {
        $redirect = $_GET['redirect'] ?? 'index.php';
        // Csak relatív path engedélyezett
        if (!preg_match('#^[a-z0-9_\-./]+$#i', $redirect) || str_contains($redirect, '..')) {
            $redirect = 'index.php';
        }
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Hibás felhasználónév vagy jelszó.';
    }
}

if (sz_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$csrf = sz_csrf_token();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bejelentkezés — Admin</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body">
  <main class="admin-shell admin-shell--centered">
    <section class="admin-card admin-card--narrow">
      <h1 class="admin-title">Admin bejelentkezés</h1>

      <?php if ($error): ?>
        <div class="admin-alert admin-alert--error" role="alert"><?= sz_e($error) ?></div>
      <?php endif; ?>

      <form method="post" class="admin-form" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= sz_e($csrf) ?>">

        <label class="admin-field">
          <span class="admin-field__label">Felhasználónév</span>
          <input class="admin-input" type="text" name="username" required autofocus
                 value="<?= sz_e($_POST['username'] ?? '') ?>">
        </label>

        <label class="admin-field">
          <span class="admin-field__label">Jelszó</span>
          <input class="admin-input" type="password" name="password" required>
        </label>

        <button class="admin-btn admin-btn--primary" type="submit">Belépés</button>
      </form>

      <p class="admin-text admin-text--muted" style="margin-top:1.5rem">
        <a class="admin-link" href="../index.html">← Vissza az oldalra</a>
      </p>
    </section>
  </main>
</body>
</html>
