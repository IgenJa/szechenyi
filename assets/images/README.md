# Images — migrációs útmutató

A HTML fájlok jelenleg a Stitch AI által generált `lh3.googleusercontent.com/aida-public/...` URL-eken hivatkoznak képekre. **Ezek nem stabilak**, production deploy előtt mindenképpen le kell tölteni és helyi fájlokra cserélni.

## Ajánlott mappastruktúra

```
assets/images/
├── icons/        — UI ikonok, ha lokálisra váltunk Material Symbols-ról
├── photos/       — fényképek (galéria, hero, about, kapcsolat, stúdió)
├── og/           — Open Graph / social megosztó képek (1200×630)
└── README.md     — ez a fájl
```

## Névadási konvenció

`leiro-nev-suffix.{webp|jpg}` — pl.:
- `hero-editorial-2024.webp`
- `gallery-csaladi-01.webp`
- `gallery-portre-02.webp`
- `about-portrait.webp`
- `studio-interior-01.webp`
- `og-default.jpg`

## Optimalizálás

1. **Eredeti**: minimum 2000px hosszabbik oldal, JPEG quality 85
2. **WebP** verzió `cwebp -q 80` szinten
3. **Több méret** srcset-hez: 640w, 1024w, 1600w
4. Hero / above-the-fold képek **NE legyenek `loading="lazy"`** (eltünteti a fold-perf gain-t — vagy ha igen, akkor `fetchpriority="high"` mellett)

## Csere a HTML-ben

A `<img src="https://lh3.googleusercontent.com/aida-public/...">` URL-eket cseréld:
```html
<img src="assets/images/photos/gallery-csaladi-01.webp"
     srcset="assets/images/photos/gallery-csaladi-01-640w.webp 640w,
             assets/images/photos/gallery-csaladi-01-1024w.webp 1024w,
             assets/images/photos/gallery-csaladi-01-1600w.webp 1600w"
     sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw"
     width="1600" height="2133"
     alt="Konkrét, beszélő alt szöveg"
     loading="lazy"
     decoding="async">
```

## OG kép (legalább egy)

`assets/images/og/og-default.jpg` — 1200×630px, alatta:
- bal felső: SZÉCHENYI MÁRK wordmark
- jobb alsó: szechenyifoto.hu
- háttér: stúdió enteriőr vagy reprezentatív fotó
