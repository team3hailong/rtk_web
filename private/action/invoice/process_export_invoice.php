<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Truy cập không hợp lệ.');
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/auth/login.php');
    exit;
}

require_once dirname(__DIR__, 3) . '/classes/Database.php';
require_once dirname(__DIR__, 2) . '/classes/invoice/InvoiceService.php';

$tx_id = isset($_POST['tx_id']) ? intval($_POST['tx_id']) : 0;
if ($tx_id <= 0) {
    http_response_code(400);
    exit('Thiếu hoặc sai tham số.');
}

$invoiceService = new InvoiceService();

// Verify transaction exists, belongs to user, and has 'completed' status
if (!$invoiceService->checkOwnership($tx_id, $_SESSION['user_id'])) {
    http_response_code(404);
    exit('Không tìm thấy giao dịch hoặc giao dịch không thuộc về bạn.');
}

// Chỉ cho phép xuất hóa đơn với giao dịch đã hoàn thành
if (!$invoiceService->isTransactionCompleted($tx_id)) {
    http_response_code(400);
    exit('Chỉ có thể xuất hóa đơn với các giao dịch đã hoàn thành.');
}

// Kiểm tra đã có invoice chưa
$stmt = $invoiceService->conn->prepare('SELECT id FROM invoice WHERE transaction_history_id = ?');
$stmt->execute([$tx_id]);
$exists = $stmt->fetchColumn();

if (!$exists) {
    $stmt2 = $invoiceService->conn->prepare('INSERT INTO invoice (transaction_history_id, status, created_at) VALUES (?, "pending", NOW())');
    $stmt2->execute([$tx_id]);
    $invoice_id = $invoiceService->conn->lastInsertId();
    // --- Ghi log vào activity_logs ---
    try {
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $new_values = json_encode([
            'transaction_history_id' => $tx_id
        ], JSON_UNESCAPED_UNICODE);
        $notify_content = 'Yêu cầu xuất hóa đơn cho giao dịch #' . $tx_id;
        $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at)
                    VALUES (:user_id, 'request_invoice', 'invoice', :entity_id, :ip_address, :user_agent, :new_values, :notify_content, NOW())";        $stmt_log = $invoiceService->conn->prepare($sql_log);
        $stmt_log->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_log->bindParam(':entity_id', $invoice_id, PDO::PARAM_INT);
        $stmt_log->bindParam(':ip_address', $ip_address);
        $stmt_log->bindParam(':user_agent', $user_agent);
        $stmt_log->bindParam(':new_values', $new_values);
        $stmt_log->bindParam(':notify_content', $notify_content);
        $stmt_log->execute();
    } catch (Exception $e) {
        error_log('Lỗi ghi activity_logs khi xuất hóa đơn: ' . $e->getMessage());
    }
}

header('Location: /pages/transaction.php?invoice=success');
exit;