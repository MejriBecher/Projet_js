<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $error = 'Invalid session. Please try again.'; }
    else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'All fields are required.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid email or password.';
            } elseif (!$user['is_active']) {
                $error = 'Your account has been deactivated. Contact support.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . $user['name'] . '!'];

                if ($user['role'] === 'admin') {
                    redirect('/app/admin/index.php');
                }
                redirect('/index.php');
            }
        } catch (PDOException $e) {
            log_error("Login error: " . $e->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
    }
}
?>
<h1>Login</h1>
<form method="post" class="form">
    <?php if ($error): ?><div class="flash flash-error"><?= escape($error) ?></div><?php endif; ?>
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
    </div>
    <button type="submit" class="btn">Login</button>
    <p>Don't have an account? <a href="/app/auth/register.php">Register</a></p>
</form>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
