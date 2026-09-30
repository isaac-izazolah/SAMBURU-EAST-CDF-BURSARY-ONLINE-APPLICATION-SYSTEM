<?php
require_once 'includes/functions.php';
$page_title = 'Home';
include 'includes/header.php';
$stats = getDashboardCounts($pdo);
$announcements = getAnnouncements($pdo, 3);
$appStatus = getSetting($pdo, 'application_status', 'Closed');
$appOpenDate = getSetting($pdo, 'application_open_date', '');
$appCloseDate = getSetting($pdo, 'application_close_date', '');
$financialYear = getSetting($pdo, 'financial_year', '');
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <h1>Welcome to <span class="hero-accent">Samburu East</span> NG-CDF</h1>
        <p class="lead mb-4 mx-auto" style="max-width:700px;">Online Bursary Management System — Apply for your bursary online, anytime, anywhere. No registration required.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap hero-buttons">
            <a href="apply.php" class="btn btn-primary-custom btn-lg"><i class="fas fa-pen me-2"></i>Apply Now</a>
            <a href="check_status.php" class="btn btn-outline-light-custom btn-lg"><i class="fas fa-search me-2"></i>Check Status</a>
        </div>
    </div>
</section>

<!-- Application Dates Banner -->
<?php if ($appOpenDate || $appCloseDate || $appStatus): ?>
<section class="py-3">
    <div class="container">
        <div class="app-dates-banner">
            <div class="app-dates-inner">
                <div class="app-dates-status <?= $appStatus === 'Open' ? 'status-open' : 'status-closed' ?>">
                    <i class="fas fa-<?= $appStatus === 'Open' ? 'door-open' : 'door-closed' ?>"></i>
                    <span>Applications <strong><?= $appStatus ?></strong></span>
                </div>
                <?php if ($financialYear): ?>
                <div class="app-dates-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Financial Year: <strong><?= sanitize($financialYear) ?></strong></span>
                </div>
                <?php endif; ?>
                <?php if ($appOpenDate && strtotime($appOpenDate)): ?>
                <div class="app-dates-item">
                    <i class="fas fa-play-circle"></i>
                    <span>Opens: <strong><?= date(DATE_FORMAT, strtotime($appOpenDate)) ?></strong></span>
                </div>
                <?php endif; ?>
                <?php if ($appCloseDate && strtotime($appCloseDate)): ?>
                <div class="app-dates-item">
                    <i class="fas fa-stop-circle"></i>
                    <span>Closes: <strong><?= date(DATE_FORMAT, strtotime($appCloseDate)) ?></strong></span>
                </div>
                <?php endif; ?>
                <?php if ($appStatus === 'Open'): ?>
                <a href="apply.php" class="btn btn-apply-now"><i class="fas fa-pen me-1"></i>Apply Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Stats Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card card p-3 text-center h-100" style="border-left:4px solid var(--accent);">
                    <div class="stat-icon bg-primary-blue bg-opacity-10 mx-auto mb-2 text-primary-blue" style="width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:var(--blue-bg);color:var(--accent);"><i class="fas fa-file-alt"></i></div>
                    <h3 class="fw-bold mb-0 text-primary-navy"><?= number_format($stats['total']) ?></h3>
                    <small class="text-muted">Total Applications</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card p-3 text-center h-100" style="border-left:4px solid #ffc107;">
                    <div class="stat-icon mx-auto mb-2" style="width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:#fff8e1;color:#856404;"><i class="fas fa-clock"></i></div>
                    <h3 class="fw-bold mb-0 text-primary-navy"><?= number_format($stats['pending'] + $stats['pending_verification']) ?></h3>
                    <small class="text-muted">Pending Review</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card p-3 text-center h-100" style="border-left:4px solid #28a745;">
                    <div class="stat-icon mx-auto mb-2" style="width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:#e8f5e9;color:#28a745;"><i class="fas fa-check-circle"></i></div>
                    <h3 class="fw-bold mb-0 text-primary-navy"><?= number_format($stats['approved']) ?></h3>
                    <small class="text-muted">Approved</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card card p-3 text-center h-100" style="border-left:4px solid var(--primary);">
                    <div class="stat-icon mx-auto mb-2" style="width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:rgba(26,58,92,0.08);color:var(--primary);"><i class="fas fa-hand-holding-usd"></i></div>
                    <h3 class="fw-bold mb-0 text-primary-navy"><?= number_format($stats['allocated']) ?></h3>
                    <small class="text-muted">Allocated</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-5 bg-white">
    <div class="container">
        <h2 class="section-title text-center">How It Works</h2>
        <p class="text-center text-muted mb-5" style="max-width:600px;margin-left:auto;margin-right:auto;">Apply for a bursary in three simple steps — no account needed</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card p-4 text-center h-100">
                    <div class="card-icon blue"><i class="fas fa-file-alt"></i></div>
                    <h5 class="fw-bold text-primary-navy">1. Submit Application</h5>
                    <p class="text-muted small">Fill out the online form and upload required documents. No registration or login needed.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card p-4 text-center h-100">
                    <div class="card-icon navy"><i class="fas fa-search"></i></div>
                    <h5 class="fw-bold text-primary-navy">2. Application Review</h5>
                    <p class="text-muted small">The NG-CDF committee reviews your application for verification and approval.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card p-4 text-center h-100">
                    <div class="card-icon blue"><i class="fas fa-check-circle"></i></div>
                    <h5 class="fw-bold text-primary-navy">3. Track Status</h5>
                    <p class="text-muted small">Use your Application Number and ID Number to check your status anytime.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Announcements -->
