<?php
require_once 'includes/functions.php';
$page_title = 'Announcements';
include 'includes/header.php';
$announcements = getAnnouncements($pdo, 50);
$appStatus = getSetting($pdo, 'application_status', 'Closed');
$appOpenDate = getSetting($pdo, 'application_open_date', '');
$appCloseDate = getSetting($pdo, 'application_close_date', '');
$financialYear = getSetting($pdo, 'financial_year', '');
?>
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-bullhorn me-2"></i>News & Announcements</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Announcements</li>
            </ol>
        </nav>
    </div>
</div>
<section class="py-5">
    <div class="container-fluid px-4">
        <?php if ($appOpenDate && strtotime($appOpenDate) || $appCloseDate && strtotime($appCloseDate)): ?>
        <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid var(--accent) !important;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fas fa-info-circle fa-lg" style="color:var(--accent);"></i>
                    <strong style="color:var(--primary);">Application Period</strong>
                    <span class="badge <?= $appStatus === 'Open' ? 'bg-success' : 'bg-secondary' ?>"><?= $appStatus ?></span>
                </div>
                <div class="d-flex flex-wrap gap-4 small">
                    <?php if ($financialYear): ?>
                    <span><i class="fas fa-calendar-alt me-1"></i> Financial Year: <strong><?= sanitize($financialYear) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($appOpenDate && strtotime($appOpenDate)): ?>
                    <span><i class="fas fa-play-circle me-1 text-success"></i> Opens: <strong><?= date(DATE_FORMAT, strtotime($appOpenDate)) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($appCloseDate && strtotime($appCloseDate)): ?>
                    <span><i class="fas fa-stop-circle me-1 text-danger"></i> Closes: <strong><?= date(DATE_FORMAT, strtotime($appCloseDate)) ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (count($announcements) > 0): ?>
        <div class="announcements-horizontal">
            <?php foreach ($announcements as $ann): ?>
            <div class="announcement-card">
                <div class="announcement-card-inner">
                    <div class="announcement-badge">
                        <span class="badge bg-cdf-green"><?= ucfirst(str_replace('_', ' ', $ann['announcement_type'])) ?></span>
                        <small class="text-muted"><i class="far fa-calendar me-1"></i><?= ($ann['published_date'] && strtotime($ann['published_date'])) ? date(DATE_FORMAT, strtotime($ann['published_date'])) : 'N/A' ?></small>
                    </div>
                    <h5 class="announcement-title"><?= sanitize($ann['title']) ?></h5>
                    <div class="announcement-text"><?= $ann['content'] ?></div>
                    <?php if ($ann['closing_date']): ?>
                    <div class="announcement-closing">
                        <i class="fas fa-clock me-1"></i> Closing date: <?= date(DATE_FORMAT, strtotime($ann['closing_date'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
            <p class="text-muted">No announcements at the moment. Please check back later.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
