<?php
/**
 * Központi DB és config betöltő.
 * Használat: require_once __DIR__ . '/../db/db.php';
 */

declare(strict_types=1);

// Hibakezelés — fejlesztéshez engedélyezzük a hibákat
if (!defined('SZ_BOOTSTRAPPED')) {
    define('SZ_BOOTSTRAPPED', true);
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

function sz_config(): array {
    static $config = null;
    if ($config !== null) return $config;

    $configPath = __DIR__ . '/config.php';
    if (!file_exists($configPath)) {
        http_response_code(500);
        echo '<h1>Hiányzó konfiguráció</h1>';
        echo '<p>Hozz létre egy <code>db/config.php</code> fájlt a <code>db/config.example.php</code> alapján, ';
        echo 'majd töltsd ki a MySQL adataiddal. Részletek: <code>README_ADMIN.md</code></p>';
        exit;
    }
    $config = require $configPath;
    return $config;
}

function sz_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $cfg = sz_config()['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'], $cfg['port'], $cfg['name'], $cfg['charset']
    );

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<h1>Adatbázis hiba</h1>';
        echo '<p>Nem sikerült csatlakozni: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p>Ellenőrizd a <code>db/config.php</code> adatait és hogy a MySQL fut-e.</p>';
        exit;
    }

    return $pdo;
}

/**
 * URL-barát slug készítése magyar szövegből.
 */
function sz_slug(string $text): string {
    $text = trim($text);
    if ($text === '') return '';

    // Magyar ékezetes karakterek egyszerű átalakítása
    $map = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ö'=>'o','ő'=>'o',
        'ú'=>'u','ü'=>'u','ű'=>'u',
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ö'=>'O','Ő'=>'O',
        'Ú'=>'U','Ü'=>'U','Ű'=>'U',
    ];
    $text = strtr($text, $map);
    $text = strtolower($text);

    // Nem alfanumerikus → kötőjel
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text === '' ? 'kategoria' : $text;
}

/**
 * Biztonságos HTML escape.
 */
function sz_e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Projekt gyökér abszolút útvonala (fájlrendszeren).
 */
function sz_root(): string {
    return dirname(__DIR__);
}

/**
 * Feltöltési könyvtár abszolút útvonala (mindig létezik a hívás után).
 */
function sz_upload_dir(): string {
    $cfg = sz_config()['app'];
    $dir = sz_root() . '/' . trim($cfg['upload_dir'], '/');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Adatbázisban tárolt képforrás (külső URL vagy relatív útvonal) -> tényleges src
 * a megadott prefix-szel (relatív útvonal esetén). Pl. admin/ alól '../', gyökérről ''.
 */
function sz_image_src(string $url, string $prefix = ''): string {
    if (preg_match('#^https?://#i', $url)) return $url;
    return $prefix . ltrim($url, '/');
}
