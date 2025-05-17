# Tài liệu Hệ thống Quản lý Tài khoản RTK

## 1. Chi tiết chức năng và trang

### 1.1. Trang Quản lý Tài khoản RTK
- **Tệp tin UI**: `public/pages/rtk_accountmanagement.php`
- **Lớp xử lý**: `private/classes/RtkAccount.php`
- **Chức năng**: Hiển thị, quản lý và thao tác với các tài khoản RTK của người dùng, bao gồm xem thông tin chi tiết, đổi mật khẩu, vô hiệu hóa, xóa tài khoản, gia hạn và xuất dữ liệu.

### 1.2. Chức năng xem danh sách tài khoản
- Hiển thị danh sách tài khoản RTK với thông tin cơ bản
- Hỗ trợ phân trang và lọc theo trạng thái (tất cả, đang hoạt động, hết hạn, bị khóa)
- Hiển thị thông tin về thời gian sử dụng còn lại hoặc đã quá hạn

### 1.3. Chức năng xem thông tin chi tiết tài khoản
- Xem thông tin chi tiết từng tài khoản bao gồm tên đăng nhập, mật khẩu, thời gian bắt đầu và kết thúc
- Hiển thị danh sách các mountpoint được liên kết với tài khoản
- Xem thông tin thời gian sử dụng còn lại

### 1.4. Chức năng đổi mật khẩu tài khoản
- Cho phép người dùng thay đổi mật khẩu tài khoản RTK
- Xác thực và cập nhật mật khẩu trong cơ sở dữ liệu
- Xử lý và hiển thị thông báo thành công/thất bại

### 1.5. Chức năng vô hiệu hóa/kích hoạt tài khoản
- Cho phép người dùng tạm thời vô hiệu hóa/kích hoạt lại tài khoản
- Cập nhật trạng thái tài khoản trong cơ sở dữ liệu
- Hiển thị trạng thái hiện tại của tài khoản

### 1.6. Chức năng gia hạn tài khoản
- Chọn một hoặc nhiều tài khoản để gia hạn
- Chuyển đến trang thanh toán gia hạn với thông tin tài khoản đã chọn
- Xử lý việc loại trừ các tài khoản dùng thử không thể gia hạn

### 1.7. Chức năng xuất dữ liệu tài khoản
- Cho phép chọn và xuất thông tin tài khoản ra file Excel
- Hỗ trợ chọn tất cả hoặc chọn từng tài khoản để xuất
- Hiển thị số lượng tài khoản đã chọn

## 2. Điểm quan trọng hình thành chức năng

### 2.1. Mô hình dữ liệu và cơ sở dữ liệu

#### 2.1.1. Bảng survey_account
Lưu trữ thông tin tài khoản RTK của người dùng.

```sql
CREATE TABLE `survey_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `username_acc` varchar(100) NOT NULL,
  `password_acc` varchar(100) NOT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `concurrent_user` int(11) DEFAULT 1,
  `caster` varchar(100) DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `regionIds` varchar(255) DEFAULT NULL,
  `customerBizType` varchar(50) DEFAULT NULL,
  `area` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  CONSTRAINT `survey_account_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 2.1.2. Bảng registration
Lưu trữ thông tin đăng ký sử dụng dịch vụ liên kết với tài khoản.

```sql
CREATE TABLE `registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, active, expired, cancelled',
  `payment_status` varchar(50) NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid, partially_paid, paid, refunded',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 2.1.3. Bảng mount_point
Lưu trữ thông tin về các mount point liên kết với tài khoản.

```sql
CREATE TABLE `mount_point` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) NOT NULL,
  `mountpoint` varchar(100) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `port` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `mount_point_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2.2. Lớp RtkAccount

Lớp `RtkAccount` xử lý tất cả các tác vụ liên quan đến quản lý tài khoản RTK:

```php
class RtkAccount {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    // Lấy tài khoản của người dùng với phân trang và lọc
    public function getAccountsByUserIdWithPagination($userId, $page = 1, $perPage = 10, $filter = 'all') {
        // Xây dựng truy vấn với các điều kiện lọc
        // Thực hiện truy vấn và xử lý kết quả
        // Trả về thông tin tài khoản và thông tin phân trang
    }

