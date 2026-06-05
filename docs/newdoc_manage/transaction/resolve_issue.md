# Các vấn đề thường gặp và cách giải quyết

## 1. Vấn đề hiển thị danh sách giao dịch trống

### 1.1. Danh sách giao dịch không hiển thị

#### Nguyên nhân thường gặp
1. **Lỗi kết nối database**
   - Cấu hình database không chính xác
   - Server MySQL không hoạt động
   - Timeout kết nối

2. **Lỗi truy vấn SQL**
   - Sai cú pháp SQL
   - Tên bảng hoặc cột không tồn tại
   - Join table không chính xác

3. **Lỗi phân quyền**
   - Session user_id không tồn tại
   - User không có giao dịch nào

#### Cách giải quyết
1. **Kiểm tra kết nối database**:
   ```php
   try {
       $db = new Database();
       $conn = $db->getConnection();
       // Test connection
       $testStmt = $conn->query("SELECT 1");
       echo "Database connection successful";
   } catch (PDOException $e) {
       echo "Connection failed: " . $e->getMessage();
   }
   ```

2. **Kiểm tra và tối ưu truy vấn**:
   - Sử dụng `EXPLAIN` để phân tích truy vấn
   - Kiểm tra tên bảng, cột, join conditions
   - In ra truy vấn SQL trước khi thực hiện để debug

3. **Kiểm tra phân quyền và session**:
   - Xác thực user_id trong session
   - Thử truy vấn trực tiếp từ database với user_id cụ thể
   - Kiểm tra các điều kiện WHERE trong truy vấn

### 1.2. Phân trang không hoạt động đúng

#### Nguyên nhân thường gặp
1. **Tính toán phân trang không chính xác**
   - Sai logic khi tính tổng số trang
   - Độ lệch trong tính toán OFFSET

2. **URL tham số không đúng**
   - Các tham số page, per_page không được truyền đúng
   - Encoding URL không đúng

3. **JavaScript không cập nhật UI**
   - Sự kiện onClick không được kích hoạt
   - AJAX không được gọi hoặc không xử lý response

#### Cách giải quyết
1. **Kiểm tra logic phân trang**:
   ```php
   $totalItems = 25;
   $itemsPerPage = 10;
   $totalPages = ceil($totalItems / $itemsPerPage); // Kết quả phải là 3
   $currentPage = 2;
   $offset = ($currentPage - 1) * $itemsPerPage; // Kết quả phải là 10
   ```

2. **Chuẩn hóa URL tham số**:
   ```php
   $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
   if ($page < 1) $page = 1;
   $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
   if (!in_array($perPage, [10, 20, 50])) {
       $perPage = 10; // Default to 10 items per page
   }
   ```

3. **Debug JavaScript phân trang**:
   - Kiểm tra sự kiện click trên các nút phân trang
   - Xác nhận URL được tạo đúng với tham số phân trang
   - Theo dõi network requests để đảm bảo AJAX hoạt động đúng

## 2. Vấn đề cập nhật trạng thái giao dịch

### 2.1. Không thể cập nhật trạng thái

#### Nguyên nhân thường gặp
1. **Quyền truy cập database**
   - Tài khoản database không có quyền UPDATE
   - Bảng đã bị khóa bởi transaction khác

2. **Sai tham số đầu vào**
   - ID giao dịch không tồn tại
   - Trạng thái không nằm trong danh sách cho phép
   - Thiếu tham số bắt buộc

3. **Lỗi trong transaction**
   - Không thể bắt đầu transaction
   - Lỗi commit transaction
   - Exception trong quá trình xử lý không được bắt đúng

#### Cách giải quyết
1. **Kiểm tra quyền database**:
   - Kiểm tra quyền của user database
   - Chạy truy vấn UPDATE đơn giản để test

2. **Xác thực và kiểm tra tham số**:
   ```php
   if (!$transaction_id || !in_array($new_status, ['completed', 'pending', 'failed', 'cancelled', 'refunded'])) {
       error_log("Invalid parameters: ID=$transaction_id, Status=$new_status");
       throw new Exception('Invalid parameters');
   }

   // Kiểm tra sự tồn tại của giao dịch
   $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaction_history WHERE id = :id");
   $stmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
   $stmt->execute();
   if ($stmt->fetchColumn() == 0) {
       throw new Exception('Transaction not found');
   }
   ```

