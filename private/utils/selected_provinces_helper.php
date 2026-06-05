<?php
/**
 * Helper functions để làm việc với selected_provinces
 * 
 * File này cung cấp các hàm tiện ích để xử lý dữ liệu multiple provinces
 * trong bảng registration
 */

/**
 * Lấy danh sách location IDs từ selected_provinces JSON
 * 
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @return array Mảng các location_id (integer)
 */
function get_selected_province_ids($selected_provinces_json) {
    if (empty($selected_provinces_json)) {
        return [];
    }
    
    $ids = json_decode($selected_provinces_json, true);
    
    if (!is_array($ids)) {
        return [];
    }
    
    // Đảm bảo tất cả đều là số nguyên
    return array_map('intval', $ids);
}

/**
 * Lấy thông tin chi tiết các tỉnh từ selected_provinces
 * 
 * @param PDO $pdo Database connection
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @return array Mảng các object tỉnh với thông tin đầy đủ
 */
function get_selected_provinces_details($pdo, $selected_provinces_json) {
    $ids = get_selected_province_ids($selected_provinces_json);
    
    if (empty($ids)) {
        return [];
    }
    
    // Tạo placeholders cho IN clause
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $sql = "SELECT id, province, province_code, lat, lon 
            FROM location 
            WHERE id IN ($placeholders)
            ORDER BY FIELD(id, $placeholders)"; // Giữ đúng thứ tự đã chọn
    
    $stmt = $pdo->prepare($sql);
    // Bind parameters 2 lần: 1 cho WHERE IN, 1 cho ORDER BY FIELD
    $params = array_merge($ids, $ids);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy tên các tỉnh dưới dạng string
 * 
 * @param PDO $pdo Database connection
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @param string $separator Ký tự phân cách (mặc định: ", ")
 * @return string Chuỗi tên các tỉnh được nối với nhau
 */
function get_selected_provinces_names($pdo, $selected_provinces_json, $separator = ', ') {
    $provinces = get_selected_provinces_details($pdo, $selected_provinces_json);
    
    if (empty($provinces)) {
        return '';
    }
    
    $names = array_column($provinces, 'province');
    return implode($separator, $names);
}

/**
 * Lấy province codes của các tỉnh đã chọn
 * 
 * @param PDO $pdo Database connection
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @return array Mảng các province_code
 */
function get_selected_provinces_codes($pdo, $selected_provinces_json) {
    $provinces = get_selected_provinces_details($pdo, $selected_provinces_json);
    
    if (empty($provinces)) {
        return [];
    }
    
    return array_column($provinces, 'province_code');
}

/**
 * Kiểm tra xem một location_id có trong danh sách selected_provinces không
 * 
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @param int $location_id Location ID cần kiểm tra
 * @return bool True nếu có, false nếu không
 */
function is_province_selected($selected_provinces_json, $location_id) {
    $ids = get_selected_province_ids($selected_provinces_json);
    return in_array((int)$location_id, $ids, true);
}

/**
 * Thêm một location_id vào selected_provinces
 * 
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @param int $location_id Location ID cần thêm
 * @return string JSON string mới
 */
function add_province_to_selected($selected_provinces_json, $location_id) {
    $ids = get_selected_province_ids($selected_provinces_json);
    
    $location_id = (int)$location_id;
    
    // Kiểm tra xem đã có chưa
    if (!in_array($location_id, $ids, true)) {
        $ids[] = $location_id;
    }
    
    return json_encode($ids);
}

/**
 * Xóa một location_id khỏi selected_provinces
 * 
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @param int $location_id Location ID cần xóa
 * @return string JSON string mới
 */
function remove_province_from_selected($selected_provinces_json, $location_id) {
    $ids = get_selected_province_ids($selected_provinces_json);
    
    $location_id = (int)$location_id;
    
    // Lọc bỏ location_id
    $ids = array_filter($ids, function($id) use ($location_id) {
        return $id !== $location_id;
    });
    
    // Reset array keys
    $ids = array_values($ids);
    
    return json_encode($ids);
}

/**
 * Lấy thông tin tỉnh chính (tỉnh đầu tiên)
 * 
 * @param PDO $pdo Database connection
 * @param int $location_id Location ID chính (cột location_id)
 * @return array|null Thông tin tỉnh chính hoặc null
 */
function get_primary_province($pdo, $location_id) {
    if (empty($location_id)) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, province, province_code, lat, lon FROM location WHERE id = ?");
    $stmt->execute([(int)$location_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Validate selected_provinces JSON
 * 
 * @param string|null $selected_provinces_json JSON string cần validate
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validate_selected_provinces($selected_provinces_json) {
    if (empty($selected_provinces_json)) {
        return ['valid' => false, 'error' => 'Selected provinces is empty'];
    }
    
    $ids = json_decode($selected_provinces_json, true);
    
    if ($ids === null && json_last_error() !== JSON_ERROR_NONE) {
        return ['valid' => false, 'error' => 'Invalid JSON format: ' . json_last_error_msg()];
    }
    
    if (!is_array($ids)) {
        return ['valid' => false, 'error' => 'Selected provinces must be an array'];
    }
    
    if (empty($ids)) {
        return ['valid' => false, 'error' => 'Selected provinces array is empty'];
    }
    
    foreach ($ids as $id) {
        if (!is_numeric($id) || $id <= 0) {
            return ['valid' => false, 'error' => 'Invalid location ID: ' . $id];
        }
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Format danh sách tỉnh để hiển thị (với tỉnh chính được đánh dấu)
 * 
 * @param PDO $pdo Database connection
 * @param int $location_id Location ID chính
 * @param string|null $selected_provinces_json JSON string chứa array các location_id
 * @param string $separator Ký tự phân cách
 * @return string HTML string
 */
function format_provinces_display($pdo, $location_id, $selected_provinces_json, $separator = ', ') {
    $provinces = get_selected_provinces_details($pdo, $selected_provinces_json);
    
    if (empty($provinces)) {
        return '<span style="color: #999;">Chưa chọn tỉnh</span>';
    }
    
    $formatted = [];
    foreach ($provinces as $province) {
        if ((int)$province['id'] === (int)$location_id) {
            // Tỉnh chính - đánh dấu bằng in đậm
            $formatted[] = '<strong>' . htmlspecialchars($province['province']) . '</strong> (Chính)';
        } else {
            $formatted[] = htmlspecialchars($province['province']);
        }
    }
    
    return implode($separator, $formatted);
}

/**
 * Tạo selected_provinces JSON từ array location IDs
 * 
 * @param array $location_ids Mảng các location_id
 * @return string JSON string
 */
function create_selected_provinces_json($location_ids) {
    if (empty($location_ids) || !is_array($location_ids)) {
        return json_encode([]);
    }
    
    // Lọc và chuyển về integer, loại bỏ giá trị không hợp lệ
    $ids = array_filter(
        array_map('intval', $location_ids),
        function($id) { return $id > 0; }
    );
    
    // Reset array keys
    $ids = array_values($ids);
    
    return json_encode($ids);
}
