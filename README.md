# Széchenyi Márk Fényképészet

Egy PHP + MySQL alapú fotós portfólió oldal, dinamikus galériával és admin felülettel (képek feltöltése, kategóriák kezelése, sorrendezés drag & droppal).

## Követelmények

- **PHP 7.4+** (PDO_MYSQL, `fileinfo` kiterjesztések)
- **MySQL 5.7+ / MariaDB 10+**
- Git

## Kipróbálás 5 lépésben

### 1. Klónozd a repót

```bash
git clone https://github.com/<felhasznalonev>/szechenyi.git
cd szechenyi
```

### 2. Hozz létre egy MySQL adatbázist

```sql
CREATE DATABASE szechenyi
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 3. Állítsd be a konfigurációt

```bash
cp db/config.example.php db/config.php
```

Nyisd meg a `db/config.php`-t, és írd át a MySQL kapcsolati adatokat (host, port, felhasználó, jelszó).

> **MAMP-en** az alap adatok: user `root`, jelszó `root`, port `8889`.

### 4. Indítsd el a szervert

A repó gyökerében:

```bash
php -S localhost:8000
```

Vagy ha MAMP / XAMPP-ot használsz, másold a mappát a `htdocs`-ba.

### 5. Futtasd a telepítőt

Nyisd meg a böngészőben:

```
http://localhost:8000/install.php
```

Add meg az admin felhasználónevet és jelszót (min. 8 karakter), majd kattints a **„Telepítés indítása”** gombra. A telepítő létrehozza a táblákat és az alap kategóriákat.

**Telepítés után töröld az `install.php` fájlt!**

```bash
rm install.php
```

## Használat

- **Publikus oldal**: <http://localhost:8000/index.html>
- **Admin felület**: <http://localhost:8000/admin/login.php> (vagy a lábléc „ADMIN” linkje)
- **Dinamikus galéria**: <http://localhost:8000/galeria.php>

## Funkciók

- Reszponzív portfólió oldalak (kezdőlap, szolgáltatások, galéria, kapcsolat, rólam, stúdió, vászonkép)
- Admin felület képek és kategóriák kezeléséhez
- Több fájl feltöltése egyszerre (drag & drop is)
- Alt szöveg / felirat szerkesztése
- Kategóriák és képek sorrendezése drag & drop-pal
- Főkép kijelölése kategóriánként
- CSRF-védett session alapú admin auth

## Fájlstruktúra (röviden)

```
szechenyi/
├── index.html, galeria.php, ...    ← publikus oldalak
├── install.php                     ← egyszeri telepítő (utána törlendő!)
├── admin/                          ← admin felület
├── db/                             ← DB konfig + schema
│   ├── config.example.php
│   ├── schema.sql
│   └── db.php
├── assets/                         ← CSS, JS, képek
└── uploads/                        ← feltöltött képek (írhatónak kell lennie)
```

Részletes telepítési útmutató (élesbe állítás, hibaelhárítás, jogosultságok): lásd [README_ADMIN.md](README_ADMIN.md).

## Megjegyzések

- A `db/config.php` és a `uploads/` mappa tartalma **NE** kerüljön publikus repo-ba.
- Éles környezetben tiltsd le a PHP futtatást az `uploads/` mappában (lásd `README_ADMIN.md` 8. pont).
