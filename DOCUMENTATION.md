# Széchenyi Márk Fényképészet — Fejlesztői dokumentáció

> Ez a dokumentum mindent tartalmaz a projektről, amit egy kódon dolgozó fejlesztő tudni szeretne: architektúra, fájlszerkezet, adatbázis, biztonság, gyakori szerkesztések, hibaelhárítás. Olvasd el a 1-2. fejezetet a teljes kép kedvéért, utána már célzottan ugorhatsz a fejezetekre, amelyek a feladatodhoz kellenek.

---

## Tartalomjegyzék

1. [Áttekintés](#1-áttekintés)
2. [Mappastruktúra](#2-mappastruktúra)
3. [Tech stack és követelmények](#3-tech-stack-és-követelmények)
4. [Publikus oldal](#4-publikus-oldal)
5. [Admin rendszer](#5-admin-rendszer)
6. [Adatbázis séma](#6-adatbázis-séma)
7. [Kép feltöltési folyamat](#7-kép-feltöltési-folyamat)
8. [Kategóriák kezelése](#8-kategóriák-kezelése)
9. [CSS architektúra](#9-css-architektúra)
10. [JavaScript architektúra](#10-javascript-architektúra)
11. [Biztonság](#11-biztonság)
12. [Lokális fejlesztés](#12-lokális-fejlesztés)
13. [Élesítés](#13-élesítés)
14. [Gyakori szerkesztések (how-to)](#14-gyakori-szerkesztések-how-to)
15. [Hibaelhárítás](#15-hibaelhárítás)
16. [Konvenciók](#16-konvenciók)

---

## 1. Áttekintés

A projekt **Széchenyi Márk fotográfus** portfólió oldala, **minimalista fekete-fehér** dizájnnal és teljes admin felülettel.

**Két fő rész:**

- **Publikus oldal** — statikus HTML oldalak (kezdőlap, rólam, kapcsolat, stúdió stb.) + egy dinamikus `galeria.php` oldal, amely az adatbázisból olvas
- **Admin felület** (`/admin/`) — PHP-ban írt webfelület, ahol be lehet jelentkezni, kategóriákat és képeket lehet kezelni

**Tech stack:**

- Frontend: vanilla HTML/CSS/JS (semmi framework, semmi build)
- Backend: PHP 7.4+ (PDO)
- Adatbázis: MySQL 5.7+ / MariaDB 10+
- Fontkészlet: Inter (Google Fonts)
- Képek: lokálisan tárolva az `uploads/` mappában (kivéve a publikus oldal placeholder képeit, amelyek Google CDN-en vannak)

**Adatfolyam dióhéjban:**

```
Admin böngészőben → POST /admin/gallery.php → PHP feldolgozza → MySQL frissül + fájl uploads/-ban
Látogató böngészőben → GET /galeria.php → PHP SELECT-tel olvas → HTML rendereli a képeket
```

---

## 2. Mappastruktúra

```
szechenyi/
├── DOCUMENTATION.md          ← ez a fájl
├── README_ADMIN.md           ← telepítési kézikönyv felhasználóknak
├── CHANGELOG.md              ← változási napló
├── AUDIT.md                  ← korábbi audit
├── FIX_PLAN.md               ← korábbi javítási terv
│
├── index.html                ← kezdőlap (statikus)
├── szolgaltatasok.html       ← szolgáltatások (statikus, has-aside)
├── vaszonkep.html            ← vászonkép (statikus)
├── studio.html               ← stúdió (statikus)
├── rolam.html                ← rólam (statikus)
├── kapcsolat.html            ← kapcsolat (statikus)
├── galeria.php               ← galéria (DINAMIKUS — DB-ből rendereli)
├── galeria.html              ← csak átirányít galeria.php-ra
├── lightbox.html             ← legacy redirect a galériához
├── favicon.svg
├── robots.txt
├── sitemap.xml
│
├── install.php               ← egyszeri telepítő (élesben TÖRLENDŐ)
│
├── admin/                    ← admin felület
│   ├── _layout.php           ← közös header/footer/nav függvények
│   ├── auth.php              ← session, login/logout, CSRF helperek
│   ├── login.php             ← bejelentkezési form
│   ├── logout.php            ← kijelentkezés (session destroy)
│   ├── index.php             ← admin dashboard (statisztikák, kat. kártyák)
│   ├── categories.php        ← kategóriák CRUD + drag&drop sorrend
│   ├── gallery.php           ← képek feltöltése, szerkesztése, törlése
│   └── assets/
│       ├── admin.css         ← admin stílusok
│       └── admin.js          ← admin JS (drag&drop, upload preview, confirm)
│
├── assets/                   ← publikus oldal asset-jei
│   ├── css/main.css          ← KÖZPONTI stylesheet (~1600 sor)
│   ├── js/main.js            ← belépési pont
│   ├── js/components/        ← külön JS modulok
│   │   ├── mobile-menu.js
│   │   ├── active-nav.js
│   │   ├── lightbox.js
│   │   ├── forms.js
│   │   └── gallery-filter.js  ← galéria szűrő logika
│   └── images/
│       └── README.md         ← (kép könyvtár, statikus képeknek)
│
├── db/                       ← adatbázis és config
│   ├── schema.sql            ← táblák definíciója (CREATE TABLE)
│   ├── config.example.php    ← config minta
│   ├── config.php            ← TÉNYLEGES MySQL credentials (gitignore-ban!)
│   ├── db.php                ← PDO kapcsolat + helper függvények
│   └── .htaccess             ← tiltja a web-ről való hozzáférést
│
└── uploads/                  ← feltöltött képek
    └── .htaccess             ← tiltja a PHP futtatást ebben a mappában
```

---

## 3. Tech stack és követelmények

| Komponens | Minimum verzió | Megjegyzés |
|-----------|----------------|------------|
| PHP       | 7.4            | `PDO_MYSQL`, `fileinfo` modul kell |
| MySQL     | 5.7            | vagy MariaDB 10+, `utf8mb4` charset |
| Webszerver | bármi PHP-vel | Apache, nginx + PHP-FPM, vagy `php -S` |
| Böngésző  | modern         | ES2017+ JS, CSS custom properties |

**Külső függőség:** csak a Google Fonts (Inter) — minden más a projektben van.

---

## 4. Publikus oldal

### 4.1 Oldalak

| URL                     | Fájl                  | Jellege            | Megjegyzés |
|-------------------------|----------------------|--------------------|-------------|
| `/`                     | `index.html`         | statikus           | kezdőlap, kategória grid, admin link a footerben |
| `/galeria.php`          | `galeria.php`        | **DINAMIKUS**      | kategória aside + képek DB-ből |
| `/szolgaltatasok.html`  | `szolgaltatasok.html`| statikus, has-aside | szolgáltatások + árak, bal oldali szekcióválasztó |
| `/vaszonkep.html`       | `vaszonkep.html`     | statikus           | |
| `/studio.html`          | `studio.html`        | statikus           | |
| `/rolam.html`           | `rolam.html`         | statikus           | |
| `/kapcsolat.html`       | `kapcsolat.html`     | statikus           | kapcsolati form (jelenleg dummy) |

### 4.2 Közös elemek minden oldalon

Minden oldal ugyanazt a fejlécet és láblécet használja:

```html
<header class="site-header">
  <a class="site-header__wordmark" href="index.html">Fényképészet</a>
  <nav class="site-nav"><ul class="site-nav__list">...</ul></nav>
  <button class="mobile-menu-toggle" data-mobile-menu-toggle>...</button>
</header>

<div id="mobile-menu" class="mobile-menu" data-mobile-menu>...</div>

<main id="main" class="page-wrapper">...</main>

<a class="floating-cta" href="kapcsolat.html">...</a>

<footer class="site-footer">...</footer>
```

> **Ha új menüpontot adsz hozzá**, az `<nav class="site-nav">` ÉS az `<nav>` a `.mobile-menu`-ben — **mindkettőt** kell frissíteni MINDEN oldalon (8 fájl). Lásd: [14.1 Új menüpont](#141-új-menüpont-a-navbarhoz).

### 4.3 Breakpointok (reszponzív)

Az oldal **mobile-first**. Breakpointok az `assets/css/main.css`-ben:

| Breakpoint | min-width | Mi történik |
|------------|-----------|-------------|
| (default)  | < 480px   | mobil layout, mobil menü hamburgerrel |
| sm         | 480px     | kisebb finomítások |
| md         | 768px     | desktop nav megjelenik, hamburger eltűnik, kategória/galéria grid 3 oszlopos |
| lg         | 1024px    | `.site-aside` sidebar megjelenik (galéria, szolgáltatások), kategória grid 5 oszlopos |
| xl         | 1280px    | tipográfia és spacing finomítások |

A breakpointokat keresd a CSS-ben így: `grep "@media (min-width" assets/css/main.css`

### 4.4 Galéria oldal (`galeria.php`) — dinamikus

Ez az egyetlen PHP-ban írt publikus oldal. A `db/db.php` betöltése után két SELECT-tel olvas:

```php
// 1) kategóriák a bal oldali sidebar-hoz és mobil menühöz
$categories = $pdo->query('SELECT id, slug, name FROM categories ORDER BY sort_order, id')->fetchAll();

// 2) képek a galéria gridhez (join-olva a kategória slug-ra)
$images = $pdo->query("SELECT i.*, c.slug AS cat_slug, c.name AS cat_name
                        FROM images i JOIN categories c ON c.id = i.category_id
                        ORDER BY c.sort_order, i.sort_order, i.id")->fetchAll();
```

**Szűrési logika:** kliensoldalon, `assets/js/components/gallery-filter.js`:

- Felhasználó rákattint egy kategória linkre → `data-gallery-filter="slug"` attribútum
- JS minden képet végignéz: ha `data-category === filter`, megmutatja, egyébként `hidden` attribútumot kap
- URL hash-szel is működik: `galeria.php#csaladi` automatikusan szűr Családi-ra

> A `data-category` az **image** elemen a kategória slug. Ha a slug üres (régi bug), a szűrő nem működik — lásd: [8.4 Slug-ok](#84-slug-generálás).

### 4.5 Lightbox

A galéria képekre kattintva nagy nézetben nyílnak (lightbox). A logika a `assets/js/components/lightbox.js`-ben van. A galériában minden `.gallery__item` automatikusan trigger:

```html
<a class="gallery__item" data-lightbox-trigger
   data-lightbox-caption="Családi · 2024"
   href="#">
  <img src="..." alt="...">
</a>
```

A `data-lightbox-trigger` jelenléte aktiválja, a `data-lightbox-caption` a nagy nézet felirata.

---

## 5. Admin rendszer

### 5.1 Belépési folyamat

1. Felhasználó megnyitja `/admin/login.php`-t
2. Megadja felhasználónevét + jelszót, POST-ol
3. `auth.php` → `sz_login()` lekérdezi a `users` táblát, `password_verify`-jel ellenőrzi a hash-t
4. Sikerre: új session ID-t generál (`session_regenerate_id`), `$_SESSION['user_id']` beállítva, redirect az `index.php`-ra
5. Hibára: hibaüzenet, form újra rendereli

### 5.2 Védett oldalak

Minden admin oldal így kezdődik:

```php
require_once __DIR__ . '/_layout.php';   // ez sz_require_login()-t hív
```

Ha nincs aktív session, automatikusan átirányít a `login.php?redirect=<jelenlegi-URL>`-re.

### 5.3 CSRF védelem

Minden POST formban van rejtett `csrf` mező:

```php
<input type="hidden" name="csrf" value="<?= sz_e(sz_csrf_token()) ?>">
```

A server az `auth.php`-ban:
- `sz_csrf_token()` generál tokent (32 byte hex) ha még nincs, és session-be menti
- `sz_csrf_check($_POST['csrf'])` `hash_equals` összehasonlítást végez (timing-safe)

Ha CSRF hibás, a `try/catch` blokk kapja el és hibaüzenetet ír. AJAX (drag&drop reorder) is ugyanazt a tokent küldi (`data-csrf` attribútumból az admin.js olvassa).

### 5.4 Layout függvények (`admin/_layout.php`)

Két függvényt biztosít:

```php
sz_admin_header(string $pageTitle, string $activeNav);
// kiírja: <!DOCTYPE html>... fej, top nav (Áttekintés / Kategóriák / Galéria), <main>

sz_admin_footer();
// </main></body></html>
```

Az `$activeNav` paraméter határozza meg, melyik nav link kapja az `is-active` osztályt. Lehetséges értékek: `'dashboard'`, `'categories'`, `'gallery'`.

### 5.5 Admin oldalak

- **`index.php`** — dashboard: statisztika kártyák (kategóriák / képek száma), majd kategória kártya grid az adminhoz vezető linkekkel
- **`categories.php`** — kategóriák listája (drag&drop átrendezhetőek), név + slug inline szerkesztés, törlés, alul új kategória form
- **`gallery.php`** — kategória tab váltó felül, alatta upload zóna + kép grid (per kép: alt/caption szerkesztés, kategória váltás, főkép kijelölés, törlés, drag&drop sorrend)

### 5.6 Session lejárat

Az `auth.php` figyeli az inaktivitást:

```php
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $cfg['lifetime'])) {
    sz_logout();
}
```

Alapból 2 óra (`'lifetime' => 7200` a `db/config.php`-ban). Minden kérésnél frissül a `last_activity`.

---

## 6. Adatbázis séma

Definíció: `db/schema.sql`. Három tábla.

### 6.1 `users`

```sql
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,    -- BCRYPT (password_hash())
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL
);
```

**Új admin user kézzel:**

```bash
php -r 'echo password_hash("ÚJ_JELSZÓ", PASSWORD_BCRYPT) . PHP_EOL;'
# másold a kimenetet:
mysql -u root szechenyi -e "INSERT INTO users (username, password_hash) VALUES ('uj_admin', '<HASH_IDE>');"
```

### 6.2 `categories`

```sql
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(64) NOT NULL UNIQUE,        -- URL-barát, csak [a-z0-9-]
  name VARCHAR(128) NOT NULL,              -- humán olvasható
  sort_order INT NOT NULL DEFAULT 0,       -- alacsonyabb előbb
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Slug konvenció:** csak kisbetű, szám, kötőjel. Az `sz_slug()` segédfüggvény (`db/db.php`) ékezeteket ASCII-ra konvertál, mindent kisbetűsít, és nem-alfanumerikust kötőjelre cserél.

### 6.3 `images`

```sql
CREATE TABLE images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,       -- FK categories.id
  image_url VARCHAR(500) NOT NULL,         -- 'uploads/foo.jpg' VAGY 'https://...'
  width INT UNSIGNED NULL,                 -- pixelben (getimagesize-ből)
  height INT UNSIGNED NULL,
  alt_text VARCHAR(500) NULL,              -- akadálymentesítés
  caption VARCHAR(255) NULL,               -- lightbox felirat
  sort_order INT NOT NULL DEFAULT 0,       -- kategórián belüli sorrend
  is_cover TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = főkép a kategórián belül
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

**Fontos:** `ON DELETE CASCADE` → ha kategóriát törölsz, az összes kép DB sora is törlődik. A fizikai fájlok törlése a `sz_delete_image_file()` függvény dolga (`admin/categories.php`).

**`is_cover`** — kategórián belül csak egy kép legyen `is_cover=1`. A `set_cover` action a `gallery.php`-ban először 0-ra állít minden képet az adott kategóriában, aztán 1-re a kiválasztottat (transaction-ben).

### 6.4 Hasznos lekérdezések

```sql
-- minden kategória + képszám
SELECT c.name, COUNT(i.id) AS images FROM categories c
LEFT JOIN images i ON i.category_id = c.id GROUP BY c.id ORDER BY c.sort_order;

-- egy adott kategória képei sorrendben
SELECT * FROM images WHERE category_id = 1 ORDER BY sort_order, id;

-- főképek minden kategóriához
SELECT c.name, i.image_url FROM categories c
LEFT JOIN images i ON i.category_id = c.id AND i.is_cover = 1
ORDER BY c.sort_order;

-- üres slug kategóriák (régebbi bug)
SELECT * FROM categories WHERE slug = '' OR slug IS NULL;
```

---

## 7. Kép feltöltési folyamat

### 7.1 Frontend (admin/gallery.php)

```html
<form method="post" enctype="multipart/form-data" class="admin-upload" data-max-size="...">
  <input type="hidden" name="csrf" value="<?=...?>">
  <input type="hidden" name="action" value="upload">
  <input type="hidden" name="category_id" value="<?=$activeCatId?>">
  <label class="admin-dropzone">
    <input type="file" name="files[]" accept=".jpg,..." multiple data-file-input hidden>
    ...
  </label>
  <div data-previews hidden></div>
  <button type="submit" data-upload-submit disabled>Feltöltés</button>
</form>
```

A JS (`admin/assets/admin.js → initDropzone()`):
- File picker click handler
- Drag&drop support (DataTransfer API)
- Előnézet generálása `URL.createObjectURL()`-lel
- Méret pre-validáció (data-max-size attribútum alapján)

### 7.2 Backend feldolgozás

`admin/gallery.php` az upload POST-ot kezeli:

1. CSRF check
2. Kategória ID ellenőrzés
3. `$_FILES['files']` üres? → ha igen, ellenőrizzük, hogy a `post_max_size` túllépéseről van-e szó (akkor `$_FILES` üres lehet)
4. `sz_handle_uploads()` per-fájl feldolgozást végez:
   - PHP upload error code (`UPLOAD_ERR_INI_SIZE` stb.) → hibalista
   - Méret check (`$cfg['max_size']`, alapból 10 MB) → hibalista
   - `is_uploaded_file()` (security: csak tényleg feltöltött fájlt fogadunk)
   - Kiterjesztés whitelist: jpg, jpeg, png, webp
   - MIME ellenőrzés `finfo` modullal: image/jpeg, image/png, image/webp
   - `getimagesize()` → width, height
   - Egyedi fájlnév: `YmdHis_8hexchars.ext`
   - `move_uploaded_file()` az `uploads/`-ba
   - DB INSERT

A függvény `[int $inserted, string[] $errors]` tuple-t ad vissza. A hívó eldönti, hogy success vagy error notice-t mutat.

### 7.3 PHP limitek

| Direktíva | Default | Ajánlott |
|-----------|---------|----------|
| `upload_max_filesize` | 2M | 20M |
| `post_max_size` | 8M | 80M (több fájl batchben) |
| `memory_limit` | 128M | 256M |
| `max_file_uploads` | 20 | 20 (default jó) |

**Helyi fejlesztéshez** indítsd a PHP szervert így:

```bash
cd /Users/igen/Desktop/szechenyi && php \
  -d upload_max_filesize=20M \
  -d post_max_size=80M \
  -d memory_limit=256M \
  -S localhost:8000
```

**Élesben** `.htaccess`-ben vagy `php.ini`-ben kell beállítani.

### 7.4 Fájl tárolás

Az `uploads/` mappa:
- A fájl konkrét neve `time()_randomhex.ext` formátum (pl. `20260513_142233_a1b2c3d4.jpg`)
- A DB `image_url` mezőjében relatív útvonal mentődik: `'uploads/20260513_142233_a1b2c3d4.jpg'`
- A `sz_image_src($url, $prefix='')` helper kezeli az URL-t: ha külső (`https://...`), úgy hagyja; ha relatív, hozzáfűzi a prefixet (admin/-ból `'../'`, gyökérről `''`)

---

## 8. Kategóriák kezelése

### 8.1 Műveletek (admin/categories.php)

A `$_POST['action']` alapján:

| Action      | Mit csinál                                                |
|-------------|-----------------------------------------------------------|
| `create`    | Új kategória + auto slug + max sort_order + 10            |
| `update`    | Név és slug módosítása, slug egyediség check más sorokra  |
| `delete`    | Kategória + minden képe (CASCADE + fájl törlés)           |
| `reorder`   | JSON ID lista alapján sort_order frissítése (AJAX)        |

### 8.2 Drag & drop sorrend

Frontend: `admin/assets/admin.js → initSortable()`:
- Listaelemek `draggable` attribútumot kapnak a handle-ön való mousedown-ra
- HTML5 drag API: `dragstart`, `dragover`, `drop`
- Drop után minden elem `data-id`-jét egy JSON tömbbe gyűjtve POST-olja a szervernek
- A szerver `sort_order = (idx + 1) * 10` módon frissít

Az `* 10` szándékos: ha kézzel kell beékelni közbe egy értéket, marad hely.

### 8.3 Slug egyediség

```php
$base = $slug; $i = 2;
while (true) {
    $stmt = $pdo->prepare('SELECT 1 FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    if (!$stmt->fetch()) break;
    $slug = $base . '-' . $i++;
}
```

Ha pl. „Esküvő" → `eskuvo` ütközik, akkor `eskuvo-2`, `eskuvo-3` stb.

### 8.4 Slug generálás (`sz_slug()`)

Lépések (`db/db.php`):

1. Trim
2. Ha üres → vissza `''`
3. Magyar ékezetek transzliterálása (á→a, é→e, ő→o, ű→u stb.)
4. Lowercase
5. Nem `[a-z0-9]` karaktert kötőjelre
6. Levezető/követő kötőjel trim
7. Ha végül üres lenne → `'kategoria'` fallback

> **Korábban javított bug**: a form `$_POST['slug'] ?? $name` régen csak NULL-ra váltott vissza, üres stringre nem. Ezt mára kezeli a `trim()` + nem-üres check. Új bug elkerülése: ne hagyd ki ezt a guardot, ha új helyen használod a slug-ot.

### 8.5 Kategória törlése = képek is törlődnek

A `delete` action a `categories.php`-ban így működik:

```php
$imgs = $pdo->prepare('SELECT image_url FROM images WHERE category_id = ?');
$imgs->execute([$id]);
foreach ($imgs->fetchAll() as $row) {
    sz_delete_image_file($row['image_url']);  // unlink() a uploads/-ban
}
$pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
// DB sorok: CASCADE törli a images sorait
```

**Figyelem:** nincs „soft delete", visszavonhatatlan művelet. JS confirm dialog jelenik meg előtte.

---

## 9. CSS architektúra

### 9.1 Központi fájl: `assets/css/main.css` (~1600 sor)

Számozott szekciók, könnyű keresni:

| # | Szekció | Sorok kb. |
|---|---------|-----------|
| 1 | CSS Reset / Normalize | 1-40 |
| 2 | Design Tokens (custom properties) | 40-180 |
| 3 | Base + Utility | 180-220 |
| 4 | Layout (page-wrapper) | 200-215 |
| 5 | Header + Nav | 215-320 |
| 6 | Mobile menu | 320-378 |
| 7 | Aside (sidebar) | 378-470 |
| 8 | Buttons | 450-490 |
| 9 | Hero | 530-580 |
| 10 | Category grid | 580-650 |
| 11 | Gallery | 700-770 |
| 12 | Service cards | 770-830 |
| 13 | About + Portrait | 830-940 |
| ... | további szekciók | |
| 30 | Print styles | 1568-1590 |

Keresd a szekciókat így: `grep "===" assets/css/main.css`

### 9.2 Design tokens (custom properties)

A `:root`-ban definiált változók (~70 db). Mindenhol ezeket használjuk, soha ne hard-code-olj:

```css
:root {
  /* Colors */
  --color-primary: #000;
  --color-on-primary: #fff;
  --color-bg: #fff;
  --color-surface-2: #fafafa;
  --color-text: #000;
  --color-text-muted: #525252;
  --color-border: #e5e5e5;

  /* Typography */
  --font-family: 'Inter', ...;
  --fs-xs: 0.6875rem;
  --fs-base: 0.875rem;
  /* ... */

  /* Spacing scale */
  --space-1: 0.25rem;  /* 4 */
  --space-4: 1rem;     /* 16 */
  --space-8: 2rem;     /* 32 */

  /* Z-index */
  --z-sticky: 100;
  --z-header: 200;
  --z-mobile-menu: 900;
  --z-lightbox: 1000;
}
```

> **Színpaletta váltása**: csak a `:root` változókat módosítsd, és az egész oldal átszíneződik. Lásd: [14.4 Új színpaletta](#144-új-színpaletta).

### 9.3 Naming konvenció: BEM-szerű

```
.block            ← komponens
.block__element   ← elem a komponensen belül
.block--modifier  ← variáns
.is-state         ← állapot (is-active, is-open, is-dragging)
```

Példák a kódból:

- `.site-header`, `.site-header__wordmark`
- `.gallery`, `.gallery__item`, `.gallery__caption`
- `.btn`, `.btn--primary`, `.btn--ghost`
- `.admin-cat-row`, `.admin-cat-row__form`, `.admin-cat-row__buttons`

### 9.4 Reszponzív stratégia

**Mobile-first.** Az alapstílusok mobilra szólnak, a `@media (min-width: …)` blokkokban felülírjuk nagyobb képernyőre.

A breakpointokat NE redefiniáld új helyen — használd a meglévő `@media (min-width: 768px)`, `@media (min-width: 1024px)` blokkokat.

### 9.5 Admin CSS (admin/assets/admin.css)

Külön stílus az admin felülethez. Saját namespace: `.admin-*`. A publikus CSS design tokeneit használja (`var(--font-family)`, stb.), így konzisztens.

---

## 10. JavaScript architektúra

### 10.1 Belépési pont: `assets/js/main.js`

ES Modules. Komponens import-ok, majd inicializálás DOM ready-re:

```js
import { initMobileMenu } from './components/mobile-menu.js';
import { initLightbox } from './components/lightbox.js';
import { initGalleryFilter } from './components/gallery-filter.js';
import { initForms } from './components/forms.js';
import { initActiveNav } from './components/active-nav.js';

const ready = (fn) => {
  if (document.readyState !== 'loading') fn();
  else document.addEventListener('DOMContentLoaded', fn);
};

ready(() => {
  initMobileMenu();
  initLightbox();
  initGalleryFilter();
  initForms();
  initActiveNav();
});
```

Minden komponens **idempotens** és **fail-safe** — ha a hozzá tartozó DOM elemek nincsenek az oldalon, csendben kilép. Ezért tudjuk minden oldalon ugyanazt a `main.js`-t betölteni.

### 10.2 Komponensek

| Fájl | Mit csinál |
|------|-----------|
| `mobile-menu.js` | hamburger gomb toggle, ESC/scroll lock, aria-attribútumok |
| `active-nav.js`  | aktuális oldal nav linkjére `aria-current="page"` |
| `lightbox.js`    | galéria képek nagy nézete + prev/next gombok |
| `gallery-filter.js` | kategória szűrés (`data-gallery-filter` + `data-category`) |
| `forms.js`       | kapcsolat form validáció (jelenleg dummy) |

### 10.3 Admin JS (`admin/assets/admin.js`)

Egyetlen file, IIFE-ben. Három fő rész:

```js
initConfirms();   // form[data-confirm]: confirm dialog submit előtt
initDropzone();   // file input + drag&drop + preview
initSortable(container, action);  // drag&drop reorder, AJAX POST
```

A `initSortable` általános: bármelyik `<ul data-sortable-*>` listára működik, ha az elemeknek `data-id` van, és van benne `.admin-handle` fogantyú.

---

## 11. Biztonság

### 11.1 Auth

- **Password hash:** `password_hash($pw, PASSWORD_BCRYPT)` → 60 karakteres BCRYPT hash, plain text NEM tárolódik soha
- **Verify:** `password_verify($plain, $hash)` — timing-safe
- **Session fixation védelem:** sikeres login után `session_regenerate_id(true)`
- **Cookie flag-ek:** `httponly`, `samesite=Lax`, `secure` (csak HTTPS-en)

### 11.2 CSRF

- Token generálás: `bin2hex(random_bytes(32))` → 64 hex karakter
- Session-be mentve, minden POST-on újra ellenőrizve `hash_equals`-szel
- Hibás token → 403 + JSON / hibaüzenet

### 11.3 SQL injection

**Minden** lekérdezés prepared statement, PDO-val:

```php
$stmt = $pdo->prepare('UPDATE images SET alt_text = ? WHERE id = ?');
$stmt->execute([$alt, $id]);
```

Soha ne fűzz string-be felhasználói adatot SQL-be.

### 11.4 XSS

Minden DB-ből vagy felhasználótól származó adatot HTML-be kiíráskor escape-elünk:

```php
<?= sz_e($category['name']) ?>     // ezt használd MINDIG
<!-- NE: -->
<?= $category['name'] ?>
```

A `sz_e()` (`db/db.php`) wrapper: `htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.

### 11.5 Fájl feltöltés

- **Whitelist kiterjesztések:** csak jpg/jpeg/png/webp
- **MIME ellenőrzés** `finfo` modullal (NEM a kliens által küldött `Content-Type`)
- **`is_uploaded_file()` check** — security against path manipulation
- **Egyedi fájlnév** — soha ne tartjuk meg az eredeti `$_FILES['name']`-et a tárolásnál (path traversal védelem)
- **`uploads/.htaccess`** — letiltja a PHP/script futtatást a feltöltési mappában (ha valaki shellt töltene fel, ne tudja futtatni)

### 11.6 Webről nem elérhető fájlok

- `db/.htaccess` — `Require all denied` (Apache-on a config file védve)
- Ha nincs Apache (csak `php -S`), akkor a `db/` mappa minden tartalma elérhető — fejlesztéshez OK, élesben mindenképp Apache vagy nginx kell

### 11.7 install.php

- **Élesben TÖRLENDŐ.** Egy hívással `DROP TABLE`-ezi és újragenerálja az adatbázist
- Lokálisan elhagyható, de óvatosan: ha véletlenül megnyitod és submitálsz, minden adat törlődik
- Plusz biztonság: lehetne hozzáadni egy check-et („már van user → tagadd meg") — jelenleg nincs

---

## 12. Lokális fejlesztés

### 12.1 Indítás (Mac, brew alapján)

```bash
# Egyszer: MySQL telepítés és indítás
brew install mysql
brew services start mysql

# Egyszer: adatbázis és config
mysql -u root -e "CREATE DATABASE szechenyi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cp db/config.example.php db/config.php

# Egyszer: telepítő böngészőből
php -S localhost:8000
# → nyisd meg http://localhost:8000/install.php

# Mindennap: webszerver indítás magas limitekkel
cd /Users/igen/Desktop/szechenyi && php \
  -d upload_max_filesize=20M \
  -d post_max_size=80M \
  -d memory_limit=256M \
  -S localhost:8000
```

### 12.2 MySQL kapcsolat tesztelés

```bash
mysql -u root szechenyi -e "SELECT COUNT(*) FROM categories;"
```

Ha nem megy, ellenőrizd:
- `brew services list` — fut a MySQL?
- `db/config.php` — helyes-e a `user`/`pass`/`port`?

### 12.3 Logok

A `php -S` parancsablakban íródnak az error log-ok és request log-ok. Mindenképp **nézd meg**, ha valami nem működik.

### 12.4 Élő reload

Nincs build-tool. Csak frissítsd a böngészőt (Cmd+Shift+R cache-bypass-szal). A `.css`/`.js` fájlokat NE cache-eld erősen — fejlesztéshez a hard refresh elég.

---

## 13. Élesítés

### 13.1 Lépések

1. Hosting beszerzése PHP 7.4+ és MySQL támogatással (pl. Magyarban: rackhost, dhosting, Hostlandia)
2. MySQL adatbázis létrehozása a hosting panelen
3. FTP/SFTP feltöltés: az ÖSSZES fájlt feltölted (kivéve `db/config.php` — ezt a hostinghoz igazítva)
4. `db/config.php` készítése a hosting adatokkal
5. Telepítő futtatás: `https://oldalad.hu/install.php` egyszer
6. **TÖRÖLD** az `install.php`-t (SFTP-vel, vagy `rm install.php` SSH-val)
7. Ha lehet, HTTPS-t kapcsolj be (Let's Encrypt) — admin sütik secure flag-jéhez kell

### 13.2 Biztonsági ellenőrzőlista

- [ ] `install.php` törölve
- [ ] `db/config.php` jó adatokkal, web-ről NEM elérhető (Apache `.htaccess` aktív)
- [ ] `uploads/.htaccess` aktív (PHP futtatás letiltva)
- [ ] HTTPS aktív, redirect HTTP → HTTPS
- [ ] PHP `display_errors=Off` éles módban (production)
- [ ] Erős admin jelszó (12+ karakter, random)

### 13.3 PHP `display_errors` éles módban

A `db/db.php` jelenleg `ini_set('display_errors', '1')` — fejlesztéshez OK, élesben **kapcsold ki**:

```php
if (!defined('SZ_BOOTSTRAPPED')) {
    define('SZ_BOOTSTRAPPED', true);
    error_reporting(E_ALL);
    ini_set('display_errors', '0');   // ← 1 helyett 0
    ini_set('log_errors', '1');       // helyette a log-ba
}
```

---

## 14. Gyakori szerkesztések (how-to)

### 14.1 Új menüpont a navbarhoz

**Mit kell tenned:**

1. Nyisd meg az összes HTML és PHP oldalt (index.html, galeria.php, szolgaltatasok.html, vaszonkep.html, studio.html, rolam.html, kapcsolat.html, lightbox.html)
2. **Két helyen** add hozzá az új linket minden fájlban:
   - `<nav class="site-nav">` `<ul>` — desktop nav
   - `<div id="mobile-menu">` `<nav>` `<ul>` — mobil nav

Egy új menüpont HTML-je:

```html
<!-- desktop -->
<li><a class="site-nav__link" data-nav-link href="uj-oldal.html">Új oldal</a></li>

<!-- mobil -->
<li><a class="mobile-menu__link" data-nav-link href="uj-oldal.html">Új oldal</a></li>
```

> Tipp: használd a `sed`-et a tömeges szerkesztéshez, ha 8 fájlban kell hozzáadni egyszerre. Vagy egy egyszerű PHP `include`-dal centralizálhatnád a header-t (nagyobb refaktor).

### 14.2 Új admin oldal

1. Hozz létre `admin/uj-oldal.php`-t:

```php
<?php
require_once __DIR__ . '/_layout.php';   // auth + layout

// kódod itt ...

sz_admin_header('Új oldal címe', 'gallery');   // 2. param: melyik nav legyen aktív
?>
<h1>Új oldal</h1>
<?php
sz_admin_footer();
```

2. Add hozzá a felső navhoz (`admin/_layout.php`):

```php
<a href="uj-oldal.php" class="admin-nav__link <?= $activeNav==='uj'?'is-active':'' ?>">Új oldal</a>
```

### 14.3 Új mező egy képhez (pl. „kameraadatok")

1. **DB-ben:** új oszlop hozzáadása

```sql
ALTER TABLE images ADD COLUMN camera_info VARCHAR(255) NULL AFTER caption;
```

2. **Admin form (`admin/gallery.php`):** új input

```html
<label class="admin-field">
  <span class="admin-field__label">Kamera adatok</span>
  <input class="admin-input admin-input--sm" name="camera_info"
         value="<?= sz_e($img['camera_info'] ?? '') ?>">
</label>
```

3. **Admin update handler (`gallery.php`):**

```php
$cameraInfo = trim((string)($_POST['camera_info'] ?? ''));
$pdo->prepare('UPDATE images SET ..., camera_info = ? WHERE id = ?')
    ->execute([..., $cameraInfo, $id]);
```

4. **Publikus oldalon (`galeria.php`):** a SELECT-be vedd be, és rendereld

```sql
SELECT i.id, ..., i.camera_info, ...
```

```html
<small><?= sz_e($img['camera_info']) ?></small>
```

### 14.4 Új színpaletta

Csak a `:root` változókat módosítsd az `assets/css/main.css` elején (~36-50 sor):

```css
:root {
  --color-primary: #1a5fff;        /* fekete helyett kék */
  --color-on-primary: #ffffff;
  --color-text: #14213d;
  --color-bg: #faf8f3;             /* meleg háttér */
  --color-surface-2: #f4f1ea;
  /* ... */
}
```

Az admin oldal is automatikusan átveszi, mert `var(--font-family)` és más tokeneket használ.

### 14.5 Új kép formátum engedélyezése (pl. AVIF)

`db/config.php`-ban:

```php
'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp', 'avif'],
```

A MIME check-be (`admin/gallery.php → sz_handle_uploads`):

```php
$okMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
```

A HTML `accept` attribútum (`admin/gallery.php` upload form):

```html
<input type="file" accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif" ...>
```

### 14.6 Új menü szakasz a galéria mobile menüjébe

A `galeria.php`-ban a mobile menü tartalmaz egy „Kategóriák" szekciót, ami a DB-ből generálódik. Új kategóriáknak ott automatikusan megjelenik, nem kell HTML módosítás.

### 14.7 Lapszámozás (paginálás) a galériához

Jelenleg nincs — az összes képet egyszerre tölti be a galéria. Ha sok kép van (100+), érdemes lehet:

1. `galeria.php`-ba LIMIT/OFFSET a SELECT-be
2. `?page=2` paraméter olvasás
3. Lapozó UI alulra

### 14.8 Admin szerepkörök (több user típus)

Jelenleg minden user „superadmin". Ha role-okat akarsz:

```sql
ALTER TABLE users ADD COLUMN role ENUM('admin','editor') NOT NULL DEFAULT 'admin';
```

Aztán a `_layout.php` `sz_require_login()` helyén ellenőrizd: `if ($user['role'] !== 'admin') ...`

---

## 15. Hibaelhárítás

### 15.1 „Hiányzó konfiguráció" hibaüzenet

- Hiányzik a `db/config.php`. Másold: `cp db/config.example.php db/config.php` és töltsd ki

### 15.2 „Adatbázis hiba" / „SQLSTATE[HY000] [2002]"

- MySQL nem fut. `brew services start mysql`
- Vagy rossz host/port: `db/config.php`-ban ellenőrizd

### 15.3 „0 kép feltöltve" zöld bar

- A PHP `upload_max_filesize` túl kicsi. Indítsd újra a `php -S`-t magasabb limitekkel (lásd 7.3)
- Vagy a kép tényleg nagyobb, mint `db/config.php → max_size` — emeld meg

### 15.4 Bejelentkezés után visszadob a login-ra

- Süti probléma. A böngésződben engedélyezd a cookie-kat
- Vagy `localhost` helyett `127.0.0.1`-en próbálj — néha az `HttpOnly` viselkedik furcsán

### 15.5 Drag & drop sorrend nem ment

- Nyisd a böngésző konzolt — Network tab — figyeld a POST-okat
- CSRF token lejárt? — Reload a lap
- 403 hiba? — Session timeout, jelentkezz be újra

### 15.6 Új kategória nem szűr a publikus galérián

- Üres slug-ja van? Lásd: [8.4 Slug generálás](#84-slug-generálás)
- Ellenőrizd DB-ben: `SELECT id, name, slug FROM categories;`
- Ha üres, javítsd: `UPDATE categories SET slug = 'megfelelo-slug' WHERE id = X;`

### 15.7 Képek nem jelennek meg

- A relatív útvonal helyes? A `uploads/` mappa elérhető?
- Néhány hosting nem engedi a `Options +Indexes`-t — nem ez a probléma, csak a fájl ne legyen 0 byte
- Jogosultság: `chmod 755 uploads && chmod 644 uploads/*.jpg`

### 15.8 Stílusok lemaradnak / régi CSS-t lát

- Browser cache. Hard reload: Cmd+Shift+R
- Vagy adj cache-busterszetet a `<link>` tag-ben: `href="assets/css/main.css?v=2"`

---

## 16. Konvenciók

### 16.1 PHP

- `declare(strict_types=1);` minden új fájl tetején
- Függvény prefix: `sz_*` — minden saját helper függvény, hogy ne ütközzön
- Soha ne `mysqli_*` — mindig PDO
- Soha ne string interpoláció SQL-be — prepared statement
- Soha ne `echo $variable` HTML-be — mindig `<?= sz_e($variable) ?>`
- Hibakezelés: `throw new RuntimeException('...')` a try/catch blokkokon belül

### 16.2 HTML

- Lang attribútum: `<html lang="hu">`
- Charset: `<meta charset="utf-8">`
- Viewport: `<meta name="viewport" content="width=device-width, initial-scale=1">`
- Aria attribútumok minden interaktív elemen (`aria-label`, `aria-current`, `aria-expanded`, `aria-hidden`, `aria-controls`)
- Linkek: `data-nav-link` attribútum (az `active-nav.js` ezt használja)
- Form-ok: `method="post"` + CSRF rejtett mező

### 16.3 CSS

- BEM naming (lásd 9.3)
- Design tokenek (lásd 9.2)
- Mobile-first media queryk
- Soha ne `!important` (kivéve override-okat, mint print)
- Soha ne inline style (kivéve egyedi, futási idejű érték — pl. `style="--progress: 50%"`)

### 16.4 JS

- ES Modules
- Idempotens init függvények
- Esemény delegáció ha lehet
- DOM query-k cache-elve (`const items = Array.from(...)`)
- Soha ne `var` — `const` vagy `let`

### 16.5 Útvonalak

- Publikus oldalakon: relatív útvonalak (`assets/css/main.css`, `galeria.php`)
- Admin oldalakon: relatív, de `../`-ral utal a gyökérre (`../assets/css/main.css`, `../uploads/...`)
- DB-ben: relatív útvonal a projekt gyökérhez (`uploads/...`), így az `sz_image_src()` helper hozzátudja fűzni a megfelelő prefixet

### 16.6 Commit konvenció (ha git-be teszed)

Javasolt:

- `feat: új admin oldal a beállításokhoz`
- `fix: üres slug kezelése kategória létrehozáskor`
- `chore: PHP php.ini limitek dokumentálva`
- `refactor: galeria.php query-k szétválasztva`
- `style: új design tokenek meleg színpalettához`

---

## Függelékek

### A. Gyakori MySQL parancsok

```bash
# DB tartalom dump
mysqldump -u root szechenyi > backup.sql

# Visszaállítás
mysql -u root szechenyi < backup.sql

# Csak schema (üres táblák)
mysqldump -u root --no-data szechenyi > schema.sql

# Csak adatok (struktúra nélkül)
mysqldump -u root --no-create-info szechenyi > data.sql

# Interaktív shell
mysql -u root szechenyi

# Egy lekérdezés egy-soros
mysql -u root szechenyi -e "SELECT * FROM categories;"
```

### B. Hasznos PHP one-liner-ek

```bash
# Új admin user létrehozása (jelszó hash)
php -r 'echo password_hash("ÚJ_JELSZÓ", PASSWORD_BCRYPT) . PHP_EOL;'

# PHP konfiguráció megtekintése
php -i | grep "upload_max_filesize\|post_max_size\|memory_limit"

# Szintaxis ellenőrzés egy fájlra
php -l admin/gallery.php

# Szintaxis ellenőrzés minden PHP fájlra
find . -name "*.php" -exec php -l {} \;
```

### C. Fájl jogosultságok éles szerveren

```bash
# Mappák: 755 (read+exec mindenkinek, write csak ownernek)
find . -type d -exec chmod 755 {} \;

# Fájlok: 644 (read mindenkinek, write csak ownernek)
find . -type f -exec chmod 644 {} \;

# uploads/ mappa esetleg 775 ha a webszerver más user-ként fut
chmod 775 uploads
```

### D. Külső linkek és referenciák

- [PHP password_hash docs](https://www.php.net/manual/en/function.password-hash.php)
- [PDO prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [MDN — CSS custom properties](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)
- [MDN — HTML drag and drop](https://developer.mozilla.org/en-US/docs/Web/API/HTML_Drag_and_Drop_API)
- [Google Fonts — Inter](https://fonts.google.com/specimen/Inter)

---

## Verzió és változások

**1.0** (2026-05-13) — Első verzió, az admin felület és a dinamikus galéria elkészültével.

Mostantól a `CHANGELOG.md`-be tedd a változásokat (data + leírás), és frissítsd ezt a dokumentumot, ha:
- új tábla / oszlop kerül a DB-be
- új admin oldal készül
- biztonsági modell változik
- új konvenció vagy törött kompatibilitás
