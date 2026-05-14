<?php
/**
 * Adatbázis és alkalmazás konfiguráció — MINTA
 *
 * Másold át ezt a fájlt `config.php` néven, majd töltsd ki a saját adataiddal.
 *   cp db/config.example.php db/config.php
 *
 * A `config.php`-t SOHA ne tölts fel publikus repo-ba, és ne legyen webről elérhető!
 */

return [
    // --- MySQL kapcsolat ---
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'szechenyi',           // adatbázis neve
        'user'     => 'root',                // MySQL felhasználó
        'pass'     => '',                    // MySQL jelszó
        'charset'  => 'utf8mb4',
    ],

    // --- Alkalmazás ---
    'app' => [
        // Site bázis URL (záró perjellel). Pl. 'http://localhost:8888/szechenyi/'
        // Üresen hagyva relatív URL-eket használunk, ami helyi fejlesztéshez jó.
        'base_url'    => '',

        // Feltöltések helye (relatív a projekt gyökérhez)
        'upload_dir'  => 'uploads',

        // Megengedett képkiterjesztések
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],

        // Maximum képméret bájtban (alapból 10 MB)
        'max_size'    => 10 * 1024 * 1024,
    ],

    // --- Munkamenet / biztonság ---
    'session' => [
        // Süti név (egyedi, hogy más alkalmazással ne ütközzön)
        'name'        => 'szechenyi_admin',

        // Session inaktivitás után kijelentkeztet (másodperc, alapból 2 óra)
        'lifetime'    => 7200,
    ],
];
