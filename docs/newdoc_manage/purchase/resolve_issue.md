# Các vấn đề thường gặp và cách giải quyết

## 1. Vấn đề khi mua gói mới

### 1.1. Không thể chọn gói dịch vụ

#### Nguyên nhân thường gặp
1. **Gói không còn hoạt động**
   - Gói dịch vụ có thể đã bị tắt trong hệ thống (`is_active = 0`)
   
2. **Lỗi tải dữ liệu**
   - Kết nối database bị lỗi
   - Truy vấn SQL không thành công

3. **Gói dùng thử không hiển thị**
   - Người dùng đã có tài khoản trước đó
   - Gói dùng thử đã bị tắt

#### Cách giải quyết
1. **Kiểm tra trạng thái gói**:
   ```sql
   SELECT * FROM package WHERE package_id = 'ten_goi' AND is_active = 1;
   ```

2. **Kiểm tra kết nối database**:
   - Xem logs để tìm lỗi kết nối
   - Kiểm tra file cấu hình database

3. **Kiểm tra điều kiện hiển thị gói dùng thử**:
   ```php
   // Đảm bảo logic ẩn hiện gói dùng thử đúng
   $survey_account_count = $service->getUserSurveyAccountCount($user_id);
   if ($package['package_id'] === 'trial_7d' && $survey_account_count > 0) {
       // Ẩn gói dùng thử
   }
   ```

### 1.2. Lỗi khi tính giá và thời gian sử dụng

#### Nguyên nhân thường gặp
1. **Định dạng duration_text không hợp lệ**
   - Format không đúng chuẩn "X Ngày/Tháng/Năm"
   
2. **Lỗi parse thời gian**
   - Hàm parse date/time thất bại
   
3. **Giá không khớp**
   - Giá hiển thị trong UI không khớp với giá trong database

#### Cách giải quyết
1. **Kiểm tra định dạng duration_text**:
   ```sql
   SELECT package_id, duration_text FROM package;
   ```
   
2. **Đảm bảo logic parse thời gian đúng**:
   ```php
   // Kiểm tra logic parse trong process_order.php
   if (preg_match('/(\d+)\s*(Năm|Tháng|Ngày)/iu', $package['duration_text'], $matches)) {
       $num = (int)$matches[1];
       $unit = strtolower($matches[2]);
       $interval_spec = '';
       if ($unit === 'năm') $interval_spec = "P{$num}Y";
       elseif ($unit === 'tháng') $interval_spec = "P{$num}M";
       elseif ($unit === 'ngày') $interval_spec = "P{$num}D";
   }
   ```

3. **Xác thực giá server-side**:
   ```php
   // Luôn lấy giá từ database để đảm bảo chính xác
   $base_price = (float)$package['price'];
   $calculated_subtotal = $base_price * $quantity;
   ```

### 1.3. Lỗi khi thanh toán

#### Nguyên nhân thường gặp
1. **Thông tin thanh toán không đúng**
   - Thông tin ngân hàng không được cấu hình đúng
   - Mã VietQR không tạo được
   
2. **Session bị mất**
   - Session `pending_registration_id` hoặc `pending_total_price` bị mất
   
3. **Không upload được minh chứng**
   - Quyền thư mục upload không đủ
   - Lỗi khi xử lý file upload

#### Cách giải quyết
1. **Kiểm tra cấu hình thanh toán**:
   - Kiểm tra các biến môi trường thanh toán (config)
   ```php
   echo defined('VIETQR_BANK_ID') ? VIETQR_BANK_ID : 'Not defined';
   echo defined('VIETQR_ACCOUNT_NO') ? VIETQR_ACCOUNT_NO : 'Not defined';
   ```

2. **Đảm bảo tính nhất quán của session**:
   ```php
   // Kiểm tra session trước khi xử lý
   if (!isset($_SESSION['pending_registration_id']) || !isset($_SESSION['pending_total_price'])) {
       header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=no_pending_order');
       exit;
   }
   ```

3. **Kiểm tra quyền thư mục upload**:
   - Đảm bảo thư mục có quyền ghi
   - Kiểm tra giới hạn kích thước upload trong PHP
   ```shell
   # Kiểm tra giới hạn upload
   php -i | grep upload_max_filesize
   php -i | grep post_max_size
   ```

