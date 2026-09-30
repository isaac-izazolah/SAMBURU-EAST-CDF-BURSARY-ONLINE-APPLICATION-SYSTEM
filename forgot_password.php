<?php
require_once 'config/config.php';

if (isAdminLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_request'])) {
    $email = sanitize($_POST['email'] ?? '');
    if ($email) {
        $stmt = $pdo->prepare("SELECT id, email, full_name FROM administrators WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $pdo->prepare("UPDATE administrators SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $admin['id']]);
            $message = 'A password reset link has been sent to your email. Please contact the Super Administrator if you cannot access your email.';
        } else {
            $error = 'No active account found with that email address.';
        }
    } else {
        $error = 'Please enter your email address.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset'])) {
    $token = sanitize($_POST['token'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$newPassword || strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM administrators WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $admin = $stmt->fetch();
        if ($admin) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE administrators SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")->execute([$hashed, $admin['id']]);
            $message = 'Password reset successful! You can now login.';
            $token = '';
        } else {
            $error = 'Invalid or expired reset link.';
        }
    }
}

$showResetForm = isset($_GET['token']) && $_GET['token'];
$tokenValue = isset($_GET['token']) ? sanitize($_GET['token']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= SITE_NAME ?></title>
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
            <p class="login-subtitle">Password Recovery</p>

            <?php if ($message): ?>
            <div class="alert alert-success py-2" style="font-size:0.85rem;"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger py-2" style="font-size:0.85rem;"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <?php if ($showResetForm && !$message): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= $tokenValue ?>">
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.85rem;font-weight:600;">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.85rem;font-weight:600;">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <button type="submit" name="do_reset" class="btn btn-cdf-green w-100" style="padding:11px 22px;"><i class="fas fa-key me-2"></i>Reset Password</button>
            </form>
            <?php elseif (!$message): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.85rem;font-weight:600;">Enter your registered email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required autofocus placeholder="admin@samburueastcdf.go.ke">
                    </div>
                </div>
                <button type="submit" name="reset_request" class="btn btn-cdf-green w-100" style="padding:11px 22px;"><i class="fas fa-paper-plane me-2"></i>Send Reset Link</button>
            </form>
            <div class="alert alert-info mt-3 py-2" style="font-size:0.82rem;">
                <i class="fas fa-info-circle me-1"></i> For immediate assistance, contact the Super Administrator.
            </div>
            <?php endif; ?>

            <div style="text-align:center;margin-top:20px;">
                <a href="admin_login.php" style="color:#8a9aa8;text-decoration:none;font-size:0.82rem;"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
            </div>
        </div>
    </div>
    <script src="<?= SITE_URL ?>assets/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="<?= SITE_URL ?>assets/vendor/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
