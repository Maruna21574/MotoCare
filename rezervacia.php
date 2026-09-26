<?php
require_once __DIR__ . '/inc/config.php';
$selectedService = cleanText($_GET['service'] ?? '', 40);
if (!isset($services[$selectedService])) $selectedService = '';
$pageTitle = 'Rezervácia termínu — MotoCare Pro';
$activePage = 'rezervacia';
$bodyClass = 'page-plain';
include __DIR__ . '/inc/header.php';
?>
<section class="book section-light">
    <div class="container book-wrap">
        <div class="book-head">
            <p class="overline"><?= t('booking.overline') ?></p>
            <h1><?= t('booking.title') ?></h1>
            <p><?= t('booking.text') ?></p>
        </div>

        <?php include __DIR__ . '/inc/booking-form.php'; ?>

        <p class="book-help"><?= t('booking.help') ?></p>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
