<?php
// Start session and include configuration
session_start();
require_once dirname(__DIR__, 3) . '/private/config/config.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/public/pages/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Initialize necessary classes
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Referral.php';

$db = new Database();
$referralService = new Referral($db);

// Get user's referral code
$referralCodeResult = $referralService->getUserReferralCode($user_id);
$referralCode = $referralCodeResult['success'] ? $referralCodeResult['code'] : '';

// Get referred users
$referredUsers = $referralService->getReferredUsers($user_id);

// Get commission information
$totalCommissionEarned = $referralService->getTotalCommissionEarned($user_id);
$totalCommissionPaid = $referralService->getTotalCommissionPaid($user_id);
$pendingWithdrawals = $referralService->getTotalPendingWithdrawals($user_id);
$availableBalance = $totalCommissionEarned - $totalCommissionPaid - $pendingWithdrawals;

// Get withdrawal history
$withdrawalHistory = $referralService->getWithdrawalHistory($user_id);

// Get commission transactions
$commissionTransactions = $referralService->getCommissionTransactions($user_id);

// Include header and sidebar
$page_title = "Quản lý giới thiệu";
require_once PROJECT_ROOT_PATH . '/private/includes/header.php';
?>

<!-- Import Bootstrap CSS and JS từ CDN để đảm bảo các styles hoạt động -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<style>
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e3e6f0;
    }
    .tab-content {
        padding: 20px 0;
    }
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        border-bottom: 3px solid #007bff;
        color: #007bff;
        font-weight: 600;
    }
    .table-responsive {
        margin-top: 15px;
    }
    .alert {
        margin-bottom: 20px;
    }
    .page-title {
        margin-bottom: 25px;
        color: #333;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .input-group {
        margin-bottom: 15px;
    }    .badge {
        padding: 5px 10px;
        font-size: 0.8rem;
    }    /* Ensure Bootstrap plays nicely with our layout */
    .container-fluid {
        padding: 0;
    }
    
    /* Make sure tabs don't overflow on mobile */
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    /* Ensure proper z-index for elements that need to be clickable */
    .input-group-append button {
        position: relative;
        z-index: 1;
    }
</style>

