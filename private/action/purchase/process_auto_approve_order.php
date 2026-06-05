<?php
/**
 * Action: Process Auto-Approve Order
 *
 * When a voucher with auto_approve = 1 is applied, this action triggers the admin cron
 * at https://quantri.taikhoandodac.vn/public/handlers/purchase/cron_auto_approve.php
 * to attempt automatic approval of the related transaction/order.
 */

// --- Session and bootstrap project dependencies ---
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
// File này nằm trong /private/action/purchase/, cần đi lên 3 cấp để đến project root
$project_root_path = realpath(dirname(__FILE__) . '/../../../');
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/utils/functions.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/purchase/PaymentService.php';

// --- Allow both POST & GET trigger (was POST only) ---
// No longer block non-POST so cron can be triggered flexibly.

// --- Auth check ---
if (!isset($_SESSION['user_id'])) {
	header('Location: ' . BASE_URL . '/public/pages/auth/login.php?error=not_logged_in');
	exit;
}

$user_id = (int)$_SESSION['user_id'];
// Accept registration_id from POST or GET (fallback) in case form or link triggers it.
$registration_id = 0;
if (isset($_POST['registration_id'])) {
	$registration_id = (int)$_POST['registration_id'];
} elseif (isset($_GET['registration_id'])) {
	$registration_id = (int)$_GET['registration_id'];
}

if ($registration_id <= 0) {
	header('Location: ' . BASE_URL . '/public/pages/purchase/packages.php?error=missing_data');
	exit;
}

// --- Ensure auto-approve voucher is applied ---
// Previously: verified voucher has auto_approve. Per request, we skip this check and always trigger cron.
$is_renewal = $_SESSION['is_renewal'] ?? false;
$sessionKey = $is_renewal ? 'renewal' : 'order';

// --- Optionally ensure a transaction exists/updated amount (for paid orders) ---
try {
	if (isset($_SESSION['payment_data'])) {
		$paymentService = new PaymentService();
		// Re-assert the amount to transaction history for visibility (safe no-op for free orders)
		$verified_total_price = $_SESSION['payment_data']['verified_total_price'] ?? null;
		if ($verified_total_price !== null) {
			$paymentService->updateTransactionHistoryAmount($registration_id, $user_id, (float)$verified_total_price);
		}
	}
} catch (Throwable $e) {
	error_log('[AUTO_APPROVE] Failed to ensure transaction amount: ' . $e->getMessage());
}

// --- Call admin cron to auto-approve (plain GET) ---
// Append registration_id and user_id as query params (safe if cron ignores them; may help filtering).
$baseCronUrl = 'https://quantri.taikhoandodac.vn/public/handlers/purchase/cron_auto_approve.php';
$cronQuery = http_build_query([
	'registration_id' => $registration_id,
	'user_id' => $user_id,
	'source' => 'rtk_web'
]);
$cronUrl = $baseCronUrl . '?' . $cronQuery;

// Build headers to mimic a real browser (Cloudflare sometimes blocks minimalist bots)
$browserHeaders = [
	'Accept: application/json, text/plain, */*',
	'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
	'Cache-Control: no-cache',
	'Pragma: no-cache',
	'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'
];

// Pass along session cookie if available (may help bypass WAF rules)
if (isset($_COOKIE['PHPSESSID'])) {
	$browserHeaders[] = 'Cookie: PHPSESSID=' . $_COOKIE['PHPSESSID'];
}

// Optional: if Cloudflare clearance cookie is stored somewhere (e.g., CF_CLEARANCE), attach it
if (isset($_COOKIE['cf_clearance'])) {
	$browserHeaders[] = 'Cookie: cf_clearance=' . $_COOKIE['cf_clearance'];
}

$responseBody = null;
$httpCode = 0;
$curlError = '';
$lastInfo = [];
$curl = curl_init($cronUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false); // avoid redirect delays (Cloudflare)
curl_setopt($curl, CURLOPT_HTTPGET, true);
// Short timeouts: goal is to trigger remote job, not wait for full processing
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($curl, CURLOPT_TIMEOUT, 4);
// Force IPv4 + HTTP/1.1 to bypass potential IPv6 / HTTP2 stalls
if (defined('CURL_IPRESOLVE_V4')) curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
if (defined('CURL_HTTP_VERSION_1_1')) curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // adjust for production if needed
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_HTTPHEADER, $browserHeaders);
curl_setopt($curl, CURLOPT_ENCODING, '');
$responseBody = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
$lastInfo = curl_getinfo($curl);
curl_close($curl);

// Only consider success when JSON response has success=true; no fallback
$decoded = null;
if (is_string($responseBody)) {
	$decoded = json_decode($responseBody, true);
}
if (is_array($decoded) && isset($decoded['success']) && $decoded['success'] === true) {
	header('Location: ' . BASE_URL . '/public/pages/purchase/success.php');
	exit;
}
// Log response for debugging then redirect with error
error_log('[AUTO_APPROVE] Cron response invalid or unsuccessful: ' . var_export($responseBody, true));
header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php?error=auto_approve_failed');
exit;

