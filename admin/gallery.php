<?php
require_once __DIR__ . '/_layout.php';

$pdo    = sz_db();
$notice = '';
$error  = '';

$cats = $pdo->query('SELECT id, name, slug FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();

if (empty($cats)) {
    sz_admin_header('Galéria', 'gallery');
    ?>
    <div class="admin-page-head"><h1 class="admin-page-title">Galéria</h1></div>
    <p class="admin-empty">
      Először hozz létre legalább egy kategóriát.
      <a class="admin-link" href="categories.php#new">Új kategória →</a>
    </p>
    <?php
    sz_admin_footer();
    exit;
}

// Aktív kategória meghatározása
$activeCatId = (int)($_GET['cat'] ?? $cats[0]['id']);
$activeCat   = null;
foreach ($cats as $c) {
    if ((int)$c['id'] === $activeCatId) { $activeCat = $c; break; }
}
if (!$activeCat) { $activeCat = $cats[0]; $activeCatId = (int)$activeCat['id']; }

// --- Form műveletek ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sz_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Érvénytelen kérés, töltsd újra az oldalt.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'upload') {
                $catId = (int)($_POST['category_id'] ?? 0);
                if ($catId < 1) throw new RuntimeException('Válassz kategóriát.');

                if (empty($_FILES['files']) || !is_array($_FILES['files']['name'])) {
                    // post_max_size túllépésekor $_FILES üres lehet
                    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
                    $postMax = sz_ini_to_bytes((string)ini_get('post_max_size'));
                    if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax) {
                        throw new RuntimeException(sprintf(
                            'A feltöltött adat (%.1f MB) meghaladja a PHP post_max_size limitjét (%.1f MB). Indítsd újra a PHP szervert magasabb limitekkel.',
                            $contentLength / 1048576, $postMax / 1048576
                        ));
                    }
                    throw new RuntimeException('Nincs feltöltött fájl.');
                }

                [$uploaded, $errors] = sz_handle_uploads($_FILES['files'], $catId);
                if ($uploaded > 0 && empty($errors)) {
                    $notice = $uploaded . ' kép feltöltve.';
                } elseif ($uploaded > 0 && !empty($errors)) {
                    $notice = $uploaded . ' kép feltöltve. Hibák: ' . implode(' • ', $errors);
                } else {
                    throw new RuntimeException('Egy kép sem töltődött fel. ' . implode(' • ', $errors));
                }
                $activeCatId = $catId;

            } elseif ($action === 'update_image') {
                $id      = (int)($_POST['id'] ?? 0);
                $catId   = (int)($_POST['category_id'] ?? 0);
                $alt     = trim((string)($_POST['alt_text'] ?? ''));
                $caption = trim((string)($_POST['caption'] ?? ''));
                if ($id < 1 || $catId < 1) throw new RuntimeException('Érvénytelen adatok.');

                $pdo->prepare('UPDATE images SET category_id = ?, alt_text = ?, caption = ? WHERE id = ?')
                    ->execute([$catId, $alt, $caption, $id]);
                $notice = 'Kép frissítve.';

            } elseif ($action === 'delete_image') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id < 1) throw new RuntimeException('Érvénytelen kép.');
                $stmt = $pdo->prepare('SELECT image_url FROM images WHERE id = ?');
                $stmt->execute([$id]);
                if ($row = $stmt->fetch()) {
                    sz_delete_image_file($row['image_url']);
                    $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$id]);
                    $notice = 'Kép törölve.';
                }

            } elseif ($action === 'set_cover') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id < 1) throw new RuntimeException('Érvénytelen kép.');
                $stmt = $pdo->prepare('SELECT category_id FROM images WHERE id = ?');
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if (!$row) throw new RuntimeException('Nem található kép.');
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE images SET is_cover = 0 WHERE category_id = ?')->execute([$row['category_id']]);
                $pdo->prepare('UPDATE images SET is_cover = 1 WHERE id = ?')->execute([$id]);
                $pdo->commit();
                $notice = 'Főkép beállítva.';

            } elseif ($action === 'reorder_images') {
                $order = json_decode((string)($_POST['order'] ?? '[]'), true);
                if (!is_array($order)) throw new RuntimeException('Érvénytelen sorrend.');
                $stmt = $pdo->prepare('UPDATE images SET sort_order = ? WHERE id = ?');
                foreach ($order as $idx => $id) {
                    $stmt->execute([($idx + 1) * 10, (int)$id]);
                }
                $notice = 'Sorrend mentve.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
            if ($pdo->inTransaction()) $pdo->rollBack();
        }
    }
}

/** Helper: külön függvényben a kép-fájl törléshez. */
function sz_delete_image_file(string $url): void {
    if (preg_match('#^https?://#i', $url)) return;
    $path = sz_root() . '/' . ltrim($url, '/');
    if (is_file($path)) @unlink($path);
}