    // Tính toán trạng thái tài khoản dựa trên thời hạn và trạng thái kích hoạt
    private function calculateAccountStatus($account) {
        $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $endTime = new DateTime($account['effective_end_time'], new DateTimeZone('Asia/Ho_Chi_Minh'));
        
        if ($account['enabled'] == 0) {
            return 'locked'; // Tài khoản bị khóa
        } else if ($endTime < $now) {
            return 'expired'; // Tài khoản đã hết hạn
        } else {
            return 'active'; // Tài khoản đang hoạt động
        }
    }

    // Lấy thông tin chi tiết tài khoản theo ID
    public function getAccountById($id) {
        // Truy vấn và trả về thông tin chi tiết tài khoản
    }

    // Đổi mật khẩu tài khoản
    public function changePassword($accountId, $newPassword) {
        // Cập nhật mật khẩu mới trong cơ sở dữ liệu
    }

    // Vô hiệu hóa/kích hoạt tài khoản
    public function toggleAccountStatus($accountId, $enabled) {
        // Cập nhật trạng thái kích hoạt trong cơ sở dữ liệu
    }

    // Xóa tài khoản (soft delete)
    public function deleteAccount($accountId) {
        // Đánh dấu tài khoản là đã xóa trong cơ sở dữ liệu
    }
}
```

### 2.3. Quản lý phiên và xác thực

Trang quản lý tài khoản RTK yêu cầu người dùng phải đăng nhập:

```php
// Kiểm tra xác thực người dùng
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng về login
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}
```

### 2.4. Xử lý phân trang

Xử lý phân trang cho danh sách tài khoản RTK, cho phép người dùng dễ dàng điều hướng qua các trang kết quả:

```php
// Xử lý tham số từ URL cho phân trang
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
// Chỉ cho phép các giá trị cụ thể cho per_page
if (!in_array($perPage, [10, 20, 50])) {
    $perPage = 10; // Mặc định
}
```

### 2.5. Xử lý lọc dữ liệu

Cung cấp chức năng lọc để người dùng dễ dàng tìm kiếm tài khoản theo trạng thái:

```php
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
// Chỉ cho phép các filter hợp lệ
if (!in_array($filter, ['all', 'active', 'expired', 'locked'])) {
    $filter = 'all'; // Mặc định
}
```

### 2.6. Xử lý JavaScript phía client

File JavaScript `rtk_accountmanagement.js` xử lý các tương tác phía client:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý hiển thị và đóng modal chi tiết tài khoản
    // Xử lý chọn và bỏ chọn tài khoản để xuất Excel hoặc gia hạn
    // Xử lý lọc và tìm kiếm dữ liệu
    // Xử lý sự kiện đổi mật khẩu, vô hiệu hóa, xóa tài khoản
});
```

## 3. Các luồng xử lý của chức năng

### 3.1. Luồng xem danh sách tài khoản

1. **Truy cập trang quản lý**:
   - Người dùng truy cập trang `rtk_accountmanagement.php`
   - Hệ thống kiểm tra xác thực người dùng (đã đăng nhập hay chưa)
   - Lấy các tham số phân trang và lọc từ URL

2. **Truy vấn và hiển thị dữ liệu**:
   - Hệ thống gọi `RtkAccount.getAccountsByUserIdWithPagination()` để lấy dữ liệu
   - Xử lý dữ liệu trả về, tính toán thời gian sử dụng còn lại/quá hạn
   - Hiển thị danh sách tài khoản với thông tin cơ bản

3. **Tùy chỉnh hiển thị**:
   - Người dùng có thể thay đổi số lượng tài khoản hiển thị trên mỗi trang
   - Người dùng có thể lọc tài khoản theo trạng thái
   - Hệ thống cập nhật URL và tải lại dữ liệu khi thay đổi tùy chọn

### 3.2. Luồng xem chi tiết tài khoản

1. **Mở modal chi tiết**:
   - Người dùng nhấp vào nút "Xem chi tiết" của một tài khoản
   - JavaScript bắt sự kiện và hiển thị modal với thông tin chi tiết

2. **Hiển thị thông tin**:
   - Modal hiển thị tên đăng nhập, mật khẩu, thời gian bắt đầu và kết thúc
   - Hiển thị danh sách các mountpoint với IP và cổng kết nối
   - Hiển thị thông tin thời gian sử dụng còn lại

3. **Đóng modal**:
   - Người dùng có thể đóng modal bằng cách nhấp vào nút "Đóng" hoặc bên ngoài modal