3. **Cải thiện xử lý transaction**:
   ```php
   try {
       $pdo->beginTransaction();
       
       // Các truy vấn UPDATE
       
       $pdo->commit();
   } catch (Exception $e) {
       if ($pdo->inTransaction()) {
           $pdo->rollBack();
       }
       error_log("Transaction update error: " . $e->getMessage());
       throw $e;
   }
   ```

### 2.2. Tác vụ liên quan không được thực hiện

#### Nguyên nhân thường gặp
1. **Voucher không được cập nhật**
   - Quên gọi method incrementUsage()
   - Lỗi trong logic cập nhật voucher
   - Voucher không tồn tại

2. **Registration không được cập nhật**
   - Thiếu truy vấn update registration
   - Sai điều kiện WHERE trong câu truy vấn
   - Transaction ID không liên kết với registration nào

3. **Lỗi tính hoa hồng giới thiệu**
   - Không tìm thấy thông tin giới thiệu
   - Lỗi khi tính toán số tiền hoa hồng
   - Exception trong quá trình xử lý hoa hồng

#### Cách giải quyết
1. **Kiểm tra xử lý voucher**:
   ```php
   // Lưu log trước khi xử lý voucher
   error_log("Processing voucher for transaction: $transactionId, Voucher ID: {$transaction['voucher_id']}");
   
   // Kiểm tra tồn tại của voucher trước khi update
   if (!empty($transaction['voucher_id'])) {
       $voucherCheck = $pdo->prepare("SELECT id FROM voucher WHERE id = :id");
       $voucherCheck->bindParam(':id', $transaction['voucher_id'], PDO::PARAM_INT);
       $voucherCheck->execute();
       if ($voucherCheck->rowCount() > 0) {
           $voucher->incrementUsage($transaction['voucher_id']);
       } else {
           error_log("Voucher not found: {$transaction['voucher_id']}");
       }
   }
   ```

2. **Cải thiện cập nhật registration**:
   ```php
   // Kiểm tra có registration_id không trước khi update
   $regCheck = $pdo->prepare("SELECT registration_id FROM transaction_history WHERE id = :id");
   $regCheck->bindParam(':id', $transactionId, PDO::PARAM_INT);
   $regCheck->execute();
   $registration_id = $regCheck->fetchColumn();
   
   if ($registration_id) {
       // Cập nhật registration status
       $regUpdate = $pdo->prepare("UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = :id AND status = 'pending'");
       $regUpdate->bindParam(':id', $registration_id, PDO::PARAM_INT);
       $result = $regUpdate->execute();
       error_log("Registration update result: " . ($result ? "Success" : "Failed") . ", ID: $registration_id");
   } else {
       error_log("No registration found for transaction ID: $transactionId");
   }
   ```

3. **Tách biệt xử lý hoa hồng**:
   ```php
   // Tách xử lý hoa hồng ra khỏi khối try-catch chính
   try {
       if ($status == 'completed') {
           require_once dirname(__FILE__) . '/Referral.php';
           $referral = new Referral($this->db);
           $referral->calculateCommission($transactionId);
       }
   } catch (Exception $e) {
       // Chỉ log lỗi mà không làm ảnh hưởng đến xử lý chính
       error_log("Error processing referral: " . $e->getMessage());
   }
   ```

## 3. Vấn đề xuất hóa đơn

### 3.1. Không thể xuất hóa đơn VAT

#### Nguyên nhân thường gặp
1. **Thiếu thông tin người dùng**
   - Chưa cập nhật thông tin công ty
   - Thiếu mã số thuế
   - Địa chỉ công ty không đầy đủ

2. **Lỗi tạo yêu cầu hóa đơn**
   - Không thể insert vào bảng invoice
   - Lỗi truy cập file system để lưu bản sao
   - Lỗi gửi thông báo email

3. **Điều kiện xuất hóa đơn không thỏa mãn**
   - Giao dịch không ở trạng thái "completed"
   - Đã có yêu cầu hóa đơn cho giao dịch này trước đó
   - Thời gian yêu cầu nằm ngoài khoảng cho phép

