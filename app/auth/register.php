<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid session. Please try again.'; }
    else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hash]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registration successful. Please log in.'];
                redirect('/app/auth/login.php');
            }
        } catch (PDOException $e) {
            log_error("Register error: " . $e->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
    }
}
?>
<h1>Register</h1>
<form method="post" class="form">
    <?php if ($error): ?><div class="flash flash-error"><?= escape($error) ?></div><?php endif; ?>
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" value="<?= escape(old('name')) ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= escape(old('email')) ?>" required>
    </div>
    <div class="form-group">
        <label for="password">Password (min 6 chars)</label>
        <input type="password" name="password" id="password" required minlength="6">
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
    </div>
    <button type="submit" class="btn">Register</button>
    <p>Already have an account? <a href="/app/auth/login.php">Log in</a></p>
</form>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