### 3.3. Luồng đổi mật khẩu tài khoản

1. **Mở form đổi mật khẩu**:
   - Người dùng nhấp vào nút "Đổi mật khẩu" của một tài khoản
   - JavaScript hiển thị form đổi mật khẩu

2. **Nhập và gửi dữ liệu**:
   - Người dùng nhập mật khẩu mới
   - Gửi dữ liệu đến server qua AJAX hoặc form submission

3. **Xử lý và cập nhật**:
   - Server xác thực và xử lý yêu cầu đổi mật khẩu
   - Gọi `RtkAccount.changePassword()` để cập nhật mật khẩu trong database
   - Trả về kết quả thành công hoặc thất bại
   - Client hiển thị thông báo phù hợp

### 3.4. Luồng vô hiệu hóa/kích hoạt tài khoản

1. **Gửi yêu cầu thay đổi trạng thái**:
   - Người dùng nhấp vào nút "Vô hiệu hóa" hoặc "Kích hoạt" tùy theo trạng thái hiện tại
   - JavaScript gửi yêu cầu đến server qua AJAX

2. **Xử lý yêu cầu**:
   - Server xác thực yêu cầu
   - Gọi `RtkAccount.toggleAccountStatus()` để cập nhật trạng thái trong database
   - Trả về kết quả xử lý

3. **Cập nhật giao diện**:
   - Client cập nhật trạng thái hiển thị trên giao diện
   - Hiển thị thông báo thành công hoặc thất bại

### 3.5. Luồng gia hạn tài khoản

1. **Chọn tài khoản để gia hạn**:
   - Người dùng chọn một hoặc nhiều tài khoản bằng cách tích vào checkbox
   - JavaScript cập nhật trạng thái nút "Gia hạn" (bật/tắt) dựa trên số lượng tài khoản đã chọn

2. **Gửi form gia hạn**:
   - Người dùng nhấp vào nút "Gia hạn"
   - JavaScript kiểm tra xem có tài khoản nào được chọn không
   - Gửi form với danh sách ID tài khoản đã chọn

3. **Chuyển hướng đến trang thanh toán gia hạn**:
   - Server nhận thông tin và lưu vào session
   - Chuyển hướng người dùng đến trang thanh toán gia hạn để hoàn tất quy trình

### 3.6. Luồng xuất dữ liệu tài khoản

1. **Chọn tài khoản để xuất**:
   - Người dùng chọn một hoặc nhiều tài khoản bằng cách tích vào checkbox
   - JavaScript cập nhật trạng thái nút "Xuất Excel" và số lượng tài khoản đã chọn

2. **Xuất dữ liệu**:
   - Người dùng nhấp vào nút "Xuất Excel"
   - JavaScript kiểm tra và gửi form chứa danh sách ID tài khoản đã chọn

3. **Tạo và tải xuống file Excel**:
   - Server xử lý yêu cầu, truy vấn thông tin tài khoản
   - Tạo file Excel với dữ liệu tài khoản
   - Gửi file Excel về client để tải xuống

## 4. Các lỗi có thể phát sinh và cách sửa

### 4.1. Lỗi hiển thị danh sách tài khoản trống

- **Triệu chứng**: Không hiển thị bất kỳ tài khoản nào, dù người dùng đã đăng ký dịch vụ.
- **Nguyên nhân**:
  - Lỗi trong truy vấn SQL
  - Tham số user_id không được truyền đúng
  - Lỗi kết nối database
- **Giải pháp**:
  - Kiểm tra lại truy vấn SQL trong phương thức `getAccountsByUserIdWithPagination()`
  - Xác nhận rằng session `user_id` đang hoạt động chính xác
  - Kiểm tra logs lỗi database
  - Thêm xử lý lỗi chi tiết hơn và ghi log

```php
// Ví dụ: Thêm xử lý lỗi và logging
try {
    $result = $rtkAccountManager->getAccountsByUserIdWithPagination($userId, $currentPage, $perPage, $filter);
    $accounts = $result['accounts'];
    $pagination = $result['pagination'];
} catch (Exception $e) {
    error_log("Error retrieving RTK accounts: " . $e->getMessage());
    $accounts = [];
    $pagination = ['total' => 0, 'per_page' => $perPage, 'current_page' => $currentPage, 'total_pages' => 0];
    $error_message = "Không thể tải danh sách tài khoản. Vui lòng thử lại sau.";
}
```