#### Cách giải quyết
1. **Kiểm tra đầy đủ thông tin**:
   ```php
   $user = $userService->getUserById($_SESSION['user_id']);
   if (empty($user['company_name']) || empty($user['tax_code']) || empty($user['company_address'])) {
       $_SESSION['invoice_error'] = 'Vui lòng cập nhật đầy đủ thông tin công ty và mã số thuế trước khi yêu cầu xuất hóa đơn.';
       header('Location: ' . $base_url . '/public/pages/setting/invoice.php?error=missing_info');
       exit;
   }
   ```

2. **Cải thiện xử lý tạo yêu cầu**:
   ```php
   try {
       // Kiểm tra đã có yêu cầu chưa
       $checkStmt = $this->conn->prepare("SELECT id FROM invoice WHERE transaction_history_id = :tx_id AND status != 'failed'");
       $checkStmt->bindParam(':tx_id', $tx_id, PDO::PARAM_INT);
       $checkStmt->execute();
       
       if ($checkStmt->rowCount() > 0) {
           throw new Exception('Giao dịch này đã có yêu cầu xuất hóa đơn.');
       }
       
       // Tạo yêu cầu mới
       $stmt = $this->conn->prepare("INSERT INTO invoice (transaction_history_id, status, created_at) VALUES (:tx_id, 'pending', NOW())");
       $stmt->bindParam(':tx_id', $tx_id, PDO::PARAM_INT);
       $stmt->execute();
       
       // Ghi log
       error_log("Created invoice request for transaction ID: $tx_id");
   } catch (PDOException $e) {
       error_log("Database error: " . $e->getMessage());
       throw new Exception('Không thể tạo yêu cầu xuất hóa đơn.');
   }
   ```

3. **Xác minh điều kiện giao dịch**:
   ```php
   $txStmt = $this->conn->prepare("
       SELECT status, DATEDIFF(NOW(), created_at) as days_since_creation
       FROM transaction_history 
       WHERE id = :tx_id AND user_id = :user_id
   ");
   $txStmt->bindParam(':tx_id', $tx_id, PDO::PARAM_INT);
   $txStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
   $txStmt->execute();
   $tx = $txStmt->fetch(PDO::FETCH_ASSOC);
   
   if (!$tx) {
       throw new Exception('Không tìm thấy giao dịch hoặc bạn không có quyền truy cập.');
   }
   
   if ($tx['status'] !== 'completed') {
       throw new Exception('Chỉ những giao dịch đã hoàn thành mới có thể yêu cầu xuất hóa đơn.');
   }
   
   if ($tx['days_since_creation'] > 365) {
       throw new Exception('Không thể yêu cầu xuất hóa đơn cho giao dịch cũ hơn 1 năm.');
   }
   ```

### 3.2. Không thể xuất hóa đơn bán lẻ

#### Nguyên nhân thường gặp
1. **Lỗi AJAX và xử lý client-side**
   - Sai endpoint API
   - Lỗi trong việc thu thập ID giao dịch đã chọn
   - Lỗi xử lý response

2. **Lỗi tạo file PDF**
   - MPDF không được cấu hình đúng
   - Lỗi định dạng template
   - Lỗi khi tạo file tạm

3. **Lỗi khi tạo file ZIP**
   - Thiếu extension ZipArchive
   - Lỗi quyền truy cập thư mục tạm
   - Lỗi khi thêm file vào archive

#### Cách giải quyết
1. **Cải thiện xử lý AJAX**:
   ```javascript
   // Kiểm tra và log dữ liệu trước khi gửi
   const selectedIds = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
       .map(cb => cb.value);
   
   console.log("Selected transaction IDs:", selectedIds);
   
   if (selectedIds.length === 0) {
       alert('Vui lòng chọn ít nhất 1 giao dịch để xuất hóa đơn.');
       return;
   }
   
   fetch('/public/handlers/export_retail_invoice.php', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({ transaction_ids: selectedIds })
   })
   .then(response => {
       if (!response.ok) {
           return response.json().then(data => {
               throw new Error(data.error || 'Unknown error');
           });
       }
       return response.blob();
   })
   .then(blob => {
       // Xử lý tải xuống
   })
   .catch(error => {
       console.error("Export error:", error);
       alert(`Lỗi: ${error.message}`);
   });
   ```

