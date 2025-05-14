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
$pdo = $db->getConnection();
$transactionHandler = new Transaction($db);
$retail_invoices = [];

// Lấy thông tin người dùng/công ty
$user_info = [];
$user_stmt = $pdo->prepare("SELECT username, email, phone, is_company, company_name, tax_code, company_address FROM user WHERE id = :user_id");
$user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

foreach ($tx_ids as $tx_id) {
    $tx = $transactionHandler->getTransactionByIdAndUser($tx_id, $user_id);
    if (!$tx) continue;
      // Lấy thêm thông tin chi tiết từ bảng registration nếu có
    $registration_details = null;
    if (!empty($tx['registration_id'])) {
        $reg_stmt = $pdo->prepare("SELECT r.*, p.name as package_name, p.duration_text as duration, l.province 
                                   FROM registration r 
                                   LEFT JOIN package p ON r.package_id = p.id
                                   LEFT JOIN location l ON r.location_id = l.id
                                   WHERE r.id = :reg_id");
        $reg_stmt->bindParam(':reg_id', $tx['registration_id'], PDO::PARAM_INT);
        $reg_stmt->execute();
        $registration_details = $reg_stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Prepare invoice data (enhanced retail invoice)
    $retail_invoices[] = [
        'id' => $tx['id'],
        'created_at' => $tx['created_at'],
        'amount' => $tx['amount'],
        'type' => $tx['transaction_type'],
        'method' => $tx['payment_method'],
        'user_info' => $user_info,
        'registration_details' => $registration_details
    ];
}
if (empty($retail_invoices)) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy giao dịch hợp lệ']);
    exit;
}

$tmp_dir = sys_get_temp_dir();
$pdf_files = [];

// Định nghĩa CSS
$css = '
<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        color: #333;
        line-height: 1.5;
    }
    .invoice-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .invoice-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }
    .invoice-title {
        font-size: 24px;
        font-weight: bold;
        color: #1a73e8;
        margin-bottom: 5px;
    }
    .invoice-number {
        font-size: 14px;
        margin-bottom: 15px;
    }
    .company-info {
        text-align: left;
        margin-bottom: 20px;
    }
    .customer-info {
        text-align: left;
        margin-bottom: 20px;
    }
    .info-section {
        margin-bottom: 20px;
    }
    .info-section h3 {
        font-size: 16px;
        margin-bottom: 10px;
        color: #1a73e8;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    .info-row {
        display: flex;
        margin-bottom: 5px;
    }
    .info-label {
        width: 180px;
        font-weight: bold;
    }
    .transaction-details {
        margin-bottom: 30px;
    }
    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .transaction-table th, .transaction-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }
    .transaction-table th {
        background-color: #f2f2f2;
    }
    .total-row {
        font-weight: bold;
    }
    .footer {
        margin-top: 30px;
        text-align: center;
        font-size: 12px;
        color: #666;
        border-top: 1px solid #ddd;
        padding-top: 10px;
    }
    .signature-section {
        display: flex;
        justify-content: space-between;
        margin-top: 50px;
        margin-bottom: 20px;
    }
    .signature-box {
        width: 45%;
        text-align: center;
    }
    .signature-title {
        font-weight: bold;
        margin-bottom: 40px;
    }
    .date-section {
        margin-top: 20px;
        text-align: right;
        font-style: italic;
    }
</style>
';

