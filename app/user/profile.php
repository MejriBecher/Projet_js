<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT name, email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT r.id, r.check_in, r.check_out, r.total_price, r.status, r.created_at,
           rm.name AS room_name, rm.type AS room_type
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll();
?>
<h1>My Account</h1>
<div class="form" style="max-width:500px">
    <p><strong>Name:</strong> <?= escape($user['name']) ?></p>
    <p><strong>Email:</strong> <?= escape($user['email']) ?></p>
    <p><strong>Member since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
    <p><a href="/app/auth/logout.php" class="btn btn-sm">Logout</a></p>
</div>

<h2>My Reservations</h2>
<?php if (empty($reservations)): ?>
<p class="empty-state">No reservations yet. <a href="/index.php">Browse rooms</a>.</p>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr><th>Room</th><th>Type</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Status</th><th>Booked</th></tr>
</thead>
<tbody>
<?php foreach ($reservations as $rsv): ?>
<tr>
    <td><?= escape($rsv['room_name']) ?></td>
    <td><?= escape(ucfirst($rsv['room_type'])) ?></td>
    <td><?= escape($rsv['check_in']) ?></td>
    <td><?= escape($rsv['check_out']) ?></td>
    <td>$<?= number_format($rsv['total_price'], 2) ?></td>
    <td><span class="badge badge-<?= escape($rsv['status']) ?>"><?= escape(ucfirst(str_replace('_', ' ', $rsv['status']))) ?></span></td>
    <td><?= date('M j, Y', strtotime($rsv['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
