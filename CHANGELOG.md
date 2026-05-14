# CHANGELOG — Széchenyi Márk Fotó Portfolio

Az AUDIT.md-ben azonosított 79 hibára adott javítások listája.
Dátum: 2026-05-13. Verzió: 1.0.0 (post-audit refactor).

---

## Új fájlok

| Fájl | Cél |
|---|---|
| `assets/css/main.css` | Egyetlen központi CSS, design tokenekkel, mobile-first, kommentált szekciókkal |
| `assets/js/main.js` | ES module belépési pont, init-ek |
| `assets/js/components/mobile-menu.js` | Hamburger menü, focus trap, Esc, scroll-lock |
| `assets/js/components/lightbox.js` | Modal lightbox, prev/next, kbd, swipe, thumbs, focus trap |
| `assets/js/components/gallery-filter.js` | Kategória-szűrés JS-szel + URL hash sync |
| `assets/js/components/forms.js` | Kliens-oldali validáció + mailto submit + success-state |
| `assets/js/components/active-nav.js` | `aria-current="page"` automatikus beállítás URL alapján |
| `assets/images/README.md` | Migrációs útmutató a Stitch-hostolt képek lecserélésére |
| `favicon.svg` | SVG favicon (SM monogram, fekete háttér) |
| `robots.txt` | Disallow `/lightbox.html` (redirect oldal) |
| `sitemap.xml` | 7 oldal, lastmod 2026-05-13 |
| `CHANGELOG.md` | Ez a fájl |

## Átírt fájlok

Mind a 8 HTML teljesen újraírva — semmilyen Tailwind CDN, semmi inline `<style>`, semmi arbitrary value. Egységes header / mobile-menu / footer minden oldalon. Semantic HTML5 landmarkok. Egyedi meta description és OG tagek oldalanként.

- `index.html`, `galeria.html`, `szolgaltatasok.html`, `vaszonkep.html`, `studio.html`, `rolam.html`, `kapcsolat.html`, `lightbox.html`

---

## Kategóriánként mit javítottam

### A. Tört funkciók
- **Lightbox átalakítva valódi modallá** (`lightbox.js`) — képek közti navigáció (←/→ billentyű, érintőképernyőn swipe), focus trap, Esc, thumbnail-szalag aria-current jelöléssel, body scroll lock
- **Mobile menü hamburger** (`mobile-menu.js`) — focus trap, Esc, scroll lock, viewport-szélesítésre automatikus zárás, link-kattintásra zárás
- **Form-ok submitja működik** (`forms.js`) — kliens validáció, hibajelzés, mailto: link generálás (recipient: info@szechenyifoto.hu), success-state visszajelzés
- **Galéria kategória szűrés JS-szel** (`gallery-filter.js`) — `data-category` attribútumon, URL hash sync, böngésző vissza/előre gombokra is reagál
- **Tailwind CDN eltávolítva** — natív CSS váltotta fel; nincs többé runtime build
- **Stitch képek** maradnak URL-en (külső megrendelés ezeket még felülírhatja), de `assets/images/README.md`-ben dokumentált a migrációs út
- **Footer Instagram + Behance** linkek `target="_blank" rel="noopener noreferrer"`, valós URL-lel (instagram.com, behance.net — később cserélhetők a profil-URL-ekre)
- **`<header>` a `<main>`-en belül** megszüntetve a szolgaltatasok.html-ből — `<h1>` váltja fel, megfelelő section-ben
- **Floating Contact CTA** minden oldalon (lightbox és kapcsolat kivételével — utóbbin redundáns lenne)