2. **Kiểm tra và cải thiện tạo PDF**:
   ```php
   try {
       // Kiểm tra thư mục cache của MPDF
       $tempDir = sys_get_temp_dir() . '/mpdf';
       if (!is_dir($tempDir)) {
           mkdir($tempDir, 0777, true);
       }
       
       // Cấu hình MPDF
       $mpdfConfig = [
           'mode' => 'utf-8',
           'format' => 'A4',
           'tempDir' => $tempDir,
           'debug' => true // Enable debug in development
       ];
       
       // Tạo instance với exception rõ ràng
       $mpdf = new \Mpdf\Mpdf($mpdfConfig);
       
       // Kiểm tra template
       $template = file_get_contents($templatePath);
       if (!$template) {
           throw new Exception("Could not read template file at: $templatePath");
       }
       
       // Render PDF
       $mpdf->WriteHTML($template);
       
       // Lưu vào file tạm và kiểm tra kết quả
       $outputFile = tempnam(sys_get_temp_dir(), 'inv_');
       $mpdf->Output($outputFile, \Mpdf\Output\Destination::FILE);
       
       if (!file_exists($outputFile)) {
           throw new Exception("Failed to create PDF file");
       }
       
       return $outputFile;
   } catch (\Mpdf\MpdfException $e) {
       error_log("MPDF error: " . $e->getMessage());
       throw new Exception("Error creating PDF: " . $e->getMessage());
   }
   ```

3. **Cải thiện xử lý ZIP**:
   ```php
   // Kiểm tra extension
   if (!class_exists('ZipArchive')) {
       throw new Exception('ZipArchive extension is not available');
   }
   
   try {
       $zipFile = tempnam(sys_get_temp_dir(), 'invoices_');
       $zip = new ZipArchive();
       
       if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
           throw new Exception("Cannot create ZIP file");
       }
       
       foreach ($pdfFiles as $index => $file) {
           if (!file_exists($file)) {
               error_log("PDF file does not exist: $file");
               continue;
           }
           
           $filename = "invoice_" . ($index + 1) . ".pdf";
           if (!$zip->addFile($file, $filename)) {
               error_log("Failed to add file to ZIP: $file");
           }
       }
       
       if (!$zip->close()) {
           throw new Exception("Failed to save ZIP file");
       }
       
       // Check if ZIP was created
       if (!file_exists($zipFile)) {
           throw new Exception("ZIP file was not created");
       }
       
       return $zipFile;
   } catch (Exception $e) {
       error_log("ZIP error: " . $e->getMessage());
       throw $e;
   }
   ```

## 4. Vấn đề tải lên minh chứng thanh toán

### 4.1. Không thể tải file lên

#### Nguyên nhân thường gặp
1. **Lỗi cấu hình PHP**
   - `post_max_size` và `upload_max_filesize` quá nhỏ
   - `max_execution_time` quá ngắn cho tệp lớn
   - `memory_limit` không đủ

2. **Lỗi thư mục uploads**
   - Không đủ quyền truy cập để ghi file
   - Thư mục không tồn tại
   - Disk quota đầy

3. **Lỗi form HTML**
   - Thiếu thuộc tính `enctype="multipart/form-data"`
   - Sai tên field cho input file
   - JavaScript validation chặn submit form

#### Cách giải quyết
1. **Kiểm tra và cập nhật cấu hình PHP**:
   ```php
   // Kiểm tra cấu hình hiện tại
   echo "post_max_size: " . ini_get('post_max_size') . "<br>";
   echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
   echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
   echo "memory_limit: " . ini_get('memory_limit') . "<br>";
   
   // Cập nhật tạm thời (nếu được phép)
   ini_set('post_max_size', '20M');
   ini_set('upload_max_filesize', '10M');
   ini_set('max_execution_time', 300); // 5 minutes
   ini_set('memory_limit', '256M');
   ```

2. **Kiểm tra và chuẩn bị thư mục uploads**:
   ```php
   $uploadsDir = dirname(__DIR__) . '/public/uploads/payment_proof';
   
   // Kiểm tra thư mục
   if (!is_dir($uploadsDir)) {
       // Tạo thư mục nếu chưa tồn tại
       if (!mkdir($uploadsDir, 0755, true)) {
           error_log("Failed to create directory: $uploadsDir");
           throw new Exception("Không thể tạo thư mục lưu trữ.");
       }
   }
   
   // Kiểm tra quyền ghi
   if (!is_writable($uploadsDir)) {
       error_log("Directory not writable: $uploadsDir");
       throw new Exception("Không có quyền ghi vào thư mục lưu trữ.");
   }
   
   // Kiểm tra disk space
   $freeSpace = disk_free_space($uploadsDir);
   if ($freeSpace < 10 * 1024 * 1024) { // Ít nhất 10MB
       error_log("Not enough disk space: " . ($freeSpace / 1024 / 1024) . "MB");
       throw new Exception("Không đủ dung lượng đĩa.");
   }
   ```

