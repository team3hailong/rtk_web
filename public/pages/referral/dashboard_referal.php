<?php
// Start session and include configuration
require_once dirname(__DIR__, 3) . '/private/config/config.php';
init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . $base_path . "/pages/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Initialize necessary classes
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Referral.php';

$db = new Database();
$referralService = new Referral($db);

// Get user's referral code and link
$referralCodeResult = $referralService->getUserReferralCode($user_id);
$referralCode = $referralCodeResult['success'] ? $referralCodeResult['code'] : '';
$referralLink = $referralCode ? ($base_path . '/pages/auth/register.php?ref=' . $referralCode) : '';

// Get other referral data
$referredUsers = $referralService->getReferredUsers($user_id);
$totalCommissionEarned = $referralService->getTotalCommissionEarned($user_id);
$totalCommissionPaid = $referralService->getTotalCommissionPaid($user_id);
$pendingWithdrawals = $referralService->getTotalPendingWithdrawals($user_id);
$availableBalance = $totalCommissionEarned - $totalCommissionPaid - $pendingWithdrawals;
$withdrawalHistory = $referralService->getWithdrawalHistory($user_id);
$commissionTransactions = $referralService->getCommissionTransactions($user_id);
$monthlyRankings = $referralService->getMonthlyRankings(10);
$totalRankings = $referralService->getTotalRankings(10);
$userRankings = $referralService->getUserRankings($user_id);

// Include header and sidebar
$page_title = "Quản lý giới thiệu";
require_once $project_root_path . '/private/includes/header.php';
?>

