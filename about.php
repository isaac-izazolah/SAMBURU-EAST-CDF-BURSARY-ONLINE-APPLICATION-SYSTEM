<?php
require_once 'includes/functions.php';
$page_title = 'About Us';
include 'includes/header.php';
$about_content = getSetting($pdo, 'about_content');
?>
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-info-circle me-2"></i>About Samburu East NG-CDF</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" style="color:var(--accent-light);">Home</a></li>
                <li class="breadcrumb-item active">About Us</li>
            </ol>
        </nav>
    </div>
</div>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="form-section">
                    <?= sanitizeHTML($about_content) ?>
                    <h4 class="mt-4">Our Mission</h4>
                    <p>To provide equitable and quality education support to needy and deserving students from Samburu East Constituency through transparent and accountable bursary management.</p>
                    <h4 class="mt-4">Our Vision</h4>
                    <p>A well-educated and empowered community in Samburu East Constituency contributing to national development.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-hand-holding-heart fa-3x text-cdf-green mb-3"></i>
                        <h5 class="fw-bold">NG-CDF Bursary</h5>
                        <p class="small text-muted">The NG-CDF bursary program is an initiative of the Kenyan Government to support students from needy backgrounds access secondary and tertiary education.</p>
                        <a href="apply.php" class="btn btn-cdf-green w-100"><i class="fas fa-pen me-2"></i>Apply Now</a>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold">Contact Information</h6>
                        <hr>
                        <p class="small mb-1"><i class="fas fa-map-marker-alt text-cdf-green me-2"></i><?= sanitize(getSetting($pdo, 'contact_address')) ?></p>
                        <p class="small mb-1"><i class="fas fa-phone text-cdf-green me-2"></i><?= sanitize(getSetting($pdo, 'contact_phone')) ?></p>
                        <p class="small mb-0"><i class="fas fa-envelope text-cdf-green me-2"></i><?= sanitize(getSetting($pdo, 'contact_email')) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