3. **Kiểm tra form HTML**:
   ```html
   <!-- Form đúng -->
   <form action="upload_proof.php" method="post" enctype="multipart/form-data">
       <!-- Tên field phải khớp với code PHP -->
       <input type="file" name="payment_proof" accept="image/jpeg,image/png" required>
       <input type="hidden" name="transaction_id" value="123">
       <button type="submit">Tải lên</button>
   </form>
   
   <script>
   // Thêm client-side validation đơn giản
   document.querySelector('form').addEventListener('submit', function(e) {
       const fileInput = document.querySelector('input[name="payment_proof"]');
       const maxSize = 5 * 1024 * 1024; // 5MB
       
       if (fileInput.files.length === 0) {
           alert('Vui lòng chọn file để tải lên');
           e.preventDefault();
           return;
       }
       
       if (fileInput.files[0].size > maxSize) {
           alert('File quá lớn. Kích thước tối đa là 5MB');
           e.preventDefault();
           return;
       }
   });
   </script>
   ```

### 4.2. File được tải lên nhưng không hiển thị

#### Nguyên nhân thường gặp
1. **Đường dẫn file không chính xác**
   - Đường dẫn lưu vào database không khớp với vị trí thực tế
   - URL tương đối thay vì URL tuyệt đối
   - Thiếu base URL khi hiển thị

2. **Lỗi phân quyền file**
   - File được tạo với quyền không cho phép đọc
   - Quyền sở hữu file không khớp với user web server

3. **Lỗi server configuration**
   - Web server không được cấu hình để phục vụ nội dung tĩnh
   - Thiếu MIME type mapping
   - Cấu hình bảo mật chặn truy cập file

#### Cách giải quyết
1. **Chuẩn hóa đường dẫn file**:
   ```php
   // Đảm bảo lưu đường dẫn đúng vào database
   $uploadsDir = '/uploads/payment_proof';
   $fileExt = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
   $fileName = uniqid('payment_') . '.' . $fileExt;
   $relativePath = $uploadsDir . '/' . $fileName;
   $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;
   
   // Upload file
   if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $absolutePath)) {
       // Lưu đường dẫn tương đối
       $stmt = $pdo->prepare("UPDATE transaction_history SET payment_image = :path WHERE id = :id");
       $stmt->bindParam(':path', $relativePath);
       $stmt->bindParam(':id', $transaction_id);
       $stmt->execute();
   }
   
   // Khi hiển thị, sử dụng base URL
   $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
   $imageUrl = $baseUrl . $transaction['payment_image'];
   echo "<img src='$imageUrl' alt='Payment Proof'>";
   ```

2. **Kiểm tra và sửa quyền file**:
   ```php
   // Đặt quyền đúng sau khi upload
   $filePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;
   if (file_exists($filePath)) {
       chmod($filePath, 0644); // Quyền đọc cho tất cả, ghi chỉ cho owner
   }
   
   // Kiểm tra quyền trước khi hiển thị
   if (!is_readable($filePath)) {
       error_log("File not readable: $filePath");
       echo "Không thể đọc file minh chứng. Vui lòng liên hệ quản trị viên.";
   }
   ```

3. **Cấu hình server**:
   - Apache: Thêm vào .htaccess
   ```
   <Directory "/var/www/html/uploads/payment_proof">
       Options -Indexes
       Order allow,deny
       Allow from all
       
       <FilesMatch "\.(jpg|jpeg|png)$">
           Header set Content-disposition "inline"
       </FilesMatch>
   </Directory>
   ```
   
   - Nginx: Thêm vào server block configuration
   ```
   location /uploads/payment_proof/ {
       add_header Content-disposition "inline";
       types {
           image/jpeg jpg jpeg;
           image/png png;
       }
   }
   ```
