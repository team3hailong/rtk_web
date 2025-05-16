<?php
class Dashboard {
    private $pdo;
    private $user_id;
    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
    }

    // 1. Đếm số lượng tài khoản survey_account của user hiện tại
    public function getSurveyAccountCount() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM survey_account sa INNER JOIN registration r ON sa.registration_id = r.id WHERE r.user_id = :user_id");
        $stmt->execute(['user_id' => $this->user_id]);
        return (int)$stmt->fetchColumn();
    }

    // 2. Đếm số giao dịch đang xử lý
    public function getPendingTransactions() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM transaction_history WHERE user_id = :user_id AND status = 'pending'");
        $stmt->execute(['user_id' => $this->user_id]);
        return (int)$stmt->fetchColumn();
    }

    // 3. Đếm số cộng tác viên đã duyệt (toàn hệ thống)
    public function getApprovedCollaborators() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM collaborator WHERE status = 'approved'");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // 4. Lấy hoạt động gần đây (loại trừ auth, dịch tiếng Việt, parse new_values)
    public function getRecentActivities($limit = 5) {
        $stmt = $this->pdo->prepare("SELECT action, entity_type, entity_id, new_values, created_at FROM activity_logs WHERE user_id = :user_id AND NOT (entity_type = 'user' AND (action = 'login' OR action = 'logout')) ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $row['description'] = $this->buildActivityDescription($row);
            $result[] = $row;
        }
        return $result;
    }

    // Helper: Dịch và mô tả hoạt động
    private function buildActivityDescription($activity) {
        $action_vi = $this->translateAction($activity['action']);
        $entity_vi = $this->translateEntity($activity['entity_type']);
        $desc = '';

        // Hiển thị ngắn gọn theo loại hoạt động
        if ($activity['action'] === 'referral' && isset($activity['new_values'])) {
            $json = json_decode($activity['new_values'], true);
            if (isset($json['referrer_name'])) {
                $desc = $json['referrer_name'] . ' vừa đăng ký theo link giới thiệu của bạn.';
            }
        } elseif ($activity['action'] === 'purchase' && $activity['entity_type'] === 'registration') {
            $desc = 'Một giao dịch vừa được thực hiện.';
        } else {
            $desc = $action_vi . ' ' . $entity_vi;
        }

        // Thêm nút xem chi tiết nếu có new_values
        if (!empty($activity['new_values'])) {
            $desc .= ' <button class="btn-view-details" data-new-values="' . htmlspecialchars($activity['new_values'], ENT_QUOTES, 'UTF-8') . '">Xem chi tiết</button>';
        }

        return $desc;
    }

    private function translateValue($key, $value) {
        $translations = [
            'package' => [
                'Gói 6 Tháng' => 'Gói 6 Tháng',
                // Thêm các gói khác nếu cần
            ],
            'location' => [
                'Yên Bái' => 'Yên Bái',
                // Thêm các địa điểm khác nếu cần
            ]
        ];
        if (isset($translations[$key]) && isset($translations[$key][$value])) {
            return $translations[$key][$value];
        }
        return $value;
    }

    private function translateAction($action) {
        $map = [
            'create' => 'Tạo',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'login' => 'Đăng nhập',
            'logout' => 'Đăng xuất',
            // Thêm các action khác nếu cần
        ];
        return $map[$action] ?? ucfirst($action);
    }
    private function translateEntity($entity) {
        $map = [
            'user' => 'người dùng',
            'profile' => 'hồ sơ',
            'survey_account' => 'tài khoản đo đạc',
            'transaction' => 'giao dịch',
            // Thêm các entity khác nếu cần
        ];
        return $map[$entity] ?? $entity;
    }
    private function translateField($field) {
        $map = [
            'username' => 'Tên đăng nhập',
            'email' => 'Email',
            'status' => 'Trạng thái',
            // Thêm các field khác nếu cần
        ];
        return $map[$field] ?? $field;
    }
}
