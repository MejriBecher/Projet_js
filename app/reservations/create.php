<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$error = '';
$success = '';
$room = null;
$room_id = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);

if ($room_id) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND is_available = 1");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid session. Please try again.'; }
    else {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND is_available = 1");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();

    if (!$room) {
        $error = 'Room not found or unavailable.';
    } elseif ($check_in === '' || $check_out === '') {
        $error = 'Please select check-in and check-out dates.';
    } elseif ($check_in >= $check_out) {
        $error = 'Check-out must be after check-in.';
    } elseif ($check_in < date('Y-m-d')) {
        $error = 'Check-in cannot be in the past.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id FROM reservations WHERE room_id = ? AND status NOT IN ('cancelled','checked_out') AND check_in < ? AND check_out > ? FOR UPDATE");
            $stmt->execute([$room_id, $check_out, $check_in]);
            if ($stmt->fetch()) {
                $error = 'This room is not available for the selected dates.';
                $pdo->rollBack();
            } else {
                $days = max(1, (strtotime($check_out) - strtotime($check_in)) / 86400);
                $total = $room['price'] * $days;
                $stmt = $pdo->prepare("INSERT INTO reservations (user_id, room_id, check_in, check_out, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['user_id'], $room_id, $check_in, $check_out, $total]);
                $pdo->commit();
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Reservation created! Total: $' . number_format($total, 2)];
                redirect('/app/user/profile.php');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_error("Reservation error: " . $e->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
    }
}

$stmt = $pdo->query("SELECT id, name, type, price, capacity, image FROM rooms WHERE is_available = 1 ORDER BY name");
$rooms = $stmt->fetchAll();
?>
<h1>Book a Room</h1>
<?php if ($error): ?><div class="flash flash-error"><?= escape($error) ?></div><?php endif; ?>
<form method="post" class="form" id="booking-form" style="max-width:500px">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <div class="form-group">
        <label for="room_id">Room</label>
        <select name="room_id" id="room_id" required>
            <option value="">— Select a room —</option>
            <?php foreach ($rooms as $r): ?>
            <option value="<?= (int)$r['id'] ?>" data-price="<?= (int)$r['price'] ?>" <?= $r['id'] === $room_id ? 'selected' : '' ?>>
                <?= escape($r['name']) ?> — $<?= number_format($r['price'], 2) ?>/night
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="check_in">Check-in</label>
        <input type="date" name="check_in" id="check_in" value="<?= escape($_POST['check_in'] ?? date('Y-m-d')) ?>" required min="<?= date('Y-m-d') ?>">
    </div>
    <div class="form-group">
        <label for="check_out">Check-out</label>
        <input type="date" name="check_out" id="check_out" value="<?= escape($_POST['check_out'] ?? date('Y-m-d', strtotime('+1 day'))) ?>" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
    </div>
    <div id="price-preview" class="form-group" style="display:none">
        <p><strong>Total: $<span id="total-price">0.00</span></strong></p>
        <p class="room-capacity" id="price-detail"></p>
    </div>
    <button type="submit" class="btn">Confirm Booking</button>
</form>
<script src="/assets/js/booking.js"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