foreach ($retail_invoices as $invoice) {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15
    ]);
    
    // Định dạng ngày tháng theo tiếng Việt
    $created_date = !empty($invoice['created_at']) ? date('d/m/Y H:i', strtotime($invoice['created_at'])) : '';
    $today_date = date('d/m/Y');
    $invoice_number = 'HDBL-' . $invoice['id'] . '-' . date('YmdHi');
      // Xác định loại giao dịch và tên sản phẩm
    $transaction_type = htmlspecialchars((string)($invoice['type'] ?? ''));
    $product_name = "Dịch vụ RTK";
    if (isset($invoice['registration_details']) && !empty($invoice['registration_details']['package_name'])) {
        $product_name = htmlspecialchars($invoice['registration_details']['package_name']);
        if (!empty($invoice['registration_details']['duration'])) {
            $product_name .= " (" . $invoice['registration_details']['duration'] . ")";
        }
    }
    
    // Build HTML cho hóa đơn
    $html = $css . '
    <div class="invoice-container">
        <div class="invoice-header">
            <h1 class="invoice-title">HÓA ĐƠN BÁN LẺ</h1>
            <div class="invoice-number">Số: ' . $invoice_number . '</div>
        </div>
        
        <div class="company-info info-section">
            <h3>THÔNG TIN CÔNG TY</h3>
            <div class="info-row">
                <div class="info-label">Tên công ty:</div>
                <div>CÔNG TY CỔ PHẦN CÔNG NGHỆ RTK</div>
            </div>
            <div class="info-row">
                <div class="info-label">Địa chỉ:</div>
                <div>Số 20, Đường Nguyễn Trãi, Phường Thanh Xuân, Hà Nội</div>
            </div>
            <div class="info-row">
                <div class="info-label">Mã số thuế:</div>
                <div>0123456789</div>
            </div>
            <div class="info-row">
                <div class="info-label">Điện thoại:</div>
                <div>(024) 1234 5678</div>
            </div>
        </div>
        
        <div class="customer-info info-section">
            <h3>THÔNG TIN KHÁCH HÀNG</h3>';
            
    // Thêm thông tin khách hàng
    if (!empty($invoice['user_info'])) {
        if (!empty($invoice['user_info']['company_name'])) {
            $html .= '
            <div class="info-row">
                <div class="info-label">Tên công ty/tổ chức:</div>
                <div>' . htmlspecialchars((string)($invoice['user_info']['company_name'] ?? '')) . '</div>
            </div>';
        }
        
        if (!empty($invoice['user_info']['company_address'])) {
            $html .= '
            <div class="info-row">
                <div class="info-label">Địa chỉ:</div>
                <div>' . htmlspecialchars((string)($invoice['user_info']['company_address'] ?? '')) . '</div>
            </div>';
        }
        
        if (!empty($invoice['user_info']['tax_code'])) {
            $html .= '
            <div class="info-row">
                <div class="info-label">Mã số thuế:</div>
                <div>' . htmlspecialchars((string)($invoice['user_info']['tax_code'] ?? '')) . '</div>
            </div>';
        }
        
        $html .= '
            <div class="info-row">
                <div class="info-label">Người liên hệ:</div>
                <div>' . htmlspecialchars((string)($invoice['user_info']['username'] ?? '')) . '</div>
            </div>';
        
        if (!empty($invoice['user_info']['email'])) {
            $html .= '
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div>' . htmlspecialchars((string)($invoice['user_info']['email'] ?? '')) . '</div>
            </div>';
        }
        
        if (!empty($invoice['user_info']['phone'])) {
            $html .= '
            <div class="info-row">
                <div class="info-label">Điện thoại:</div>
                <div>' . htmlspecialchars((string)($invoice['user_info']['phone'] ?? '')) . '</div>
            </div>';
        }
    }
    
    $html .= '
        </div>
        
        <div class="transaction-details info-section">
            <h3>CHI TIẾT GIAO DỊCH</h3>
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th width="5%">STT</th>
                        <th width="50%">Tên dịch vụ/sản phẩm</th>
                        <th width="15%">Đơn giá</th>
                        <th width="10%">Số lượng</th>
                        <th width="20%">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>' . $product_name . '</td>
                        <td>' . number_format((float)($invoice['amount'] ?? 0), 0, ',', '.') . ' đ</td>
                        <td>1</td>
                        <td>' . number_format((float)($invoice['amount'] ?? 0), 0, ',', '.') . ' đ</td>
                    </tr>';
    
    // Thêm thông tin chi tiết về gói dịch vụ nếu có    if (isset($invoice['registration_details']) && is_array($invoice['registration_details'])) {
        if (!empty($invoice['registration_details']['province'])) {
            $html .= '
                    <tr>
                        <td></td>
                        <td colspan="4">Khu vực: ' . htmlspecialchars($invoice['registration_details']['province']) . '</td>
                    </tr>';
        }
        
        if (isset($invoice['registration_details']['num_account'])) {
            $html .= '
                    <tr>
                        <td></td>
                        <td colspan="4">Số lượng tài khoản: ' . htmlspecialchars((string)$invoice['registration_details']['num_account']) . '</td>
                    </tr>';
        }
    }
    
    // Tổng thanh toán
    $html .= '
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;"><strong>Tổng cộng:</strong></td>
                        <td>' . number_format((float)($invoice['amount'] ?? 0), 0, ',', '.') . ' đ</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="payment-info info-section">
            <h3>THÔNG TIN THANH TOÁN</h3>
            <div class="info-row">
                <div class="info-label">Mã giao dịch:</div>
                <div>' . htmlspecialchars((string)($invoice['id'] ?? '')) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Thời gian giao dịch:</div>
                <div>' . $created_date . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phương thức thanh toán:</div>
                <div>' . htmlspecialchars((string)($invoice['method'] ?? '')) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Loại giao dịch:</div>
                <div>' . $transaction_type . '</div>
            </div>
        </div>
        
        <div class="date-section">
            Hà Nội, ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y') . '
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Khách hàng</div>
                <div>(Ký, ghi rõ họ tên)</div>
            </div>
            
            <div class="signature-box">
                <div class="signature-title">Đại diện công ty</div>
                <div>(Ký, ghi rõ họ tên, đóng dấu)</div>
            </div>
        </div>
        
        <div class="footer">
            <p>Hóa đơn này được tạo tự động từ hệ thống và có giá trị không cần đóng dấu.</p>
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
        </div>
    </div>
    ';
    
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