/** "2M" / "8M" / "512K" stringet bájtokká konvertál. */
function sz_ini_to_bytes(string $val): int {
    $val = trim($val);
    if ($val === '') return 0;
    $last = strtolower($val[strlen($val) - 1]);
    $num  = (float)$val;
    switch ($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return (int)$num;
}

/** PHP upload error kód → emberi szöveg. */
function sz_upload_err_msg(int $code): string {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'túl nagy fájl (PHP upload_max_filesize, jelenleg ' . ini_get('upload_max_filesize') . ')';
        case UPLOAD_ERR_FORM_SIZE:   return 'túl nagy fájl (form limit)';
        case UPLOAD_ERR_PARTIAL:     return 'a feltöltés félbeszakadt';
        case UPLOAD_ERR_NO_FILE:     return 'nincs fájl';
        case UPLOAD_ERR_NO_TMP_DIR:  return 'hiányzó temp könyvtár a szerveren';
        case UPLOAD_ERR_CANT_WRITE:  return 'nem írható a temp könyvtár';
        case UPLOAD_ERR_EXTENSION:   return 'PHP kiterjesztés blokkolta';
        default: return 'ismeretlen hiba (' . $code . ')';
    }
}

/**
 * Feltöltött fájlok feldolgozása és DB-be írás.
 * Vissza: [int $inserted, string[] $errors].
 */
function sz_handle_uploads(array $files, int $catId): array {
    $cfg = sz_config()['app'];
    $allowed   = $cfg['allowed_ext'];
    $maxSize   = (int)$cfg['max_size'];
    $uploadDir = sz_upload_dir();
    $pdo       = sz_db();

    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        return [0, ["A `uploads/` könyvtár nem írható: $uploadDir"]];
    }

    $count = count($files['name']);
    $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM images WHERE category_id = '
                                . (int)$catId)->fetchColumn();

    $inserted = 0;
    $errors   = [];

    for ($i = 0; $i < $count; $i++) {
        $err  = (int)$files['error'][$i];
        $name = (string)$files['name'][$i];
        $tmp  = (string)$files['tmp_name'][$i];
        $size = (int)$files['size'][$i];

        $label = $name !== '' ? $name : "fájl#$i";

        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = "$label: " . sz_upload_err_msg($err);
            continue;
        }
        if ($size <= 0) {
            $errors[] = "$label: üres fájl";
            continue;
        }
        if ($size > $maxSize) {
            $errors[] = sprintf('%s: túl nagy (%.1f MB > limit %.1f MB)',
                $label, $size / 1048576, $maxSize / 1048576);
            continue;
        }
        if (!is_uploaded_file($tmp)) {
            $errors[] = "$label: érvénytelen feltöltés";
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $errors[] = "$label: nem engedélyezett kiterjesztés (.$ext)";
            continue;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $tmp) : null;
        if ($finfo) finfo_close($finfo);
        $okMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $okMimes, true)) {
            $errors[] = "$label: nem kép fájl (MIME: $mime)";
            continue;
        }

        $info = @getimagesize($tmp);
        $w = $info[0] ?? null;
        $h = $info[1] ?? null;

        $unique  = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destAbs = $uploadDir . '/' . $unique;
        if (!move_uploaded_file($tmp, $destAbs)) {
            $errors[] = "$label: a fájl áthelyezése nem sikerült";
            continue;
        }
        @chmod($destAbs, 0644);

        $relUrl = trim($cfg['upload_dir'], '/') . '/' . $unique;
        $maxOrder += 10;

        $stmt = $pdo->prepare(
            'INSERT INTO images (category_id, image_url, width, height, sort_order, alt_text)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $catId, $relUrl, $w, $h, $maxOrder,
            pathinfo($name, PATHINFO_FILENAME),
        ]);
        $inserted++;
    }

    return [$inserted, $errors];
}