## 2. Vấn đề khi dùng thử

### 2.1. Không thể kích hoạt gói dùng thử

#### Nguyên nhân thường gặp
1. **Người dùng đã có tài khoản**
   - Người dùng đã đăng ký tài khoản trước đó
   
2. **Lỗi khi tạo tài khoản trên API**
   - API RTK bị lỗi hoặc không phản hồi
   
3. **Lỗi transaction database**
   - Transaction rollback do lỗi trong quá trình xử lý

#### Cách giải quyết
1. **Kiểm tra điều kiện dùng thử**:
   ```php
   // Đảm bảo kiểm tra chính xác số lượng tài khoản
   $survey_account_count = $service->getUserSurveyAccountCount($user_id);
   if ($survey_account_count > 0) {
       $_SESSION['error'] = 'Bạn đã có tài khoản và không đủ điều kiện dùng thử.';
       header('Location: ' . $base_url . '/public/pages/purchase/packages.php');
       exit;
   }
   ```

2. **Kiểm tra và debug API RTK**:
   ```php
   // Thêm log chi tiết hơn khi gọi API
   try {
       $api_response = createRtkAccount($username, $password, $params);
       error_log("API Response: " . json_encode($api_response));
   } catch (Exception $e) {
       error_log("API Error: " . $e->getMessage());
   }
   ```

3. **Kiểm tra transaction**:
   - Thêm log chi tiết hơn trong quá trình xử lý
   - Đảm bảo các bước trong transaction logic

### 2.2. Tài khoản dùng thử không hoạt động

#### Nguyên nhân thường gặp
1. **Không đồng bộ với hệ thống RTK**
   - Tài khoản đã tạo trong database nhưng chưa đồng bộ với RTK
   
2. **Cấu hình tài khoản không đúng**
   - Các thông số như caster, regionIds không đúng
   
3. **Lỗi khi cập nhật trạng thái**
   - Trạng thái account không được set là 'enabled'

#### Cách giải quyết
1. **Kiểm tra đồng bộ hóa**:
   ```php
   // Đảm bảo account được đồng bộ với RTK system
   $account_id = $conn->lastInsertId();
   $sync_result = syncAccountWithRtk($account_id);
   if (!$sync_result['success']) {
       throw new Exception('Lỗi đồng bộ tài khoản với RTK: ' . $sync_result['message']);
   }
   ```

2. **Kiểm tra các thông số cấu hình**:
   ```php
   // Đảm bảo các thông số đúng
   $stmt = $conn->prepare("UPDATE survey_account SET 
       caster = ?, 
       regionIds = ?, 
       customerBizType = ? 
       WHERE id = ?");
   $stmt->execute([$caster, $regionIds, $customerBizType, $account_id]);
   ```

3. **Kiểm tra trạng thái enabled**:
   ```sql
   SELECT id, username_acc, enabled FROM survey_account 
   WHERE registration_id = ? AND deleted_at IS NULL;
   ```

## 3. Vấn đề khi gia hạn tài khoản

### 3.1. Không thể chọn tài khoản để gia hạn

#### Nguyên nhân thường gặp
1. **Tài khoản không thuộc người dùng**
   - Người dùng cố gắng gia hạn tài khoản không thuộc họ
   
2. **Tài khoản đã bị xóa**
   - Tài khoản có `deleted_at` không phải NULL
   
3. **Tài khoản đã có đăng ký gia hạn pending**
   - Đã có đăng ký gia hạn đang chờ xử lý

#### Cách giải quyết
1. **Kiểm tra quyền sở hữu**:
   ```php
   // Đảm bảo tài khoản thuộc người dùng
   $stmt = $conn->prepare("SELECT sa.id FROM survey_account sa 
                          JOIN registration r ON sa.registration_id = r.id 
                          WHERE sa.id = ? AND r.user_id = ? AND sa.deleted_at IS NULL");
   $stmt->execute([$account_id, $user_id]);
   ```

2. **Kiểm tra trạng thái xóa**:
   ```sql
   SELECT id, deleted_at FROM survey_account WHERE id = ?;
   ```

