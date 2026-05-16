<?php
require_once __DIR__ . '/partials/admin_header.php';
?>
<h1>Admin Dashboard</h1>
<p>Welcome, <?= escape($_SESSION['user_name']) ?>.</p>
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Rooms</h3>
        <div class="value" id="stat-rooms">-</div>
    </div>
    <div class="stat-card">
        <h3>Active Reservations</h3>
        <div class="value" id="stat-reservations">-</div>
    </div>
    <div class="stat-card">
        <h3>Total Users</h3>
        <div class="value" id="stat-users">-</div>
    </div>
    <div class="stat-card">
        <h3>Revenue</h3>
        <div class="value" id="stat-revenue">$0</div>
    </div>
</div>
<div class="chart-grid">
    <div class="chart-box chart-wrap">
        <h3>Monthly Revenue</h3>
        <canvas id="chart-revenue"></canvas>
    </div>
    <div class="chart-box chart-wrap">
        <h3>Reservation Status</h3>
        <canvas id="chart-status"></canvas>
    </div>
</div>
<?php require_once __DIR__ . '/partials/admin_footer.php'; ?>
