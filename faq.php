<?php
require_once 'includes/functions.php';
$page_title = 'Frequently Asked Questions';
include 'includes/header.php';
$faqs = getFAQs($pdo);
?>
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">FAQs</li>
            </ol>
        </nav>
    </div>
</div>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if (count($faqs) > 0): ?>
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <div class="accordion-item border-0 mb-2 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $faq['id'] ?>">
                                <?= sanitize($faq['question']) ?>
                            </button>
                        </h2>
                        <div id="faq<?= $faq['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?= nl2br(sanitize($faq['answer'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No FAQs available at the moment. Please check back later.</p>
                </div>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <p class="text-muted">Still have questions? <a href="contact.php" class="text-cdf-green">Contact us</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