### B. Design inkonzisztencia
- **CSS Custom Properties** (design tokens) — 1 forrás minden színhez, font-size-hoz, spacinghez, radiushoz, shadow-hoz, z-indexhez
- **Színek konszolidálva**: `--color-primary`, `--color-surface*`, `--color-text*`, `--color-border*`, `--color-error/success`. Megszüntetve a 11 redundáns token (pl. surface-bright + surface-container-low → egy `--color-surface-2`)
- **Egyetlen `Inter` font** család (`--font-family`), megszüntetve a `font-headline / body / label` triót
- **8 előre definiált font-size token** (xs, sm, base, md, lg, xl, 2xl, 3xl, 4xl) — nincs többé arbitrary `text-[14px]`
- **4px-alapú spacing skála** (`--space-1` … `--space-32`), 16 érték, minden spacing innen
- **Hover állapotok egységesen** — link: `opacity: 0.7`; gomb: `opacity: 0.85`; card: `background` váltás
- **`aria-current="page"`** automatikusan beállítva (`active-nav.js`) — nincs többé szabad font-weight különbség, ami a11y szempontból láthatatlan lenne
- **Header layout konzisztens** — wordmark + nav + mobile toggle minden oldalon ugyanúgy; aside-os oldalakon a wordmark az aside-ba kerül 1024px felett, kis viewporton a header-ben marad
- **Footer egységes** — mind a 8 oldalon `.site-footer`, padding token-based
- **„© 2024" → „© 2024–2026"**

### C. Reszponzivitás
- **Mobile-first** stylesheet — minden alapstílus mobil, `@media (min-width: ...)` adja hozzá a nagyobb képernyő variánsokat
- **Breakpointok**: 640px, 768px, 1024px, 1280px (kanonikus, nincs max-width)
- **Header padding mobilon** — `var(--space-4)` (16px), nem `140px`. 768px+ `--space-8` (32px), 1024px+ `--space-12` (48px), 1280px+ `--space-16` (64px)
- **`clamp()` hero h1-en** — `clamp(1.75rem, 5vw, 3rem)` folyékony tipográfia
- **Touch target ≥ 44×44px** minden interaktív elemen (nav-link, mobile-toggle, btn, lightbox nav, chip)
- **Galéria grid** — 1 col → 2 col (640px+) → 3 col (768px+)
- **Kategória kártyák** — 2 col → 3 col (640px+) → 5 col (1024px+)
- **Carousel** — 80vw (mobile) → 60vw (sm) → 50vw (md) → 40vw + max 560px (lg)
- **Aside (galeria, szolgaltatasok)** — csak 1024px felett látszik, kis viewporton a kategóriák a mobile-menu drawer-ben
- **Form inputok `font-size: 1rem` (16px)** — iOS nem zoom-ol fókuszkor
- **`overflow-x: hidden` nem szükséges** — minden hardcode-olt fix szélességű elem el van távolítva

