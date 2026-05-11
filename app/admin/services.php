<?php
require_once __DIR__ . '/partials/admin_header.php';

$error = '';
$success = '';
$edit_service = null;

function upload_image(array $file, string $subdir): ?string {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) { log_error("Upload error code: " . $file['error']); return null; }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $ext = match ($mime) { 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = "uploads/$subdir/$name";
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . "/../../$dest")) return null;
    return $dest;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid session. Please try again.'; }
    else {
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            if ($name === '') { $error = 'Name is required.'; }
            else {
                $img = upload_image($_FILES['image'] ?? [], 'services');
                if ($img === null && $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE !== UPLOAD_ERR_NO_FILE) {
                    $error = 'Invalid image. Only JPG/PNG/WEBP under 5MB allowed.';
                } else {
                    if ($_POST['action'] === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO services (name, description, price, image) VALUES (?,?,?,?)");
                        $stmt->execute([$name, $desc, $price, $img]);
                        $success = 'Service created.';
                    } else {
                        if ($img) {
                            $stmt = $pdo->prepare("UPDATE services SET name=?, description=?, price=?, image=? WHERE id=?");
                            $stmt->execute([$name, $desc, $price, $img, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE services SET name=?, description=?, price=? WHERE id=?");
                            $stmt->execute([$name, $desc, $price, $id]);
                        }
                        $success = 'Service updated.';
                    }
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Service deleted.';
        }
    } catch (PDOException $e) { log_error("Admin services error: " . $e->getMessage()); $error = 'Database error.'; }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_service = $stmt->fetch();
}

$services = $pdo->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll();
?>

<?php if ($success): ?><div class="flash flash-success"><?= escape($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= escape($error) ?></div><?php endif; ?>

<h2><?= $edit_service ? 'Edit Service' : 'Add Service' ?></h2>
<form method="post" enctype="multipart/form-data" class="form" style="max-width:600px">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="<?= $edit_service ? 'update' : 'create' ?>">
    <?php if ($edit_service): ?><input type="hidden" name="id" value="<?= (int)$edit_service['id'] ?>"><?php endif; ?>
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="<?= escape($edit_service['name'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description"><?= escape($edit_service['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label>Price ($)</label>
        <input type="number" step="0.01" name="price" value="<?= escape($edit_service['price'] ?? '0') ?>" min="0">
    </div>
    <div class="form-group">
        <label>Image (JPG/PNG/WEBP, max 5MB)</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if ($edit_service && $edit_service['image']): ?>
            <p><img src="<?= image_url($edit_service['image']) ?>" style="max-width:100px;margin-top:5px"></p>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn"><?= $edit_service ? 'Update' : 'Create' ?> Service</button>
    <?php if ($edit_service): ?><a href="/app/admin/services.php" class="btn btn-sm">Cancel</a><?php endif; ?>
</form>

<h2>All Services</h2>
<div class="table-wrap">
<table>
<thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($services as $s): ?>
<tr>
    <td><?= (int)$s['id'] ?></td>
    <td><?php if ($s['image']): ?><img src="<?= image_url($s['image']) ?>" style="width:60px;height:40px;object-fit:cover"><?php endif; ?></td>
    <td><?= escape($s['name']) ?></td>
    <td>$<?= number_format($s['price'], 2) ?></td>
    <td>
        <a href="?edit=<?= (int)$s['id'] ?>" class="btn btn-sm">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this service?')">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
