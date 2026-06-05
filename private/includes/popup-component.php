<?php
/**
 * Popup component for displaying the Kinh Tuyen Truc announcement
 * This file should be included at the end of the page before the closing </body> tag
 */

// Generate unique cache-busting parameter based on current version/date
$cacheBuster = "v=" . date('Ymd');
?>

<?php
// Respect global config to disable this specific popup (safe and reversible)
// Respect global config to disable only the Kinh Tuyen Truc popup JS (keep CSS for other popups)
$disablePopupJs = defined('DISABLE_KINH_TUYEN_TRUC_POPUP') && DISABLE_KINH_TUYEN_TRUC_POPUP;
?>

<!-- Popup CSS (kept for other popup components on the site) -->
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/components/popup.css?<?= $cacheBuster ?>">

<?php if (!$disablePopupJs): ?>
<!-- Popup JavaScript -->
<script>
    // Make BASE_URL available to JavaScript
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/public/assets/js/components/popup.js?<?= $cacheBuster ?>"></script>
<?php else: ?>
<!-- Kinh Tuyến Trục popup JS disabled by config -->
<?php endif; ?>
