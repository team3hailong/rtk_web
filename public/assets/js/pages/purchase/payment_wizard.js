/**
 * Payment Wizard Navigation
 * Handles step transitions and file upload functionality
 */

let currentStep = 1;
let selectedFile = null;

// Initialize wizard on page load
document.addEventListener('DOMContentLoaded', function() {
    if (!JS_IS_TRIAL) {
        setupWizardNavigation();
        setupFileUpload();
        setupCopyButtons();
    }
});

/**
 * Navigate to specific step
 */
function goToStep(step) {
    // Hide all sections
    document.querySelectorAll('.wizard-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show target section
    const targetSection = document.querySelector(`.wizard-section[data-section="${step}"]`);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Update step indicators
    document.querySelectorAll('.wizard-step').forEach(stepEl => {
        const stepNum = parseInt(stepEl.getAttribute('data-step'));
        stepEl.classList.remove('active', 'completed');
        
        if (stepNum === step) {
            stepEl.classList.add('active');
        } else if (stepNum < step) {
            stepEl.classList.add('completed');
        }
    });
    
    currentStep = step;
    
    // Scroll to top of wizard
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Setup wizard navigation
 */
function setupWizardNavigation() {
    // Handle voucher application/removal to update total and potentially skip step 2
    const applyVoucherBtn = document.getElementById('apply-voucher');
    const removeVoucherBtn = document.getElementById('remove-voucher');
    
    if (applyVoucherBtn) {
        applyVoucherBtn.addEventListener('click', handleVoucherApplication);
    }
    
    if (removeVoucherBtn) {
        removeVoucherBtn.addEventListener('click', handleVoucherRemoval);
    }
}

/**
 * Handle voucher application
 */
function handleVoucherApplication() {
    // The voucher application logic is in payment_voucher.js
    // After successful application, check if total is 0 to update step 2 visibility
    setTimeout(() => {
        checkOrderTotal();
    }, 500);
}

/**
 * Handle voucher removal
 */
function handleVoucherRemoval() {
    // After voucher removal, check order total again
    setTimeout(() => {
        checkOrderTotal();
    }, 500);
}

/**
 * Check order total and update step 2 visibility
 */
function checkOrderTotal() {
    const totalDisplay = document.getElementById('total-price-display');
    if (!totalDisplay) return;
    
    const totalText = totalDisplay.textContent.replace(/[^\d]/g, '');
    const total = parseInt(totalText) || 0;
    
    const freeOrderSection = document.getElementById('free-order-confirmation-section');
    const paidOrderSection = document.getElementById('payment-qr-code-section');
    
    if (total <= 0) {
        // Show free order confirmation
        if (freeOrderSection) freeOrderSection.style.display = 'block';
        if (paidOrderSection) paidOrderSection.style.display = 'none';
    } else {
        // Show paid order QR code
        if (freeOrderSection) freeOrderSection.style.display = 'none';
        if (paidOrderSection) paidOrderSection.style.display = 'block';
    }
}

/**
 * Setup file upload functionality
 */
function setupFileUpload() {
    const fileInput = document.getElementById('proof-file-input');
    const uploadArea = document.querySelector('.upload-area');
    const selectFileBtn = document.querySelector('.btn-select-file');
    
    if (!fileInput || !uploadArea) return;
    
    // File input change
    fileInput.addEventListener('change', handleFileSelect);
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--primary-600)';
        uploadArea.style.backgroundColor = '#eff6ff';
    });
    
    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--gray-300)';
        uploadArea.style.backgroundColor = 'var(--gray-50)';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--gray-300)';
        uploadArea.style.backgroundColor = 'var(--gray-50)';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect({ target: fileInput });
        }
    });
    
    // Click on upload area to select file
    uploadArea.addEventListener('click', (e) => {
        if (!e.target.closest('.btn-select-file')) {
            fileInput.click();
        }
    });
}

/**
 * Handle file selection
 */
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    
    if (!allowedTypes.includes(file.type)) {
        showUploadStatus('Vui lòng chọn file ảnh (JPG, PNG) hoặc PDF.', 'error');
        return;
    }
    
    if (file.size > maxSize) {
        showUploadStatus('Kích thước file không được vượt quá 5MB.', 'error');
        return;
    }
    
    selectedFile = file;
    displayFilePreview(file);
    
    // Enable submit button
    const submitBtn = document.getElementById('upload-submit-btn');
    if (submitBtn) {
        submitBtn.disabled = false;
    }
}

/**
 * Display file preview
 */
