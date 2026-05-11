<?php
session_start();
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_admin();

$total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$active_rsv = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status NOT IN ('cancelled','checked_out')")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$revenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM reservations WHERE status IN ('checked_in','checked_out')")->fetchColumn();

echo json_encode([
    'rooms' => (int)$total_rooms,
    'reservations' => (int)$active_rsv,
    'users' => (int)$total_users,
    'revenue' => (float)$revenue,
]);
