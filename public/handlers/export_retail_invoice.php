<?php
// Handler for exporting retail invoices (Hóa đơn bán lẻ) instantly
session_start();
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/invoice/RetailInvoiceService.php';

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

// Sử dụng service để lấy dữ liệu hóa đơn - xử lý tất cả giao dịch được chọn
$retailInvoiceService = new RetailInvoiceService();
$retail_invoices = $retailInvoiceService->getRetailInvoicesData($tx_ids, $user_id);

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
    }    .signature-section {
        margin-top: 50px;
        margin-bottom: 20px;
        width: 100%;
    }
    .signature-table {
        width: 100%;
        border-collapse: collapse;
    }
    .signature-table td {
        width: 50%;
        text-align: center;
        padding: 10px;
        vertical-align: top;
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
    // Lấy thông tin công ty
    $db_company = new Database();
    $pdo_company = $db_company->getConnection();
    $stmt_company = $pdo_company->prepare("SELECT * FROM company_info ORDER BY id ASC LIMIT 1");
    $stmt_company->execute();
    $company = $stmt_company->fetch(PDO::FETCH_ASSOC) ?: [];
    $companyName = $company['name'] ?? '';
    $companyTax = $company['tax_code'] ?? '';
    $companyPhone = $company['phone'] ?? '';
    $companyEmail = $company['email'] ?? '';
    $companyWebsite = $company['website'] ?? '';
    $addresses = json_decode($company['address'] ?? '[]', true);
    $headOffice = '';
    foreach ($addresses as $addr) {
        if (($addr['type'] ?? '') === 'trụ sở') {
            $headOffice = $addr['location'];
            break;
        }
    }
    // Build dynamic company info HTML
    $company_info_html = '<div class="company-info info-section">'
        . '<h3>THÔNG TIN CÔNG TY</h3>'
        . '<div class="info-row"><div class="info-label">Tên công ty:</div><div>' . htmlspecialchars($companyName) . '</div></div>'
        . '<div class="info-row"><div class="info-label">Địa chỉ:</div><div>' . htmlspecialchars($headOffice) . '</div></div>'
        . '<div class="info-row"><div class="info-label">Mã số thuế:</div><div>' . htmlspecialchars($companyTax) . '</div></div>'
        . '<div class="info-row"><div class="info-label">Điện thoại:</div><div>' . htmlspecialchars($companyPhone) . '</div></div>'
        . '<div class="info-row"><div class="info-label">Email:</div><div>' . htmlspecialchars($companyEmail) . '</div></div>'
        . '<div class="info-row"><div class="info-label">Website:</div><div>' . htmlspecialchars($companyWebsite) . '</div></div>'
        . '</div>';

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
' . $company_info_html . '
        <div class="customer-info info-section">
            <h3>THÔNG TIN KHÁCH HÀNG</h3>';
              // Thêm thông tin khách hàng
    if (!empty($invoice['user_info'])) {
        // Đã bỏ 3 trường: Tên công ty/tổ chức, Địa chỉ và Mã số thuế
        
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
                        <th width="10%">STT</th>
                        <th width="40%">Tên dịch vụ/sản phẩm</th>
                        <th width="15%">Đơn giá</th>
                        <th width="15%">Số lượng tài khoản</th>
                        <th width="20%">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>' . $product_name . '</td>
                        <td>' . number_format((float)($invoice['amount'] ?? 0), 0, ',', '.') . ' đ</td>
                        <td> '. htmlspecialchars((string)$invoice['registration_details']['num_account']) .'</td>
                        <td>' . number_format((float)($invoice['amount'] ?? 0), 0, ',', '.') . ' đ</td>
                    </tr>';
      // Thêm thông tin chi tiết về gói dịch vụ nếu có
    if (isset($invoice['registration_details']) && is_array($invoice['registration_details'])) {
        if (!empty($invoice['registration_details']['province'])) {
            $html .= '
                    <tr>
                        <td></td>
                        <td colspan="4">Khu vực: ' . htmlspecialchars($invoice['registration_details']['province']) . '</td>
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
        
        
        
        <div class="date-section">
            Hà Nội, ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y') . '
        </div>
          <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-title">Khách hàng</div>
                        <div>(Ký, ghi rõ họ tên)</div>
                    </td>
                    <td>
                        <div class="signature-title">Đại diện công ty</div>
                        <div>(Ký, ghi rõ họ tên, đóng dấu)</div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p>Hóa đơn này được tạo tự động từ hệ thống và có giá trị không cần đóng dấu.</p>
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
        </div>
    </div>
    ';
      // Định dạng ngày giờ cho tên file
    $now = new \DateTime();
    $dateFormat = $now->format('Y_m_d_H_i_s');
    
    // Tạo file PDF với tên có ID giao dịch và ngày giờ
    $file_path = $tmp_dir . '/hoadonbanle_' . $invoice['id'] . '_' . $dateFormat . '.pdf';    $mpdf->WriteHTML($html);
    $mpdf->Output($file_path, \Mpdf\Output\Destination::FILE);
    $pdf_files[] = $file_path;
}

if (count($pdf_files) === 1) {
    $file = $pdf_files[0];
    
    // Lấy transaction ID từ tên file
    $txId = '';
    if (preg_match('/hoadonbanle_(\d+)_/', basename($file), $matches)) {
        $txId = $matches[1];
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="hoadonbanle_' . $txId . '_' . date('Y_m_d_H_i_s') . '.pdf"');
    readfile($file);    unlink($file);
    exit;
} else {
    $zip_path = $tmp_dir . '/hoadonbanle_' . date('Y_m_d_H_i_s') . '.zip';
    
    // Kiểm tra xem ZipArchive có khả dụng không
    if (!class_exists('ZipArchive')) {
        error_log('PHP ZipArchive extension is not available');
        http_response_code(500);
        echo json_encode(['error' => 'Không thể tạo file zip (ZipArchive không khả dụng)']);
        exit;
    }
    
    $zip = new ZipArchive();
    $result = $zip->open($zip_path, ZipArchive::CREATE);
    if ($result === TRUE) {
        foreach ($pdf_files as $file) {
            if (file_exists($file)) {
                // Lấy tên file không có đường dẫn
                $filename = basename($file);
                $zip->addFile($file, $filename);
            } else {
                error_log('PDF file does not exist: ' . $file);
            }
        }
        $zip->close();
        
        // Xóa các file PDF đã được thêm vào ZIP
        foreach ($pdf_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        if (file_exists($zip_path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="hoadonbanle_' . date('Y_m_d_H_i_s') . '.zip"');
            readfile($zip_path);
            unlink($zip_path);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Không thể tạo file zip (file không tồn tại)']);
            exit;
        }
    } else {
        error_log('Failed to create ZIP file. Error code: ' . $result);
        http_response_code(500);
        echo json_encode(['error' => 'Không thể tạo file zip (mã lỗi: ' . $result . ')']);
        exit;
    }
}
