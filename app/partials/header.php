<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
unset($_SESSION['_old']);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Azur Cove Hotel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=1">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a href="/index.php" class="brand">Azur Cove Hotel</a>
        <ul class="nav-links">
            <li><a href="/index.php">Rooms</a></li>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <li><a href="/app/user/profile.php">My Account</a></li>
                <li><a href="/app/auth/logout.php">Logout</a></li>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <li><a href="/app/admin/index.php">Admin</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="/app/auth/login.php">Login</a></li>
                <li><a href="/app/auth/register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<main class="container">
<?php if ($flash): ?>
<div class="flash flash-<?= $flash['type'] ?>"><?= escape($flash['message']) ?></div>
<?php endif; ?>
