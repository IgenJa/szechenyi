<?php
require_once __DIR__ . '/_layout.php';

$pdo    = sz_db();
$notice = '';
$error  = '';

/** Helper: töröld a feltöltési könyvtárból a képet, ha lokális. */
function sz_delete_image_file(string $url): void {
    if (preg_match('#^https?://#i', $url)) return;
    $path = sz_root() . '/' . ltrim($url, '/');
    if (is_file($path)) @unlink($path);
}

// --- Form műveletek ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sz_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Érvénytelen kérés, töltsd újra az oldalt.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $name = trim((string)($_POST['name'] ?? ''));
                if ($name === '') throw new RuntimeException('A kategória neve kötelező.');

                $slugInput = trim((string)($_POST['slug'] ?? ''));
                $slug = sz_slug($slugInput !== '' ? $slugInput : $name);
                $base = $slug; $i = 2;
                while (true) {
                    $stmt = $pdo->prepare('SELECT 1 FROM categories WHERE slug = ?');
                    $stmt->execute([$slug]);
                    if (!$stmt->fetch()) break;
                    $slug = $base . '-' . $i++;
                }

                $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM categories')->fetchColumn();
                $stmt = $pdo->prepare('INSERT INTO categories (slug, name, sort_order) VALUES (?, ?, ?)');
                $stmt->execute([$slug, $name, $maxOrder + 10]);
                $notice = 'Kategória létrehozva: ' . $name;

            } elseif ($action === 'update') {
                $id   = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $slugInput = trim((string)($_POST['slug'] ?? ''));
                $slug = sz_slug($slugInput !== '' ? $slugInput : $name);
                if ($id < 1 || $name === '') throw new RuntimeException('Hiányzó adatok.');

                $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id <> ?');
                $stmt->execute([$slug, $id]);
                if ($stmt->fetch()) throw new RuntimeException('Már van ilyen URL slug másik kategóriánál.');

                $pdo->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?')
                    ->execute([$name, $slug, $id]);
                $notice = 'Kategória frissítve.';

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id < 1) throw new RuntimeException('Érvénytelen kategória.');

                $imgs = $pdo->prepare('SELECT image_url FROM images WHERE category_id = ?');
                $imgs->execute([$id]);
                foreach ($imgs->fetchAll() as $row) {
                    sz_delete_image_file($row['image_url']);
                }
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                $notice = 'Kategória törölve a képeivel együtt.';

            } elseif ($action === 'reorder') {
                $order = json_decode((string)($_POST['order'] ?? '[]'), true);
                if (!is_array($order)) throw new RuntimeException('Érvénytelen sorrend.');
                $stmt = $pdo->prepare('UPDATE categories SET sort_order = ? WHERE id = ?');
                foreach ($order as $idx => $id) {
                    $stmt->execute([($idx + 1) * 10, (int)$id]);
                }
                $notice = 'Sorrend mentve.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$cats = $pdo->query("
    SELECT c.id, c.slug, c.name, c.sort_order,
           (SELECT COUNT(*) FROM images i WHERE i.category_id = c.id) AS img_count
      FROM categories c
     ORDER BY c.sort_order ASC, c.id ASC
")->fetchAll();

$csrf = sz_csrf_token();

sz_admin_header('Kategóriák', 'categories');
?>

<div class="admin-page-head">
  <h1 class="admin-page-title">Kategóriák</h1>
  <div class="admin-page-actions">
    <a class="admin-btn admin-btn--primary" href="#new">Új kategória</a>
  </div>
</div>

<?php if ($notice): ?>
  <div class="admin-alert admin-alert--success"><?= sz_e($notice) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="admin-alert admin-alert--error"><?= sz_e($error) ?></div>
<?php endif; ?>

<section class="admin-section">
  <p class="admin-text admin-text--muted">
    Fogd és húzd a fogantyút a sorrend módosításához. Kategória törlése a benne lévő képeket is törli.
  </p>

  <?php if (empty($cats)): ?>
    <p class="admin-empty">Nincs még kategória.</p>
  <?php else: ?>
    <ul class="admin-cat-list" data-sortable-cats data-csrf="<?= sz_e($csrf) ?>">
      <?php foreach ($cats as $c): ?>
        <li class="admin-cat-row" data-id="<?= (int)$c['id'] ?>">
          <button type="button" class="admin-handle" aria-label="Áthelyezés" title="Húzd a rendezéshez">⋮⋮</button>

          <form method="post" class="admin-cat-row__form">
            <input type="hidden" name="csrf"   value="<?= sz_e($csrf) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id"     value="<?= (int)$c['id'] ?>">

            <label class="admin-field admin-field--inline">
              <span class="admin-field__label">Név</span>
              <input class="admin-input" name="name" value="<?= sz_e($c['name']) ?>" required>
            </label>

            <label class="admin-field admin-field--inline">
              <span class="admin-field__label">URL slug</span>
              <input class="admin-input" name="slug" value="<?= sz_e($c['slug']) ?>"
                     pattern="[a-z0-9\-]+" title="Csak kisbetű, szám és kötőjel">
            </label>

            <span class="admin-cat-row__count">
              <a class="admin-link" href="gallery.php?cat=<?= (int)$c['id'] ?>">
                <?= (int)$c['img_count'] ?> kép →
              </a>
            </span>

            <div class="admin-cat-row__buttons">
              <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">Mentés</button>
            </div>
          </form>

          <form method="post" class="admin-cat-row__delete"
                data-confirm="Biztosan törlöd „<?= sz_e($c['name']) ?>” kategóriát és minden képét?">
            <input type="hidden" name="csrf"   value="<?= sz_e($csrf) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id"     value="<?= (int)$c['id'] ?>">
            <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Törlés</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<section class="admin-section" id="new">
  <div class="admin-section__head">
    <h2 class="admin-section__title">Új kategória felvétele</h2>
  </div>
  <form method="post" class="admin-form admin-form--row">
    <input type="hidden" name="csrf"   value="<?= sz_e($csrf) ?>">
    <input type="hidden" name="action" value="create">

    <label class="admin-field">
      <span class="admin-field__label">Név</span>
      <input class="admin-input" name="name" required placeholder="pl. Esküvő">
    </label>

    <label class="admin-field">
      <span class="admin-field__label">URL slug (opcionális)</span>
      <input class="admin-input" name="slug" pattern="[a-z0-9\-]+" placeholder="automatikus, ha üres">
    </label>

    <button class="admin-btn admin-btn--primary" type="submit">Hozzáadás</button>
  </form>
</section>

<?php sz_admin_footer(); ?>
