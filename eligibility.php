<?php
require_once 'includes/functions.php';
$page_title = 'Eligibility Criteria';
include 'includes/header.php';
$eligibility_content = getSetting($pdo, 'eligibility_content');
?>
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-check-circle me-2"></i>Eligibility Criteria</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Eligibility</li>
            </ol>
        </nav>
    </div>
</div>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="form-section">
                    <?= sanitizeHTML($eligibility_content) ?>
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>Please ensure you meet all the eligibility criteria before submitting your application.
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <a href="apply.php" class="btn btn-cdf-green"><i class="fas fa-pen me-2"></i>Apply Now</a>
                        <a href="documents.php" class="btn btn-outline-cdf-green"><i class="fas fa-file-alt me-2"></i>Required Documents</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
