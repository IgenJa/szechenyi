<?php
require_once __DIR__ . '/db/db.php';

$pdo = sz_db();
$categories = $pdo->query(
    'SELECT id, slug, name FROM categories ORDER BY sort_order ASC, id ASC'
)->fetchAll();

$images = $pdo->query("
    SELECT i.id, i.image_url, i.alt_text, i.caption, i.width, i.height,
           c.slug AS cat_slug, c.name AS cat_name
      FROM images i
      JOIN categories c ON c.id = i.category_id
     ORDER BY c.sort_order ASC, i.sort_order ASC, i.id ASC
")->fetchAll();

// SVG ikon kategória slug alapján
function sz_cat_icon(string $slug): string {
    $icons = [
        'csaladi'    => '<circle cx="8" cy="10" r="3" fill="none" stroke="currentColor"/><circle cx="16" cy="10" r="3" fill="none" stroke="currentColor"/><path d="M3 20c0-3 2.5-5 5-5s5 2 5 5M13 20c0-3 2.5-5 5-5s5 2 5 5" fill="none" stroke="currentColor"/>',
        'karacsonyi' => '<path d="M12 3l3 6h-2v3l4 1-3 3v4h-4v-4l-3-3 4-1V9H9l3-6z" fill="none" stroke="currentColor"/>',
        'paros'      => '<path d="M12 20s-7-4.5-7-10a4 4 0 017-2.5A4 4 0 0119 10c0 5.5-7 10-7 10z" fill="none" stroke="currentColor"/>',
        'portre'     => '<circle cx="12" cy="9" r="4" fill="none" stroke="currentColor"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7" fill="none" stroke="currentColor"/>',
        'glamour'    => '<path d="M12 3l2.4 6.5L21 12l-6.6 2.5L12 21l-2.4-6.5L3 12l6.6-2.5L12 3z" fill="none" stroke="currentColor"/>',
    ];
    // Általános ikon új kategóriáknak: kis négyzetháló
    $fallback = '<rect x="5" y="5" width="6" height="6" fill="none" stroke="currentColor"/><rect x="13" y="5" width="6" height="6" fill="none" stroke="currentColor"/><rect x="5" y="13" width="6" height="6" fill="none" stroke="currentColor"/><rect x="13" y="13" width="6" height="6" fill="none" stroke="currentColor"/>';
    return $icons[$slug] ?? $fallback;
}
?><!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Galéria — Széchenyi Márk Fényképészet</title>
  <meta name="description" content="Válogatás Széchenyi Márk munkáiból: családi, karácsonyi, páros, portré és glamour fotók. Lightbox nézet és kategória szűrés.">
  <meta name="theme-color" content="#000000">
  <link rel="canonical" href="https://szechenyifoto.hu/galeria.php">

  <meta property="og:type" content="website">
  <meta property="og:title" content="Galéria — Széchenyi Márk Fényképészet">
  <meta property="og:description" content="Válogatás az elmúlt évek legkedvesebb pillanataiból.">
  <meta property="og:url" content="https://szechenyifoto.hu/galeria.php">
  <meta property="og:image" content="https://szechenyifoto.hu/assets/images/og/og-default.jpg">
  <meta property="og:locale" content="hu_HU">
  <meta name="twitter:card" content="summary_large_image">

  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="apple-touch-icon" href="favicon.svg">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap">

  <link rel="stylesheet" href="assets/css/main.css">
  <script type="module" src="assets/js/main.js" defer></script>
</head>
<body class="has-aside">
  <a href="#main" class="skip-link">Tartalomra ugrás</a>

  <header class="site-header">
    <a href="index.html" class="site-header__wordmark">Fényképészet</a>
    <nav class="site-nav" aria-label="Fő navigáció">
      <ul class="site-nav__list">
        <li><a class="site-nav__link" data-nav-link href="index.html">Kezdőlap</a></li>
        <li><a class="site-nav__link" data-nav-link href="galeria.php">Galéria</a></li>
        <li><a class="site-nav__link" data-nav-link href="szolgaltatasok.html">Szolgáltatások</a></li>
        <li><a class="site-nav__link" data-nav-link href="vaszonkep.html">Vászonkép</a></li>
        <li><a class="site-nav__link" data-nav-link href="studio.html">Stúdió</a></li>
        <li><a class="site-nav__link" data-nav-link href="rolam.html">Rólam</a></li>
        <li><a class="site-nav__link" data-nav-link href="kapcsolat.html">Kapcsolat</a></li>
      </ul>
    </nav>
    <button type="button" class="mobile-menu-toggle" data-mobile-menu-toggle aria-expanded="false" aria-controls="mobile-menu" aria-label="Menü megnyitása">
      <span class="mobile-menu-toggle__icon" aria-hidden="true"><span></span></span>
    </button>
  </header>

  <div id="mobile-menu" class="mobile-menu" data-mobile-menu aria-hidden="true" aria-label="Mobil menü">
    <nav aria-label="Mobil fő navigáció">
      <ul class="mobile-menu__list">
        <li><a class="mobile-menu__link" data-nav-link href="index.html">Kezdőlap</a></li>
        <li><a class="mobile-menu__link" data-nav-link href="galeria.php">Galéria</a></li>
        <li><a class="mobile-menu__link" data-nav-link href="szolgaltatasok.html">Szolgáltatások</a></li>
        <li><a class="mobile-menu__link" data-nav-link href="vaszonkep.html">Vászonkép</a></li>
        <li><a class="mobile-menu__link" data-nav-link href="studio.html">Stúdió</a></li>
        <li><a class="mobile-menu__link" data-nav-link href="rolam.html">Rólam</a></li>
        <li><a class="mobile-menu__link" data-nav-link href="kapcsolat.html">Kapcsolat</a></li>
      </ul>
    </nav>
    <div class="mobile-menu__section">
      <h2 class="mobile-menu__section-title">Kategóriák</h2>
      <nav aria-label="Galéria kategóriák">
        <ul class="mobile-menu__list">
          <li><a class="mobile-menu__link" data-gallery-filter="all" href="#">Összes</a></li>
<?php foreach ($categories as $c): ?>
          <li><a class="mobile-menu__link" data-gallery-filter="<?= sz_e($c['slug']) ?>" href="#<?= sz_e($c['slug']) ?>"><?= sz_e($c['name']) ?></a></li>
<?php endforeach; ?>
        </ul>
      </nav>
    </div>
  </div>

  <aside class="site-aside" aria-label="Galéria kategóriák">
    <a href="index.html" class="site-aside__wordmark">Fényképészet</a>
    <nav class="aside-nav" aria-label="Kategória szűrő">
      <a class="aside-nav__link" data-gallery-filter="all" href="#">
        <svg class="aside-nav__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6" height="6" fill="none" stroke="currentColor"/><rect x="14" y="4" width="6" height="6" fill="none" stroke="currentColor"/><rect x="4" y="14" width="6" height="6" fill="none" stroke="currentColor"/><rect x="14" y="14" width="6" height="6" fill="none" stroke="currentColor"/></svg>
        Összes
      </a>
<?php foreach ($categories as $c): ?>
      <a class="aside-nav__link" data-gallery-filter="<?= sz_e($c['slug']) ?>" href="#<?= sz_e($c['slug']) ?>">
        <svg class="aside-nav__icon" viewBox="0 0 24 24" aria-hidden="true"><?= sz_cat_icon($c['slug']) ?></svg>
        <?= sz_e($c['name']) ?>
      </a>
<?php endforeach; ?>
    </nav>
  </aside>

  <main id="main" class="page-wrapper page-wrapper--with-aside">
    <div class="gallery-intro">
      <h1 class="gallery-intro__title">Munkáim</h1>
      <p class="gallery-intro__lead">Válogatás az elmúlt évek legkedvesebb pillanataiból. A bal oldali menü segítségével szűrheted a képeket kategória szerint, vagy kattints bármelyikre a teljes nézetért.</p>
    </div>

    <div class="gallery" role="list">
<?php if (empty($images)): ?>
      <p style="grid-column:1/-1;text-align:center;color:#737373;padding:3rem 1rem">
        Még nincsenek képek a galériában.
      </p>
<?php else: ?>
<?php foreach ($images as $img):
    $src     = sz_image_src($img['image_url']);
    $alt     = $img['alt_text'] ?: ($img['cat_name'] ?? '');
    $caption = $img['caption']  ?: ($img['cat_name'] ?? '');
?>
      <a class="gallery__item" role="listitem"
         data-gallery-item data-category="<?= sz_e($img['cat_slug']) ?>"
         data-lightbox-trigger
         data-lightbox-caption="<?= sz_e($caption) ?>"
         href="#"
         aria-label="<?= sz_e($alt) ?> megnyitása">
        <img src="<?= sz_e($src) ?>"
             alt="<?= sz_e($alt) ?>"
             <?php if ($img['width'])  echo 'width="'  . (int)$img['width']  . '" '; ?>
             <?php if ($img['height']) echo 'height="' . (int)$img['height'] . '" '; ?>
             loading="lazy" decoding="async">
        <span class="gallery__caption"><?= sz_e($caption) ?></span>
      </a>
<?php endforeach; ?>
<?php endif; ?>
    </div>
  </main>

  <a class="floating-cta" href="kapcsolat.html" aria-label="Kapcsolatfelvétel">
    <svg class="floating-cta__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z M4 6l8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
    <span class="floating-cta__text">
      <span class="floating-cta__primary">Kérdésed van?</span>
      <span class="floating-cta__secondary">Írj nekem</span>
    </span>
  </a>

  <footer class="site-footer">
    <span class="site-footer__copyright">© 2024–<?= date('Y') ?> Széchenyi Márk</span>
    <div class="site-footer__links">
      <a class="site-footer__link" href="https://instagram.com/" target="_blank" rel="noopener noreferrer">Instagram</a>
      <a class="site-footer__link" href="https://behance.net/" target="_blank" rel="noopener noreferrer">Behance</a>
    </div>
  </footer>

</body>
</html>
