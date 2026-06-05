// Biến toàn cục để lưu trữ các tham số từ PHP
let referralLink;
let availableBalance;
let minWithdrawalAmount = 100000;
let ajaxUrl;

// Cài đặt các tham số cần thiết
function initializeReferralSystem(config) {
    referralLink = config.referralLink;
    availableBalance = config.availableBalance;
    minWithdrawalAmount = config.minWithdrawalAmount || minWithdrawalAmount;
    ajaxUrl = config.processWithdrawalUrl;

    if (referralLink) {
        generateQRCode(referralLink);
    }
}

// Hàm tạo QR Code
function generateQRCode(link) {
    const qrElement = document.getElementById('qrcode');
    if (!qrElement) return;

    new QRious({
        element: qrElement,
        value: link,
        size: 200,
        padding: 10,
        level: 'H'
    });
}

// Hàm copy dùng chung
function copyToClipboard(elementId, typeName) {
    const copyText = document.getElementById(elementId);
    if (!copyText || !copyText.value) return;

    // Sử dụng Clipboard API hiện đại
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(copyText.value).then(() => {
            showToast(`Đã sao chép ${typeName}!`);
        }).catch(err => {
            console.error('Could not copy text: ', err);
            fallbackCopy(copyText, typeName); // Fallback nếu có lỗi
        });
    } else {
        fallbackCopy(copyText, typeName); // Fallback cho trình duyệt cũ
    }
}

// Hàm copy dự phòng cho trình duyệt cũ
function fallbackCopy(copyText, typeName) {
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    try {
        document.execCommand("copy");
        showToast(`Đã sao chép ${typeName}!`);
    } catch (err) {
        alert("Rất tiếc, không thể sao chép tự động.");
    }
}

// Hàm hiển thị thông báo toast
function showToast(message) {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.style.cssText = 'background-color: rgba(33, 150, 243, 0.9); color: white; padding: 12px 20px; border-radius: 4px; margin-top: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); min-width: 250px; opacity: 0; transition: opacity 0.3s ease;';
    toast.innerText = message;

    toastContainer.appendChild(toast);

    // Fade in
    setTimeout(() => { toast.style.opacity = '1'; }, 10);

    // Fade out và xóa
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}


// Xử lý logic chính khi DOM đã tải xong
document.addEventListener('DOMContentLoaded', function() {
    
    // Sự kiện tải xuống QR Code
    const downloadBtn = document.getElementById('download-qr-btn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            const canvas = document.getElementById('qrcode');
            if (!canvas) return;
            const link = document.createElement('a');
            link.download = 'referral-qr-code.png';
            link.href = canvas.toDataURL('image/png').replace('image/png', 'image/octet-stream');
            link.click();
        });
    }

    // Xử lý các tab chính
    const referralTabs = document.querySelectorAll('#referralTabs .nav-link');
    const tabPanes = document.querySelectorAll('#referralTabsContent .tab-pane');

    referralTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            referralTabs.forEach(item => item.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('show', 'active'));

            this.classList.add('active');
            const activePane = document.querySelector(this.getAttribute('href'));
            if (activePane) {
                activePane.classList.add('show', 'active');
            }
            localStorage.setItem('activeReferralTab', this.getAttribute('href'));
        });
    });

    const activeTab = localStorage.getItem('activeReferralTab');
    if (activeTab && document.querySelector(`a[href="${activeTab}"]`)) {
        document.querySelector(`a[href="${activeTab}"]`).click();
    }
    
    // Xử lý form rút tiền
    const withdrawalForm = document.getElementById('withdrawal-form');
    if (withdrawalForm) {
        withdrawalForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const withdrawBtn = document.getElementById('withdraw-btn');
            const btnText = document.getElementById('withdraw-btn-text');
            const btnLoading = document.getElementById('withdraw-btn-loading');
            const messageDiv = document.getElementById('withdrawal-message');
            
            const amount = parseFloat(document.getElementById('amount').value);
            
            messageDiv.style.display = 'none';
            messageDiv.className = 'alert';

            if (amount < minWithdrawalAmount) {
                messageDiv.innerText = 'Số tiền rút tối thiểu là ' + minWithdrawalAmount.toLocaleString('vi-VN') + ' VNĐ.';
                messageDiv.classList.add('alert-danger');
                messageDiv.style.display = 'block';
                return;
            }
            if (amount > availableBalance) {
                messageDiv.innerText = 'Số dư khả dụng không đủ!';
                messageDiv.classList.add('alert-danger');
                messageDiv.style.display = 'block';
                return;
            }

            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            withdrawBtn.disabled = true;

            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(new FormData(this))
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerText = data.message;
                    messageDiv.classList.add('alert-success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    messageDiv.innerText = data.message || 'Đã xảy ra lỗi không xác định.';
                    messageDiv.classList.add('alert-danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.innerText = 'Lỗi khi gửi yêu cầu. Vui lòng thử lại.';
                messageDiv.classList.add('alert-danger');
            })
            .finally(() => {
                messageDiv.style.display = 'block';
                btnText.style.display = 'inline-block';
                btnLoading.style.display = 'none';
                // Không bật lại nút nếu thành công để tránh double-submit
                if (!messageDiv.classList.contains('alert-success')) {
                    withdrawBtn.disabled = false;
                }
            });
        });
    }

    // Xử lý các tab xếp hạng
    const rankTabBtns = document.querySelectorAll('.rank-tab-btn');
    const rankTabContents = document.querySelectorAll('.rank-tab-content');

    rankTabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            rankTabBtns.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            
            const targetId = this.dataset.target;
            rankTabContents.forEach(content => {
                if ('#' + content.id === targetId) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
            localStorage.setItem('activeRankingTab', targetId);
        });
    });

    const activeRankingTab = localStorage.getItem('activeRankingTab');
    if (activeRankingTab && document.querySelector(`[data-target="${activeRankingTab}"]`)) {
        document.querySelector(`[data-target="${activeRankingTab}"]`).click();
    }
});