3. **Kiểm tra đăng ký gia hạn pending**:
   ```sql
   SELECT ag.survey_account_id 
   FROM account_groups ag
   JOIN registration r ON ag.registration_id = r.id
   WHERE ag.survey_account_id = ? AND r.status = 'pending';
   ```

### 3.2. Lỗi khi tính thời gian gia hạn

#### Nguyên nhân thường gặp
1. **Thời gian hiện tại đã quá hạn**
   - Tài khoản đã hết hạn khi gia hạn
   
2. **Lỗi khi tính thời gian mới**
   - Không tính đúng thời gian bắt đầu của gia hạn
   
3. **Nhiều tài khoản với thời gian khác nhau**
   - Gia hạn nhiều tài khoản có thời hạn khác nhau

#### Cách giải quyết
1. **Xử lý tài khoản hết hạn**:
   ```php
   // Kiểm tra và xử lý cho tài khoản hết hạn
   $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
   $end_time = new DateTime($account['end_time']);
   
   // Nếu đã hết hạn, bắt đầu từ hiện tại
   if ($end_time < $now) {
       $start_time = $now;
   } else {
       $start_time = $end_time;
   }
   ```

2. **Tính thời gian đúng cho từng tài khoản**:
   ```php
   // Xử lý mỗi tài khoản riêng biệt
   foreach ($selected_accounts as $account_id) {
       // Lấy thông tin tài khoản
       // Tính thời gian mới dựa trên end_time hiện tại
       // ...
   }
   ```

3. **Đảm bảo xử lý nhất quán**:
   - Chọn phương pháp xử lý thống nhất (mỗi tài khoản riêng biệt hoặc cùng thời gian)
   - Làm rõ với người dùng cách tính thời gian gia hạn

## 4. Vấn đề với voucher

### 4.1. Voucher không áp dụng được

#### Nguyên nhân thường gặp
1. **Voucher không tồn tại hoặc hết hạn**
   - Mã không có trong hệ thống hoặc đã hết hạn
   
2. **Đã vượt quá số lần sử dụng cho phép**
   - Vượt quá giới hạn sử dụng
   
3. **Điều kiện áp dụng không thỏa mãn**
   - Giá trị đơn hàng dưới ngưỡng tối thiểu
   - Loại gói không thuộc diện áp dụng

#### Cách giải quyết
1. **Kiểm tra tồn tại và thời hạn**:
   ```php
   // Kiểm tra voucher có hiệu lực
   $stmt = $conn->prepare("SELECT * FROM voucher 
                          WHERE code = ? 
                          AND is_active = 1 
                          AND (valid_until IS NULL OR valid_until > NOW()) 
                          AND (valid_from IS NULL OR valid_from <= NOW())");
   $stmt->execute([$voucherCode]);
   ```

2. **Kiểm tra số lần sử dụng**:
   ```php
   // Kiểm tra giới hạn sử dụng
   if ($voucher['limit_usage'] > 0 && $voucher['usage_count'] >= $voucher['limit_usage']) {
       return ['success' => false, 'error' => 'voucher_limit_reached'];
   }
   ```

3. **Kiểm tra điều kiện áp dụng**:
   ```php
   // Kiểm tra điều kiện tối thiểu
   if ($voucher['min_order_value'] > 0 && $voucher['min_order_value'] > $orderAmount) {
       return ['success' => false, 'error' => 'min_amount_not_reached'];
   }
   ```

### 4.2. Lỗi khi tính giảm giá

#### Nguyên nhân thường gặp
1. **Lỗi tính toán giá trị giảm**
   - Không tính đúng % hoặc giá trị cố định
   
2. **Giảm giá vượt mức cho phép**
   - Giá trị giảm lớn hơn giá trị đơn hàng
   
3. **Không lưu đúng thông tin giảm giá**
   - Không cập nhật transaction với thông tin voucher

