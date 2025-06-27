<?php
/**
 * Popup component for displaying the Kinh Tuyen Truc announcement
 * This file should be included at the end of the page before the closing </body> tag
 */

// Generate unique cache-busting parameter based on current version/date
$cacheBuster = "v=" . date('Ymd');
?>

<!-- Popup CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/components/popup.css?<?= $cacheBuster ?>">

<!-- Popup JavaScript -->
<script>
    // Make BASE_URL available to JavaScript
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/public/assets/js/components/popup.js?<?= $cacheBuster ?>"></script>