### 4.2. Lỗi phân trang

- **Triệu chứng**: Phân trang hiển thị không chính xác, thiếu trang hoặc hiển thị sai số lượng bản ghi.
- **Nguyên nhân**:
  - Lỗi tính toán số trang
  - Tham số phân trang không được xử lý đúng
  - Lỗi truy vấn LIMIT/OFFSET trong SQL
- **Giải pháp**:
  - Kiểm tra lại logic tính toán số trang
  - Đảm bảo các tham số phân trang được validate đúng
  - Kiểm tra lại truy vấn SQL, đặc biệt là phần LIMIT và OFFSET

```php
// Ví dụ: Xác thực tham số phân trang chặt chẽ hơn
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($perPage, [10, 20, 50])) {
    $perPage = 10; // Mặc định
}

// Kiểm tra logic tính tổng số trang
$totalPages = max(1, ceil($totalRecords / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages; // Điều chỉnh lại nếu vượt quá tổng số trang
}
```

### 4.3. Lỗi hiển thị thông tin mountpoint

- **Triệu chứng**: Mountpoint không hiển thị hoặc hiển thị sai trong chi tiết tài khoản.
- **Nguyên nhân**:
  - Lỗi định dạng JSON khi nhóm các mountpoint
  - Lỗi khi chuyển đổi chuỗi JSON sang mảng
  - Thiếu liên kết giữa tài khoản và mountpoint
- **Giải pháp**:
  - Kiểm tra lại truy vấn SQL và hàm GROUP_CONCAT
  - Xử lý lỗi khi phân tích chuỗi JSON
  - Kiểm tra mối quan hệ trong cơ sở dữ liệu

```php
// Ví dụ: Xử lý lỗi khi phân tích JSON mountpoints
$account['mountpoints'] = [];
if (!empty($account['mountpoints_json'])) {
    $mountpoints_array = explode('|', $account['mountpoints_json']);
    foreach ($mountpoints_array as $mp_json) {
        if ($mp_json !== 'null' && $mp_json !== null) {
            try {
                $mp_data = json_decode($mp_json, true);
                if ($mp_data && !empty($mp_data['mountpoint'])) {
                    $account['mountpoints'][] = $mp_data;
                }
            } catch (Exception $e) {
                error_log("Error decoding mountpoint JSON: " . $e->getMessage());
            }
        }
    }
}
unset($account['mountpoints_json']);
```

### 4.4. Lỗi khi đổi mật khẩu

- **Triệu chứng**: Không thể đổi mật khẩu, hiển thị thông báo lỗi hoặc không có phản hồi.
- **Nguyên nhân**:
  - Lỗi xác thực AJAX
  - Lỗi cập nhật database
  - Vấn đề về quyền truy cập
- **Giải pháp**:
  - Kiểm tra lại endpoint xử lý đổi mật khẩu
  - Thêm xác thực CSRF cho form đổi mật khẩu
  - Kiểm tra quyền của người dùng trước khi thực hiện thay đổi
  - Cải thiện xử lý lỗi và hiển thị thông báo chi tiết

