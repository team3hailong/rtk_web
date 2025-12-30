<?php
// --- Require file config - includes path helpers ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

init_session();

// --- Use path constants defined by path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Include Database and other required files ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/SupportRequest.php';
require_once $project_root_path . '/private/utils/csrf_helper.php';

// --- Get User Data and Support Requests ---
$db = new Database();
$supportRequest = new SupportRequest($db);
$user_id = $_SESSION['user_id'];
$supportRequests = $supportRequest->getRequestsByUser($user_id);

// Get messages
$message = $_SESSION['support_message'] ?? '';
$error = $_SESSION['support_error'] ?? '';
unset($_SESSION['support_message'], $_SESSION['support_error']);

// ==========================================
// CẤU HÌNH ZALO (ĐƠN GIẢN HÓA)
// ==========================================

// 1. Link Zalo Group (Link gốc)
$zalo_link = "https://zalo.me/g/xwfsuz556";

// 2. Link dùng để Share và Copy (Chính là link gốc)
$share_link_val = $zalo_link;

// 3. Link ảnh QR Code (Tạo từ link gốc)
// Sử dụng API qrserver
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=" . urlencode($zalo_link);
// Link backup nếu cái trên lỗi
$qr_backup_url = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($zalo_link);

// --- Include Header ---
$_SESSION['base_url'] = $base_url;
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/support/contact.css">';
include $project_root_path . '/private/includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
<!-- ... (Phần Dashboard Wrapper giữ nguyên) ... -->
    <main class="content-wrapper">
        <div class="container">
            <h2 class="page-title">Hỗ trợ & Liên hệ</h2>

            <?php if ($message): ?>
                <div class="message success-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- NEW: THANH TAB CHUYỂN ĐỔI (Chỉ hiện trên Mobile do CSS quy định) -->
            <div class="mobile-tabs-nav">
                <button type="button" class="tab-btn active" onclick="switchTab('form')">
                    <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                </button>
                <button type="button" class="tab-btn" onclick="switchTab('qr')">
                    <i class="fab fa-whatsapp"></i> Chat Zalo
                </button>
                <button type="button" class="tab-btn" onclick="switchTab('history')">
                    <i class="fas fa-history"></i> Lịch sử
                </button>
            </div>

            <div class="support-grid">
                <!-- KHỐI 1: FORM -->
                <!-- Thêm class "tab-content active" và id="tab-form" -->
                <div class="form-section tab-content active" id="tab-form">
                    <h3>Gửi yêu cầu hỗ trợ</h3>
                    <form id="support-form" action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=support&action=process_support_request" method="POST">
                        <?php echo generate_csrf_input(); ?>
                        
                        <!-- ... (Nội dung Form giữ nguyên) ... -->
                        
                        <div class="form-group">
                            <label for="subject">Tiêu đề:</label>
                            <input type="text" id="subject" name="subject" required class="form-control" maxlength="100">
                            <small class="char-counter" id="subject-counter">0/100 ký tự</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Loại yêu cầu:</label>
                            <select id="category" name="category" class="form-control">
                                <option value="technical">Hỗ trợ kỹ thuật</option>
                                <option value="billing">Thanh toán/Hóa đơn</option>
                                <option value="account">Tài khoản</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Nội dung:</label>
                            <textarea id="message" name="message" rows="6" required class="form-control" maxlength="1000"></textarea>
                            <small class="char-counter" id="message-counter">0/1000 ký tự</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Gửi yêu cầu</button>
                        </div>
                    </form>
                </div>
                
                <!-- KHỐI 2: ZALO QR -->
                <!-- Thêm class "tab-content" (không có active) và id="tab-qr" -->
                <div class="qr-section tab-content" id="tab-qr">
                    <h3>Nhận hỗ trợ trực tiếp qua Zalo</h3>
                    
                    <div class="qr-wrapper">
                        <!-- Lưu ý: Nhớ dùng link ảnh nội bộ nếu bạn đã tải ảnh về như hướng dẫn trước -->
                        <img src="<?php echo $qr_api_url; ?>" 
                             alt="Zalo QR Code" 
                             class="qr-image" 
                             id="zalo-qr-img"
                             crossOrigin="anonymous"
                             onerror="this.onerror=null; this.src='https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=<?php echo urlencode($zalo_link); ?>'">
                    </div>
                    
                    <p class="qr-desc">
                        Quét mã QR để vào nhóm Zalo hỗ trợ .
                    </p>
                    
                    <input type="hidden" id="share-link-val" value="<?php echo htmlspecialchars($share_link_val); ?>">
                    
                    <div class="qr-actions">
                        <button type="button" class="btn-qr-action btn-share" id="btn-share-zalo">
                            <i class="fas fa-share-alt"></i> <span>Share</span>
                        </button>
                        <button type="button" class="btn-qr-action btn-copy" id="btn-copy-link">
                            <i class="fas fa-link"></i> <span>Copy</span>
                        </button>
                        <button type="button" class="btn-qr-action btn-download" id="btn-download-qr">
                            <i class="fas fa-download"></i> <span>Tải QR</span>
                        </button>
                    </div>
                </div>

                <!-- KHỐI 3: LỊCH SỬ (Previous Requests) -->
            <!-- Thêm class "tab-content" và id="tab-history" -->
                <div class="previous-requests-section tab-content" id="tab-history">
                <h3>Yêu cầu hỗ trợ của bạn</h3>
                <?php if (!empty($supportRequests)): ?>
                    <div class="requests-table-container">
                        <!-- ... (Giữ nguyên Table code) ... -->
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Loại</th> <!-- Rút gọn chữ cho mobile đỡ vỡ -->
                                    <th>Ngày</th>
                                    <th>Trạng thái</th>
                                    <th>Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supportRequests as $request): ?>
                                    <!-- ... (Giữ nguyên nội dung vòng lặp) ... -->
                                     <?php 
                                        $status_class = ''; $status_text = '';
                                        switch ($request['status']) {
                                            case 'pending': $status_class = 'status-pending'; $status_text = 'Chờ xử lý'; break;
                                            case 'in_progress': $status_class = 'status-progress'; $status_text = 'Đang xử lý'; break;
                                            case 'resolved': $status_class = 'status-resolved'; $status_text = 'Đã giải quyết'; break;
                                            case 'closed': $status_class = 'status-closed'; $status_text = 'Đã đóng'; break;
                                            default: $status_class = 'status-pending'; $status_text = 'Chờ xử lý';
                                        }
                                        $category_text = '';
                                        switch ($request['category']) {
                                            case 'technical': $category_text = 'Kỹ thuật'; break;
                                            case 'billing': $category_text = 'Thanh toán'; break;
                                            case 'account': $category_text = 'Tài khoản'; break;
                                            default: $category_text = 'Khác';
                                        }
                                        $created_date = new DateTime($request['created_at']);
                                        $formatted_date = $created_date->format('d/m/Y H:i');
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($category_text); ?></td>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span></td>
                                        <td>
                                            <button type="button" class="btn-view-details" 
                                                    data-request='<?php echo htmlspecialchars(json_encode($request), ENT_QUOTES, 'UTF-8'); ?>'
                                                    data-status-text="<?php echo htmlspecialchars($status_text); ?>"
                                                    data-category-text="<?php echo htmlspecialchars($category_text); ?>">
                                                <i class="fas fa-eye"></i> Xem
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-requests">Bạn chưa có yêu cầu hỗ trợ nào.</p>
                <?php endif; ?>
            </div>

            </div>
        </div>
    </main>
</div>

<!-- Modal (Giữ nguyên) -->
<div id="request-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="modal-title">Chi Tiết Yêu Cầu Hỗ Trợ</h4>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="request-details">
                <div class="detail-item">
                    <div class="detail-label">Tiêu đề:</div>
                    <div class="detail-value" id="modal-subject"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Loại yêu cầu:</div>
                    <div class="detail-value" id="modal-category"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Ngày gửi:</div>
                    <div class="detail-value" id="modal-created"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Trạng thái:</div>
                    <div class="detail-value" id="modal-status"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Nội dung:</div>
                    <div class="detail-value message-content" id="modal-message"></div>
                </div>
                <div class="detail-item" id="response-container">
                    <div class="detail-label">Phản hồi:</div>
                    <div class="detail-value message-content" id="modal-response"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $base_url; ?>/public/assets/js/pages/support/contact.js"></script>

<?php
include $project_root_path . '/private/includes/footer.php';
$db->close();
?>