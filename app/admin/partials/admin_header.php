<?php
session_start();
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_admin();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Azur Cove Hotel</title>
<link rel="stylesheet" href="/assets/css/style.css?v=1">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <h2><a href="/app/admin/index.php" class="brand">Azur Cove — Admin</a></h2>
        <ul>
            <li><a href="/app/admin/index.php">Dashboard</a></li>
            <li><a href="/app/admin/rooms.php">Rooms</a></li>
            <li><a href="/app/admin/services.php">Services</a></li>
            <li><a href="/app/admin/reservations.php">Reservations</a></li>
            <li><a href="/app/admin/clients.php">Clients</a></li>
            <li><a href="/index.php">View Site</a></li>
            <li><a href="/app/auth/logout.php">Logout</a></li>
        </ul>
    </aside>
    <main class="admin-content">
    <?php
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>"><?= escape($flash['message']) ?></div>
    <?php endif; ?>
<script src="/assets/js/admin-charts.js"></script>
