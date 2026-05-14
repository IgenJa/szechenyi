<?php
require_once __DIR__ . '/_layout.php';

$pdo = sz_db();

$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM categories) AS cat_count,
      (SELECT COUNT(*) FROM images)     AS img_count
")->fetch();

$catRows = $pdo->query("
    SELECT c.id, c.slug, c.name, c.sort_order,
           (SELECT COUNT(*) FROM images i WHERE i.category_id = c.id) AS img_count,
           (SELECT i.image_url FROM images i WHERE i.category_id = c.id ORDER BY i.is_cover DESC, i.sort_order ASC, i.id ASC LIMIT 1) AS cover_url
      FROM categories c
     ORDER BY c.sort_order ASC, c.id ASC
")->fetchAll();

sz_admin_header('Áttekintés', 'dashboard');
?>

<div class="admin-page-head">
  <h1 class="admin-page-title">Áttekintés</h1>
  <div class="admin-page-actions">
    <a class="admin-btn admin-btn--primary" href="gallery.php#upload">Új kép feltöltése</a>
    <a class="admin-btn admin-btn--ghost"   href="categories.php#new">Új kategória</a>
  </div>
</div>

<div class="admin-stats">
  <div class="admin-stat">
    <span class="admin-stat__label">Kategóriák</span>
    <span class="admin-stat__value"><?= (int)$stats['cat_count'] ?></span>
  </div>
  <div class="admin-stat">
    <span class="admin-stat__label">Képek</span>
    <span class="admin-stat__value"><?= (int)$stats['img_count'] ?></span>
  </div>
</div>

<section class="admin-section">
  <div class="admin-section__head">
    <h2 class="admin-section__title">Kategóriák</h2>
    <a class="admin-link" href="categories.php">Összes kezelése →</a>
  </div>

  <?php if (empty($catRows)): ?>
    <p class="admin-empty">Még nincs egyetlen kategória sem. <a class="admin-link" href="categories.php#new">Vegyél fel egyet</a>.</p>
  <?php else: ?>
    <div class="admin-cat-grid">
      <?php foreach ($catRows as $c): ?>
        <a class="admin-cat-card" href="gallery.php?cat=<?= (int)$c['id'] ?>">
          <div class="admin-cat-card__image">
            <?php if (!empty($c['cover_url'])): ?>
              <img src="<?= sz_e(sz_image_src($c['cover_url'], '../')) ?>" alt="">
            <?php else: ?>
              <div class="admin-cat-card__placeholder" aria-hidden="true">—</div>
            <?php endif; ?>
          </div>
          <div class="admin-cat-card__body">
            <strong class="admin-cat-card__name"><?= sz_e($c['name']) ?></strong>
            <span class="admin-cat-card__count"><?= (int)$c['img_count'] ?> kép</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php
sz_admin_footer();
