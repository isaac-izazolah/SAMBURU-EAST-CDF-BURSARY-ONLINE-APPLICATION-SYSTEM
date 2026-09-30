<?php
require_once 'includes/functions.php';
$page_title = 'Apply for Bursary';
$wards = getWards($pdo);
$institutionTypes = getInstitutionTypes();
$levels = getLevels();

$submitted = false;
$application = null;
$appStatus = getSetting($pdo, 'application_status', 'Open');
$appLocked = ($appStatus !== 'Open');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    if ($appLocked) {
        $error = 'Applications are currently closed. Please try again later.';
    } else {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please refresh the page and try again.');
        }

        $pdo->beginTransaction();

        $required = ['full_name', 'national_id', 'gender', 'date_of_birth', 'ward_id',
                     'guardian_name', 'guardian_relationship', 'guardian_phone', 'guardian_national_id',
                     'institution_name', 'institution_type', 'amount_requested', 'academic_year'];

        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception('Please fill in all required fields. Missing: ' . str_replace('_', ' ', $field));
            }
        }

        $stmt = $pdo->prepare("SELECT id FROM applications WHERE national_id = ? AND academic_year = ?");
        $stmt->execute([$_POST['national_id'], $_POST['academic_year']]);
        if ($stmt->fetch()) {
            throw new Exception('An application with this National ID/Birth Certificate Number already exists for the academic year ' . sanitize($_POST['academic_year']) . '.');
        }

        $appNumber = generateApplicationNumber($pdo);

        $totalFees = floatval($_POST['total_fees'] ?? 0);
        $feesPaid = floatval($_POST['fees_paid'] ?? 0);
        $outstandingBalance = $totalFees - $feesPaid;

        $specialCategoryRaw = $_POST['special_category'] ?? 'None';
        $specialCategory = ($specialCategoryRaw === 'None' || $specialCategoryRaw === '' || $specialCategoryRaw === null) ? 'None' : 'Special';

        $columns = [
            'application_number', 'full_name', 'national_id', 'gender', 'date_of_birth', 'phone', 'email',
            'ward_id', 'location', 'sub_location', 'postal_address', 'physical_address',
            'guardian_name', 'guardian_relationship', 'guardian_phone', 'guardian_national_id', 'guardian_occupation',
            'institution_name', 'institution_type', 'special_category', 'admission_number',
            'course_class', 'year_of_study', 'semester_term', 'level',
            'total_fees', 'fees_paid', 'outstanding_balance',
            'amount_requested',
            'payment_option', 'account_name', 'account_number', 'bank_name', 'bank_branch',
            'academic_year', 'status', 'ip_address', 'user_agent'
        ];

        $values = [
            $appNumber,
            $_POST['full_name'],
            $_POST['national_id'],
            $_POST['gender'],
            $_POST['date_of_birth'],
            $_POST['phone'] ?? null,
            $_POST['email'] ?? null,
            intval($_POST['ward_id']),
            $_POST['location'] ?? null,
            $_POST['sub_location'] ?? null,
            $_POST['postal_address'] ?? null,
            $_POST['physical_address'] ?? null,
            $_POST['guardian_name'],
            $_POST['guardian_relationship'],
            $_POST['guardian_phone'],
            $_POST['guardian_national_id'],
            $_POST['guardian_occupation'] ?? null,
            $_POST['institution_name'],
            $_POST['institution_type'],
            $specialCategory,
            $_POST['admission_number'] ?? null,
            ($_POST['course_class'] ?? '') ?: null,
            (int)($_POST['year_of_study'] ?? 0) ?: null,
            ($_POST['semester_term'] ?? '') ?: null,
            ($_POST['level'] ?? '') ?: null,
            $totalFees,
            $feesPaid,
            $outstandingBalance,
            floatval($_POST['amount_requested']),
            $_POST['payment_option'] ?? 'School Bank Account',
            $_POST['account_name'] ?? null,
            $_POST['account_number'] ?? null,
            $_POST['bank_name'] ?? null,
            $_POST['bank_branch'] ?? null,
            $_POST['academic_year'],
            'Submitted',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO applications (" . implode(', ', $columns) . ") VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $applicationId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO application_tracking (application_id, old_status, new_status, remarks) VALUES (?, NULL, 'Submitted', 'Application submitted successfully')");
        $stmt->execute([$applicationId]);

        $documentTypes = [
            'national_id_doc' => 'ids',
            'admission_letter' => 'admission_letters',
            'student_id_doc' => 'ids',
            'fee_structure' => 'fee_structures',
            'parent_id_doc' => 'ids',
            'recommendation_letter' => 'recommendations',
            'passport_photo' => 'passports',
            'evidence_doc' => 'evidence'
        ];

        foreach ($documentTypes as $field => $subfolder) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $docErrors = validateFileUpload($_FILES[$field]);
                if (!empty($docErrors)) {
                    throw new Exception('Error uploading ' . str_replace('_', ' ', $field) . ': ' . implode(', ', $docErrors));
                }
                $result = uploadFile($_FILES[$field], $subfolder);
                if ($result) {
                    $stmt = $pdo->prepare("INSERT INTO uploaded_documents (application_id, document_type, file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$applicationId, $field, $result['file_name'], $result['file_path'], $result['file_size'], $result['file_type']]);
                } else {
                    throw new Exception("Failed to upload " . str_replace("_", " ", $field) . ". Please try again.");
                }
            }
        }

        $pdo->commit();
        $submitted = true;
        $application = getApplicationByNumber($pdo, $appNumber);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
    }
}

