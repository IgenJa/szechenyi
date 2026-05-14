<?php
/**
 * Egyszeri telepítő — futtasd böngészőből: http://localhost/szechenyi/install.php
 *
 * Mit csinál:
 *   1) Létrehozza a `categories`, `images`, `users` táblákat
 *   2) Felvesz egy admin felhasználót (felhasználónév + jelszó)
 *   3) Beszúrja az alap kategóriákat (családi, karácsonyi, páros, portré, glamour)
 *
 * Telepítés után TÖRÖLD ezt a fájlt, vagy nevezd át (pl. install.php.done).
 */

declare(strict_types=1);
require_once __DIR__ . '/db/db.php';

$step    = $_POST['step']    ?? 'form';
$message = '';
$error   = '';
$done    = false;

if ($step === 'install') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm']  ?? '');

    if ($username === '' || strlen($username) < 3) {
        $error = 'A felhasználónév legalább 3 karakter legyen.';
    } elseif (strlen($password) < 8) {
        $error = 'A jelszó legalább 8 karakter legyen.';
    } elseif ($password !== $confirm) {
        $error = 'A két jelszó nem egyezik.';
    } else {
        try {
            $pdo = sz_db();

            // 1) Schema
            $sql = file_get_contents(__DIR__ . '/db/schema.sql');
            if ($sql === false) throw new RuntimeException('Nem olvasható: db/schema.sql');
            // Több utasítás futtatása soronként
            $statements = array_filter(array_map('trim', preg_split('/;\s*$/m', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt !== '') $pdo->exec($stmt);
            }

            // 2) Admin user
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insertUser = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $insertUser->execute([$username, $hash]);

            // 3) Alap kategóriák
            $seed = [
                ['csaladi',    'Családi',    10],
                ['karacsonyi', 'Karácsonyi', 20],
                ['paros',      'Páros',      30],
                ['portre',     'Portré',     40],
                ['glamour',    'Glamour',    50],
            ];
            $insertCat = $pdo->prepare('INSERT INTO categories (slug, name, sort_order) VALUES (?, ?, ?)');
            foreach ($seed as $row) $insertCat->execute($row);

            // 4) uploads/ mappa készítése
            sz_upload_dir();

            $done    = true;
            $message = 'Sikeres telepítés! Bejelentkezhetsz az admin felületre.';
        } catch (Throwable $e) {
            $error = 'Hiba a telepítés során: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Telepítés — Széchenyi Fényképészet Admin</title>
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="admin/assets/admin.css">
</head>
<body class="admin-body">
  <main class="admin-shell admin-shell--centered">
    <section class="admin-card admin-card--narrow">
      <h1 class="admin-title">Admin telepítés</h1>

      <?php if ($error): ?>
        <div class="admin-alert admin-alert--error"><?= sz_e($error) ?></div>
      <?php endif; ?>

      <?php if ($done): ?>
        <div class="admin-alert admin-alert--success">
          <strong>Kész!</strong> <?= sz_e($message) ?>
        </div>
        <p class="admin-text">
          Most már bejelentkezhetsz: <a class="admin-link" href="admin/login.php">admin/login.php</a>
        </p>
        <p class="admin-text admin-text--muted">
          <strong>Fontos:</strong> Biztonsági okokból töröld ezt a fájlt (<code>install.php</code>),
          mielőtt élesbe állítod az oldalt.
        </p>
      <?php else: ?>
        <p class="admin-text">
          Ez egy egyszeri beállítás. Hozz létre egy admin felhasználót, és a script megépíti az
          adatbázis táblákat is.
        </p>

        <form method="post" class="admin-form" autocomplete="off">
          <input type="hidden" name="step" value="install">

          <label class="admin-field">
            <span class="admin-field__label">Felhasználónév</span>
            <input class="admin-input" type="text" name="username" required minlength="3" maxlength="64"
                   value="<?= sz_e($_POST['username'] ?? '') ?>" autofocus>
          </label>

          <label class="admin-field">
            <span class="admin-field__label">Jelszó (min. 8 karakter)</span>
            <input class="admin-input" type="password" name="password" required minlength="8">
          </label>

          <label class="admin-field">
            <span class="admin-field__label">Jelszó megerősítése</span>
            <input class="admin-input" type="password" name="confirm" required minlength="8">
          </label>

          <button class="admin-btn admin-btn--primary" type="submit">Telepítés indítása</button>
        </form>

        <p class="admin-text admin-text--muted" style="margin-top: 1.5rem">
          A futtatás előtt győződj meg róla, hogy létrehoztad a <code>db/config.php</code> fájlt
          (a <code>db/config.example.php</code> alapján), és üres MySQL adatbázis vár ránk.
        </p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