function displayFilePreview(file) {
    const previewContainer = document.getElementById('file-preview');
    const previewImage = document.getElementById('preview-image');
    const fileName = document.getElementById('file-name');
    const submitBtn = document.getElementById('upload-submit-btn');
    
    if (!previewContainer || !previewImage || !fileName) return;
    
    fileName.textContent = file.name;
    previewContainer.style.display = 'block';
    
    // Show image preview if it's an image
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    } else {
        previewImage.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--gray-600);">
            <i class="fas fa-file-pdf" style="font-size: 3rem; color: var(--danger-500);"></i>
            <p style="margin-top: 0.5rem;">${file.name}</p>
        </div>`;
    }
    
    // Hide upload area
    const uploadArea = document.querySelector('.upload-area');
    if (uploadArea) {
        uploadArea.style.display = 'none';
    }
    
    // Update button text
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-upload"></i> Tải lên minh chứng';
    }
}

/**
 * Remove selected file
 */
function removeFile() {
    selectedFile = null;
    
    const fileInput = document.getElementById('proof-file-input');
    if (fileInput) {
        fileInput.value = '';
    }
    
    const previewContainer = document.getElementById('file-preview');
    if (previewContainer) {
        previewContainer.style.display = 'none';
    }
    
    const uploadArea = document.querySelector('.upload-area');
    if (uploadArea) {
        uploadArea.style.display = 'block';
    }
    
    const submitBtn = document.getElementById('upload-submit-btn');
    if (submitBtn) {
        submitBtn.disabled = true;
    }
    
    showUploadStatus('', '');
}

/**
 * Submit proof - Upload with progress tracking
 */
function submitProof() {
    if (!selectedFile) {
        showUploadStatus('Vui lòng chọn file minh chứng.', 'error');
        return;
    }
    
    // Mark as programmatic navigation to prevent beforeunload popup
    if (window.isProgrammaticNavigation !== undefined) {
        window.isProgrammaticNavigation = true;
    }
    
    const submitBtn = document.getElementById('upload-submit-btn');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải lên...';
    }
    
    // Show progress bar
    if (progressContainer) {
        progressContainer.style.display = 'block';
    }
    
    const formData = new FormData();
    formData.append('payment_proof_image', selectedFile);
    formData.append('registration_id', JS_REGISTRATION_ID);
    formData.append('csrf_token', JS_CSRF_TOKEN);
    
    // Create XMLHttpRequest for progress tracking
    const xhr = new XMLHttpRequest();
    
    // Track upload progress
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            if (progressBar) {
                progressBar.style.width = percentComplete + '%';
            }
            if (progressText) {
                progressText.textContent = 'Đang tải lên: ' + Math.round(percentComplete) + '%';
            }
        }
    });
    
    // Handle completion
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showUploadStatus('Upload thành công! Đang chuyển hướng...', 'success');
                    if (progressText) {
                        progressText.textContent = 'Upload hoàn tất!';
                    }
                    setTimeout(() => {
                        // Redirect to success page or transaction page
                        window.location.href = JS_BASE_URL + '/public/pages/transaction.php?success=proof_uploaded';
                    }, 1500);
                } else {
                    showUploadStatus(response.message || 'Có lỗi xảy ra khi upload. Vui lòng thử lại.', 'error');
                    resetUploadButton(submitBtn);
                }
            } catch (e) {
                // If not JSON, might be HTML response (check for success indicators)
                if (xhr.responseText.includes('success') || xhr.responseText.includes('thành công')) {
                    showUploadStatus('Upload thành công! Đang chuyển hướng...', 'success');
                    setTimeout(() => {
                        window.location.href = JS_BASE_URL + '/public/pages/transaction.php?success=proof_uploaded';
                    }, 1500);
                } else {
                    showUploadStatus('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                    resetUploadButton(submitBtn);
                }
            }
        } else {
            showUploadStatus('Lỗi kết nối. Vui lòng thử lại.', 'error');
            resetUploadButton(submitBtn);
        }
    });
    
    // Handle errors
    xhr.addEventListener('error', function() {
        showUploadStatus('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
        resetUploadButton(submitBtn);
    });
    
    // Send request
    xhr.open('POST', JS_BASE_URL + '/public/handlers/action_handler.php?module=purchase&action=upload_payment_proof', true);
    xhr.send(formData);
}

/**
 * Reset upload button to initial state
 */
function resetUploadButton(submitBtn) {
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-upload"></i> Tải lên minh chứng';
    }
    const progressContainer = document.getElementById('upload-progress-container');
    if (progressContainer) {
        progressContainer.style.display = 'none';
    }
    const progressBar = document.getElementById('upload-progress-bar');
    if (progressBar) {
        progressBar.style.width = '0%';
    }
}

/**
 * Show upload status message
 */
function showUploadStatus(message, type) {
    const statusDiv = document.getElementById('upload-status');
    if (!statusDiv) return;
    
    if (!message) {
        statusDiv.innerHTML = '';
        statusDiv.className = 'upload-status';
        return;
    }
    
    statusDiv.innerHTML = message;
    statusDiv.className = 'upload-status ' + type;
}

/**
 * Setup copy buttons
 */
function setupCopyButtons() {
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const targetSelector = this.getAttribute('data-copy-target');
            const targetElement = document.querySelector(targetSelector);
            
            if (targetElement) {
                const textToCopy = targetElement.textContent.trim();
                copyToClipboard(textToCopy, this);
            }
        });
    });
    
    // Legacy code support for old copy buttons
    document.querySelectorAll('.bank-details code').forEach(code => {
        code.addEventListener('click', function() {
            const targetSelector = this.getAttribute('data-copy-target');
            const targetElement = document.querySelector(targetSelector);
            
            if (targetElement) {
                const textToCopy = targetElement.textContent.trim();
                copyToClipboard(textToCopy, this);
            }
        });
    });
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text, button) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess(button);
        }).catch(err => {
            fallbackCopy(text, button);
        });
    } else {
        fallbackCopy(text, button);
    }
}

/**
 * Fallback copy method
 */
function fallbackCopy(text, button) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(button);
    } catch (err) {
        console.error('Copy failed:', err);
    }
    
    document.body.removeChild(textarea);
}

/**
 * Show copy success feedback
 */
function showCopySuccess(button) {
    const originalHTML = button.innerHTML;
    const originalClass = button.className;
    
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.style.backgroundColor = 'var(--success-600)';
    button.style.color = 'white';
    button.style.borderColor = 'var(--success-600)';
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.style.backgroundColor = '';
        button.style.color = '';
        button.style.borderColor = '';
    }, 1500);
}
