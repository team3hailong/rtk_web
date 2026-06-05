<?php
/**
 * VÍ DỤ: Cách sử dụng selected_provinces_helper.php
 * 
 * File này chứa các ví dụ về cách sử dụng các hàm helper
 * để làm việc với selected_provinces
 */

// Include helper file
require_once __DIR__ . '/../../private/utils/selected_provinces_helper.php';
require_once __DIR__ . '/../../private/classes/Database.php';

// Kết nối database
$db = new Database();
$pdo = $db->getConnection();

// ============================================
// VÍ DỤ 1: Lấy thông tin registration và hiển thị các tỉnh đã chọn
// ============================================
function example_display_selected_provinces($pdo, $registration_id) {
    // Lấy thông tin registration
    $stmt = $pdo->prepare("SELECT location_id, selected_provinces FROM registration WHERE id = ?");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        echo "Registration not found!\n";
        return;
    }
    
    echo "<h3>Thông tin các tỉnh đã chọn:</h3>\n";
    
    // 1. Lấy tỉnh chính
    $primary_province = get_primary_province($pdo, $registration['location_id']);
    echo "<p><strong>Tỉnh chính:</strong> " . htmlspecialchars($primary_province['province']) . "</p>\n";
    
    // 2. Lấy tất cả các tỉnh đã chọn
    $provinces = get_selected_provinces_details($pdo, $registration['selected_provinces']);
    echo "<p><strong>Tất cả các tỉnh:</strong></p>\n";
    echo "<ul>\n";
    foreach ($provinces as $province) {
        $isPrimary = ((int)$province['id'] === (int)$registration['location_id']) ? ' (Chính)' : '';
        echo "  <li>" . htmlspecialchars($province['province']) . $isPrimary . "</li>\n";
    }
    echo "</ul>\n";
    
    // 3. Hiển thị dạng string
    $province_names = get_selected_provinces_names($pdo, $registration['selected_provinces']);
    echo "<p><strong>Tên các tỉnh (string):</strong> $province_names</p>\n";
    
    // 4. Hiển thị dạng formatted HTML
    $formatted = format_provinces_display($pdo, $registration['location_id'], $registration['selected_provinces']);
    echo "<p><strong>Formatted:</strong> $formatted</p>\n";
}

// ============================================
// VÍ DỤ 2: Kiểm tra xem một tỉnh có được chọn không
// ============================================
function example_check_province_selected($registration_selected_provinces, $location_id_to_check) {
    $is_selected = is_province_selected($registration_selected_provinces, $location_id_to_check);
    
    if ($is_selected) {
        echo "Tỉnh ID $location_id_to_check đã được chọn.\n";
    } else {
        echo "Tỉnh ID $location_id_to_check CHƯA được chọn.\n";
    }
}

// ============================================
// VÍ DỤ 3: Thêm hoặc xóa tỉnh khỏi selected_provinces
// ============================================
function example_add_remove_province($pdo, $registration_id) {
    // Lấy registration hiện tại
    $stmt = $pdo->prepare("SELECT selected_provinces FROM registration WHERE id = ?");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $current_provinces = $registration['selected_provinces'];
    
    echo "Trước khi thay đổi: $current_provinces\n";
    
    // Thêm tỉnh ID 50 (TP.HCM)
    $updated_provinces = add_province_to_selected($current_provinces, 50);
    echo "Sau khi thêm tỉnh 50: $updated_provinces\n";
    
    // Xóa tỉnh ID 30
    $updated_provinces = remove_province_from_selected($updated_provinces, 30);
    echo "Sau khi xóa tỉnh 30: $updated_provinces\n";
    
    // Cập nhật vào database
    $update_stmt = $pdo->prepare("UPDATE registration SET selected_provinces = ? WHERE id = ?");
    $update_stmt->execute([$updated_provinces, $registration_id]);
    
    echo "Đã cập nhật vào database!\n";
}

// ============================================
// VÍ DỤ 4: Validate selected_provinces trước khi lưu
// ============================================
function example_validate_selected_provinces($selected_provinces_json) {
    $validation = validate_selected_provinces($selected_provinces_json);
    
    if ($validation['valid']) {
        echo "✓ Selected provinces hợp lệ!\n";
        return true;
    } else {
        echo "✗ Selected provinces không hợp lệ: " . $validation['error'] . "\n";
        return false;
    }
}

