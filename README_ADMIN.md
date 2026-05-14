# Admin felület — Telepítési útmutató

Ez a leírás végigvezet azon, hogyan állítsd be a Széchenyi Márk Fényképészet
oldalához tartozó admin felületet helyi gépen (macOS) és éles hostingon.

## Mit kapsz

- **Admin gomb** a kezdőlap láblécében (`index.html` → `admin/login.php`).
- **Bejelentkezés** felhasználónévvel és jelszóval.
- **Kategóriák**: létrehozás, átnevezés, sorrend (drag & drop), törlés.
  Kategória törlése a benne lévő képeket is törli.
- **Galéria**: képek feltöltése (több fájl egyszerre, drag & drop is működik),
  alt szöveg/felirat szerkesztése, kép áthelyezése másik kategóriába, főkép
  kijelölése, sorrend (drag & drop), törlés.
- **Dinamikus galéria oldal** (`galeria.php`) — az admin változások azonnal
  látszódnak a publikus oldalon, a régi `galeria.html` átirányítja a látogatókat.

## Követelmények

- **PHP 7.4 vagy újabb** (PDO_MYSQL, `fileinfo`, `gd` opcionális)
- **MySQL 5.7+ vagy MariaDB 10+**
- Webszerver, ami támogatja a PHP-t (Apache, nginx, vagy beépített `php -S`)
- A `uploads/` mappának írhatónak kell lennie

## 1. Helyi fejlesztés MAMP-pel (ajánlott Mac-en)

1. Telepítsd a [MAMP](https://www.mamp.info/)-et (ingyenes).
2. Másold ezt a teljes mappát (`/Users/igen/Desktop/szechenyi`) a MAMP htdocs
   alá. Példa:
   ```
   /Applications/MAMP/htdocs/szechenyi/
   ```
3. Indítsd a MAMP-et (Start Servers). A localhost címet és a portokat a MAMP
   felületén látod (alapból `http://localhost:8888`).
4. Nyisd meg a phpMyAdmint (MAMP → Open WebStart Page → phpMyAdmin), és hozz
   létre egy üres adatbázist:
   - Név: `szechenyi`
   - Charset: `utf8mb4_unicode_ci`
5. Add meg a kapcsolati adatokat a `db/config.php`-ban (lásd 3. lépés alább).

## 1b. Helyi fejlesztés a beépített PHP szerverrel (ha nincs MAMP)

Ha van PHP a gépen (`php -v` mutat verziót), és van egy futó MySQL:

```bash
cd /Users/igen/Desktop/szechenyi
php -S localhost:8000
```

Ezután a böngészőben: <http://localhost:8000>

## 2. Adatbázis létrehozása

phpMyAdminban (vagy CLI-ből):

```sql
CREATE DATABASE szechenyi
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

**A táblákat nem kell kézzel létrehozni** — az `install.php` megcsinálja.

## 3. Konfiguráció

```bash
cp db/config.example.php db/config.php
```

Nyisd meg a `db/config.php`-t, és írd át:

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,           // MAMP-en gyakran 8889
    'name' => 'szechenyi',
    'user' => 'root',
    'pass' => 'root',         // MAMP-en alapból 'root'
    'charset' => 'utf8mb4',
],
```

> **MAMP-en az alap MySQL adatok**: user `root`, jelszó `root`, port `8889`.

## 4. Telepítő futtatása

Nyisd meg böngészőben:

```
http://localhost:8888/szechenyi/install.php
```

Add meg az admin felhasználónevet és a jelszót (legalább 8 karakter), majd
nyomd meg a „Telepítés indítása” gombot.

A telepítő:
- létrehozza a `users`, `categories`, `images` táblákat,
- felveszi az admin usert,
- beszúr 5 alap kategóriát (Családi, Karácsonyi, Páros, Portré, Glamour),
- létrehozza az `uploads/` mappát.

Sikeres telepítés után **töröld az `install.php` fájlt**:

```bash
rm install.php
```

## 5. Bejelentkezés és használat

- Kezdőlap: <http://localhost:8888/szechenyi/index.html>
  → kattints a lábléc „ADMIN” linkjére
- Vagy közvetlenül: <http://localhost:8888/szechenyi/admin/login.php>

## 6. Mappa- és fájljogosultságok

Helyi gépen általában nincs vele dolgod. Éles hostingon, ha a feltöltés nem
megy, futtasd:

```bash
chmod 775 uploads
```

## 7. Élesbe állítás (webtárhely)

1. Az egész mappa tartalmát töltsd fel FTP/SFTP-vel a webtárhely gyökerébe.
2. Hozz létre MySQL adatbázist a hosting felületén.
3. Készítsd el a `db/config.php`-t a hosting adataival.
4. Futtasd egyszer a `https://oldalad.hu/install.php`-t.
5. **Töröld az `install.php`-t** és a `db/config.example.php`-t.
6. Ha lehetőség van rá, állítsd be HTTPS-t (admin sütik csak biztonságos
   kapcsolaton küldődnek `Secure` flag-gel).

## 8. Biztonsági tippek

- A `db/config.php` adatait soha ne tedd publikus repo-ba.
- Webszerver konfigurációban tiltsd le a `db/` és `uploads/` mappákban a PHP
  futtatást (a feltöltött kép soha ne legyen futtatható). Apache példa
  `.htaccess` az `uploads/`-ban:
  ```apache
  <FilesMatch "\.(php|phtml|phar|pl|py|jsp|asp|sh|cgi)$">
    Require all denied
  </FilesMatch>
  ```
- Erős jelszó kötelező, mert az admin felület közvetlenül elérhető a netről.

## 9. Hibaelhárítás

| Tünet | Megoldás |
|---|---|
| „Hiányzó konfiguráció” üzenet | Hozd létre a `db/config.php`-t. |
| „Adatbázis hiba” | Ellenőrizd a host/port/jelszó értékét a configban. |
| Bejelentkezés után visszadob | Süti probléma — engedélyezd a cookie-kat. |
| Feltöltés után 413 hiba | A szerver `upload_max_filesize` / `post_max_size` túl kicsi. PHP.ini-ben emeld pl. 20M-ra. |
| Drag & drop nem mented a sorrendet | Nézd a böngésző konzolt a hibákért; ellenőrizd, hogy nem időzött-e ki a session. |

## 10. Fájlstruktúra (admin része)

```
szechenyi/
├── install.php              ← egyszeri telepítő (telepítés után töröld!)
├── galeria.php              ← dinamikus galéria (DB-ből)
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── index.php            ← dashboard
│   ├── categories.php       ← kategóriák kezelése
│   ├── gallery.php          ← képek feltöltése + szerkesztése
│   ├── auth.php             ← session + CSRF
│   ├── _layout.php          ← közös admin layout
│   └── assets/
│       ├── admin.css
│       └── admin.js
├── db/
│   ├── schema.sql           ← táblák definíciója
│   ├── config.example.php   ← config minta (ezt másold át config.php-ra)
│   ├── config.php           ← saját MySQL adatok (NE töltsd fel publikusan!)
│   └── db.php               ← PDO + helper függvények
└── uploads/                 ← feltöltött képek ide kerülnek
```
