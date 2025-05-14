<?php
// Handler for exporting retail invoices (Hóa đơn bán lẻ) instantly
session_start();
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Transaction.php';

// Use mPDF for PDF generation
require_once PROJECT_ROOT_PATH . '/vendor/autoload.php';
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

// Xóa mọi output trước khi gửi header (fix lỗi Cannot modify header information)
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không hợp lệ']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$tx_ids = $data['transaction_ids'] ?? [];
if (!is_array($tx_ids) || count($tx_ids) === 0 || count($tx_ids) > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Chọn tối đa 5 giao dịch']);
    exit;
}

$db = new Database();
$transactionHandler = new Transaction($db);
$retail_invoices = [];
foreach ($tx_ids as $tx_id) {
    $tx = $transactionHandler->getTransactionByIdAndUser($tx_id, $user_id);
    if (!$tx) continue;
    // Prepare invoice data (simple retail invoice)
    $retail_invoices[] = [
        'id' => $tx['id'],
        'created_at' => $tx['created_at'],
        'amount' => $tx['amount'],
        'type' => $tx['transaction_type'],
        'method' => $tx['payment_method'],
    ];
}
if (empty($retail_invoices)) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy giao dịch hợp lệ']);
    exit;
}

$tmp_dir = sys_get_temp_dir();
$pdf_files = [];
foreach ($retail_invoices as $invoice) {
    $mpdf = new \Mpdf\Mpdf();
    $html = '<h2>HÓA ĐƠN BÁN LẺ</h2>';
    $html .= '<p><strong>ID giao dịch:</strong> ' . htmlspecialchars((string)($invoice['id'] ?? '')) . '</p>';
    $html .= '<p><strong>Ngày:</strong> ' . htmlspecialchars((string)($invoice['created_at'] ?? '')) . '</p>';
    $html .= '<p><strong>Loại giao dịch:</strong> ' . htmlspecialchars((string)($invoice['type'] ?? '')) . '</p>';
    $html .= '<p><strong>Phương thức:</strong> ' . htmlspecialchars((string)($invoice['method'] ?? '')) . '</p>';
    $html .= '<p><strong>Số tiền:</strong> ' . number_format((float)($invoice['amount'] ?? 0), 0, ',', '.') . ' đ</p>';
    $file_path = $tmp_dir . '/retail_invoice_' . $invoice['id'] . '_' . uniqid() . '.pdf';
    $mpdf->WriteHTML($html);
    $mpdf->Output($file_path, \Mpdf\Output\Destination::FILE);
    $pdf_files[] = $file_path;
}

if (count($pdf_files) === 1) {
    $file = $pdf_files[0];
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="hoa_don_ban_le_' . basename($file) . '"');
    readfile($file);
    unlink($file);
    exit;
} else {
    $zip_path = $tmp_dir . '/retail_invoices_' . uniqid() . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
        foreach ($pdf_files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();
        foreach ($pdf_files as $file) unlink($file);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="hoa_don_ban_le.zip"');
        readfile($zip_path);
        unlink($zip_path);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Không thể tạo file zip']);
        exit;
    }
}