<div class="dashboard-wrapper">
    <?php include PROJECT_ROOT_PATH . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">

        <div class="container-fluid">
            <h1 class="page-title">Quản Lý Giới Thiệu</h1>
        
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="referralTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="referral-link-tab" data-toggle="tab" href="#referral-link" role="tab">Liên kết giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="referred-users-tab" data-toggle="tab" href="#referred-users" role="tab">Người đã giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="commission-tab" data-toggle="tab" href="#commission" role="tab">Hoa hồng nhận được</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="withdrawal-tab" data-toggle="tab" href="#withdrawal" role="tab">Yêu cầu rút tiền</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="referralTabsContent">
                    <!-- Tab 1: Referral Link -->
                    <div class="tab-pane fade show active" id="referral-link" role="tabpanel">
                        <h4 class="mb-4">Liên kết giới thiệu của bạn</h4>
                        <div class="alert alert-info">
                            <strong>Chính sách hoa hồng:</strong> Bạn sẽ nhận được 5% giá trị thanh toán từ người dùng mà bạn giới thiệu.
                        </div>
                        
                        <?php if ($referralCode): ?>
                            <div class="form-group">
                                <label>Mã giới thiệu của bạn:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="referral-code" value="<?php echo $referralCode; ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyReferralCode()">
                                            <i class="fas fa-copy"></i> Sao chép
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Liên kết giới thiệu:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="referral-link" 
                                           value="<?php echo BASE_URL; ?>/public/pages/auth/register.php?ref=<?php echo $referralCode; ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyReferralLink()">
                                            <i class="fas fa-copy"></i> Sao chép
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h5>Cách giới thiệu người dùng:</h5>
                                <ol>
                                    <li>Sao chép liên kết giới thiệu hoặc mã giới thiệu của bạn</li>
                                    <li>Chia sẻ liên kết này cho bạn bè, đồng nghiệp hoặc người quen</li>
                                    <li>Khi họ đăng ký và thanh toán, bạn sẽ nhận được hoa hồng 5%</li>
                                </ol>
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
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($user['referred_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Bạn chưa giới thiệu người dùng nào. Hãy chia sẻ liên kết giới thiệu để bắt đầu kiếm hoa hồng!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 3: Commission -->
                    <div class="tab-pane fade" id="commission" role="tabpanel">
                        <h4 class="mb-4">Hoa hồng nhận được</h4>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Tổng hoa hồng đã kiếm được</h5>
                                        <h2><?php echo number_format($totalCommissionEarned, 0, ',', '.'); ?> VNĐ</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Số dư khả dụng</h5>
                                        <h2><?php echo number_format($availableBalance, 0, ',', '.'); ?> VNĐ</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Đã rút</h5>
                                        <h2><?php echo number_format($totalCommissionPaid, 0, ',', '.'); ?> VNĐ</h2>
                                    </div>
                                </div>
                            </div>
                        </div>                          <?php if (!empty($commissionTransactions)): ?>
                            <h5>Chi tiết giao dịch người được giới thiệu</h5>
                              <div class="alert alert-info mb-3">
                                <p><strong>Thông tin trạng thái hoa hồng:</strong></p>
                                <ul class="mb-0">
                                    <li><span class="badge badge-success">Đã duyệt</span> - Hoa hồng đã được tự động duyệt và sẵn sàng để rút</li>
                                    <li><span class="badge badge-info">Đang duyệt</span> - Hệ thống đang xử lý hoa hồng cho giao dịch thành công</li>
                                    <li><span class="badge badge-warning">Đang xử lý</span> - Giao dịch đang xử lý, hoa hồng sẽ được duyệt sau khi hoàn tất</li>
                                    <li><span class="badge badge-primary">Đã thanh toán</span> - Hoa hồng đã được thanh toán vào tài khoản của bạn</li>
                                    <li><span class="badge badge-secondary">Chưa đủ điều kiện</span> - Giao dịch không thành công hoặc chưa được xác nhận</li>
                                </ul>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Người được giới thiệu</th>
                                            <th>Số tiền giao dịch</th>
                                            <th>Hoa hồng (5%)</th>
                                            <th>Trạng thái giao dịch</th>
                                            <th>Trạng thái hoa hồng</th>
                                            <th>Ngày</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($commissionTransactions as $index => $transaction): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($transaction['referred_username']); ?></td>
                                                <td><?php echo number_format($transaction['transaction_amount'], 0, ',', '.'); ?> VNĐ</td>
                                                <td><?php echo number_format($transaction['commission_amount'], 0, ',', '.'); ?> VNĐ</td>
                                                <td>
                                                    <?php
                                                    // Trạng thái giao dịch
                                                    if (
                                                        strtolower($transaction['transaction_status']) === 'completed' &&
                                                        isset($transaction['payment_confirmed']) && $transaction['payment_confirmed'] == 1
                                                    ) {
                                                        echo '<span class="badge badge-success">Thành công</span>';
                                                    } else {
                                                        echo '<span class="badge badge-warning">Đang chờ</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Trạng thái hoa hồng
                                                    if (
                                                        strtolower($transaction['transaction_status']) === 'completed' &&
                                                        isset($transaction['payment_confirmed']) && $transaction['payment_confirmed'] == 1
                                                    ) {
                                                        echo '<span class="badge badge-success">Đã duyệt</span>';
                                                    } else {
                                                        echo '<span class="badge badge-warning">Đang chờ</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Bạn chưa có giao dịch hoa hồng nào. Hãy giới thiệu người dùng mới để bắt đầu kiếm hoa hồng!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 4: Withdrawal -->
                    <div class="tab-pane fade" id="withdrawal" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">                                <h4 class="mb-4">Yêu cầu rút tiền</h4>
                                
                                <div class="mb-4 card bg-light p-3">
                                    <div class="d-flex justify-content-between">
                                        <h5>Số dư khả dụng:</h5>
                                        <h5><?php echo number_format($availableBalance, 0, ',', '.'); ?> VNĐ</h5>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mb-4">
                                    <p><strong>Hệ thống hoa hồng tự động:</strong></p>
                                    <ul class="mb-0">
                                        <li>Khi người được bạn giới thiệu thanh toán thành công, hệ thống tự động duyệt hoa hồng 5%</li>
                                        <li>Hoa hồng được duyệt sẽ được cộng vào số dư khả dụng của bạn</li>
                                        <li>Bạn có thể yêu cầu rút tiền khi số dư từ 100,000 VNĐ trở lên</li>
                                        <li>Yêu cầu rút tiền sẽ được xử lý trong vòng 1-3 ngày làm việc</li>
                                    </ul>
                                </div>
                                
                                <div class="alert" id="withdrawal-message" style="display:none;"></div>
                                
                                <form id="withdrawal-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <div class="form-group">
                                        <label for="amount">Số tiền muốn rút (VNĐ) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               min="100000" max="<?php echo $availableBalance; ?>" step="10000" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                        <?php if ($availableBalance < 100000): ?>
                                            <small class="form-text text-muted">Số dư tối thiểu để rút tiền là 100.000 VNĐ</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bank_name">Ngân hàng <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="account_number">Số tài khoản <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="account_number" name="account_number" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="account_holder">Tên chủ tài khoản <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="account_holder" name="account_holder" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <button type="submit" name="submit_withdrawal" class="btn btn-primary" id="withdraw-btn"
                                            <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                        <span id="withdraw-btn-text">Gửi yêu cầu rút tiền</span>
                                        <span id="withdraw-btn-loading" style="display:none"><i class="fas fa-spinner fa-spin"></i> Đang gửi...</span>
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h4 class="mb-4">Lịch sử yêu cầu rút tiền</h4>
                                
                                <?php if (!empty($withdrawalHistory)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Ngày yêu cầu</th>
                                                    <th>Số tiền</th>
                                                    <th>Trạng thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($withdrawalHistory as $withdrawal): ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y', strtotime($withdrawal['created_at'])); ?></td>
                                                        <td><?php echo number_format($withdrawal['amount'], 0, ',', '.'); ?> VNĐ</td>
                                                        <td>
                                                            <?php 
                                                            $status_class = '';
                                                            $status_text = '';
                                                            switch ($withdrawal['status']) {
                                                                case 'pending':
                                                                    $status_class = 'badge-warning';
                                                                    $status_text = 'Đang xử lý';
                                                                    break;
                                                                case 'completed':
                                                                    $status_class = 'badge-success';
                                                                    $status_text = 'Hoàn thành';
                                                                    break;
                                                                case 'rejected':
                                                                    $status_class = 'badge-danger';
                                                                    $status_text = 'Từ chối';
                                                                    break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        Bạn chưa có yêu cầu rút tiền nào.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

<script>
function copyReferralCode() {
    var copyText = document.getElementById("referral-code");
    copyText.select();
    document.execCommand("copy");
    if (confirm("Đã sao chép mã giới thiệu: " + copyText.value + "\nBạn có muốn chia sẻ ngay không?")) {
        // Có thể mở popup chia sẻ nếu muốn
    }
}

function copyReferralLink() {
    var copyText = document.getElementById("referral-link");
    copyText.select();
    document.execCommand("copy");
    if (confirm("Đã sao chép liên kết giới thiệu!\nBạn có muốn chia sẻ ngay không?")) {
        // Có thể mở popup chia sẻ nếu muốn
    }
}

// Script để xử lý các tab và hiệu ứng
$(document).ready(function() {
    // Activate Bootstrap tabs
    $('#referralTabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Form validation & loading
    $('#withdrawal-form').submit(function(event) {
        event.preventDefault();
        
        var form = $(this);
        var withdrawBtn = $('#withdraw-btn');
        var btnText = $('#withdraw-btn-text');
        var btnLoading = $('#withdraw-btn-loading');
        var messageDiv = $('#withdrawal-message');

        var amount = parseFloat($('#amount').val());
        var bankName = $('#bank_name').val().trim();
        var accountNumber = $('#account_number').val().trim();
        var accountHolder = $('#account_holder').val().trim();
        var available = parseFloat(<?php echo (float)$availableBalance; ?>);
        var minWithdrawal = 100000;

        messageDiv.hide().removeClass('alert-success alert-danger');

        if (!amount || !bankName || !accountNumber || !accountHolder) {
            messageDiv.text('Vui lòng điền đầy đủ thông tin yêu cầu rút tiền.').addClass('alert-danger').show();
            return false;
        }
        if (isNaN(amount) || amount <= 0) {
            messageDiv.text('Số tiền không hợp lệ.').addClass('alert-danger').show();
            return false;
        }
        if (amount < minWithdrawal) {
            messageDiv.text('Số tiền rút tối thiểu là ' + minWithdrawal.toLocaleString('vi-VN') + ' VNĐ.').addClass('alert-danger').show();
            return false;
        }
        if (amount > available) {
            messageDiv.text('Số dư khả dụng không đủ!').addClass('alert-danger').show();
            return false;
        }

        btnText.hide();
        btnLoading.show();
        withdrawBtn.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '<?php echo BASE_URL; ?>/private/action/referral/process_withdrawal.php',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.message).removeClass('alert-danger').addClass('alert-success').show();
                    setTimeout(function(){ location.reload(); }, 2000);
                } else {
                    messageDiv.text(response.message || 'Đã xảy ra lỗi không xác định.').removeClass('alert-success').addClass('alert-danger').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error: ", textStatus, errorThrown, jqXHR.responseText);
                messageDiv.text('Lỗi khi gửi yêu cầu. Vui lòng thử lại.').removeClass('alert-success').addClass('alert-danger').show();
            },
            complete: function() {
                btnText.show();
                btnLoading.hide();
                withdrawBtn.prop('disabled', false);
            }
        });
    });
});
</script>

<?php
// Include footer
require_once PROJECT_ROOT_PATH . '/private/includes/footer.php';
?>