```php
// Ví dụ: Cải thiện xử lý đổi mật khẩu
public function changePassword($accountId, $newPassword, $userId) {
    try {
        // Kiểm tra quyền: Người dùng chỉ có thể thay đổi mật khẩu tài khoản của họ
        $stmt = $this->conn->prepare("
            SELECT 1 FROM survey_account sa
            JOIN registration r ON sa.registration_id = r.id
            WHERE sa.id = ? AND r.user_id = ?
        ");
        $stmt->execute([$accountId, $userId]);
        if (!$stmt->fetchColumn()) {
            throw new Exception("Không có quyền thay đổi mật khẩu tài khoản này");
        }
        
        // Cập nhật mật khẩu
        $updateStmt = $this->conn->prepare("UPDATE survey_account SET password_acc = ? WHERE id = ?");
        $success = $updateStmt->execute([$newPassword, $accountId]);
        
        if (!$success) {
            throw new Exception("Không thể cập nhật mật khẩu");
        }
        
        return ['success' => true, 'message' => 'Mật khẩu đã được cập nhật thành công'];
    } catch (Exception $e) {
        error_log("Error changing password: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

### 4.5. Lỗi khi vô hiệu hóa/kích hoạt tài khoản

- **Triệu chứng**: Không thể vô hiệu hóa hoặc kích hoạt tài khoản, trạng thái không thay đổi.
- **Nguyên nhân**:
  - Lỗi xử lý AJAX
  - Lỗi cập nhật database
  - Vấn đề về quyền truy cập
- **Giải pháp**:
  - Kiểm tra lại endpoint xử lý thay đổi trạng thái
  - Thêm xác thực CSRF cho request
  - Thêm xác thực quyền truy cập
  - Cải thiện ghi log và thông báo lỗi

```php
// Ví dụ: Cải thiện xử lý vô hiệu hóa/kích hoạt tài khoản
public function toggleAccountStatus($accountId, $enabled, $userId) {
    try {
        // Kiểm tra quyền
        $stmt = $this->conn->prepare("
            SELECT 1 FROM survey_account sa
            JOIN registration r ON sa.registration_id = r.id
            WHERE sa.id = ? AND r.user_id = ?
        ");
        $stmt->execute([$accountId, $userId]);
        if (!$stmt->fetchColumn()) {
            throw new Exception("Không có quyền thay đổi trạng thái tài khoản này");
        }
        
        // Cập nhật trạng thái
        $updateStmt = $this->conn->prepare("UPDATE survey_account SET enabled = ? WHERE id = ?");
        $success = $updateStmt->execute([$enabled, $accountId]);
        
        if (!$success) {
            throw new Exception("Không thể cập nhật trạng thái tài khoản");
        }
        
        return [
            'success' => true,
            'message' => $enabled ? 'Tài khoản đã được kích hoạt' : 'Tài khoản đã bị vô hiệu hóa',
            'new_status' => $enabled ? 'active' : 'locked'
        ];
    } catch (Exception $e) {
        error_log("Error toggling account status: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

## 5. Các dự kiến phát triển trong tương lai

### 5.1. Quản lý tài khoản nâng cao

Phát triển chức năng quản lý tài khoản nâng cao:
- Tự động gia hạn khi gần hết hạn
- Cấu hình tùy chỉnh cho từng tài khoản (vùng sử dụng, giới hạn kết nối, v.v.)
- Thống kê và báo cáo sử dụng tài khoản (thời gian kết nối, dữ liệu đã sử dụng)
- Quản lý nhóm tài khoản để dễ dàng thao tác hàng loạt

### 5.2. Giao diện người dùng nâng cao

Cải thiện giao diện người dùng:
- Thêm chức năng tìm kiếm và lọc nâng cao
- Hiển thị bản đồ vị trí sử dụng tài khoản
- Giao diện thích ứng tốt hơn với thiết bị di động
- Thông báo trực quan về tình trạng tài khoản (sắp hết hạn, đang bảo trì, v.v.)

### 5.3. Hệ thống thông báo và cảnh báo

Triển khai hệ thống thông báo:
- Cảnh báo khi tài khoản sắp hết hạn qua email, SMS
- Thông báo khi có sự thay đổi trạng thái tài khoản
- Cảnh báo về việc sử dụng bất thường (nhiều kết nối cùng lúc, sử dụng ngoài vùng)
- Tùy chỉnh loại thông báo mà người dùng muốn nhận

### 5.4. Tích hợp với hệ thống giám sát

Tích hợp với hệ thống giám sát RTK:
- Hiển thị trạng thái kết nối thời gian thực
- Biểu đồ lịch sử sử dụng
- Cảnh báo khi có sự cố hệ thống ảnh hưởng đến tài khoản
- Đánh giá chất lượng kết nối và độ chính xác

### 5.5. API cho ứng dụng di động

Phát triển API để hỗ trợ ứng dụng di động:
- API để xem và quản lý tài khoản RTK từ ứng dụng di động
- Xác thực và bảo mật API
- Thông báo đẩy (push notification) về trạng thái tài khoản
- Tính năng quét mã QR để thiết lập nhanh tài khoản trên thiết bị

### 5.6. Tích hợp với hệ thống thanh toán

Cải thiện tích hợp với hệ thống thanh toán:
- Thanh toán tự động khi gia hạn
- Lịch sử thanh toán chi tiết
- Xuất hóa đơn điện tử tự động
- Thêm phương thức thanh toán mới (ví điện tử, thẻ tín dụng)
