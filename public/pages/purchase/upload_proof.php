<?php
session_start();

// --- Project Root Path for Includes ---
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));

// --- Base URL Configuration ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = '';
if (strpos($script_dir, '/public/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/utils/functions.php';
require_once $project_root_path . '/private/classes/Database.php'; // Add this line

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}

// --- Get Registration ID ---
// Get from URL parameter first, fallback to session if needed (though URL is preferred here)
$registration_id = null;
if (isset($_GET['reg_id']) && is_numeric($_GET['reg_id'])) {
    $registration_id = (int)$_GET['reg_id'];
} elseif (isset($_SESSION['pending_registration_id'])) {
    // Fallback, but ideally the link from payment.php provides it
    $registration_id = $_SESSION['pending_registration_id'];
}

if (!$registration_id) {
    // If no registration ID is found, redirect to packages or dashboard
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=missing_order_id');
    exit;
}

// --- Fetch Existing Payment Proof ---
$existing_proof_image = null;
$existing_proof_url = null;
try {
    $db = new Database();
    $conn = $db->getConnection();
    $sql_get_proof = "SELECT payment_image FROM payment WHERE registration_id = :registration_id LIMIT 1";
    $stmt_get_proof = $conn->prepare($sql_get_proof);
    $stmt_get_proof->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_get_proof->execute();
    $existing_proof_image = $stmt_get_proof->fetchColumn();

    if ($existing_proof_image) {
        // Construct the URL relative to the web root
        $upload_dir_relative = '/uploads/payment_proofs/'; // Make sure this matches the definition in the action script
        $existing_proof_url = $base_url . '/public' . $upload_dir_relative . htmlspecialchars($existing_proof_image);
    }
} catch (Exception $e) {
    // Log error or handle gracefully
    error_log("Error fetching existing payment proof: " . $e->getMessage());
    // Optionally display an error message to the user
}

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>

<!-- CSS for Upload Page (can reuse some styles or add specific ones) -->
<style>
    /* Re-use relevant styles from payment.php or base.css */
    .content-wrapper {
        max-width: 700px; /* Limit width for better focus */
        margin: 2rem auto; /* Center the content */
        padding: 2rem;
        background-color: #fff;
        border-radius: var(--rounded-lg);
        box-shadow: var(--shadow-md);
    }
    .upload-section {
        margin-top: 1rem; /* Reduced top margin */
        padding: 1.5rem;
        background-color: var(--gray-50);
        border: 1px dashed var(--gray-300);
        border-radius: var(--rounded-md);
        text-align: center;
    }
    .upload-section h3 { /* Changed from h4 for better hierarchy */
        font-size: var(--font-size-lg); /* Slightly larger */
        font-weight: var(--font-semibold);
        color: var(--gray-700);
        margin-bottom: 1rem;
    }
    .upload-section p {
        font-size: var(--font-size-sm);
        color: var(--gray-500);
        margin-bottom: 1rem;
    }
    .upload-section input[type="file"] {
        display: block;
        margin: 1rem auto;
        padding: 0.5rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--rounded-md);
        max-width: 300px;
        cursor: pointer;
    }
     .upload-section .btn-upload {
        padding: 0.6rem 1.2rem;
        background-color: var(--primary-600);
        color: white;
        border: none;
        border-radius: var(--rounded-md);
        font-weight: var(--font-medium);
        cursor: pointer;
        transition: background-color 0.2s;
     }
     .upload-section .btn-upload:hover {
         background-color: var(--primary-700);
     }
     .upload-section .btn-upload:disabled {
         background-color: var(--gray-400);
         cursor: not-allowed;
     }
     #upload-status-js { /* Renamed from upload-status */
         margin-top: 1rem;
         font-size: var(--font-size-sm);
         font-weight: var(--font-medium);
     }
     .status-success { color: var(--success-600); }
     .status-error { color: var(--danger-600); }

     /* === Style for existing proof image === */
     .existing-proof-section {
         margin-bottom: 1.5rem;
         padding: 1rem;
         background-color: var(--gray-100);
         border: 1px solid var(--gray-200);
         border-radius: var(--rounded-md);
         text-align: center;
     }
     .existing-proof-section h4 {
         font-size: var(--font-size-base);
         font-weight: var(--font-semibold);
         color: var(--gray-600);
         margin-bottom: 0.75rem;
     }
     .existing-proof-section img {
         max-width: 100%;
         max-height: 300px; /* Limit height */
         border-radius: var(--rounded-sm);
         border: 1px solid var(--gray-300);
         margin-top: 0.5rem;
     }
     /* === End Style === */

     .back-link {
         display: inline-block;
         margin-bottom: 1.5rem;
         font-size: var(--font-size-sm);
         color: var(--primary-600);
         text-decoration: none;
     }
     .back-link:hover {
         text-decoration: underline;
     }
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <a href="<?php echo $base_url; ?>/public/pages/transaction.php" class="back-link">&larr; Quay lại Lịch sử giao dịch</a>

        <h2 class="text-2xl font-semibold mb-4">Tải lên minh chứng thanh toán</h2>
        <p class="text-sm text-gray-600 mb-6">Đơn hàng: <strong>REG<?php echo htmlspecialchars($registration_id); ?></strong></p>

        <!-- === Hiển thị minh chứng đã tải lên (nếu có) === -->
        <?php if ($existing_proof_url): ?>
        <div class="existing-proof-section">
            <h4>Minh chứng đã tải lên:</h4>
            <img src="<?php echo $existing_proof_url; ?>" alt="Minh chứng thanh toán hiện tại">
        </div>
        <?php endif; ?>
        <!-- === Kết thúc hiển thị === -->

        <!-- === Phần Tải Lên Minh Chứng === -->
        <div class="upload-section">
            <h3><?php echo $existing_proof_image ? 'Thay thế minh chứng thanh toán' : 'Tải lên ảnh chụp màn hình giao dịch'; ?></h3>
            <p>Vui lòng tải lên ảnh chụp màn hình hoặc biên lai giao dịch thành công để chúng tôi xác nhận nhanh hơn.</p>
            <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=upload_payment_proof" method="post" enctype="multipart/form-data" id="upload-form">
                <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                <!-- CSRF Token protection -->
                <?php require_once $project_root_path . '/private/utils/csrf_helper.php'; echo generate_csrf_input(); ?>

                <input type="file" name="payment_proof_image" id="payment_proof_image" accept="image/png, image/jpeg, image/gif" required>

                <button type="submit" class="btn btn-upload" id="upload-button"><?php echo $existing_proof_image ? 'Gửi minh chứng mới' : 'Gửi minh chứng'; ?></button>
                <div id="upload-progress" style="margin-top: 0.5rem; font-size: var(--font-size-sm); display: none;">Đang tải lên...</div>
                <div id="upload-status-js" class="mt-3" style="font-size: var(--font-size-sm); font-weight: var(--font-medium);"></div>
            </form>
        </div>
         <!-- === Kết thúc Phần Tải Lên Minh Chứng === -->

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Xử lý form tải lên bằng AJAX ---
    const uploadForm = document.getElementById('upload-form');
    const uploadButton = document.getElementById('upload-button');
    const fileInput = document.getElementById('payment_proof_image');
    const uploadProgress = document.getElementById('upload-progress');
    const uploadStatusJs = document.getElementById('upload-status-js'); // Corrected ID
    const transactionUrl = '<?php echo $base_url; ?>/public/pages/transaction.php'; // Define transaction URL

    if (uploadForm) {
        uploadForm.addEventListener('submit', function(event) {
            event.preventDefault();

            uploadStatusJs.textContent = '';
            uploadStatusJs.className = 'mt-3'; // Reset classes

            if (fileInput.files.length === 0) {
                alert('Vui lòng chọn một tệp ảnh minh chứng.');
                return;
            }

            uploadButton.disabled = true;
            uploadButton.innerText = 'Đang xử lý...';
            uploadProgress.style.display = 'block';

            const formData = new FormData(uploadForm);
            const actionUrl = uploadForm.getAttribute('action');
            console.log('Submitting to:', actionUrl);

            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Received response status:', response.status);
                const contentType = response.headers.get("content-type");
                console.log('Received response content-type:', contentType);
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json().then(data => ({ ok: response.ok, status: response.status, jsonData: data }));
                } else {
                    return response.text().then(text => {
                        console.error('Server response was not JSON:', text);
                        throw new Error(`Server returned non-JSON response (status: ${response.status}). See console for details.`);
                    });
                }
            })
            .then(({ ok, status, jsonData }) => {
                console.log('Parsed JSON data:', jsonData);
                if (ok && jsonData.success) {
                    uploadStatusJs.textContent = 'Tải lên thành công! Đang chuyển hướng...';
                    uploadStatusJs.classList.remove('status-error'); // Ensure error class is removed
                    uploadStatusJs.classList.add('status-success');
                    fileInput.value = ''; // Clear file input on success

                    // Wait 1 second then redirect
                    setTimeout(() => {
                        window.location.href = transactionUrl + '?upload=success'; // Add query param for potential feedback
                    }, 1000); // 1000 milliseconds = 1 second

                } else {
                    const errorMessage = jsonData.error || `Lỗi không xác định từ server (HTTP ${status})`;
                    alert(`Lỗi tải lên: ${errorMessage}`);
                    uploadStatusJs.textContent = `Lỗi: ${errorMessage}`;
                    uploadStatusJs.classList.remove('status-success'); // Ensure success class is removed
                    uploadStatusJs.classList.add('status-error');
                    console.error('Upload failed:', errorMessage, 'Data:', jsonData);
                    // Re-enable button immediately on failure
                    uploadButton.disabled = false;
                    uploadButton.innerText = 'Gửi minh chứng';
                    uploadProgress.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Đã xảy ra lỗi khi gửi minh chứng. Vui lòng thử lại. Chi tiết: ' + error.message);
                uploadStatusJs.textContent = 'Lỗi mạng hoặc phản hồi không hợp lệ. Kiểm tra console.';
                uploadStatusJs.classList.remove('status-success');
                uploadStatusJs.classList.add('status-error');
                 // Re-enable button on catch
                uploadButton.disabled = false;
                uploadButton.innerText = 'Gửi minh chứng';
                uploadProgress.style.display = 'none';
            })
            .finally(() => {
                // Only re-enable button etc. here if NOT successful, as success handles its own state before redirect
                if (!uploadStatusJs.classList.contains('status-success')) {
                     uploadButton.disabled = false;
                     uploadButton.innerText = 'Gửi minh chứng';
                     uploadProgress.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>