// Aktív kategória képeinek lekérdezése
$imgStmt = $pdo->prepare("
    SELECT id, image_url, alt_text, caption, sort_order, is_cover, width, height
      FROM images
     WHERE category_id = ?
     ORDER BY sort_order ASC, id ASC
");
$imgStmt->execute([$activeCatId]);
$images = $imgStmt->fetchAll();

$csrf = sz_csrf_token();
$maxSize = (int)sz_config()['app']['max_size'];
$maxSizeMb = round($maxSize / 1024 / 1024, 1);

sz_admin_header('Galéria — ' . $activeCat['name'], 'gallery');
?>

<div class="admin-page-head">
  <h1 class="admin-page-title">Galéria</h1>
  <div class="admin-page-actions">
    <a class="admin-btn admin-btn--ghost" href="categories.php">Kategóriák kezelése</a>
  </div>
</div>

<?php if ($notice): ?>
  <div class="admin-alert admin-alert--success"><?= sz_e($notice) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="admin-alert admin-alert--error"><?= sz_e($error) ?></div>
<?php endif; ?>

<nav class="admin-tabs" aria-label="Kategória választó">
  <?php foreach ($cats as $c): ?>
    <a class="admin-tab <?= ((int)$c['id'] === $activeCatId) ? 'is-active' : '' ?>"
       href="?cat=<?= (int)$c['id'] ?>"><?= sz_e($c['name']) ?></a>
  <?php endforeach; ?>
</nav>

<section class="admin-section" id="upload">
  <div class="admin-section__head">
    <h2 class="admin-section__title">Új képek feltöltése — <?= sz_e($activeCat['name']) ?></h2>
  </div>

  <form method="post" enctype="multipart/form-data" class="admin-upload"
        data-max-size="<?= (int)$maxSize ?>">
    <input type="hidden" name="csrf"        value="<?= sz_e($csrf) ?>">
    <input type="hidden" name="action"      value="upload">
    <input type="hidden" name="category_id" value="<?= (int)$activeCatId ?>">

    <label class="admin-dropzone" tabindex="0">
      <input type="file" name="files[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
             multiple data-file-input hidden>
      <span class="admin-dropzone__icon" aria-hidden="true">⬆</span>
      <span class="admin-dropzone__text">
        <strong>Húzd ide a képeket</strong>, vagy kattints a tallózáshoz.<br>
        <small>JPG, PNG, WEBP — max <?= sz_e((string)$maxSizeMb) ?> MB / fájl</small>
      </span>
    </label>

    <div class="admin-dropzone__previews" data-previews hidden></div>

    <button class="admin-btn admin-btn--primary" type="submit" data-upload-submit disabled>
      Feltöltés
    </button>
  </form>
</section>

<section class="admin-section">
  <div class="admin-section__head">
    <h2 class="admin-section__title">Képek — <?= sz_e($activeCat['name']) ?> (<?= count($images) ?>)</h2>
    <p class="admin-text admin-text--muted" style="margin:0">Húzd a képeket a sorrend módosításához.</p>
  </div>

  <?php if (empty($images)): ?>
    <p class="admin-empty">Még nincs kép ebben a kategóriában. Tölts fel néhányat fent.</p>
  <?php else: ?>
    <ul class="admin-img-grid" data-sortable-images data-csrf="<?= sz_e($csrf) ?>">
      <?php foreach ($images as $img): ?>
        <li class="admin-img-card <?= $img['is_cover'] ? 'is-cover' : '' ?>" data-id="<?= (int)$img['id'] ?>">
          <div class="admin-img-card__media">
            <img src="<?= sz_e(sz_image_src($img['image_url'], '../')) ?>"
                 alt="<?= sz_e($img['alt_text'] ?? '') ?>" loading="lazy">
            <?php if ($img['is_cover']): ?>
              <span class="admin-badge">Főkép</span>
            <?php endif; ?>
          </div>

          <form method="post" class="admin-img-card__form">
            <input type="hidden" name="csrf"   value="<?= sz_e($csrf) ?>">
            <input type="hidden" name="action" value="update_image">
            <input type="hidden" name="id"     value="<?= (int)$img['id'] ?>">

            <label class="admin-field">
              <span class="admin-field__label">Kategória</span>
              <select class="admin-input" name="category_id">
                <?php foreach ($cats as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $activeCatId) ? 'selected' : '' ?>>
                    <?= sz_e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="admin-field">
              <span class="admin-field__label">Alt szöveg (akadálymentesítés)</span>
              <input class="admin-input admin-input--sm" name="alt_text"
                     value="<?= sz_e($img['alt_text'] ?? '') ?>"
                     placeholder="pl. Családi pillanat természetes fényben">
            </label>

            <label class="admin-field">
              <span class="admin-field__label">Felirat (lightbox)</span>
              <input class="admin-input admin-input--sm" name="caption"
                     value="<?= sz_e($img['caption'] ?? '') ?>"
                     placeholder="pl. Családi · 2024">
            </label>

            <div class="admin-img-card__buttons">
              <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">Mentés</button>
            </div>
          </form>

          <div class="admin-img-card__actions">
            <?php if (!$img['is_cover']): ?>
              <form method="post" class="admin-inline-form">
                <input type="hidden" name="csrf"   value="<?= sz_e($csrf) ?>">
                <input type="hidden" name="action" value="set_cover">
                <input type="hidden" name="id"     value="<?= (int)$img['id'] ?>">
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">Legyen főkép</button>
              </form>
            <?php endif; ?>

            <form method="post" class="admin-inline-form"
                  data-confirm="Biztosan törlöd ezt a képet?">
              <input type="hidden" name="csrf"   value="<?= sz_e($csrf) ?>">
              <input type="hidden" name="action" value="delete_image">
              <input type="hidden" name="id"     value="<?= (int)$img['id'] ?>">
              <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Törlés</button>
            </form>

            <button type="button" class="admin-handle admin-handle--floating"
                    title="Húzd a rendezéshez" aria-label="Áthelyezés">⋮⋮</button>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php sz_admin_footer(); ?>
