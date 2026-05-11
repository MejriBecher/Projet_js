<?php
require_once __DIR__ . '/partials/admin_header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid session. Please try again.'; }
    else {
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($_POST['action'] === 'toggle_active') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'admin'");
            $stmt->execute([$id]);
            $success = 'User status toggled.';
        }
    } catch (PDOException $e) { log_error("Admin clients error: " . $e->getMessage()); $error = 'Database error.'; }
    }
}

$users = $pdo->query("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>

<?php if ($success): ?><div class="flash flash-success"><?= escape($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= escape($error) ?></div><?php endif; ?>

<h1>Clients</h1>
<div class="table-wrap">
<table>
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
    <td><?= (int)$u['id'] ?></td>
    <td><?= escape($u['name']) ?></td>
    <td><?= escape($u['email']) ?></td>
    <td><?= escape(ucfirst($u['role'])) ?></td>
    <td><span class="badge badge-<?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
    <td>
        <?php if ($u['role'] !== 'admin'): ?>
        <form method="post" style="display:inline">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="toggle_active">
            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
            </button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
