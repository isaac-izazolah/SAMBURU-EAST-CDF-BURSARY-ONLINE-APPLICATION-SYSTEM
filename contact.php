<?php
require_once 'includes/functions.php';

// Handle contact form BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: contact.php');
        exit;
    }

    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if ($name && $email && $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $phone, $message]);
            $_SESSION['success_message'] = 'Your message has been sent successfully. We will get back to you soon.';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Failed to send message. Please try again later.';
        }
    } else {
        $_SESSION['error_message'] = 'Please fill in all required fields.';
    }
    header('Location: contact.php');
    exit;
}

$page_title = 'Contact Us';
include 'includes/header.php';

// Get user's recent messages with replies (by email from session or latest)
$userMessages = [];
$userEmail = '';
if (!empty($_GET['email']) && !empty($_GET['token'])) {
    $lookupEmail = sanitize($_GET['email']);
    $lookupToken = $_GET['token'];
    if (hash_equals($_SESSION['contact_lookup_token'] ?? '', $lookupToken)) {
        $userEmail = $lookupEmail;
        $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE email = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userEmail]);
        $userMessages = $stmt->fetchAll();
        unset($_SESSION['contact_lookup_token']);
    }
}
?>
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-envelope me-2"></i>Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Contact</li>
            </ol>
        </nav>
    </div>
</div>
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="form-section">
                    <h5 class="fw-bold mb-4"><i class="fas fa-paper-plane text-cdf-green me-2"></i>Send us a Message</h5>
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Message</label>
                            <textarea name="message" rows="4" class="form-control" required></textarea>
                        </div>
                        <?= getCSRFTokenField() ?>
                        <button type="submit" class="btn btn-cdf-green w-100"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                    </form>
                </div>

                <?php if (!empty($userMessages)): ?>
                <div class="form-section mt-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-inbox text-cdf-green me-2"></i>Your Messages &amp; Replies</h5>
                    <?php foreach ($userMessages as $um): ?>
                    <div class="card mb-3 border-0 shadow-sm" style="background:#f8f9fb;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('d M Y, H:i', strtotime($um['created_at'])) ?></small>
                                </div>
                                <?php if (!empty($um['admin_reply'])): ?>
                                <span class="badge bg-success"><i class="fas fa-reply me-1"></i>Replied</span>
                                <?php endif; ?>
                            </div>
                            <p class="mb-2" style="font-size:0.9rem;"><?= nl2br(sanitize($um['message'])) ?></p>
                            <?php if (!empty($um['admin_reply'])): ?>
                            <div class="mt-2 p-3 rounded" style="background:#dcfce7;border-left:3px solid #16a34a;">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-reply text-success me-1" style="font-size:0.8rem;"></i>
                                    <strong style="font-size:0.8rem;color:#166534;">Admin Reply</strong>
                                    <small class="text-muted ms-2"><?= date('d M Y, H:i', strtotime($um['replied_at'])) ?></small>
                                </div>
                                <p class="mb-0" style="font-size:0.88rem;color:#166534;"><?= nl2br(sanitize($um['admin_reply'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <div class="form-section h-100">
                    <h5 class="fw-bold mb-4"><i class="fas fa-address-card text-cdf-green me-2"></i>Contact Information</h5>
                    <div class="mb-4">
                        <h6><i class="fas fa-map-marker-alt text-cdf-green me-2"></i>Address</h6>
                        <p class="text-muted mb-0"><?= getSetting($pdo, 'contact_address', 'P.O. Box 248-40600, Maralal') ?></p>
                    </div>
                    <div class="mb-4">
                        <h6><i class="fas fa-phone text-cdf-green me-2"></i>Phone</h6>
                        <p class="text-muted mb-0"><?= getSetting($pdo, 'contact_phone', '+254-053-22025') ?></p>
                    </div>
                    <div class="mb-4">
                        <h6><i class="fas fa-envelope text-cdf-green me-2"></i>Email</h6>
                        <p class="text-muted mb-0"><?= getSetting($pdo, 'contact_email', 'info@samburueastcdf.go.ke') ?></p>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6><i class="fas fa-clock text-cdf-green me-2"></i>Office Hours</h6>
                        <p class="small mb-0">Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 8:00 AM - 1:00 PM<br>Sunday: Closed</p>
                    </div>

                    <div class="mt-4 p-3 rounded" style="background:#f0f6ff;border:1px solid #bfdbfe;">
                        <h6><i class="fas fa-info-circle text-primary me-2"></i>Check Your Messages</h6>
                        <p class="small text-muted mb-2">Enter your email address to see replies from our team.</p>
                        <?php $lookupToken = bin2hex(random_bytes(32)); $_SESSION['contact_lookup_token'] = $lookupToken; ?>
                        <form method="GET" class="input-group input-group-sm">
                            <input type="hidden" name="token" value="<?= $lookupToken ?>">
                            <input type="email" name="email" class="form-control" placeholder="Enter your email..." value="<?= sanitize($_GET['email'] ?? '') ?>">
                            <button type="submit" class="btn btn-cdf-green"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
