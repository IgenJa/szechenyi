# FIX PLAN — Széchenyi Márk Fotó Portfolio

> Az `AUDIT.md` 79 hibájára adott terv. A user „no clarifying questions" módban van — a kritikus döntéseket itt rögzítem, és végrehajtom; ha valamit másképp szeretnél, írj.

---

## Megoldott döntések (no-question-mode)

1. **Tailwind CDN ki, custom CSS be.** Build pipeline (npm) nincs a projektben — production-ben CDN runtime build vagy build pipeline kellene. Mivel ez egy statikus, többoldalas portfolio, a tisztább megoldás: egyetlen `assets/css/main.css` natív CSS-szel, design tokenekkel. Csökkenti a JS payload-ot ~600 KB-tal, futási idejű build-et megszünteti, és a kódbázis sokkal olvashatóbb lesz.
2. **Lightbox modal a galéria oldalon, nem külön HTML.** A `lightbox.html`-t megtartom redirect-oldalként (galériára visszairányít), de a valódi élmény a `galeria.html`-en, JS lightbox modallal, képek közti navigálással, billentyűzettel (←, →, Esc), focus trap-pel.
3. **Mobile menu hamburger ikonnal**, off-canvas drawer mobilon, billentyűzet-elérhető, Esc-cel zárható.
4. **Stitch képek (lh3.googleusercontent.com)** maradnak URL-szinten, de loading=lazy, width/height, informatív alt-szövegek. Külön `assets/images/` mappát létrehozok és dokumentálom, hogy ide kerüljenek a végleges fájlok. Production deploy előtt mindenképp helyi képekre kell cserélni — a Stitch URL-ek nem stabilak.
5. **Form-ok**: kliens oldali validáció + `mailto:` fallback (nincs backend). Az `action="mailto:info@szechenyifoto.hu"` és JS-es success-state mutatás. Ha a tulajdonos később Formspree-szerű service-t használna, az `action` URL egy helyen cserélődik.
6. **Pricing tábla** szolgáltatások oldalon: a meglévő „Családi" csomagokat megtartom, és tabbing-gel a többi kategória pricing-jét is mutatom (üres `data-category` elemekkel placeholdert teszek, ha a tulajdonos még nem adott meg adatot). A bal aside-on JS-vel váltunk kategóriát.
7. **Galéria szűrés**: data-category attribútumok + JS-es szűrés a sidebar nav-ról. Mobilon a sidebar a mobile menü drawerbe kerül.

---

## Fix blokkok sorrendje

### Blokk 1 — Kódstruktúra alap (CRITICAL)
- `assets/css/main.css`, `assets/js/main.js`, `assets/js/components/lightbox.js`, `assets/js/components/mobile-menu.js`, `assets/js/components/forms.js`, `assets/js/components/gallery-filter.js`
- `assets/images/` mappa + `assets/images/README.md` (Stitch URL → helyi kép migráció dokumentum)
- `favicon.svg`, `robots.txt`, `sitemap.xml`

### Blokk 2 — Központi CSS design tokenekkel (CRITICAL)
- Reset, tokenek (color, font, spacing, radius, shadow, transition, z-index)
- Base typography (mobile-first)
- Layout primitív-ek (container, grid)
- Komponensek: header, nav, mobile-menu, btn, card, form, lightbox, gallery, pricing-table, footer, floating-cta
- Utilities
- Responsive: `min-width` 640, 768, 1024, 1280
- Print stylesheet

### Blokk 3 — JS modulok (CRITICAL)
- `main.js`: init + delegálás
- `mobile-menu.js`: hamburger toggle, focus trap, Esc, body scroll lock
- `lightbox.js`: open/close, prev/next, kbd, focus trap, image preloading
- `gallery-filter.js`: kategória szűrés
- `forms.js`: validáció + mailto submit + success state

### Blokk 4 — HTML újraírás (CRITICAL)
Minden 8 HTML újraírva:
- Egységes head: meta description, OG, Twitter, canonical, favicon, Schema.org JSON-LD
- Egyetlen `<link rel="stylesheet" href="assets/css/main.css">`
- Egyetlen `<script src="assets/js/main.js" defer>` + komponens script-ek defer-rel
- Semantic landmark struktúra (header, nav, main, aside, footer)
- aria-current, aria-label, aria-hidden ikonokon
- Image width/height, loading="lazy", informatív alt
- Skip link
- Mobile menu trigger

### Blokk 5 — Tartalom konzisztencia (HIGH)
- Floating Contact CTA minden oldalra (lightbox kivételével)
- Footer copyright „© 2024" → „© 2024–2026" vagy „© 2026"
- Külső link `rel="noopener noreferrer" target="_blank"`
- Header wordmark mindenhol egységes pozícióban
- Szolgáltatások oldali kettős `<header>` javítása

### Blokk 6 — SEO + dokumentum (HIGH)
- `sitemap.xml`, `robots.txt`
- Schema.org Photographer + LocalBusiness JSON-LD
- Meta description page-enként egyedileg
- OG image (Stitch URL placeholder, később cserélni)

### Blokk 7 — Final pass + CHANGELOG (MEDIUM)
- Production-ready checklist végigfutás
- `CHANGELOG.md`

---

## Érintett fájlok overview

**Új fájlok (12):**
- `assets/css/main.css`
- `assets/js/main.js`
- `assets/js/components/lightbox.js`
- `assets/js/components/mobile-menu.js`
- `assets/js/components/forms.js`
- `assets/js/components/gallery-filter.js`
- `assets/images/README.md`
- `favicon.svg`
- `robots.txt`
- `sitemap.xml`
- `AUDIT.md` (kész)
- `CHANGELOG.md`

**Átírt fájlok (8):**
- `index.html`, `galeria.html`, `szolgaltatasok.html`, `vaszonkep.html`, `studio.html`, `rolam.html`, `kapcsolat.html`, `lightbox.html`

**Kockázat:** az átírás nagy volumenű (8 HTML × ~200 sor + új CSS + JS). A vizuális design intent megőrzése prioritás — minimal-monochrome, magazin-szerű, Inter type, sok whitespace, fekete-fehér képek. A Tailwind utility-k → custom CSS-re cserélődnek de a vizuális kimenet ugyanaz marad mobile-ban javított, desktop-on identikus.
