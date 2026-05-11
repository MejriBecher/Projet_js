<?php
require_once __DIR__ . '/partials/admin_header.php';

$error = '';
$success = '';
$edit_room = null;

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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid session. Please try again.'; }
    else {
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $capacity = (int)($_POST['capacity'] ?? 1);
            $available = isset($_POST['is_available']) ? 1 : 0;

            if ($name === '' || $price <= 0) { $error = 'Name and valid price required.'; }
            else {
                $img = upload_image($_FILES['image'] ?? [], 'rooms');
                if ($img === null && $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE !== UPLOAD_ERR_NO_FILE) {
                    $error = 'Invalid image. Only JPG/PNG/WEBP under 5MB allowed.';
                } else {
                    if ($_POST['action'] === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO rooms (name, type, description, price, capacity, image, is_available) VALUES (?,?,?,?,?,?,?)");
                        $stmt->execute([$name, $type, $desc, $price, $capacity, $img, $available]);
                        $success = 'Room created.';
                    } else {
                        if ($img) {
                            $stmt = $pdo->prepare("UPDATE rooms SET name=?, type=?, description=?, price=?, capacity=?, image=?, is_available=? WHERE id=?");
                            $stmt->execute([$name, $type, $desc, $price, $capacity, $img, $available, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE rooms SET name=?, type=?, description=?, price=?, capacity=?, is_available=? WHERE id=?");
                            $stmt->execute([$name, $type, $desc, $price, $capacity, $available, $id]);
                        }
                        $success = 'Room updated.';
                    }
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Room deleted.';
        }
    } catch (PDOException $e) { log_error("Admin rooms error: " . $e->getMessage()); $error = 'Database error.'; }
    }
}

// Load edit data
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_room = $stmt->fetch();
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY created_at DESC")->fetchAll();
?>

<?php if ($success): ?><div class="flash flash-success"><?= escape($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= escape($error) ?></div><?php endif; ?>

<h2><?= $edit_room ? 'Edit Room' : 'Add Room' ?></h2>
<form method="post" enctype="multipart/form-data" class="form" style="max-width:600px">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="<?= $edit_room ? 'update' : 'create' ?>">
    <?php if ($edit_room): ?><input type="hidden" name="id" value="<?= (int)$edit_room['id'] ?>"><?php endif; ?>
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="<?= escape($edit_room['name'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label>Type</label>
        <select name="type">
            <option value="single" <?= ($edit_room['type'] ?? '') === 'single' ? 'selected' : '' ?>>Single</option>
            <option value="standard" <?= ($edit_room['type'] ?? '') === 'standard' ? 'selected' : '' ?>>Standard</option>
            <option value="deluxe" <?= ($edit_room['type'] ?? '') === 'deluxe' ? 'selected' : '' ?>>Deluxe</option>
            <option value="suite" <?= ($edit_room['type'] ?? '') === 'suite' ? 'selected' : '' ?>>Suite</option>
        </select>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description"><?= escape($edit_room['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label>Price ($/night)</label>
        <input type="number" step="0.01" name="price" value="<?= escape($edit_room['price'] ?? '') ?>" required min="0">
    </div>
    <div class="form-group">
        <label>Capacity (guests)</label>
        <input type="number" name="capacity" value="<?= (int)($edit_room['capacity'] ?? 1) ?>" min="1" max="20">
    </div>
    <div class="form-group">
        <label>Image (JPG/PNG/WEBP, max 5MB)</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if ($edit_room && $edit_room['image']): ?>
            <p><img src="<?= image_url($edit_room['image']) ?>" style="max-width:100px;margin-top:5px"></p>
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_available" <?= (!isset($edit_room) || $edit_room['is_available']) ? 'checked' : '' ?>> Available</label>
    </div>
    <button type="submit" class="btn"><?= $edit_room ? 'Update' : 'Create' ?> Room</button>
    <?php if ($edit_room): ?><a href="/app/admin/rooms.php" class="btn btn-sm">Cancel</a><?php endif; ?>
</form>

<h2>All Rooms</h2>
<div class="table-wrap">
<table>
<thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Type</th><th>Price</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($rooms as $r): ?>
<tr>
    <td><?= (int)$r['id'] ?></td>
    <td><?php if ($r['image']): ?><img src="<?= image_url($r['image']) ?>" style="width:60px;height:40px;object-fit:cover"><?php endif; ?></td>
    <td><?= escape($r['name']) ?></td>
    <td><?= escape(ucfirst($r['type'])) ?></td>
    <td>$<?= number_format($r['price'], 2) ?></td>
    <td><?= (int)$r['capacity'] ?></td>
    <td><span class="badge badge-<?= $r['is_available'] ? 'active' : 'inactive' ?>"><?= $r['is_available'] ? 'Available' : 'Hidden' ?></span></td>
    <td>
        <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this room?')">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
