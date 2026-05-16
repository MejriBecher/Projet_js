<?php
// Run only after importing schema.sql
// php setup.php
require_once __DIR__ . '/app/config/database.php';

$email = 'admin@azurcove.com';
$password = 'admin123';
$name = 'Azur Admin';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "Admin user already exists.\n";
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
$stmt->execute([$name, $email, $hash]);
echo "Admin user created: $email / $password\n";
