<?php
require_once 'includes/functions.php';
$page_title = 'Required Documents';
include 'includes/header.php';
$docs_content = getSetting($pdo, 'required_documents_content');
?>
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-file-alt me-2"></i>Required Documents</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Required Documents</li>
            </ol>
        </nav>
    </div>
</div>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="form-section">
                    <?= sanitizeHTML($docs_content) ?>
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6><i class="fas fa-info-circle text-cdf-green me-2"></i>Document Guidelines</h6>
                        <ul class="small mb-0">
                            <li>All documents must be clearly legible</li>
                            <li>Accepted formats: <strong>PDF, JPG, PNG</strong></li>
                            <li>Maximum file size: <strong>5 MB per document</strong></li>
                            <li>Documents should be recent (not older than 6 months)</li>
                            <li>Fee structure must show the current academic year fees</li>
                            <li>Fee statement must show fees paid to date</li>
                        </ul>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <a href="apply.php" class="btn btn-cdf-green"><i class="fas fa-pen me-2"></i>Apply Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