#### Cách giải quyết
1. **Kiểm tra logic tính giảm giá**:
   ```php
   // Tính giảm giá đúng theo loại voucher
   if ($voucher['discount_type'] === 'percentage') {
       $discountAmount = ($orderAmount * $voucher['discount_value']) / 100;
       if ($voucher['max_discount'] > 0) {
           $discountAmount = min($discountAmount, $voucher['max_discount']);
       }
   } else { // fixed
       $discountAmount = $voucher['discount_value'];
   }
   ```

2. **Đảm bảo không giảm quá giá đơn hàng**:
   ```php
   // Đảm bảo không giảm quá giá đơn hàng
   $discountAmount = min($discountAmount, $orderAmount);
   $newAmount = $orderAmount - $discountAmount;
   ```

3. **Cập nhật đầy đủ thông tin**:
   ```php
   // Cập nhật transaction với thông tin voucher
   $stmt = $conn->prepare("UPDATE transaction_history 
                          SET voucher_code = ?, 
                              voucher_discount = ?, 
                              final_amount = ? 
                          WHERE id = ?");
   $stmt->execute([$voucher['code'], $discountAmount, $newAmount, $transaction_id]);
   ```

## 5. Vấn đề tích hợp với RTK

### 5.1. Không tạo được tài khoản trên hệ thống RTK

#### Nguyên nhân thường gặp
1. **Lỗi kết nối API**
   - Không thể kết nối đến API RTK
   
2. **Lỗi xác thực API**
   - Token API không hợp lệ hoặc hết hạn
   
3. **Thông tin không hợp lệ**
   - Tham số gửi đến API không đúng format

#### Cách giải quyết
1. **Kiểm tra kết nối**:
   - Test kết nối API bằng tool như Postman
   - Kiểm tra logs kết nối

2. **Đảm bảo xác thực API**:
   ```php
   // Refresh token trước khi gọi API
   function ensureValidApiToken() {
       global $rtk_token, $rtk_token_expires;
       $now = time();
       if (!isset($rtk_token) || $now >= $rtk_token_expires - 60) {
           // Refresh token
           $new_token = getRtkApiToken();
           $rtk_token = $new_token['token'];
           $rtk_token_expires = $now + $new_token['expires_in'];
       }
       return $rtk_token;
   }
   ```

3. **Xác thực format dữ liệu**:
   ```php
   // Đảm bảo dữ liệu đúng format trước khi gửi
   function validateRtkAccountData($data) {
       $required = ['username', 'password', 'caster', 'regionIds'];
       foreach ($required as $field) {
           if (!isset($data[$field]) || empty($data[$field])) {
               throw new Exception("Missing required field: $field");
           }
       }
       // Kiểm tra thêm format của mỗi trường
       return true;
   }
   ```

### 5.2. Mountpoint không hiển thị đúng

#### Nguyên nhân thường gặp
1. **Chưa liên kết mountpoint với tài khoản**
   - Thiếu thông tin trong bảng liên kết
   
2. **Format JSON không đúng**
   - Lỗi khi tạo JSON từ dữ liệu mountpoint
   
3. **Tỉnh/thành không khớp với mountpoint**
   - Mountpoint không tương ứng với tỉnh/thành đã chọn

#### Cách giải quyết
1. **Kiểm tra liên kết**:
   ```sql
   SELECT * FROM account_mountpoints 
   WHERE survey_account_id = ?;
   ```

2. **Đảm bảo format JSON đúng**:
   ```php
   // Sử dụng GROUP_CONCAT với JSON_OBJECT
   $sql = "SELECT sa.id, GROUP_CONCAT(
               JSON_OBJECT(
                   'mountpoint', mp.mountpoint,
                   'ip', mp.ip,
                   'port', mp.port
               ) SEPARATOR '|'
           ) as mountpoints_json
           FROM survey_account sa
           LEFT JOIN account_mountpoints am ON sa.id = am.survey_account_id
           LEFT JOIN mountpoint mp ON am.mountpoint_id = mp.id
           WHERE sa.id = ?
           GROUP BY sa.id";
   ```

3. **Kiểm tra tương ứng tỉnh/thành và mountpoint**:
   ```sql
   SELECT mp.* FROM mountpoint mp
   JOIN mountpoint_locations ml ON mp.id = ml.mountpoint_id
   JOIN location l ON ml.location_id = l.id
   WHERE l.id = ?;
   ```
