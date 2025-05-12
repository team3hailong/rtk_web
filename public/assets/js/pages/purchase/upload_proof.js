
document.addEventListener('DOMContentLoaded', function() {
    // --- Các phần tử DOM ---
    const uploadForm = document.getElementById('upload-form');
    const uploadButton = document.getElementById('upload-button');
    const fileInput = document.getElementById('payment_proof_image');
    const uploadProgressText = document.getElementById('upload-progress'); // Renamed from uploadProgress
    const progressBarContainer = document.getElementById('progress-bar-container');
    const progressBarInner = document.getElementById('progress-bar-inner');
    const uploadDetails = document.getElementById('upload-details');    const uploadSpeedSpan = document.getElementById('upload-speed');
    const uploadTimeRemainingSpan = document.getElementById('upload-time-remaining');
    const uploadStatusJs = document.getElementById('upload-status-js');
    // Transaction URL will be set from PHP page
    const transactionUrl = TRANSACTION_URL;

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
