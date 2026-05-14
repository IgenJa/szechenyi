# AUDIT — Széchenyi Márk Fotó Portfolio

**Audit dátuma:** 2026-05-13
**Auditálta:** Senior front-end developer (audit-only fázis)
**Projekt állapota:** Stitch AI által generált, 8 statikus HTML, semmi külön asset

---

## Projekt áttekintés

**Oldalak (8 db):**
1. `index.html` — Kezdőlap (hero, kategóriák, szolgáltatások, about preview)
2. `galeria.html` — Galéria (bal oldali aside nav + képgrid)
3. `szolgaltatasok.html` — Szolgáltatás aloldal (bal aside + pricing + carousel)
4. `vaszonkep.html` — Vászonkép „coming soon"
5. `studio.html` — Stúdióbérlés (hero + details + booking form)
6. `rolam.html` — Rólam (portré + bio)
7. `kapcsolat.html` — Kapcsolat (form + info)
8. `lightbox.html` — Lightbox nézet (külön oldalként, nem modalként!)

**Megjegyzés:** Semmi assets/, semmi css/, semmi js/, semmi képmappa — minden inline Tailwind, minden image a `lh3.googleusercontent.com/aida-public/...` URL-ekről.

---

## A. Tört funkciók

- [`*.html` mind] Hiányzik a **mobile menu** — a fixed `<header>` `px-[140px] pt-[140px]`-tel mobilon nem fér ki, és nincs hamburger menü → CRITICAL
- [`*.html` mind] **NULLA JavaScript fájl** a projektben — nincs lightbox JS, nincs slider JS, nincs menu toggle, nincs form submit handler → CRITICAL
- [`galeria.html`] A galéria thumbnail-ekre kattintva `lightbox.html`-re navigál (külön oldal), nem nyit modális lightbox-ot — minden kép ugyanazt a hardcode-olt lightbox-ot mutatja, nincs valódi képek közti navigáció → CRITICAL
- [`lightbox.html:70-72,83-85`] Az „Előző / Következő" gombok JS handler nélkül vannak — egyszerűen semmit nem csinálnak kattintáskor → CRITICAL
- [`lightbox.html:91-108`] A thumbnail strip gombjai sehova nem kötődnek, kattintásra semmi nem történik → HIGH
- [`galeria.html:77-94`] A bal oldali kategória sidebar `<a href="#osszes">` típusú anchor linkek vannak, **de a tartalom oldalon nincs `id="osszes"` és nincs `id="csaladi"` szekció a galéria gridben** — kattintásra nem ugrik szűrt szekcióhoz, az ID-k magán a nav linken vannak → HIGH
- [`szolgaltatasok.html:79-96`] Ugyanaz: aside `<a href="#csaladi">` link, de a `<main>`-ben nincs `<section id="csaladi">`. Kategóriák közti váltás nem működik → HIGH
- [`index.html:94`] „Specialized Services" linkek (`href="galeria.html#csaladi"`) — a galéria oldalon ezek a hash-ek nem szekciókhoz tartoznak, hanem a sidebar nav linkjeihez. Az ugrás megtörténik, de nem hova kéne → MEDIUM
- [`kapcsolat.html:94-108`] Form `action`/`method` nélkül van, nincs submit handler — kattintásra reload és üres URL-paraméterek → CRITICAL
- [`studio.html:152-187`] Foglalási form ugyanúgy `action`/`method` nélkül → CRITICAL
- [`*.html` mind] Footer Instagram + Behance linkek `href="#"` → tört külső linkek → HIGH
- [`szolgaltatasok.html:104`] `<header>` JSX-szerűen használva a `<main>`-en belül (line 61 main header is `<header>`), de a `<main>` belsejében szintén `<header>` (line 104) — invalid HTML struktúra, kettős landmark szerep → HIGH
- [`*.html` mind] Tailwind CDN használat (`https://cdn.tailwindcss.com`) — production-ben CSAK warning + bizonytalan render — sosem szabad így használni → CRITICAL
- [`*.html` mind] Minden kép `lh3.googleusercontent.com/aida-public/...` — ezek Stitch AI által hostolt, NEM stabil URL-ek, bármikor leeshetnek → CRITICAL
- [`index.html:170-178`, `szolgaltatasok.html:176-182`, `studio.html` nincs] Floating Contact CTA inkonzisztensen jelenik meg: index, szolgaltatasok = van; galeria, rolam, kapcsolat, vaszonkep, studio, lightbox = nincs → MEDIUM

---

## B. Design inkonzisztencia

### Színek (Tailwind config-ban definiált palette)

