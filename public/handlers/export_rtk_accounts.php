<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/RtkAccount.php';
require_once PROJECT_ROOT_PATH . '/vendor/autoload.php';

// Sử dụng các lớp từ PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng về login
    header('Location: ' . BASE_URL . '/public/pages/auth/login.php');
    exit;
}

// Kiểm tra xem có tài khoản nào được chọn không
if (!isset($_POST['selected_accounts']) || empty($_POST['selected_accounts'])) {
    // Quay lại trang quản lý tài khoản với thông báo lỗi
    $_SESSION['error_message'] = 'Vui lòng chọn ít nhất một tài khoản để xuất Excel.';
    header('Location: ' . BASE_URL . '/public/pages/rtk_accountmanagement.php');
    exit;
}

// Lấy các ID tài khoản được chọn
$selectedAccountIds = $_POST['selected_accounts'];

// Kết nối database và lấy dữ liệu tài khoản
$db = new Database();
$rtkAccountManager = new RtkAccount($db);
$userId = $_SESSION['user_id'];

try {
    // Lấy dữ liệu tất cả tài khoản của user
    $allAccounts = $rtkAccountManager->getAccountsByUserId($userId);
    
    // Lọc ra những tài khoản được chọn
    $selectedAccounts = array_filter($allAccounts, function($account) use ($selectedAccountIds) {
        return in_array($account['id'], $selectedAccountIds);
    });
    
    if (empty($selectedAccounts)) {
        throw new Exception("Không tìm thấy tài khoản nào trong số các tài khoản đã chọn.");
    }
    
    // Tạo một Spreadsheet mới
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Thiết lập tiêu đề sheet
    $sheet->setTitle('Danh sách tài khoản');
    
    // Set các tiêu đề cột
    $headers = [
        'STT', 
        'IP', 
        'Port', 
        'Tài khoản', 
        'Mật khẩu', 
        'Khu vực', 
        'Mount Point', 
        'Ngày bắt đầu', 
        'Ngày kết thúc', 
        'Trạng thái', 
        'Tên người dùng', 
        'Số điện thoại'
    ];
    
    // Định dạng cho tiêu đề
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '007BFF'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ];
    
    // Đặt tiêu đề và định dạng
    for ($i = 0; $i < count($headers); $i++) {
        $column = chr(65 + $i); // A, B, C, ...
        $sheet->setCellValue($column . '1', $headers[$i]);
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->applyFromArray($headerStyle);
    
    // Lấy thông tin người dùng
    $userQuery = $db->getConnection()->prepare("SELECT username, phone FROM user WHERE id = :user_id");
    $userQuery->bindParam(':user_id', $userId);
    $userQuery->execute();
    $userInfo = $userQuery->fetch(PDO::FETCH_ASSOC);
    
    $username = $userInfo['username'] ?? 'N/A';
    $phone = $userInfo['phone'] ?? 'N/A';
    
    // Chuẩn bị dữ liệu để gộp các tài khoản cùng username_acc
    $groupedAccounts = [];
    foreach ($selectedAccounts as $account) {
        $key = $account['username_acc'];
        
        if (!isset($groupedAccounts[$key])) {
            // Tạo mới nếu chưa có
            $groupedAccounts[$key] = $account;
            $groupedAccounts[$key]['all_mountpoints'] = [];
        }
        
        // Thêm mountpoints vào tài khoản đã gộp
        if (!empty($account['mountpoints'])) {
            foreach ($account['mountpoints'] as $mountpoint) {
                $groupedAccounts[$key]['all_mountpoints'][] = $mountpoint;
            }
        }
    }
    
    // Điền dữ liệu tài khoản vào bảng
    $row = 2;
    $stt = 1;
    
    foreach ($groupedAccounts as $account) {
        // Xử lý thông tin trạng thái
        $status = 'Không xác định';
        switch ($account['status']) {
            case 'active':
                $status = 'Hoạt động';
                break;
            case 'expired':
                $status = 'Hết hạn';
                break;
            case 'pending':
            case 'locked':
                $status = 'Đã khóa';
                break;
        }
        
        // Xử lý định dạng ngày giờ
        $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        $startDateTime = new DateTime($account['effective_start_time'], $tz);
        $endDateTime = new DateTime($account['effective_end_time'], $tz);
        $startDate = $startDateTime->format('d/m/Y H:i:s');
        $endDate = $endDateTime->format('d/m/Y H:i:s');
        
        // Gộp danh sách mountpoints
        $mountpointText = '';
        $ipText = '';
        $portText = '';
        
        if (!empty($account['all_mountpoints'])) {
            $mountpoints = array_map(function($mp) {
                return $mp['mountpoint'] ?? 'N/A';
            }, $account['all_mountpoints']);
            
            $ips = array_map(function($mp) {
                return $mp['ip'] ?? 'N/A';
            }, $account['all_mountpoints']);
            
            $ports = array_map(function($mp) {
                return $mp['port'] ?? 'N/A';
            }, $account['all_mountpoints']);
            
            // Loại bỏ các giá trị trùng lặp
            $uniqueIps = array_unique($ips);
            $uniquePorts = array_unique($ports);
            
            $ipText = implode(', ', $uniqueIps);
            $portText = implode(', ', $uniquePorts);
            $mountpointText = implode(', ', $mountpoints);
        }
        
        $sheet->setCellValue('A' . $row, $stt);
        $sheet->setCellValue('B' . $row, $ipText ?: 'N/A');
        $sheet->setCellValue('C' . $row, $portText ?: 'N/A');
        $sheet->setCellValueExplicit('D' . $row, $account['username_acc'], DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $row, $account['password_acc'], DataType::TYPE_STRING);
        $sheet->setCellValue('F' . $row, $account['province'] ?? 'N/A');
        $sheet->setCellValue('G' . $row, $mountpointText ?: 'N/A');
        $sheet->setCellValue('H' . $row, $startDate);
        $sheet->setCellValue('I' . $row, $endDate);
        $sheet->setCellValue('J' . $row, $status);
        $sheet->setCellValue('K' . $row, $username);
        $sheet->setCellValue('L' . $row, $phone);
        
        $row++;
        $stt++;
    }
    
    // Định dạng cho nội dung
    $contentStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    
    if ($row > 2) {
        $sheet->getStyle('A2:' . chr(64 + count($headers)) . ($row - 1))->applyFromArray($contentStyle);
    }
    
    // Căn giữa một số cột
    $centeredColumns = ['A', 'C', 'H', 'I', 'J'];
    foreach ($centeredColumns as $column) {
        $sheet->getStyle($column . '2:' . $column . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    
    // Đặt căn trái cho cột B, G (IP và Mount Point)
    $leftAlignedColumns = ['B', 'G'];
    foreach ($leftAlignedColumns as $column) {
        $sheet->getStyle($column . '2:' . $column . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }
    
    // Tạo Writer để xuất file
    $writer = new Xlsx($spreadsheet);
    
    // Đặt tên file và header cho việc tải xuống
    $filename = 'danh_sach_tai_khoan_rtk_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Đặt header để tải xuống
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Xuất file
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    // Xử lý lỗi
    error_log("Lỗi xuất Excel: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi xuất file Excel: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/public/pages/rtk_accountmanagement.php');
    exit;
}