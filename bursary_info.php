<?php
require_once 'includes/functions.php';
$page_title = 'Bursary Information';
include 'includes/header.php';
$guidelines = getSetting($pdo, 'bursary_guidelines');
$appStatus = getSetting($pdo, 'application_status', 'Closed');
$appOpenDate = getSetting($pdo, 'application_open_date', '');
$appCloseDate = getSetting($pdo, 'application_close_date', '');
$financialYear = getSetting($pdo, 'financial_year', '');
?>
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-book me-2"></i>Bursary Information & Guidelines</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Bursary Info</li>
            </ol>
        </nav>
    </div>
</div>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="form-section">
                    <?= sanitizeHTML($guidelines) ?>
                    <hr>
                    <h5>Important Notes</h5>
                    <ul>
                        <li>Incomplete applications will not be processed</li>
                        <li>Providing false information leads to automatic disqualification</li>
                        <li>The committee reserves the right to verify all information provided</li>
                        <li>Allocation decisions are final and binding</li>
                        <li>Keep your Application Number safe for future reference</li>
                    </ul>
                    <div class="alert <?= $appStatus === 'Open' ? 'alert-success' : 'alert-warning' ?> mt-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-<?= $appStatus === 'Open' ? 'door-open' : 'door-closed' ?> fa-lg"></i>
                            <strong>Application Status: <?= $appStatus ?></strong>
                        </div>
                        <?php if ($financialYear): ?>
                        <p class="mb-1"><i class="fas fa-calendar-alt me-1"></i> Financial Year: <strong><?= sanitize($financialYear) ?></strong></p>
                        <?php endif; ?>
                        <?php if ($appOpenDate && strtotime($appOpenDate)): ?>
                        <p class="mb-1"><i class="fas fa-play-circle me-1"></i> Opening Date: <strong><?= date(DATE_FORMAT, strtotime($appOpenDate)) ?></strong></p>
                        <?php endif; ?>
                        <?php if ($appCloseDate && strtotime($appCloseDate)): ?>
                        <p class="mb-0"><i class="fas fa-stop-circle me-1"></i> Closing Date: <strong><?= date(DATE_FORMAT, strtotime($appCloseDate)) ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="apply.php" class="btn btn-cdf-green"><i class="fas fa-pen me-2"></i>Apply Now</a>
                        <a href="check_status.php" class="btn btn-outline-cdf-green"><i class="fas fa-search me-2"></i>Check Status</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
