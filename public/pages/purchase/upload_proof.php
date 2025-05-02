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

    /* Responsive styles for mobile devices */
    @media (max-width: 768px) {
        .content-wrapper {
            margin: 1rem;
            padding: 1rem;
        }
        
        .existing-proof-section img {
            max-height: 200px;
        }
        
        .upload-section {
            padding: 1rem;
        }
        
        .upload-section h3 {
            font-size: 1rem;
        }
        
        .btn-upload {
            width: 100%;
            padding: 0.75rem 0;
            display: block;
            text-align: center;
        }
    }
    
    @media (max-width: 480px) {
        .content-wrapper {
            margin: 0.5rem;
            padding: 0.75rem;
            border-radius: var(--rounded-md);
        }
        
        .upload-section {
            padding: 0.5rem;
        }
        
        .upload-section h3 {
            font-size: 0.875rem;
        }
        
        .btn-upload {
            font-size: 0.875rem;
        }
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
            
            <!-- Form thông thường (không dùng AJAX) sẽ được sử dụng nếu JavaScript bị tắt -->
            <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=upload_payment_proof" 
                  method="post" 
                  enctype="multipart/form-data" 
                  id="upload-form">
                <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                <!-- CSRF Token protection -->
                <?php require_once $project_root_path . '/private/utils/csrf_helper.php'; echo generate_csrf_input(); ?>

                <input type="file" name="payment_proof_image" id="payment_proof_image" accept="image/png, image/jpeg, image/gif" required>

                <button type="submit" class="btn btn-upload" id="upload-button"><?php echo $existing_proof_image ? 'Gửi minh chứng mới' : 'Gửi minh chứng'; ?></button>
                <div id="upload-progress" style="margin-top: 0.5rem; font-size: var(--font-size-sm); display: none;">Đang tải lên...</div>
                <!-- Container for progress bar -->
                <div id="progress-bar-container" style="width: 100%; background-color: #f0f0f0; border-radius: 4px; margin: 10px 0; display: none;">
                    <div id="progress-bar-inner" style="height: 10px; background-color: var(--primary-600); border-radius: 4px; width: 0%; transition: width 0.2s;"></div>
                </div>
                <!-- Container for upload details (speed, time) -->
                <div id="upload-details" style="font-size: var(--font-size-xs); color: var(--gray-600); margin-top: 5px; display: none;">
                    <span id="upload-speed"></span> | <span id="upload-time-remaining"></span>
                </div>
                <div id="upload-status-js" class="mt-3" style="font-size: var(--font-size-sm); font-weight: var(--font-medium);"></div>
            </form>
        </div>
         <!-- === Kết thúc Phần Tải Lên Minh Chứng === -->

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Các phần tử DOM ---
    const uploadForm = document.getElementById('upload-form');
    const uploadButton = document.getElementById('upload-button');
    const fileInput = document.getElementById('payment_proof_image');
    const uploadProgressText = document.getElementById('upload-progress'); // Renamed from uploadProgress
    const progressBarContainer = document.getElementById('progress-bar-container');
    const progressBarInner = document.getElementById('progress-bar-inner');
    const uploadDetails = document.getElementById('upload-details');
    const uploadSpeedSpan = document.getElementById('upload-speed');
    const uploadTimeRemainingSpan = document.getElementById('upload-time-remaining');
    const uploadStatusJs = document.getElementById('upload-status-js');
    const transactionUrl = '<?php echo $base_url; ?>/public/pages/transaction.php';

    // --- Biến theo dõi upload ---
    let uploadStartTime = 0;

    // --- Khởi tạo progress bar (đã di chuyển vào HTML) ---
    // REMOVED JavaScript progress bar creation

    // --- Sự kiện cho file input ---
    fileInput.addEventListener('change', function() {
        handleFileSelection(this);
    });

    // --- Xử lý form submit ---
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (fileInput.files.length === 0) {
                alert('Vui lòng chọn một tệp ảnh minh chứng.');
                return;
            }

            const fileSize = fileInput.files[0].size / 1024 / 1024; // Size in MB
            if (fileSize > 15) {
                alert('File quá lớn (giới hạn 15MB).');
                return;
            }

            // Chuẩn bị UI
            uploadButton.disabled = true;
            uploadButton.innerText = 'Đang xử lý...';
            uploadProgressText.style.display = 'block'; // Show text progress
            progressBarContainer.style.display = 'block'; // Show progress bar
            progressBarInner.style.width = '0%';
            uploadDetails.style.display = 'none'; // Hide details initially
            uploadStatusJs.textContent = 'Đang chuẩn bị tải lên...';
            uploadStatusJs.className = 'mt-3'; // Reset class

            // Reset and record start time
            uploadStartTime = Date.now();

            // Luôn sử dụng XHR cho tải lên
            uploadViaXHR();
        });
    }
    
    // --- Hàm xử lý chọn file ---
    function handleFileSelection(inputElement) {
        if (inputElement.files.length > 0) {
            const file = inputElement.files[0];
            const fileSize = file.size / 1024 / 1024;
            const fileSizeMB = fileSize.toFixed(2);
            
            // Hiển thị thông tin file
            uploadStatusJs.textContent = `File: ${file.name} (${fileSizeMB}MB)`;
            uploadStatusJs.className = 'mt-3';
            
            // Kiểm tra kích thước
            if (fileSize > 15) {
                uploadStatusJs.textContent += ' - File quá lớn, giới hạn là 15MB';
                uploadStatusJs.classList.add('status-error');
                uploadButton.disabled = true;
            } else {
                uploadStatusJs.classList.remove('status-error');
                uploadButton.disabled = false;
            }
        }
    }
    
    // --- Phương pháp upload qua XHR ---
    function uploadViaXHR() {
        const formData = new FormData(uploadForm);
        const actionUrl = uploadForm.getAttribute('action');
        const xhr = new XMLHttpRequest();
        
        // Thiết lập timeout dài hơn cho file lớn (5 phút)
        xhr.timeout = 300000;
        
        // Theo dõi tiến trình upload
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                const currentTime = Date.now();
                const elapsedTime = (currentTime - uploadStartTime) / 1000; // seconds
                updateProgressUI(percentComplete, e.loaded, e.total, elapsedTime);
            }
        });
        
        xhr.addEventListener('load', function() {
            progressBarInner.style.width = '100%';
            
            if (xhr.status === 200) {
                handleXhrSuccess(xhr);
            } else {
                // Xử lý các mã HTTP lỗi
                handleHttpError(xhr.status);
            }
        });
        
        xhr.addEventListener('error', function() {
            console.log("XHR error occurred");
            handleError('Lỗi kết nối mạng. Vui lòng thử lại sau.');
        });
        
        xhr.addEventListener('timeout', function() {
            handleError('Quá thời gian chờ phản hồi từ server.');
        });
        
        xhr.addEventListener('abort', function() {
            handleError('Upload bị hủy.');
        });
        
        // Mở kết nối và gửi dữ liệu
        try {
            xhr.open('POST', actionUrl, true);
            xhr.send(formData);
        } catch (e) {
            console.error("Exception in XHR upload", e);
            handleError('Lỗi không mong muốn khi gửi yêu cầu.');
        }
    }
    
    // --- Xử lý thành công của XHR ---
    function handleXhrSuccess(xhr) {
        try {
            const responseText = xhr.responseText.trim();
            
            // Kiểm tra nếu phản hồi bắt đầu bằng <!DOCTYPE hoặc <html
            if (responseText.startsWith('<!DOCTYPE') || responseText.startsWith('<html')) {
                throw new Error('Server trả về HTML thay vì JSON.');
            }
            
            try {
                const jsonResponse = JSON.parse(responseText);
                if (jsonResponse.success) {
                    handleSuccess();
                } else {
                    throw new Error(jsonResponse.error || 'Lỗi không xác định từ server');
                }
            } catch (e) {
                throw new Error('Dữ liệu nhận được không phải JSON hợp lệ');
            }
        } catch (error) {
            handleError(error.message);
        }
    }
    
    // --- Xử lý thành công chung ---
    function handleSuccess() {
        uploadStatusJs.textContent = 'Tải lên thành công! Đang chuyển hướng...';
        uploadStatusJs.classList.remove('status-error');
        uploadStatusJs.classList.add('status-success');
        uploadProgressText.style.display = 'none'; // Hide progress text
        progressBarContainer.style.display = 'none'; // Hide progress bar
        uploadDetails.style.display = 'none'; // Hide details

        setTimeout(function() {
            window.location.href = transactionUrl + '?upload=success';
        }, 1000);
    }
    
    // --- Cập nhật UI tiến trình ---
    function updateProgressUI(percent, loadedBytes, totalBytes, elapsedTime) {
        progressBarInner.style.width = percent + '%';
        uploadProgressText.textContent = `Đang tải lên: ${Math.round(percent)}%`;

        if (elapsedTime > 0) {
            const bytesPerSecond = loadedBytes / elapsedTime;
            const remainingBytes = totalBytes - loadedBytes;
            const remainingSeconds = bytesPerSecond > 0 ? remainingBytes / bytesPerSecond : Infinity;

            uploadSpeedSpan.textContent = formatSpeed(bytesPerSecond);
            uploadTimeRemainingSpan.textContent = formatTime(remainingSeconds);
            uploadDetails.style.display = 'block'; // Show details
        } else {
            uploadDetails.style.display = 'none'; // Hide if no time elapsed yet
        }
    }

    // --- Helper function to format speed ---
    function formatSpeed(bytesPerSecond) {
        if (bytesPerSecond < 1024) {
            return bytesPerSecond.toFixed(0) + ' B/s';
        } else if (bytesPerSecond < 1024 * 1024) {
            return (bytesPerSecond / 1024).toFixed(1) + ' KB/s';
        } else {
            return (bytesPerSecond / (1024 * 1024)).toFixed(1) + ' MB/s';
        }
    }

    // --- Helper function to format time ---
    function formatTime(seconds) {
        if (seconds === Infinity || isNaN(seconds) || seconds < 0) {
            return 'ước tính...';
        }
        if (seconds < 60) {
            return Math.round(seconds) + ' giây còn lại';
        } else if (seconds < 3600) {
            return Math.round(seconds / 60) + ' phút còn lại';
        } else {
            return Math.round(seconds / 3600) + ' giờ còn lại';
        }
    }

    // --- Xử lý lỗi HTTP ---
    function handleHttpError(status) {
        let errorMsg = 'Lỗi không xác định';
        if (status === 413) {
            errorMsg = 'File quá lớn so với giới hạn của server';
        } else if (status === 403) {
            errorMsg = 'Không có quyền upload file';
        } else if (status === 404) {
            errorMsg = 'Đường dẫn upload không chính xác';
        } else if (status >= 500) {
            errorMsg = 'Lỗi server (mã ' + status + ')';
        }
        
        handleError(errorMsg);
    }
    
    // --- Xử lý lỗi chung ---
    function handleError(message) {
        console.error('Upload error:', message);
        uploadStatusJs.textContent = 'Lỗi: ' + message;
        uploadStatusJs.classList.remove('status-success');
        uploadStatusJs.classList.add('status-error');
        uploadButton.disabled = false;
        uploadButton.innerText = 'Gửi minh chứng';
        uploadProgressText.textContent = 'Tải lên thất bại';
        progressBarContainer.style.display = 'none'; // Hide progress bar on error
        uploadDetails.style.display = 'none'; // Hide details on error
    }
});
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>
