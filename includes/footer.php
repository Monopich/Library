<?php
// Ensure language variables are loaded
if (!isset($lang)) {
    include('includes/config.php');
}
?>
<section class="footer-section bg-white fixed-bottom py-3" style="border-top: 4px solid #007bff; z-index: 1030;">
    <div class="container text-end">
        <div class="row">
            <div class="col-md-12 fw-medium text-primary">
                <?= $lang['footer_text']; ?>
            </div>
        </div>
    </div>
</section>
