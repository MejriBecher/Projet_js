<?php
require_once __DIR__ . '/partials/admin_header.php';

$success = '';
$error = '';

// Status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid session. Please try again.'; }
    else {
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($_POST['action'] === 'update_status') {
            $status = $_POST['status'] ?? '';
            $valid = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
            if (in_array($status, $valid)) {
                $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                $success = 'Status updated.';
            }
        }
    } catch (PDOException $e) { log_error("Admin reservation update error: " . $e->getMessage()); $error = 'Database error.'; }
    }
}

$reservations = $pdo->query("
    SELECT r.*, u.name AS user_name, u.email AS user_email, rm.name AS room_name
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    ORDER BY r.created_at DESC
")->fetchAll();
?>

<?php if ($success): ?><div class="flash flash-success"><?= escape($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= escape($error) ?></div><?php endif; ?>

<h1>Reservations</h1>
<div class="table-wrap">
<table>
<thead>
<tr><th>ID</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Status</th><th>Booked</th></tr>
</thead>
<tbody>
<?php foreach ($reservations as $r): ?>
<tr>
    <td><?= (int)$r['id'] ?></td>
    <td><?= escape($r['user_name']) ?><br><small><?= escape($r['user_email']) ?></small></td>
    <td><?= escape($r['room_name']) ?></td>
    <td><?= escape($r['check_in']) ?></td>
    <td><?= escape($r['check_out']) ?></td>
    <td>$<?= number_format($r['total_price'], 2) ?></td>
    <td>
        <form method="post" style="display:flex;gap:5px;align-items:center">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <select name="status" class="badge badge-<?= escape($r['status']) ?>" onchange="this.form.submit()">
                <option value="pending" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $r['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="checked_in" <?= $r['status'] === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                <option value="checked_out" <?= $r['status'] === 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                <option value="cancelled" <?= $r['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </form>
    </td>
    <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($reservations)): ?>
<tr><td colspan="8" class="empty-state">No reservations yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
