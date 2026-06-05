<?php
// Simple one-off SMTP test page.
// Usage: open in browser on the host: /public/tools/smtp_test.php?to=you@domain.tld

require_once __DIR__ . '/../../private/utils/email_helper.php';

$to_param = trim($_GET['to'] ?? '');
$to = $to_param !== '' ? $to_param : (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '');
$username = 'SMTP Test';

// Validate recipient
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid or missing recipient email. Use ?to=you@domain.tld or ensure SMTP_FROM_EMAIL is set in config.\n";
    echo "Configured SMTP host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'undefined') . "\n";
    echo "Configured SMTP from: " . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'undefined') . "\n";
    echo "Ensure .env SMTP_FROM_EMAIL is a valid email and remove surrounding quotes in SMTP_PASSWORD if present.";
    exit;
}

$sent = false;
try {
    $sent = sendSurveyAccountLinkNotification($to, $username, 'SMTP_TEST_ACCOUNT');
} catch (Exception $e) {
    error_log('smtp_test exception: ' . $e->getMessage());
    $sent = false;
}

if ($sent) {
    echo "SMTP test: success. Server debug (if enabled) is written to private/logs/error.log.";
} else {
    echo "SMTP test: failed. Check private/logs/error.log for SMTP debug output (set SMTP_DEBUG=3 in .env).";
}
