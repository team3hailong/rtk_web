<?php
session_start();
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/RtkAccount.php';

// Get POST data
$registrationId = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
$otpInput = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$registrationId || !$otpInput) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin xác nhận.']);
    exit;
}

// Retrieve pending OTP confirmation
if (empty($_SESSION['transfer_confirmation'][$registrationId])) {
    echo json_encode(['success' => false, 'message' => 'Không có yêu cầu chuyển quyền hoặc đã hết hạn.']);
    exit;
}

$confirmData = $_SESSION['transfer_confirmation'][$registrationId];

if ($confirmData['otp'] !== $otpInput) {
    echo json_encode(['success' => false, 'message' => 'OTP không đúng.']);
    exit;
}

$newUserId = intval($confirmData['new_user']);

// Perform ownership update
$db = new Database();
$rtkAccountManager = new RtkAccount($db);
$success = $rtkAccountManager->updateAccountOwnership($registrationId, $newUserId);

// Clean up session
unset($_SESSION['transfer_confirmation'][$registrationId]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Chuyển quyền sở hữu thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể chuyển quyền sở hữu.']);
}
exit;
