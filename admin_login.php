<?php
require_once 'config/config.php';

if (isAdminLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM administrators WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_logged_in'] = true;

            // Update last login
            $stmt = $pdo->prepare("UPDATE administrators SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);

            // Log successful login
            $stmt = $pdo->prepare("INSERT INTO login_logs (admin_id, username, ip_address, user_agent, status) VALUES (?, ?, ?, ?, 'Success')");
            $stmt->execute([$admin['id'], $username, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

            // Log audit
            logAudit($pdo, $admin['id'], 'Login', 'Admin logged in successfully');

            header('Location: admin/index.php');
            exit;
        } else {
            $error = 'Invalid username/email or password.';
            // Log failed login
            $stmt = $pdo->prepare("INSERT INTO login_logs (admin_id, username, ip_address, user_agent, status, failure_reason) VALUES (NULL, ?, ?, ?, 'Failed', 'Invalid credentials')");
            $stmt->execute([$username, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
        }
    } else {
        $error = 'Please enter your username and password.';
    }
}

$page_title = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= SITE_NAME ?></title>
    <link href="<?= SITE_URL ?>assets/vendor/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/vendor/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-icon"><img src="img/court of arm.png" alt="Court of Arms" style="width:80px;height:80px;object-fit:contain;"></div>
            <h4><?= SITE_NAME ?></h4>
            <p class="login-subtitle">Administrator Login</p>
            <?php if ($error): ?>
            <div class="alert alert-danger py-2" style="font-size:0.85rem;"><?= sanitize($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.85rem;font-weight:600;">Username / Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.85rem;font-weight:600;">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="loginPassword" class="form-control" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Show/Hide Password"><i class="fas fa-eye" id="toggleIcon"></i></button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-cdf-green w-100" style="padding:11px 22px;"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
            </form>
            <div style="text-align:center;margin-top:15px;">
                <a href="forgot_password.php" style="color:#8a9aa8;text-decoration:none;font-size:0.82rem;"><i class="fas fa-key me-1"></i>Forgot Password?</a>
            </div>
            <div style="text-align:center;margin-top:12px;">
                <a href="index.php" style="color:#8a9aa8;text-decoration:none;font-size:0.82rem;"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
            </div>
        </div>
    </div>
    <script src="<?= SITE_URL ?>assets/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="<?= SITE_URL ?>assets/vendor/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        var input = document.getElementById('loginPassword');
        var icon = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    </script>
</body>
</html>