// ============================================
// VÍ DỤ 5: Tạo selected_provinces từ form POST
// ============================================
function example_process_form_submission() {
    // Giả sử nhận được từ form
    $_POST['location_id'] = [21, 30, 40, 42]; // Hà Nội, Hải Phòng, Nghệ An, Đà Nẵng
    
    // Lọc và validate
    $location_ids = isset($_POST['location_id']) && is_array($_POST['location_id']) 
        ? $_POST['location_id'] 
        : [];
    
    $location_ids = array_filter(
        array_map('intval', $location_ids),
        function($id) { return $id > 0; }
    );
    
    if (empty($location_ids)) {
        echo "Lỗi: Phải chọn ít nhất 1 tỉnh!\n";
        return;
    }
    
    // Tạo JSON
    $selected_provinces_json = create_selected_provinces_json($location_ids);
    
    // Tỉnh đầu tiên là tỉnh chính
    $location_id = $location_ids[0];
    
    echo "Location ID (tỉnh chính): $location_id\n";
    echo "Selected provinces JSON: $selected_provinces_json\n";
    
    // Validate
    if (example_validate_selected_provinces($selected_provinces_json)) {
        echo "✓ Sẵn sàng lưu vào database!\n";
        
        // INSERT hoặc UPDATE vào database
        // $stmt = $pdo->prepare("INSERT INTO registration (..., location_id, selected_provinces, ...) VALUES (..., ?, ?, ...)");
        // $stmt->execute([..., $location_id, $selected_provinces_json, ...]);
    }
}

// ============================================
// VÍ DỤ 6: Lấy province codes để tạo mountpoint
// ============================================
function example_create_mountpoints($pdo, $registration_id) {
    // Lấy thông tin registration
    $stmt = $pdo->prepare("SELECT selected_provinces FROM registration WHERE id = ?");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy province codes
    $province_codes = get_selected_provinces_codes($pdo, $registration['selected_provinces']);
    
    echo "Cần tạo mountpoint cho các province codes:\n";
    foreach ($province_codes as $code) {
        echo "  - $code\n";
        // Tạo mountpoint ở đây
        // create_mountpoint_for_province($registration_id, $code);
    }
}

// ============================================
// VÍ DỤ 7: Hiển thị bảng danh sách registration với các tỉnh
// ============================================
function example_display_registrations_table($pdo) {
    // Lấy tất cả registration
    $stmt = $pdo->query("
        SELECT r.id, r.user_id, r.location_id, r.selected_provinces, u.username
        FROM registration r
        LEFT JOIN user u ON r.user_id = u.id
        ORDER BY r.id DESC
        LIMIT 10
    ");
    
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>\n";
    echo "<tr><th>ID</th><th>Username</th><th>Tỉnh chính</th><th>Tất cả các tỉnh</th></tr>\n";
    
    foreach ($registrations as $reg) {
        $primary = get_primary_province($pdo, $reg['location_id']);
        $all_provinces = get_selected_provinces_names($pdo, $reg['selected_provinces']);
        
        echo "<tr>";
        echo "<td>" . $reg['id'] . "</td>";
        echo "<td>" . htmlspecialchars($reg['username']) . "</td>";
        echo "<td>" . htmlspecialchars($primary['province']) . "</td>";
        echo "<td>" . htmlspecialchars($all_provinces) . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

// ============================================
// VÍ DỤ 8: Query tất cả registration của một tỉnh cụ thể
// ============================================
function example_find_registrations_by_province($pdo, $location_id) {
    // Tìm tất cả registration có chọn tỉnh này
    $stmt = $pdo->prepare("
        SELECT r.id, r.user_id, u.username, r.selected_provinces
        FROM registration r
        LEFT JOIN user u ON r.user_id = u.id
        WHERE JSON_CONTAINS(r.selected_provinces, ?, '$')
    ");
    
    $stmt->execute([json_encode($location_id)]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Có " . count($registrations) . " registration chọn tỉnh ID $location_id:\n";
    
    foreach ($registrations as $reg) {
        echo "  - Registration #{$reg['id']} - User: {$reg['username']}\n";
    }
}

// ============================================
// CHẠY CÁC VÍ DỤ
// ============================================

// Uncomment để test từng ví dụ:

// echo "<h2>Ví dụ 1: Hiển thị các tỉnh đã chọn</h2>\n";
// example_display_selected_provinces($pdo, 1);

// echo "<h2>Ví dụ 2: Kiểm tra tỉnh đã chọn</h2>\n";
// example_check_province_selected('[21, 30, 40]', 30); // Should return true
// example_check_province_selected('[21, 30, 40]', 50); // Should return false

// echo "<h2>Ví dụ 3: Thêm/Xóa tỉnh</h2>\n";
// example_add_remove_province($pdo, 1);

// echo "<h2>Ví dụ 4: Validate JSON</h2>\n";
// example_validate_selected_provinces('[21, 30, 40]'); // Valid
// example_validate_selected_provinces('[21, -5, 40]'); // Invalid
// example_validate_selected_provinces('not-json'); // Invalid

// echo "<h2>Ví dụ 5: Xử lý form submission</h2>\n";
// example_process_form_submission();

// echo "<h2>Ví dụ 6: Tạo mountpoints</h2>\n";
// example_create_mountpoints($pdo, 1);

// echo "<h2>Ví dụ 7: Hiển thị bảng</h2>\n";
// example_display_registrations_table($pdo);

// echo "<h2>Ví dụ 8: Tìm registration theo tỉnh</h2>\n";
// example_find_registrations_by_province($pdo, 21); // Tìm tất cả chọn Hà Nội

?>