include 'includes/header.php';
?>

<?php if ($submitted && $application): ?>
<!-- Success Page -->
<div class="page-header">
    <div class="container text-center">
        <h1><i class="fas fa-check-circle me-2"></i>Application Submitted Successfully!</h1>
    </div>
</div>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Important: Save Your Application Number -->
                <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:3px solid #16a34a;border-radius:16px;padding:28px 32px;margin-bottom:28px;text-align:center;">
                    <div style="font-size:0.85rem;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;"><i class="fas fa-save me-1"></i> Save This Number</div>
                    <div style="font-size:0.95rem;color:#333;margin-bottom:6px;">Your Application Number is:</div>
                    <div style="font-size:2.2rem;font-weight:800;color:#0f2440;letter-spacing:2px;font-family:monospace,monospace;"><?= $application['application_number'] ?></div>
                    <div style="font-size:0.85rem;color:#555;margin-top:10px;">Use this number together with your <strong>National ID / Birth Certificate Number</strong> to check your application status at any time.</div>
                    <div style="margin-top:14px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                        <a href="check_status.php" style="background:#16a34a;color:white;padding:8px 20px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;"><i class="fas fa-search me-1"></i>Check Status Now</a>
                        <button onclick="copyAppNumber()" style="background:#0f2440;color:white;padding:8px 20px;border-radius:8px;border:none;font-weight:600;font-size:0.9rem;cursor:pointer;"><i class="fas fa-copy me-1"></i>Copy Number</button>
                    </div>
                    <div id="copyFeedback" style="font-size:0.8rem;color:#16a34a;margin-top:6px;display:none;">Copied to clipboard!</div>
                </div>

                <div class="acknowledgement-slip" id="ackSlip">
                    <div class="slip-header text-center">
                        <div class="slip-letterhead">
                            <img src="<?= SITE_URL ?>img/cdf%20logo.png" alt="NG-CDF Logo" style="height:65px;">
                            <div class="slip-title">
                                <h4 class="fw-bold" style="color:var(--primary);margin-bottom:0;"><?= SITE_NAME ?></h4>
                                <small style="color:#555;">National Government Constituency Development Fund</small>
                            </div>
                            <img src="<?= SITE_URL ?>img/court%20of%20arm.png" alt="Court of Arms" style="height:65px;">
                        </div>
                        <h5 class="mt-3 mb-0" style="color:var(--accent);font-weight:700;">Bursary Application Acknowledgement Slip</h5>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Application Number:</strong></p>
                            <h4 class="text-cdf-green fw-bold"><?= $application['application_number'] ?></h4>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1"><strong>Date Submitted:</strong></p>
                            <p><?= date(DATE_FORMAT, strtotime($application['created_at'])) ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Full Name:</strong> <?= sanitize($application['full_name']) ?></p>
                            <p><strong>National ID/Birth Cert:</strong> <?= sanitize($application['national_id']) ?></p>
                            <p><strong>Phone:</strong> <?= sanitize($application['phone']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Institution:</strong> <?= sanitize($application['institution_name']) ?></p>
                            <p><strong>Institution Type:</strong> <?= $application['institution_type'] ?></p>
                            <?php if (!empty($application['level'])): ?>
                            <p><strong>Level:</strong> <?= $application['level'] ?></p>
                            <?php endif; ?>
                            <?php if (!empty($application['special_category']) && $application['special_category'] !== 'None'):
                                $spLabel = $application['special_category'] === 'Special' ? 'Special' : $application['special_category'];
                            ?>
                            <p><strong>Special Category:</strong> <span class="badge bg-warning text-dark"><?= sanitize($spLabel) ?></span></p>
                            <?php endif; ?>
                            <p><strong>Academic Year:</strong> <?= sanitize($application['academic_year']) ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Amount Requested:</strong> <?= formatAmount($application['amount_requested']) ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong>Status:</strong> <?= getStatusBadge($application['status']) ?></p>
                        </div>
                    </div>
                    <div class="slip-footer text-center">
                        <p class="small mb-1"><i class="fas fa-info-circle"></i> Please save your Application Number for future reference. Use it to check your application status.</p>
                        <div id="qrcode" class="mt-2"></div>
                    </div>
                    <div class="print-only-footer text-center mt-3 pt-3" style="border-top:2px solid var(--accent);">
                        <p class="small text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Printed on: <strong><?= date('d M Y H:i:s') ?></strong></p>
                        <p class="small text-muted mb-0"><?= getContactLine($pdo, ' | ') ?></p>
                    </div>
                </div>
                <div class="text-center mt-4 no-print d-flex gap-2 justify-content-center flex-wrap">
                    <button onclick="window.print()" class="btn btn-cdf-green"><i class="fas fa-print me-2"></i>Print / Download PDF</button>
                    <a href="check_status.php" class="btn btn-outline-cdf-green"><i class="fas fa-search me-2"></i>Check Status</a>
                    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-home me-2"></i>Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="<?= SITE_URL ?>assets/vendor/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById('qrcode'), {
        text: '<?= $application['application_number'] ?>',
        width: 100,
        height: 100,
        colorDark: '#1a3a5c',
        colorLight: '#ffffff'
    });

    function copyAppNumber() {
        navigator.clipboard.writeText('<?= $application['application_number'] ?>').then(function() {
            var fb = document.getElementById('copyFeedback');
            fb.style.display = 'block';
            setTimeout(function() { fb.style.display = 'none'; }, 2000);
        }).catch(function() {
            var tmp = document.createElement('textarea');
            tmp.value = '<?= $application['application_number'] ?>';
            document.body.appendChild(tmp);
            tmp.select();
            document.execCommand('copy');
            document.body.removeChild(tmp);
            var fb = document.getElementById('copyFeedback');
            fb.style.display = 'block';
            setTimeout(function() { fb.style.display = 'none'; }, 2000);
        });
    }
