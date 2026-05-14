<?php
/**
 * Admin auth + session + CSRF segédek.
 * Minden admin oldal a tetején meghívja: require __DIR__ . '/auth.php';
 */

declare(strict_types=1);
require_once __DIR__ . '/../db/db.php';

function sz_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $cfg = sz_config()['session'];
    session_name($cfg['name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Inaktivitási időkorlát
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $cfg['lifetime'])) {
        sz_logout();
    }
    $_SESSION['last_activity'] = time();
}

function sz_is_logged_in(): bool {
    sz_session_start();
    return !empty($_SESSION['user_id']);
}

function sz_require_login(): void {
    if (!sz_is_logged_in()) {
        $target = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: login.php?redirect=' . urlencode($target));
        exit;
    }
}

function sz_current_user(): ?array {
    if (!sz_is_logged_in()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
    ];
}

function sz_login(string $username, string $password): bool {
    sz_session_start();
    $pdo  = sz_db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Új session az auth fixation ellen
    session_regenerate_id(true);
    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['last_activity'] = time();

    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
    return true;
}

function sz_logout(): void {
    sz_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** CSRF token kiadása/ellenőrzése */
function sz_csrf_token(): string {
    sz_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function sz_csrf_check(?string $token): bool {
    sz_session_start();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function sz_require_csrf(): void {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!sz_csrf_check($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Érvénytelen CSRF token']);
        exit;
    }
}

/** JSON válasz küldése és kilépés. */
function sz_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
