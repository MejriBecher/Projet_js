<?php
require_once __DIR__ . '/app/partials/header.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $stmt = $pdo->query("SELECT id, name, type, description, price, capacity, image FROM rooms WHERE is_available = 1 ORDER BY created_at DESC");
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    log_error("Homepage rooms error: " . $e->getMessage());
    $rooms = [];
}
?>
<h1>Our Rooms</h1>
<div class="filter-bar">
    <select id="filter-type">
        <option value="">All Types</option>
        <option value="single">Single</option>
        <option value="standard">Standard</option>
        <option value="deluxe">Deluxe</option>
        <option value="suite">Suite</option>
    </select>
    <input type="number" id="filter-max-price" placeholder="Max price ($)" min="0">
    <button id="filter-clear" class="btn btn-sm">Clear</button>
</div>
<div class="room-grid" id="room-grid">
    <?php foreach ($rooms as $room): ?>
    <div class="room-card" data-type="<?= escape($room['type']) ?>" data-price="<?= (int)$room['price'] ?>">
        <div class="room-img">
            <?php if ($room['image']): ?>
                <img src="<?= image_url($room['image']) ?>" alt="<?= escape($room['name']) ?>">
            <?php else: ?>
                <div class="room-img-placeholder">No Image</div>
            <?php endif; ?>
        </div>
        <div class="room-info">
            <h3><?= escape($room['name']) ?></h3>
            <p class="room-type"><?= escape(ucfirst($room['type'])) ?></p>
            <p class="room-desc"><?= escape($room['description']) ?></p>
            <p class="room-price">$<?= number_format($room['price'], 2) ?> / night</p>
            <p class="room-capacity">Up to <?= (int)$room['capacity'] ?> guests</p>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="/app/reservations/create.php?room_id=<?= (int)$room['id'] ?>" class="btn">Book Now</a>
            <?php else: ?>
                <a href="/app/auth/login.php" class="btn">Login to Book</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($rooms)): ?>
    <p class="empty-state">No rooms available at this time.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/app/partials/footer.php'; ?>
