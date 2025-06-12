<?php
session_start();

// --- Require file config - includes path helpers ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

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
$conn = $db->getConnection();
$supportRequest = new SupportRequest($db);
$user_id = $_SESSION['user_id'];

// Fetch company information
$companyInfo = $supportRequest->getCompanyInfo();

// Fetch user's previous support requests
$supportRequests = $supportRequest->getRequestsByUser($user_id);

// Get messages from session (if any)
$message = $_SESSION['support_message'] ?? '';
$error = $_SESSION['support_error'] ?? '';
unset($_SESSION['support_message'], $_SESSION['support_error']);

// --- Include Header ---
$_SESSION['base_url'] = $base_url;
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/support/contact.css">';
include $project_root_path . '/private/includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <div class="container">
            <h2 class="page-title">Hỗ trợ & Liên hệ</h2>

            <?php if ($message): ?>
                <div class="message success-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="support-grid">
                <!-- Support Request Form -->
                <div class="form-section">
                    <h3>Gửi yêu cầu hỗ trợ</h3>
                    <form id="support-form" action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=support&action=process_support_request" method="POST">
                        <?php 
                        // Add CSRF token to form
                        echo generate_csrf_input();
                        ?>
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
                
                <!-- Company Information -->
                <!--
                <div class="company-info-section">
                    <h3>Thông tin công ty</h3>
                    <?php if ($companyInfo): ?>
                        <div class="company-profile">
                            <div class="company-header">
                                <h4><?php echo htmlspecialchars($companyInfo['name']); ?></h4>
                                <?php if (!empty($companyInfo['description'])): ?>
                                    <p class="company-description"><?php echo nl2br(htmlspecialchars($companyInfo['description'])); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="contact-details">
                                <?php
                                $addressesJson = $companyInfo['address'] ?? null;
                                if ($addressesJson) {
                                    $addresses = json_decode($addressesJson, true);
                                    if (is_array($addresses) && !empty($addresses)) {
                                        foreach ($addresses as $addressEntry) {
                                            $typeText = '';
                                            if (isset($addressEntry['type'])) {
                                                if ($addressEntry['type'] === 'trụ sở') {
                                                    $typeText = 'Trụ sở: ';
                                                } elseif ($addressEntry['type'] === 'chi nhánh') {
                                                    $typeText = 'Chi nhánh: ';
                                                }
                                            }
                                            $locationText = isset($addressEntry['location']) ? htmlspecialchars($addressEntry['location']) : 'N/A';
                                ?>
                                <div class="contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><strong><?php echo $typeText; ?></strong><?php echo $locationText; ?></span>
                                </div>
                                <?php
                                        }
                                    } elseif (!is_array($addresses) && !empty($companyInfo['address'])) {
                                        // Fallback for plain text address if not JSON or empty JSON
                                ?>
                                <div class="contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($companyInfo['address']); ?></span>
                                </div>
                                <?php
                                    }
                                }
                                ?>
                                
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($companyInfo['phone']); ?></span>
                                </div>
                                
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($companyInfo['email']); ?></span>
                                </div>
                                
                                <?php if (!empty($companyInfo['website'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-globe"></i>
                                        <span><a href="<?php echo htmlspecialchars($companyInfo['website']); ?>" target="_blank"><?php echo htmlspecialchars($companyInfo['website']); ?></a></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($companyInfo['tax_code'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-file-invoice"></i>
                                        <span>Mã số thuế: <?php echo htmlspecialchars($companyInfo['tax_code']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($companyInfo['working_hours'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($companyInfo['working_hours']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Không có thông tin công ty.</p>
                    <?php endif; ?>
                </div>
                -->
            </div>

            <!-- Previous Support Requests -->
            <div class="previous-requests-section">
                <h3>Yêu cầu hỗ trợ của bạn</h3>
                <?php if (!empty($supportRequests)): ?>
                    <div class="requests-table-container">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Loại yêu cầu</th>
                                    <th>Ngày gửi</th>
                                    <th>Trạng thái</th>
                                    <th>Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supportRequests as $request): ?>
                                    <?php 
                                        // Define status classes and translations
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch ($request['status']) {
                                            case 'pending':
                                                $status_class = 'status-pending';
                                                $status_text = 'Chờ xử lý';
                                                break;
                                            case 'in_progress':
                                                $status_class = 'status-progress';
                                                $status_text = 'Đang xử lý';
                                                break;
                                            case 'resolved':
                                                $status_class = 'status-resolved';
                                                $status_text = 'Đã giải quyết';
                                                break;
                                            case 'closed':
                                                $status_class = 'status-closed';
                                                $status_text = 'Đã đóng';
                                                break;
                                            default:
                                                $status_class = 'status-pending';
                                                $status_text = 'Chờ xử lý';
                                        }
                                        
                                        // Translate category
                                        $category_text = '';
                                        switch ($request['category']) {
                                            case 'technical':
                                                $category_text = 'Kỹ thuật';
                                                break;
                                            case 'billing':
                                                $category_text = 'Thanh toán';
                                                break;
                                            case 'account':
                                                $category_text = 'Tài khoản';
                                                break;
                                            default:
                                                $category_text = 'Khác';
                                        }
                                        
                                        // Format date
                                        $created_date = new DateTime($request['created_at']);
                                        $formatted_date = $created_date->format('d/m/Y H:i');
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($category_text); ?></td>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($status_text); ?>
                                            </span>
                                        </td>
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
    </main>
</div>

<!-- Modal for Request Details -->
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

<!-- Include JavaScript file -->
<script src="<?php echo $base_url; ?>/public/assets/js/pages/support/contact.js"></script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';

// Close database connection at the end of the file
$db->close();
?>