<?php if (count($announcements) > 0): ?>
<section class="py-5">
    <div class="container-fluid px-4">
        <h2 class="section-title text-center">Latest Announcements</h2>
        <div class="announcements-scroll">
            <?php foreach ($announcements as $ann): ?>
            <div class="announcement-card">
                <div class="announcement-card-inner">
                    <div class="announcement-badge">
                        <span class="badge" style="background:var(--accent);"><?= ucfirst(str_replace('_', ' ', $ann['announcement_type'])) ?></span>
                    </div>
                    <h5 class="announcement-title"><?= sanitize($ann['title']) ?></h5>
                    <p class="announcement-text"><?= mb_substr(strip_tags($ann['content']), 0, 200) ?>...</p>
                    <div class="announcement-meta">
                        <small class="text-muted"><i class="far fa-calendar me-1"></i><?= ($ann['published_date'] && strtotime($ann['published_date'])) ? date(DATE_FORMAT, strtotime($ann['published_date'])) : 'N/A' ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="announcements.php" class="btn btn-outline-primary-custom">View All Announcements</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Quick Links -->
<section class="py-5 bg-white">
    <div class="container">
        <h2 class="section-title text-center">Quick Links</h2>
        <div class="row g-3 mt-3">
            <div class="col-6 col-md-3">
                <a href="about.php" class="text-decoration-none">
                    <div class="card text-center p-4 border-0 shadow-sm h-100" style="border-radius:var(--radius);transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 25px rgba(26,58,92,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <i class="fas fa-info-circle fa-2x mb-2" style="color:var(--accent);"></i>
                        <small class="fw-bold text-primary-navy">About Us</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="eligibility.php" class="text-decoration-none">
                    <div class="card text-center p-4 border-0 shadow-sm h-100" style="border-radius:var(--radius);transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 25px rgba(26,58,92,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <i class="fas fa-check-circle fa-2x mb-2" style="color:var(--primary);"></i>
                        <small class="fw-bold text-primary-navy">Eligibility</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="documents.php" class="text-decoration-none">
                    <div class="card text-center p-4 border-0 shadow-sm h-100" style="border-radius:var(--radius);transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 25px rgba(26,58,92,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <i class="fas fa-file-alt fa-2x mb-2" style="color:var(--accent);"></i>
                        <small class="fw-bold text-primary-navy">Documents</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="faq.php" class="text-decoration-none">
                    <div class="card text-center p-4 border-0 shadow-sm h-100" style="border-radius:var(--radius);transition:all 0.3s;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 25px rgba(26,58,92,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <i class="fas fa-question-circle fa-2x mb-2" style="color:var(--primary);"></i>
                        <small class="fw-bold text-primary-navy">FAQs</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