| Hex | Token nevek | Probléma |
|---|---|---|
| `#000000` | primary, on-surface | OK |
| `#ffffff` | on-primary, surface, surface-container-lowest | 3 különböző név ugyanarra |
| `#fafafa` | surface-container-low, surface-bright | DUPLIKÁLT |
| `#f5f5f5` | surface-container | OK |
| `#ededed` | surface-container-high | OK |
| `#e5e5e5` | surface-container-highest, surface-dim | DUPLIKÁLT |
| `#f0f0f0` | surface-variant | OK |
| `#525252` | on-surface-variant | OK |
| `#A3A3A3` | outline | OK |
| `#E5E5E5` | outline-variant | **Ugyanaz mint surface-container-highest (csak nagybetűs)!** Konfliktus |
| `#ba1a1a` | error | Soha nincs használva sehol |

→ 7 különálló szín, de 11 token név — felesleg, redundancia, és egy collizió.

### Font család

- `font-headline`, `font-body`, `font-label` mind ugyanaz: `Inter, sans-serif` → felesleg absztrakció, lehet egy `--font` → MEDIUM

### Font-size értékek (arbitrary, hardcode-olt px-ben)

`text-[10px]`, `text-[11px]`, `text-[12px]`, `text-[14px]`, `text-[16px]`, `text-[24px]`, `text-[32px]`, `text-[48px]`, `text-xl`, `text-2xl`
→ Nincs design skála, minden hardcode pixel — design token átálláshoz mind újra kell írni → HIGH

### Font-weight értékek

`font-light` (300), `font-medium` (500), `font-semibold` (600), `font-bold` (700) — keverékben használva.
- Header wordmark: `font-medium` (index, rolam, stb.), de **galeria-n és szolgaltatasok-on a wordmark az aside-ban van, nem header-ben** → inkonzisztens header felépítés → HIGH

### Letter spacing (tracking)

`tracking-[-0.02em]`, `tracking-[0.05em]`, `tracking-[0.1em]`, `tracking-[0.2em]`, `tracking-[0.3em]`, `tracking-wide` → 6 variáció arbitrary value-kban → MEDIUM

### Spacing skála

Használt érték listája (px-ben gyűjtve): **4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 120, 140, 240, 280, 300**
- 8px-alapú skála részben tartható (4/8/12/16/24/32/48), DE a 80/120/140/240/280/300 egyedi mágikus számok → HIGH
- `pt-[300px]`, `pt-[280px]`, `pt-[140px]`, `px-[140px]`, `px-[80px]`, `gap-[140px]`, `gap-[120px]`, `space-y-[80px]` — minden hardcode → HIGH

### Border-radius

`borderRadius.DEFAULT = "0"` (Tailwind config), de:
- `rounded-full` — header nav-on (kompletten round pill)
- `rounded-sm` (2px) — chip-eken
- `rounded-full` — avatar
→ A „kemény szögletes" design nyelvbe random kerek elemek lépnek be — inkonzisztens design language → MEDIUM

### Hover állapotok

- `hover:opacity-70` (header wordmark)
- `hover:opacity-90` (button)
- `hover:opacity-100` (image hover)
- `hover:bg-surface-bright` (button)
- `hover:bg-surface-container` (lightbox button)
- `hover:text-primary` (nav link)
→ 6 különböző hover-stratégia ugyanazon UI elemtípusokra (linkek, gombok) → MEDIUM

### Active nav state

- `font-semibold` + `text-primary` az aktív nav linken — konzisztens
- DE: `aria-current="page"` nincs → A11y hiba (lásd D pont) → HIGH

### Header inkonzisztencia (KRITIKUS)

| Oldal | Wordmark helye | Header layout |
|---|---|---|
| index, rolam, kapcsolat, studio, vaszonkep | `<header>`-ben (bal felül) | `justify-between` |
| galeria, szolgaltatasok | `<aside>`-ban (bal sidebar) | `justify-end` (csak nav) |
| lightbox | Külön „SZÉCHENYI MÁRK" wordmark | másik layout |

→ Felhasználó számára követhetetlen, hogy a wordmark hová tűnt egy aloldalra navigálva → HIGH

### Footer inkonzisztencia

- `px-[140px]` az `<aside>` nélküli oldalakon
- `px-[80px]` + `md:ml-[240px]` az aside-os oldalakon
→ Footer pozícionálása oldalanként eltérő → MEDIUM

### Pricing tábla (`szolgaltatasok.html`)

