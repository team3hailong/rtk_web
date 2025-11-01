<?php
/**
 * Script để xóa bỏ toàn bộ INSERT records từ file SQL
 * Chỉ giữ lại cấu trúc (CREATE TABLE, ALTER TABLE, etc.)
 * 
 * Cách sử dụng:
 * php scripts/remove_sql_records.php
 * Sau đó nhập đường dẫn file SQL cần xử lý
 */

echo "==============================================\n";
echo "  XÓA DỮ LIỆU TRONG FILE SQL\n";
echo "==============================================\n\n";

// Yêu cầu nhập đường dẫn file
echo "Nhập đường dẫn file SQL (tuyệt đối hoặc tương đối): ";
$filePath = trim(fgets(STDIN));

// Kiểm tra file có tồn tại không
if (!file_exists($filePath)) {
    echo "\n[ERROR] File không tồn tại: $filePath\n";
    exit(1);
}

// Kiểm tra có phải file .sql không
if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'sql') {
    echo "\n[WARNING] File không có đuôi .sql. Tiếp tục? (y/n): ";
    $confirm = trim(fgets(STDIN));
    if (strtolower($confirm) !== 'y') {
        echo "Đã hủy.\n";
        exit(0);
    }
}

echo "\n[INFO] Đang đọc file: $filePath\n";

// Đọc nội dung file
$content = file_get_contents($filePath);
if ($content === false) {
    echo "[ERROR] Không thể đọc file!\n";
    exit(1);
}

// Thống kê trước khi xử lý
$originalSize = strlen($content);
$originalLines = substr_count($content, "\n") + 1;

echo "[INFO] Kích thước file gốc: " . number_format($originalSize) . " bytes\n";
echo "[INFO] Số dòng gốc: " . number_format($originalLines) . "\n\n";

// Xử lý xóa các câu lệnh INSERT
echo "[INFO] Đang xử lý...\n";

// Đếm số INSERT trước khi xóa
preg_match_all('/INSERT\s+INTO/i', $content, $matches);
$insertCount = count($matches[0]);
echo "[INFO] Tìm thấy $insertCount câu lệnh INSERT\n";

// Method 1: Xóa INSERT statements từng dòng
$lines = explode("\n", $content);
$newLines = [];
$inInsert = false;
$removedInserts = 0;

foreach ($lines as $line) {
    $trimmedLine = trim($line);
    
    // Bắt đầu câu lệnh INSERT
    if (preg_match('/^INSERT\s+INTO/i', $trimmedLine)) {
        $inInsert = true;
        $removedInserts++;
        
        // Nếu INSERT chỉ có 1 dòng (kết thúc bằng ;)
        if (preg_match('/;\s*$/', $trimmedLine)) {
            $inInsert = false;
        }
        continue;
    }
    
    // Đang trong câu lệnh INSERT nhiều dòng
    if ($inInsert) {
        // Kiểm tra xem dòng có kết thúc câu lệnh không
        if (preg_match('/;\s*$/', $trimmedLine)) {
            $inInsert = false;
        }
        continue;
    }
    
    // Bỏ qua các dòng LOCK/UNLOCK
    if (preg_match('/^(LOCK\s+TABLES|UNLOCK\s+TABLES)/i', $trimmedLine)) {
        continue;
    }
    
    // Bỏ qua TRUNCATE, DELETE
    if (preg_match('/^(TRUNCATE|DELETE\s+FROM)/i', $trimmedLine)) {
        continue;
    }
    
    // Giữ lại dòng này
    $newLines[] = $line;
}

$content = implode("\n", $newLines);

echo "[INFO] Đã xóa $removedInserts câu lệnh INSERT\n";

// Xóa các dòng trống liên tiếp
$content = preg_replace("/\n{3,}/", "\n\n", $content);

// Trim khoảng trắng đầu cuối
$content = trim($content) . "\n";

// Thống kê sau khi xử lý
$newSize = strlen($content);
$newLines = substr_count($content, "\n") + 1;
$savedSize = $originalSize - $newSize;
$savedPercent = $originalSize > 0 ? ($savedSize / $originalSize * 100) : 0;

echo "\n[SUCCESS] Xử lý hoàn tất!\n";
echo "[INFO] Kích thước mới: " . number_format($newSize) . " bytes\n";
echo "[INFO] Số dòng mới: " . number_format($newLines) . "\n";
echo "[INFO] Đã giảm: " . number_format($savedSize) . " bytes (" . number_format($savedPercent, 2) . "%)\n\n";

// Tạo tên file backup và file output
$pathInfo = pathinfo($filePath);
$backupFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_backup_' . date('YmdHis') . '.' . $pathInfo['extension'];
$outputFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_structure.' . $pathInfo['extension'];

// Hỏi có muốn backup file gốc không
echo "Bạn có muốn backup file gốc không? (y/n): ";
$backupConfirm = trim(fgets(STDIN));
if (strtolower($backupConfirm) === 'y') {
    if (copy($filePath, $backupFile)) {
        echo "[SUCCESS] Đã backup file gốc: $backupFile\n";
    } else {
        echo "[ERROR] Không thể tạo backup!\n";
    }
}

// Hỏi có muốn ghi đè file gốc hay tạo file mới
echo "\nChọn cách lưu kết quả:\n";
echo "1. Ghi đè file gốc (overwrite)\n";
echo "2. Tạo file mới (tên: {$pathInfo['filename']}_structure.{$pathInfo['extension']})\n";
echo "Lựa chọn (1/2): ";
$saveOption = trim(fgets(STDIN));

if ($saveOption === '1') {
    // Ghi đè file gốc
    if (file_put_contents($filePath, $content) !== false) {
        echo "\n[SUCCESS] Đã ghi đè file gốc: $filePath\n";
    } else {
        echo "\n[ERROR] Không thể ghi file!\n";
        exit(1);
    }
} else {
    // Tạo file mới
    if (file_put_contents($outputFile, $content) !== false) {
        echo "\n[SUCCESS] Đã tạo file mới: $outputFile\n";
    } else {
        echo "\n[ERROR] Không thể tạo file mới!\n";
        exit(1);
    }
}

echo "\n==============================================\n";
echo "  HOÀN TẤT!\n";
echo "==============================================\n";
