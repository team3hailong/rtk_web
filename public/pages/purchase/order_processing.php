<?php
// --- Cấu hình và Header ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
init_session();
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// Chuyển hướng người dùng về trang dashboard sau 5 giây
$redirect_url = $base_url . '/public/pages/dashboard.php';
header("refresh:5;url=" . $redirect_url);

include $project_root_path . '/private/includes/header.php';
?>

<style>
    .processing-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        height: 70vh;
        padding: 2rem;
    }
    .processing-container h2 {
        font-size: 2rem;
        color: var(--primary-600, #2980b9);
        margin-bottom: 1rem;
    }
    .processing-container p {
        font-size: 1.1rem;
        color: var(--gray-700, #495057);
        margin-bottom: 2rem;
        max-width: 500px;
    }
    .spinner {
        border: 8px solid var(--gray-200, #e9ecef);
        border-top: 8px solid var(--primary-600, #2980b9);
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 1s linear infinite;
        margin-bottom: 2rem;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <div class="processing-container">
            <div class="spinner"></div>
            <h2>Đơn hàng của bạn đang được xử lý...</h2>
            <p>Tài khoản của bạn sẽ được kích hoạt trong giây lát. Hệ thống sẽ tự động chuyển hướng bạn đến trang quản lý.</p>
            <p style="font-size: 0.9rem; color: var(--gray-600);">Nếu không được chuyển hướng, vui lòng <a href="<?php echo $redirect_url; ?>" style="color: var(--primary-600); font-weight: bold;">nhấn vào đây</a>.</p>
        </div>
    </main>
</div>

<?php
include $project_root_path . '/private/includes/footer.php';
?>