A bal sidebar 6 kategóriát ígér (Összes, Családi, Karácsonyi, Páros, Portré, Glamour), de a tartalom csak Családi pricing-et tartalmaz, plus h1 fixen „Családi Fotózás" → félkész tartalom → HIGH

### Carousel labels (szolgaltatasok.html:142, 150, 158)

`STUDIO / 2023`, `CLOSE UP / 2023`, `OUTDOOR / 2024` — random English ahol minden más Hungarian → MEDIUM

### „2024" copyright

Minden footer „© 2024" — 2026 májusban ez stale → LOW

---

## C. Reszponzivitás

- [`*.html` mind] **Fixed header `px-[140px] pt-[140px]`** — mobilon (375px viewport) 280px-t emel ki padding-ben → biztos horizontal overflow + tartalom levágva → CRITICAL
- [`*.html` mind] **`<main>` `pt-[300px]` / `pt-[280px]`** mobilon hatalmas üres tér tetején → CRITICAL
- [`*.html` mind] **`px-[140px]` a main-en** — 320px-os mobilon csak 40px tartalomszélesség marad → tartalom le van rohadva → CRITICAL
- [`index.html:93`] `grid grid-cols-5` mobilon szétesik (kategória kártyák) — nincs `sm:` és `lg:` breakpoint → HIGH
- [`index.html:76`, `vaszonkep.html:76`] `grid grid-cols-12` (hero) mobilon szétesik — nincs mobile változat → HIGH
- [`galeria.html:99`] `md:ml-[240px]` — mobilon nincs aside, de a header és footer szintén `md:ml-[240px]` → footer mobilon helyesen, OK. DE a sidebar nélkül a kategóriaszűrőhöz nincs hozzáférés mobilon → HIGH
- [`szolgaltatasok.html`] Ugyanaz: mobilon nincs aside → kategóriaszűrő elérhetetlen → HIGH
- [`lightbox.html:70, 83`] Navigációs gombok `left-12 / right-12` fix → kis viewporton átfedik a fő képet → HIGH
- [`lightbox.html:74`] `max-w-[80vw] max-h-[716px]` — fix 716px height nem fog skálázni, mobilon levágja → HIGH
- [`index.html:170`, `szolgaltatasok.html:176`] Floating CTA `bottom-12 right-12` mobilon eltakarja a tartalmat (tap target overlap) → MEDIUM
- [`rolam.html:74`] `md:grid-cols-2 gap-[120px]` — mobilon 120px gap iszonyatos → HIGH
- [`rolam.html:74`] `max-w-[1600px]` desktop ok, de a `gap-[120px]` mobilon nem szűkül → HIGH
- [`*.html` mind] **Nincs touch target méretkezelés** — header nav linkek `py-3` (12px height + 11px text) → ~32px összmagasság, ami < 44px tap target → HIGH
- [`*.html` mind] **Form inputok `text-[14px]`** — iOS-en zoom-ol fókuszkor (< 16px) → MEDIUM
- [`studio.html:155, 159, 171, 175, 180`] Date input, select, text input → `text-[14px]` zoom-trigger iOS-en → MEDIUM
- [`*.html` mind] **Nincs egyetlen `@media (min-width: ...)` szabály se** — a meglévő Tailwind `md:` (768px) és `sm:` (640px) prefixek inkonzisztensen vannak alkalmazva, és tablet/large breakpoint nincs → HIGH
- [`galeria.html:106`] Gallery grid `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3` → ez konzisztens, de gap-1 (4px) furcsa kis spacing → LOW
- [`studio.html:80`] `text-[32px] md:text-[48px]` — clamp() nélkül ugrik → LOW
- [`szolgaltatasok.html:135-162`] Carousel `-mx-[80px] px-[80px]` — mobilon a `-mx-[80px]` túl negatívra húzza ki, viewport-overflow biztos → HIGH

---

## D. Accessibility