<!-- Sử dụng CSS mới để đồng bộ giao diện -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/referral/dashboard_referal.css">

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <div class="content-wrapper referral-content-wrapper">
        <h2 class="text-2xl font-semibold mb-5">Chương trình giới thiệu</h2>
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-tabs card-header-tabs mb-0" id="referralTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="referral-link-tab" data-toggle="tab" href="#referral-link" role="tab">Liên kết giới thiệu</a></li>
                    <li class="nav-item"><a class="nav-link" id="referred-users-tab" data-toggle="tab" href="#referred-users" role="tab">Người đã giới thiệu</a></li>
                    <?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
                    <li class="nav-item"><a class="nav-link" id="commission-tab" data-toggle="tab" href="#commission" role="tab">Hoa hồng</a></li>
                    <li class="nav-item"><a class="nav-link" id="withdrawal-tab" data-toggle="tab" href="#withdrawal" role="tab">Rút tiền</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" id="ranking-tab" data-toggle="tab" href="#ranking" role="tab">Bảng xếp hạng</a></li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="referralTabsContent">
                    <!-- Tab 1: Referral Link (Tối ưu hóa với QR Code) -->
                    <div class="tab-pane fade show active" id="referral-link" role="tabpanel">
                        <h4 class="mb-3">Liên kết giới thiệu của bạn</h4>
                        <div class="alert alert-info">
                            <?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
                            <strong>Chính sách hoa hồng:</strong> Bạn sẽ nhận được 5% giá trị thanh toán từ người dùng mà bạn giới thiệu.
                            <?php else: ?>
                            <strong>Giới thiệu bạn bè:</strong> Chia sẻ liên kết của bạn để mời bạn bè đăng ký tài khoản RTK miễn phí.
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($referralCode): ?>
                            <div class="referral-container">
                                <!-- Cột bên trái: Link và Mã -->
                                <div class="referral-links-column">
                                    <div class="form-group">
                                        <label>Mã giới thiệu của bạn:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="referral-code" value="<?php echo htmlspecialchars($referralCode); ?>" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('referral-code', 'Mã giới thiệu')"><i class="fas fa-copy"></i> Sao chép</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Liên kết giới thiệu:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="referral-link-input" value="<?php echo htmlspecialchars($referralLink); ?>" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('referral-link-input', 'Liên kết giới thiệu')"><i class="fas fa-copy"></i> Sao chép</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h5>Cách giới thiệu:</h5>
                                        <ol>
                                            <li>Sao chép liên kết hoặc mã giới thiệu của bạn.</li>
                                            <li>Chia sẻ cho bạn bè, đồng nghiệp hoặc người quen.</li>
                                            <li>Khi họ đăng ký và sử dụng dịch vụ, bạn đã giúp họ truy cập tài khoản RTK!</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- Cột bên phải: QR Code -->
                                <div class="referral-qr-column">
                                    <h5>Chia sẻ mã QR</h5>
                                    <div id="qrcode-container">
                                        <canvas id="qrcode"></canvas>
                                    </div>
                                    <button id="download-qr-btn" class="btn btn-primary mt-3">
                                        <i class="fas fa-download"></i> Tải xuống QR
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Không thể tạo mã giới thiệu. Vui lòng thử lại sau hoặc liên hệ với quản trị viên.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tab 2: Referred Users -->
                    <div class="tab-pane fade" id="referred-users" role="tabpanel">
                        <h4 class="mb-4">Danh sách người dùng đã giới thiệu</h4>
                        <?php if (!empty($referredUsers)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Tên người dùng</th>
                                            <th>Email</th>
                                            <th>Ngày đăng ký</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($referredUsers as $index => $user): ?>
                                            <tr>
                                                <td data-label="STT"><?php echo $index + 1; ?></td>
                                                <td data-label="Tên người dùng"><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td data-label="Ngày đăng ký"><?php echo date('d/m/Y H:i', strtotime($user['referred_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Bạn chưa giới thiệu người dùng nào.</div>
                        <?php endif; ?>
                    </div>

                    <?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
                    <!-- Tab 3: Commission -->
                    <div class="tab-pane fade" id="commission" role="tabpanel">
                        <h4 class="mb-4">Hoa hồng nhận được</h4>
                        <div class="row mb-4">
                            <div class="col-md-4"><div class="card bg-primary text-white"><div class="card-body"><h5 class="card-title">Tổng đã kiếm</h5><h2><?php echo number_format($totalCommissionEarned, 0, ',', '.'); ?> VNĐ</h2></div></div></div>
                            <div class="col-md-4"><div class="card bg-success text-white"><div class="card-body"><h5 class="card-title">Số dư khả dụng</h5><h2><?php echo number_format($availableBalance, 0, ',', '.'); ?> VNĐ</h2></div></div></div>
                            <div class="col-md-4"><div class="card bg-info text-white"><div class="card-body"><h5 class="card-title">Đã rút</h5><h2><?php echo number_format($totalCommissionPaid, 0, ',', '.'); ?> VNĐ</h2></div></div></div>
                        </div>
                        <?php if (!empty($commissionTransactions)): ?>
                            <h5>Chi tiết giao dịch</h5>
                            <div class="alert alert-info mb-3"><p><strong>Trạng thái hoa hồng:</strong></p><ul class="mb-0"><li><span class="badge badge-success">Đã duyệt</span>: Sẵn sàng để rút.</li><li><span class="badge badge-warning">Đang chờ</span>: Chờ giao dịch hoàn tất.</li></ul></div>
                            <div class="table-responsive"><table class="table table-striped"><thead><tr><th>STT</th><th>Người được giới thiệu</th><th>Số tiền GD</th><th>Hoa hồng (5%)</th><th>Trạng thái GD</th><th>Trạng thái HH</th><th>Ngày</th></tr></thead><tbody>
                                <?php foreach ($commissionTransactions as $index => $transaction): ?>
                                    <tr>
                                        <td data-label="STT"><?php echo $index + 1; ?></td>
                                        <td data-label="Người được giới thiệu"><?php echo htmlspecialchars($transaction['referred_username']); ?></td>
                                        <td data-label="Số tiền GD"><?php echo number_format($transaction['transaction_amount'], 0, ',', '.'); ?> VNĐ</td>
                                        <td data-label="Hoa hồng (5%)"><?php echo number_format($transaction['commission_amount'], 0, ',', '.'); ?> VNĐ</td>
                                        <td data-label="Trạng thái GD"><?php echo strtolower($transaction['transaction_status']) === 'completed' ? '<span class="badge badge-success">Thành công</span>' : '<span class="badge badge-warning">Đang chờ</span>'; ?></td>
                                        <td data-label="Trạng thái HH"><?php echo strtolower($transaction['transaction_status']) === 'completed' ? '<span class="badge badge-success">Đã duyệt</span>' : '<span class="badge badge-warning">Đang chờ</span>'; ?></td>
                                        <td data-label="Ngày"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                        <?php else: ?>
                            <div class="alert alert-info">Bạn chưa có giao dịch hoa hồng nào.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 4: Withdrawal -->
                    <div class="tab-pane fade" id="withdrawal" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-4">Yêu cầu rút tiền</h4>
                                <div class="mb-4 card bg-light p-3"><div class="d-flex justify-content-between"><h5>Số dư khả dụng:</h5><h5><?php echo number_format($availableBalance, 0, ',', '.'); ?> VNĐ</h5></div></div>
                                <div class="alert alert-info mb-4"><p><strong>Hệ thống hoa hồng:</strong></p><ul class="mb-0"><li>Hoa hồng được duyệt tự động khi giao dịch hoàn tất.</li><li>Yêu cầu rút tiền khi số dư từ 100,000 VNĐ.</li><li>Yêu cầu được xử lý trong 1-3 ngày làm việc.</li></ul></div>
                                <div class="alert" id="withdrawal-message" style="display:none;"></div>
                                <form id="withdrawal-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <div class="form-group"><label for="amount">Số tiền muốn rút (VNĐ) <span class="text-danger">*</span></label><input type="number" class="form-control" id="amount" name="amount" min="100000" max="<?php echo $availableBalance; ?>" step="10000" required <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>><?php if ($availableBalance < 100000): ?><small class="form-text text-muted">Số dư tối thiểu để rút là 100.000 VNĐ</small><?php endif; ?></div>
                                    <div class="form-group"><label for="bank_name">Ngân hàng <span class="text-danger">*</span></label><input type="text" class="form-control" id="bank_name" name="bank_name" required <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>></div>
                                    <div class="form-group"><label for="account_number">Số tài khoản <span class="text-danger">*</span></label><input type="text" class="form-control" id="account_number" name="account_number" required <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>></div>
                                    <div class="form-group"><label for="account_holder">Tên chủ tài khoản <span class="text-danger">*</span></label><input type="text" class="form-control" id="account_holder" name="account_holder" required <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>></div>
                                    <button type="submit" name="submit_withdrawal" class="btn btn-primary" id="withdraw-btn" <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>><span id="withdraw-btn-text">Gửi yêu cầu</span><span id="withdraw-btn-loading" style="display:none"><i class="fas fa-spinner fa-spin"></i> Đang gửi...</span></button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <h4 class="mb-4">Lịch sử rút tiền</h4>
                                <?php if (!empty($withdrawalHistory)): ?>
                                    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Ngày yêu cầu</th><th>Số tiền</th><th>Trạng thái</th></tr></thead><tbody>
                                        <?php foreach ($withdrawalHistory as $withdrawal): ?>
                                            <tr>
                                                <td data-label="Ngày yêu cầu"><?php echo date('d/m/Y', strtotime($withdrawal['created_at'])); ?></td>
                                                <td data-label="Số tiền"><?php echo number_format($withdrawal['amount'], 0, ',', '.'); ?> VNĐ</td>
                                                <td data-label="Trạng thái">
                                                    <?php $status_map = ['pending' => 'badge-warning', 'completed' => 'badge-success', 'rejected' => 'badge-danger']; $status_text_map = ['pending' => 'Đang xử lý', 'completed' => 'Hoàn thành', 'rejected' => 'Từ chối']; ?>
                                                    <span class="badge <?php echo $status_map[$withdrawal['status']]; ?>"><?php echo $status_text_map[$withdrawal['status']]; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody></table></div>
                                <?php else: ?>
                                    <div class="alert alert-info">Bạn chưa có yêu cầu rút tiền nào.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; // HIDE_PAYMENT_UI - end commission + withdrawal tabs ?>
                    
                    <!-- Tab 5: Ranking -->
                    <div class="tab-pane fade" id="ranking" role="tabpanel">
                        <h4 class="mb-4">Bảng xếp hạng người giới thiệu</h4>
                        <div class="rank-summary-row">
                            <div class="rank-card"><div class="rank-label">Hạng (Tháng)</div><div class="rank-value"><?php echo is_numeric($userRankings['monthly_rank']) ? $userRankings['monthly_rank'] : 'N/A'; ?></div></div>
                            <div class="rank-card"><div class="rank-label">Hạng (Tổng)</div><div class="rank-value"><?php echo is_numeric($userRankings['total_rank']) ? $userRankings['total_rank'] : 'N/A'; ?></div></div>
                            <div class="rank-card"><div class="rank-label">Tổng người giới thiệu</div><div class="rank-value"><?php echo count($referredUsers); ?></div></div>
                        </div>
                        <div class="ranking-tabs mt-3">
                            <div class="rank-tab-buttons"><button class="rank-tab-btn active" data-target="#monthly-ranking">Xếp hạng tháng</button><button class="rank-tab-btn" data-target="#total-ranking">Xếp hạng tổng</button></div>
                            <div class="rank-tab-content-wrapper">
                                <div class="rank-tab-content active" id="monthly-ranking">
                                    <div class="table-responsive"><table class="table table-striped table-bordered table-ranking"><thead class="thead-light"><tr><th>Hạng</th><th>Người dùng</th><th>Số GT</th></tr></thead><tbody>
                                        <?php if (!empty($monthlyRankings)): foreach ($monthlyRankings as $index => $rank): ?>
                                            <tr class="<?php echo ($rank['user_id'] == $user_id) ? 'table-primary' : ''; ?>">
                                                <td data-label="Hạng" class="ranking-number"><?php echo $index + 1; if ($index < 3): ?><i class="fas fa-trophy trophy-icon trophy-<?php echo $index + 1; ?>"></i><?php endif; ?></td>
                                                <td data-label="Người dùng"><?php echo htmlspecialchars($rank['username']); ?></td>
                                                <td data-label="Số GT"><?php echo $rank['referral_count']; ?></td>
                                            </tr>
                                        <?php endforeach; else: ?><tr><td colspan="3" class="text-center">Chưa có dữ liệu.</td></tr><?php endif; ?>
                                    </tbody></table></div>
                                </div>
                                <div class="rank-tab-content" id="total-ranking">
                                    <div class="table-responsive"><table class="table table-striped table-bordered table-ranking"><thead class="thead-light"><tr><th>Hạng</th><th>Người dùng</th><th>Số GT</th></tr></thead><tbody>
                                        <?php if (!empty($totalRankings)): foreach ($totalRankings as $index => $rank): ?>
                                            <tr class="<?php echo ($rank['user_id'] == $user_id) ? 'table-primary' : ''; ?>">
                                                <td data-label="Hạng" class="ranking-number"><?php echo $index + 1; if ($index < 3): ?><i class="fas fa-trophy trophy-icon trophy-<?php echo $index + 1; ?>"></i><?php endif; ?></td>
                                                <td data-label="Người dùng"><?php echo htmlspecialchars($rank['username']); ?></td>
                                                <td data-label="Số GT"><?php echo $rank['referral_count']; ?></td>
                                            </tr>
                                        <?php endforeach; else: ?><tr><td colspan="3" class="text-center">Chưa có dữ liệu.</td></tr><?php endif; ?>
                                    </tbody></table></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Thư viện tạo QR Code -->
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

<!-- Khởi tạo biến cho JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeReferralSystem({
        referralLink: "<?php echo $referralLink; ?>",
        availableBalance: <?php echo (float)$availableBalance; ?>,
        minWithdrawalAmount: 100000,
        processWithdrawalUrl: '<?php echo $base_url . $base_path; ?>/handlers/referral_action.php'
    });
});
</script>

<script src="<?php echo $base_url . $base_path; ?>/assets/js/pages/referral/dashboard_referal.js"></script>
<script src="<?php echo $base_url . $base_path; ?>/assets/js/pages/referral/bank-selector.js"></script>

<?php require_once $project_root_path . '/private/includes/footer.php'; ?>