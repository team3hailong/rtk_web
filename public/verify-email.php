<?php
// Redirect đến vị trí mới của verify-email.php
$token = $_GET['token'] ?? '';
header("Location: /private/action/auth/verify-email.php?token=" . urlencode($token));
exit();