</script>

<?php else: ?>
<!-- Application Form -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-pen me-2"></i>Apply for Bursary</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" style="color:var(--accent-light);">Home</a></li>
                <li class="breadcrumb-item active">Apply Now</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-4">
    <div class="container">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= sanitize($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($appLocked): ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-lock fa-4x" style="color:var(--primary);opacity:0.3;"></i>
            </div>
            <h4 class="fw-bold" style="color:var(--primary);">Applications Are Currently Closed</h4>
            <p class="text-muted mb-3" style="max-width:500px;margin:auto;">The application period is currently closed. Please check the announcements page for information on when the next application period opens.</p>
            <div class="d-flex gap-2 justify-content-center">
                <a href="announcements.php" class="btn btn-cdf-green"><i class="fas fa-bullhorn me-2"></i>View Announcements</a>
                <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-home me-2"></i>Back to Home</a>
            </div>
        </div>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data" id="applicationForm" data-loading="true">
            <?= getCSRFTokenField() ?>
            <input type="hidden" name="submit_application" value="1">

            <!-- Step 1: Applicant Information -->
            <div class="form-section mb-4">
                <h5 class="fw-bold mb-4" style="color:var(--primary);"><i class="fas fa-user me-2"></i>Applicant Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required value="<?= sanitize($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">National ID / Birth Certificate Number</label>
                        <input type="text" name="national_id" class="form-control" required value="<?= sanitize($_POST['national_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" required value="<?= sanitize($_POST['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone Number (optional)</label>
                        <input type="tel" name="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address (optional)</label>
                        <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Ward</label>
                        <select name="ward_id" id="ward_id" class="form-select" required>
                            <option value="">Select Ward</option>
                            <?php foreach ($wards as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= ($_POST['ward_id'] ?? '') == $w['id'] ? 'selected' : '' ?>><?= sanitize($w['ward_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= sanitize($_POST['location'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sub-location</label>
                        <input type="text" name="sub_location" class="form-control" value="<?= sanitize($_POST['sub_location'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Postal Address</label>
                        <input type="text" name="postal_address" class="form-control" value="<?= sanitize($_POST['postal_address'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Physical Address</label>
                        <input type="text" name="physical_address" class="form-control" value="<?= sanitize($_POST['physical_address'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Special Category</label>
                        <select name="special_category" class="form-select" id="specialCategory" required>
                            <option value="None" <?= ($_POST['special_category'] ?? 'None') === 'None' ? 'selected' : '' ?>>Normal Application</option>
                            <option value="Special" <?= ($_POST['special_category'] ?? '') === 'Special' ? 'selected' : '' ?>>Special Application</option>
                        </select>
                        <small class="text-muted">Select "Special" for Orphans or Persons with Disability</small>
                    </div>
                </div>
            </div>

            <!-- Step 2: Parent/Guardian Information -->
            <div class="form-section mb-4">
                <h5 class="fw-bold mb-4" style="color:var(--primary)"><i class="fas fa-users me-2"></i>Parent/Guardian Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Parent/Guardian Name</label>
                        <input type="text" name="guardian_name" class="form-control" required value="<?= sanitize($_POST['guardian_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Relationship</label>
                        <select name="guardian_relationship" class="form-select" required>
                            <option value="">Select Relationship</option>
                            <option value="Father" <?= ($_POST['guardian_relationship'] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                            <option value="Mother" <?= ($_POST['guardian_relationship'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                            <option value="Guardian" <?= ($_POST['guardian_relationship'] ?? '') === 'Guardian' ? 'selected' : '' ?>>Guardian</option>
                            <option value="Sibling" <?= ($_POST['guardian_relationship'] ?? '') === 'Sibling' ? 'selected' : '' ?>>Sibling</option>
                            <option value="Other" <?= ($_POST['guardian_relationship'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Phone Number</label>
                        <input type="tel" name="guardian_phone" class="form-control" required value="<?= sanitize($_POST['guardian_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">National ID Number</label>
                        <input type="text" name="guardian_national_id" class="form-control" required value="<?= sanitize($_POST['guardian_national_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="guardian_occupation" class="form-control" value="<?= sanitize($_POST['guardian_occupation'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Step 3: Institution Details -->
            <div class="form-section mb-4">
                <h5 class="fw-bold mb-4" style="color:var(--primary)"><i class="fas fa-school me-2"></i>Institution Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Institution Name</label>
                        <input type="text" name="institution_name" class="form-control" required value="<?= sanitize($_POST['institution_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Institution Type</label>
                        <select name="institution_type" id="institutionType" class="form-select" required>
                            <option value="">Select Type</option>
                            <?php foreach ($institutionTypes as $type): ?>
                            <option value="<?= $type ?>" <?= ($_POST['institution_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Level</label>
                        <select name="level" id="levelSelect" class="form-select">
                            <option value="">Select Level (if applicable)</option>
                            <?php foreach ($levels as $lvl): ?>
                            <option value="<?= $lvl ?>" <?= ($_POST['level'] ?? '') === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Admission Number</label>
                        <input type="text" name="admission_number" class="form-control" value="<?= sanitize($_POST['admission_number'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Course/Class</label>
                        <input type="text" name="course_class" class="form-control" value="<?= sanitize($_POST['course_class'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Year of Study/Form</label>
                        <input type="number" name="year_of_study" class="form-control" min="1" max="8" value="<?= sanitize($_POST['year_of_study'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Semester/Term</label>
                        <input type="text" name="semester_term" class="form-control" value="<?= sanitize($_POST['semester_term'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" required placeholder="e.g. 2025/2026" pattern="\d{4}/\d{4}" title="Format: YYYY/YYYY (e.g. 2025/2026)" value="<?= sanitize($_POST['academic_year'] ?? getSetting($pdo, 'financial_year', date('Y') . '/' . (date('Y')+1))) ?>">
                        <small class="text-muted">Format: YYYY/YYYY</small>
                    </div>
                </div>
            </div>

            <!-- Step 4: Amount Requested -->
            <div class="form-section mb-4">
                <h5 class="fw-bold mb-4" style="color:var(--primary)"><i class="fas fa-money-bill me-2"></i>Amount Requested</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Amount Requested (KSh)</label>
                        <input type="number" name="amount_requested" class="form-control" required min="0" step="0.01" value="<?= sanitize($_POST['amount_requested'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Step 5: Document Uploads -->
            <div class="form-section mb-4">
                <h5 class="fw-bold mb-4" style="color:var(--primary)"><i class="fas fa-upload me-2"></i>Document Uploads</h5>
                <p class="small text-muted mb-4">Allowed formats: PDF, JPG, PNG. Maximum size: 5 MB per file.</p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label required">National ID / Birth Certificate</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-id-card"></i>
                            <div class="upload-placeholder"><small>Click to upload</small></div>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="national_id_doc" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admission Letter (optional)</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-file-alt"></i>
                            <div class="upload-placeholder"><small>Click to upload (optional)</small></div>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="admission_letter" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Student ID / School ID</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-user-graduate"></i>
                            <div class="upload-placeholder"><small>Click to upload (optional)</small></div>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="student_id_doc" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Fee Structure</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <div class="upload-placeholder"><small>Click to upload</small></div>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="fee_structure" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Parent/Guardian ID</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-id-card"></i>
                            <div class="upload-placeholder"><small>Click to upload</small></div>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="parent_id_doc" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Recommendation Letter (optional)</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-envelope-open-text"></i>
                            <div class="upload-placeholder"><small>Click to upload (optional)</small></div>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="recommendation_letter" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Passport Photo</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-camera"></i>
                            <div class="upload-placeholder"><small>Click to upload (optional)</small></div>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="passport_photo" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="col-md-6" id="evidenceUploadWrap" style="display:none;">
                        <label class="form-label">Supporting Evidence (Orphan/Disability)</label>
                        <div class="upload-box" onclick="this.querySelector('input').click()">
                            <i class="fas fa-file-medical"></i>
                            <div class="upload-placeholder"><small>Click to upload evidence document</small></div>
                            <small class="text-muted d-block">Death certificate, medical report, NCPWD card, etc.</small>
                            <small class="file-name text-cdf-green"></small>
                            <input type="file" name="evidence_doc" class="upload-input d-none" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <small class="text-muted">Required for special category applicants</small>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-cdf-green btn-lg"><i class="fas fa-paper-plane me-2"></i>Submit Application</button>
            </div>
            <p class="text-center text-muted small mt-2">By submitting this application, you confirm that all information provided is true and correct.</p>
        </form>
        <script>
        (function(){
            const catSel = document.getElementById('specialCategory');
            const evWrap = document.getElementById('evidenceUploadWrap');
            function toggleEvidence(){
                evWrap.style.display = (catSel.value === 'None') ? 'none' : 'block';
            }
            catSel.addEventListener('change', toggleEvidence);
            toggleEvidence();
        })();
        </script>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