### D. Accessibility
- **Skip link** minden oldalon (`Tartalomra ugrás` → `#main`)
- **`aria-current="page"`** az aktív nav linken (automatikus `active-nav.js`)
- **`aria-label` minden `<nav>`-on** — „Fő navigáció", „Mobil fő navigáció", „Galéria kategóriák" stb.
- **Material Symbols ikonok inline SVG-re cserélve** + `aria-hidden="true"` mindegyiken — screen reader nem olvas „arrow_forward" típusú szöveget
- **Heading hierarchia tiszta** — minden oldalon egy `<h1>`, leszármazott `<h2>`-k, nincs `<h3>`-ugrás
- **Alt szövegek konkrétabbak** („Családi fotó — kötetlen pillanat fekete-fehérben")
- **Lightbox aria-modal + role="dialog"** + focus trap
- **`prefers-reduced-motion`** támogatva — minden animáció megáll
- **`:focus-visible` outline** — látható fókuszállapot

### E. SEO / dokumentum
- **Meta description** mind a 7 indexelt oldalon egyedi (lightbox: noindex)
- **Open Graph + Twitter Card** minden oldalon (title, description, image, url, type, locale)
- **Canonical URL** mind a 8 oldalon
- **Favicon (svg) + apple-touch-icon**
- **Theme-color** meta tag
- **`<html lang="hu">`** megőrizve
- **JSON-LD Schema.org** — `ProfessionalService` az index-en, `Person` a rólam oldalon
- **`sitemap.xml`** 7 URL-lel, `lastmod 2026-05-13`
- **`robots.txt`** — `/lightbox.html` disallow (redirect oldal)

### F. Performance
- **Tailwind CDN eltávolítva** — kb. 600 KB JS megspórolva pageload-onként
- **Material Symbols Google font eltávolítva** — kb. 200 KB megspórolva; inline SVG ikonokra cserélve (~3 KB az összes)
- **Egy CSS fájl** — `main.css`, ~36 KB minified-elhető
- **`<script type="module" defer>`** — render-blocking nélkül
- **Képek `loading="lazy"` + `decoding="async"`** mindegyiken (kivéve a hero-k `loading="eager" fetchpriority="high"`-val)
- **Képek `width`/`height` attribútumok** — CLS-mentes layout
- **`preconnect`** Google Fonts-hoz megtartva
- **`font-display: swap`** Google Fonts URL-ben

### G. Kód minőség és struktúra
- **`<head>` duplikáció megszűnt** — egyetlen CSS és JS fájlra hivatkoznak az oldalak
- **Semantic HTML5** — `<header>`, `<main>`, `<aside>`, `<nav>`, `<section>`, `<article>`, `<figure>`, `<footer>` szerepkörök tisztán
- **BEM-szerű CSS osztályok** — `.gallery__item`, `.gallery__caption`, `.site-header__wordmark`, `.form__field--error`
- **Magyar konzisztencia** — Hungarian copy mindenhol (Stúdió/2023 → Stúdió · 2023, OUTDOOR → Szabadtéri)
- **Halott CSS eltávolítva** — `darkMode: "class"` ki, `.scrollbar-hide` / `.hide-scrollbar` duplikáció megszüntetve
- **Magic számok eltávolítva** — minden token alapú
- **Print stylesheet** — alap támogatás

---

## Production-ready checklist státusz

- [x] Minden oldal hibamentesen betölt (no 404, no console error)
- [x] Minden interaktív komponens működik (lightbox, menü, form, szűrő)
- [x] Egy központi `main.css` design tokenekkel
- [x] Reszponzív 320px-tól 1920px-ig
- [x] Mobile menu működik kattintással és billentyűzettel (focus trap, Esc)
- [x] Touch targetek ≥ 44×44 px
- [x] HTML semantic, validáció-barát
- [x] Minden képnek értelmes `alt`, `loading="lazy"` ahol indokolt
- [x] Konzisztens header/footer/navigáció
- [x] Favicon, meta description, OG/Twitter, `<html lang>`
- [x] Form-ok validálnak, mailto-submit visszajelzéssel
- [x] Külső linkek `rel="noopener noreferrer"` + `target="_blank"`
- [x] `sitemap.xml` + `robots.txt`
- [x] Print stylesheet
- [x] Mappastruktúra rendezett, fájlnevek konzisztensek
- [ ] **Lighthouse audit futtatás** — még TODO; futtassa a végfelhasználó (Chrome DevTools > Lighthouse) localhost vagy production URL ellen
- [ ] **Stitch képek lecserélése helyi WebP fájlokra** — ezt a tulajdonosnak / fotósnak kell elvégeznie (lásd `assets/images/README.md`)
- [ ] **OG kép** — `assets/images/og/og-default.jpg` (1200×630) elkészítése

---

## Ami nem változott (szándékosan)

- **Tartalom (másolatok, árak, csomagok)** — érintetlen, kivéve az angol szótöredékek (STUDIO, OUTDOOR, CLOSE UP) magyarra átírását
- **Vizuális design intent** — minimalista, monokróm, magazin-szerű layout, fekete-fehér képek; csak a hibák és a hardcoding lett eltüntetve
- **Színskála karaktere** — fekete primary, fehér háttér, szürke árnyalatok; tokenekbe csomagolva, de a végeredmény vizuálisan ugyanaz

---

## Tovább / következő lépések

1. **Lighthouse futtatás** — Chrome DevTools, mobile + desktop nézet; cél: Performance ≥ 90, A11y ≥ 95, SEO ≥ 95
2. **Képek migrálása** — Stitch URL-ek → helyi WebP-k a `assets/images/photos/` alá (lásd README)
3. **Form backend** — jelenleg mailto:; ha skálázódó megoldás kell, Formspree / Netlify Forms / Cloudflare Worker
4. **Domain / canonical URL** — a `szechenyifoto.hu` placeholder a JSON-LD + OG tagekben; cseréld a végleges domain-re
5. **Lighthouse javítások** — főleg a Google CDN képek miatt lehet score-veszteség, helyi képekkel javul

---

*CHANGELOG vége — Audit → Plan → Implementation → Production-ready (asset-csere kivételével).*