- [`*.html` mind] **Aktív nav linken nincs `aria-current="page"`** → screen readerek nem tudják melyik az aktuális oldal → HIGH
- [`*.html` mind] **Material Symbols ikonok nincsenek `aria-hidden="true"`** → screen reader felolvassa „arrow_forward", „mail", „grid_view" stb. szavakat → HIGH
- [`*.html` mind] **`<nav>` elemeken nincs `aria-label`** — több nav landmark egy oldalon, megkülönböztethetetlen → HIGH
- [`*.html` mind] **Skip link hiányzik** („Tartalomra ugrás") → keyboard navigációban hiányzik → MEDIUM
- [`index.html:78`] H1 majd `h2` (kategóriák), majd `h3` (kategória nevek) — OK
- [`index.html:159`] „Gróf Széchenyi Márk" → `<h3>` után nincs h2 a szekcióhoz — heading hierarchia tört → MEDIUM
- [`index.html:81, 96, 103...`] Alt szövegek túl generikusak: „Editorial fashion portrait", „Családi" — informatívabbat kell → LOW
- [`lightbox.html:75`] Alt: „Galéria kép" — szinte üres alt → MEDIUM
- [`lightbox.html:91`] Alt „Thumbnail 1" stb. — nem informatív → LOW
- [`index.html:170`] Floating CTA — egész link nincs aria-label, csak az inner szövegek → MEDIUM
- [`*.html` form] **Form labels működnek**, de a `kapcsolat.html` peer-trükkel — funkcionálisan jó, OK
- [`*.html` mind] **Fókusz outline reset nélkül** — Tailwind defaultja kék outline, OK, de hover stílusok eltérnek a fókusz stílustól → MEDIUM
- [`*.html` mind] **Színkontraszt**: `text-on-surface-variant` (`#525252`) `bg-surface-container-lowest` (`#ffffff`) → 7.61:1 OK. De `text-outline` (`#A3A3A3`) ugyanazon háttéren → **2.79:1 FAIL (WCAG AA = 4.5:1)** → a header nav linkek és néhány labelnél hibás → HIGH
- [`galeria.html:72-96`] `<aside>` nincs `aria-label` — második nav landmark labellelés nélkül → MEDIUM
- [`*.html` mind] **`<html lang="hu">`** megvan ✓
- [`*.html` mind] **`viewport` meta** megvan ✓

---

## E. SEO / meta / dokumentum

- [`*.html` mind] **`<meta name="description">` HIÁNYZIK** mindegyik oldalon → CRITICAL
- [`*.html` mind] **Open Graph tagek HIÁNYZNAK** (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`) → HIGH
- [`*.html` mind] **Twitter Card tagek HIÁNYZNAK** → MEDIUM
- [`*.html` mind] **Favicon HIÁNYZIK** (`<link rel="icon">`) → HIGH
- [`*.html` mind] **Apple touch icon HIÁNYZIK** → MEDIUM
- [`*.html` mind] **Canonical URL HIÁNYZIK** → MEDIUM
- [Projekt] **`sitemap.xml` HIÁNYZIK** → MEDIUM
- [Projekt] **`robots.txt` HIÁNYZIK** → MEDIUM
- [`szolgaltatasok.html:104`] **Második `<header>` a `<main>`-en belül** → invalid landmark struktúra → HIGH
- [`*.html` mind] **`<title>` egységes formátum** „Széchenyi Márk Fényképészet — X" — konzisztens ✓
- [`*.html` mind] **Schema.org / JSON-LD HIÁNYZIK** (LocalBusiness, Photographer schema) → MEDIUM

---

## F. Performance

- [`*.html` mind] **Tailwind CDN runtime build** — minden látogató pageload-jánál ~600 KB Tailwind JS letöltődik + runtime CSS generálódik. SOHA nem szabad production-ben → CRITICAL
- [`*.html` mind] **Material Symbols teljes betűkészlet** betöltődik 8-10 ikonért — `https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1` → akár 200KB+ → HIGH
- [`*.html` mind] **Képek `loading="lazy"` HIÁNYZIK** mindenütt — galéria oldalon 6 képes grid pl. → HIGH
- [`*.html` mind] **Képek nincsenek optimalizálva, nincs srcset / picture / responsive image** — minden full-res Google CDN → HIGH
- [`*.html` mind] **`<script src="cdn.tailwindcss.com">` nincs `defer`/`async`** → render-blocking → HIGH
- [`*.html` mind] **Google Fonts URL-ben `&display=swap` megvan** ✓
- [`*.html` mind] **Preconnect a Google Fontshoz megvan** ✓
- [`*.html` mind] **Tailwind config script inline minden oldalon** — DRY-violation, 50+ sor duplikálva 8x → HIGH
- [`*.html` mind] **`aspect-ratio` használata** részben jó (`aspect-[4/5]`, `aspect-square`), de a hero nélkül a hero image betöltésénél layout shift lesz → MEDIUM
- [`*.html` mind] **`<img width>/<height>` attribútumok HIÁNYOZNAK** — CLS növelő → HIGH

---

## G. Kód minőség és struktúra

- [`*.html` mind] **`<head>` 50 sora 8x duplikálva** (Tailwind config + style + font links) — szelni kell → CRITICAL
- [`*.html` mind] **Inline `<style>` blokk + Tailwind utility keveredés** — nem világos hol mit írni → HIGH
- [`*.html` mind] **Tailwind arbitrary values masszív használata** (`text-[11px]`, `px-[140px]`, `gap-[120px]` stb.) — ez nem a Tailwind szellemisége, kontraproduktív → HIGH
- [`*.html` mind] **`font-headline`, `font-body`, `font-label` mind Inter** — felesleges abstraction → MEDIUM
- [Projekt] **Nincs egyetlen CSS fájl sem** — minden inline + Tailwind CDN → CRITICAL
- [Projekt] **Nincs egyetlen JS fájl sem** — minden interaktivitás hiányzik → CRITICAL
- [Projekt] **Nincs assets/ mappa, nincs képmappa, nincs fonts/ mappa** — projekt nyersanyag-szintű → CRITICAL
- [`szolgaltatasok.html:54`] `.scrollbar-hide` lokális CSS osztály — máshol `.hide-scrollbar` (lightbox.html:54) — ugyanaz a funkció, két név → MEDIUM
- [`*.html` mind] **`darkMode: "class"` Tailwind config** — soha nincs `dark:` variant használva — halott kód → LOW
- [`*.html` mind] **HTML validáció**:
  - `szolgaltatasok.html`: kettős `<header>` (line 61 fixed + line 104 inside main) → HIGH
  - `galeria.html:77, 80, 83, 86, 89, 92`: `<a id="osszes">` ID-k a nav linkjein, ami zavart okoz a hash-routinggal → MEDIUM
- [`*.html` mind] **`<a target="_blank">` valahol?** — nincs, OK
- [`*.html` mind] **`rel="noopener"`** — Behance, Instagram linkeknek nincs, de `target="_blank"` se → linkek így is törtek (`href="#"`)
- [Projekt] **Fájlnévkonvenció kevert nyelvek**: `kapcsolat.html` magyar, `lightbox.html` angol, `vaszonkep.html` magyar, `studio.html` angol — inkonzisztens → LOW
- [`*.html` mind] **Kommentek angolul a HTML-ben**, tartalom magyarul — OK, ez elfogadható → INFO

---

## Statisztikák

### Hibák száma kategóriánként

| Kategória | CRITICAL | HIGH | MEDIUM | LOW |
|---|---|---|---|---|
| A. Tört funkciók | 6 | 4 | 1 | 0 |
| B. Design inkonzisztencia | 0 | 4 | 6 | 1 |
| C. Reszponzivitás | 4 | 9 | 3 | 2 |
| D. Accessibility | 0 | 4 | 5 | 2 |
| E. SEO / dokumentum | 1 | 3 | 6 | 0 |
| F. Performance | 1 | 5 | 1 | 0 |
| G. Kód minőség | 4 | 3 | 2 | 2 |
| **Összesen** | **16** | **32** | **24** | **7** |

**Összes hibajegy:** ~79

### Top 3 legkritikusabb

1. **NULLA működő interaktivitás** — semmilyen JS nincs, így a lightbox, mobile menu, form submit, slider mind tört. A lightbox jelenleg külön HTML oldal, és csak 1 hardcode-olt képet mutat (nincs képek közti navigálás, nincs valódi lightbox). Az oldal user-facing legnagyobb funkciója (a galéria) nem működik. → **Új JS architektúra szükséges**

2. **Tailwind CDN runtime + arbitrary value-k mindenhol + duplikált `<head>` 8x** — production-ready-vé nem tehető a jelen formájában. A CDN minden látogatónál ~600KB JS-t tölt és runtime build-eli a CSS-t. Plus minden font-size, padding, color hardcode pixel = design system nincs. → **Központi `main.css` design tokenekkel, build-time Tailwind helyett tiszta CSS**

3. **Mobilon teljesen használhatatlan** — `pt-[300px]` és `px-[140px]` mobilon = 300px üres tér tetején és csak 40px tartalom-szélesség egy 320px viewporten. Nincs mobile menü (a nav lekvártól letörik). Nincs touch target méret. Nincs mobile-first stylesheet. Egy 2026-os portfolio oldalon **a mobilos forgalom >60%** — most ez kompletten broken. → **Teljes mobile-first responsive pass szükséges**

---

## Audit lezárva — javításra NEM kerül sor ezen fázisban

A következő fázisban (FIX_PLAN.md) ezeket a hibákat csoportosítjuk fix-blokkokra, prioritás szerint sorrendezve, és külön „Kérdések" szekcióban a döntést igénylő pontokat felvetjük jóváhagyásra.
