<?php
require_once 'includes/functions.php';
$page_title = 'Check Application Status';
$statusResult = null;
$tracking = null;
$documents = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_status'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $appNumber = sanitize(trim($_POST['app_number'] ?? ''));
        $nationalId = sanitize(trim($_POST['national_id'] ?? ''));

        if ($appNumber || $nationalId) {
            if ($appNumber && $nationalId) {
                $stmt = $pdo->prepare("SELECT a.*, w.ward_name FROM applications a LEFT JOIN wards w ON a.ward_id = w.id WHERE a.application_number = ? AND a.national_id = ?");
                $stmt->execute([$appNumber, $nationalId]);
            } elseif ($appNumber) {
                $stmt = $pdo->prepare("SELECT a.*, w.ward_name FROM applications a LEFT JOIN wards w ON a.ward_id = w.id WHERE a.application_number = ?");
                $stmt->execute([$appNumber]);
            } else {
                $stmt = $pdo->prepare("SELECT a.*, w.ward_name FROM applications a LEFT JOIN wards w ON a.ward_id = w.id WHERE a.national_id = ?");
                $stmt->execute([$nationalId]);
            }
            $statusResult = $stmt->fetch();

            if ($statusResult) {
                $tracking = getApplicationTracking($pdo, $statusResult['id']);
                $documents = getApplicationDocuments($pdo, $statusResult['id']);
                if (!isset($_SESSION['tracked_app_ids'])) {
                    $_SESSION['tracked_app_ids'] = [];
                }
                if (!in_array($statusResult['id'], $_SESSION['tracked_app_ids'])) {
                    $_SESSION['tracked_app_ids'][] = $statusResult['id'];
                }
            }
        }

        if (!$statusResult) {
            $error = 'No application found. Please check your Application Number and/or National ID/Birth Certificate Number.';
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-search me-2"></i>Check Application Status</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" style="color:var(--accent-light);">Home</a></li>
                <li class="breadcrumb-item active">Check Status</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= sanitize($error) ?></div>
                <?php endif; ?>

                <div class="form-section">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Application Number</label>
                            <input type="text" name="app_number" class="form-control" placeholder="e.g. SECDF202600001" value="<?= sanitize($_POST['app_number'] ?? '') ?>">
                            <small class="text-muted">Enter your Application Number if you have it.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">National ID / Birth Certificate Number</label>
                            <input type="text" name="national_id" class="form-control" placeholder="e.g. 12345678" value="<?= sanitize($_POST['national_id'] ?? '') ?>">
                            <small class="text-muted">Enter your National ID or Birth Certificate Number.</small>
                        </div>
                        <div class="alert alert-info small mb-3"><i class="fas fa-info-circle me-1"></i> You can use <strong>either one</strong> or <strong>both</strong> to look up your application. Using both is more specific.</div>
                        <?= getCSRFTokenField() ?>
                        <button type="submit" name="check_status" class="btn btn-cdf-green w-100"><i class="fas fa-search me-2"></i>Check Status</button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($statusResult): ?>
        <div class="row justify-content-center mt-5">
            <div class="col-lg-8">
                <div class="form-section print-area" id="statusPrint">
                    <div class="print-letterhead text-center mb-4">
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <img src="<?= SITE_URL ?>img/cdf%20logo.png" alt="NG-CDF Logo" style="height:65px;">
                            <div>
                                <h4 class="fw-bold mb-0" style="color:var(--primary);"><?= SITE_NAME ?></h4>
                                <small style="color:#555;">National Government Constituency Development Fund</small>
                            </div>
                            <img src="<?= SITE_URL ?>img/court%20of%20arm.png" alt="Court of Arms" style="height:65px;">
                        </div>
                        <hr style="border-top:2px solid var(--accent);">
                        <h5 class="fw-bold" style="color:var(--accent);">Application Status Report</h5>
                    </div>
                    <h5 class="fw-bold mb-4 d-none d-print-block"><i class="fas fa-info-circle text-cdf-green me-2"></i>Application Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Application Number:</strong><br><span class="fw-bold" style="color:var(--primary)"><?= sanitize($statusResult['application_number']) ?></span></p>
                            <p><strong>Full Name:</strong><br><?= sanitize($statusResult['full_name']) ?></p>
                            <p><strong>Institution:</strong><br><?= sanitize($statusResult['institution_name']) ?> (<?= $statusResult['institution_type'] ?>)</p>
                            <?php if (!empty($statusResult['level'])): ?>
                            <p><strong>Level:</strong><br><?= sanitize($statusResult['level']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Academic Year:</strong><br><?= sanitize($statusResult['academic_year']) ?></p>
                            <p><strong>Amount Requested:</strong><br><?= formatAmount($statusResult['amount_requested']) ?></p>
                            <p><strong>Current Status:</strong><br><span id="currentStatus"><?= getStatusBadge($statusResult['status']) ?></span></p>
                            <?php if ($statusResult['allocated_amount'] > 0): ?>
                            <p><strong>Allocated Amount:</strong><br><span id="allocatedAmount" class="fw-bold" style="color:var(--primary)"><?= formatAmount($statusResult['allocated_amount']) ?></span></p>
                            <?php endif; ?>
                            <?php if ($statusResult['approval_date']): ?>
                            <p><strong>Approval Date:</strong><br><?= date(DATE_FORMAT, strtotime($statusResult['approval_date'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($statusResult['admin_remarks']): ?>
                    <div class="alert alert-info">
                        <strong><i class="fas fa-comment me-2"></i>Remarks:</strong><br>
                        <?= nl2br(sanitize($statusResult['admin_remarks'])) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($tracking && count($tracking) > 0): ?>
                    <h6 class="fw-bold mt-4 mb-3"><i class="fas fa-history me-2"></i>Status History</h6>
                    <div class="status-timeline">
                        <?php foreach ($tracking as $track): ?>
                        <div class="status-item <?= $track['new_status'] === 'Rejected' ? 'rejected' : 'completed' ?>">
                            <strong><?= $track['new_status'] ?></strong>
                            <small class="text-muted d-block"><?= date(DATETIME_FORMAT, strtotime($track['created_at'])) ?></small>
                            <?php if ($track['remarks']): ?>
                            <small class="text-muted d-block"><?= sanitize($track['remarks']) ?></small>
                            <?php endif; ?>
                            <?php if ($track['changed_by_name']): ?>
                            <small class="text-muted">By: <?= sanitize($track['changed_by_name']) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <div class="status-item active">
                            <strong>Current: <?= $statusResult['status'] ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($documents && count($documents) > 0): ?>
                    <h6 class="fw-bold mt-4 mb-3"><i class="fas fa-file me-2"></i>Uploaded Documents</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>File Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?= ucwords(str_replace('_', ' ', $doc['document_type'])) ?></td>
                                    <td><?= sanitize($doc['file_name']) ?></td>
                                    <td><a href="<?= SITE_URL ?>view_document.php?id=<?= (int)$doc['id'] ?>" target="_blank" class="btn btn-sm btn-outline-cdf-green"><i class="fas fa-eye"></i> View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <div class="print-only-footer text-center mt-3 pt-3" style="border-top:2px solid var(--accent);">
                        <p class="small text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Printed on: <strong><?= date('d M Y H:i:s') ?></strong></p>
                        <p class="small text-muted mb-0"><?= getContactLine($pdo, ' | ') ?></p>
                    </div>
                </div>

                <div class="text-center mt-3 no-print">
                    <button onclick="window.print()" class="btn btn-cdf-green"><i class="fas fa-print me-2"></i>Print / Save as PDF</button>
                    <a href="check_status.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-redo me-1"></i>Check Another</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
