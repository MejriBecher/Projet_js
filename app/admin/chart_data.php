<?php
session_start();
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_admin();

header('Content-Type: application/json');

// Monthly revenue (last 6 months)
$revenue = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COALESCE(SUM(total_price),0) AS total
    FROM reservations
    WHERE status IN ('checked_in','checked_out')
      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
")->fetchAll();

$rev_labels = [];
$rev_data = [];
foreach ($revenue as $r) {
    $rev_labels[] = $r['month'];
    $rev_data[] = (float)$r['total'];
}

// Reservation status distribution
$status = $pdo->query("SELECT status, COUNT(*) AS count FROM reservations GROUP BY status")->fetchAll();
$stat_labels = [];
$stat_data = [];
$status_map = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked_in' => 'Checked In', 'checked_out' => 'Checked Out', 'cancelled' => 'Cancelled'];
foreach ($status as $s) {
    $stat_labels[] = $status_map[$s['status']] ?? $s['status'];
    $stat_data[] = (int)$s['count'];
}

echo json_encode([
    'revenue' => ['labels' => $rev_labels, 'data' => $rev_data],
    'status' => ['labels' => $stat_labels, 'data' => $stat_data],
]);
