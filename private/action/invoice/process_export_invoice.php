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

$tx_id = isset($_POST['tx_id']) ? intval($_POST['tx_id']) : 0;
if ($tx_id <= 0) {
    http_response_code(400);
    exit('Thiếu hoặc sai tham số.');
}

$db = new Database();
$conn = $db->getConnection();

// Kiểm tra đã có invoice chưa
$stmt = $conn->prepare('SELECT id FROM invoice WHERE transaction_history_id = ?');
$stmt->execute([$tx_id]);
$exists = $stmt->fetchColumn();

if (!$exists) {
    $stmt2 = $conn->prepare('INSERT INTO invoice (transaction_history_id, status, created_at) VALUES (?, "pending", NOW())');
    $stmt2->execute([$tx_id]);
}

header('Location: /pages/transaction.php?invoice=success');
exit;