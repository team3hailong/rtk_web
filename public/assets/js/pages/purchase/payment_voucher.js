// Script JS để xử lý các chức năng voucher, sao chép và xác nhận chuyển trang
document.addEventListener('DOMContentLoaded', function() {
    // --- Lấy các biến toàn cục từ PHP ---
    const isTrial = typeof JS_IS_TRIAL !== 'undefined' ? JS_IS_TRIAL : false;
    const isRenewal = typeof JS_IS_RENEWAL !== 'undefined' ? JS_IS_RENEWAL : false;
    const baseUrl = typeof JS_BASE_URL !== 'undefined' ? JS_BASE_URL : '';
    const csrfToken = typeof JS_CSRF_TOKEN !== 'undefined' ? JS_CSRF_TOKEN : '';
    const currentPrice = typeof JS_CURRENT_PRICE !== 'undefined' ? JS_CURRENT_PRICE : 0;

    // Biến cờ để quản lý việc điều hướng
    let isProgrammaticNavigation = false;

    // --- Xử lý Voucher (chỉ chạy nếu không phải gói dùng thử) ---
    if (!isTrial) {
        const applyBtn = document.getElementById('apply-voucher');
        const removeBtn = document.getElementById('remove-voucher');
        const voucherInput = document.getElementById('voucher-code');
        const voucherStatus = document.getElementById('voucher-status');

        // Sự kiện khi nhấn nút "Áp dụng"
        if (applyBtn && voucherInput) {
            applyBtn.addEventListener('click', function() {
                // ... (Logic áp dụng voucher giữ nguyên như cũ)
                const voucherCode = voucherInput.value.trim();
                if (!voucherCode) {
                    voucherStatus.textContent = 'Vui lòng nhập mã giảm giá.';
                    voucherStatus.className = 'voucher-status error';
                    return;
                }
                applyBtn.disabled = true;
                voucherStatus.textContent = 'Đang kiểm tra...';
                const formData = new FormData();
                formData.append('voucher_code', voucherCode);
                formData.append('context', isRenewal ? 'renewal' : 'purchase');
                formData.append('csrf_token', csrfToken);
                const totalPriceDisplay = document.querySelector('#total-price-display');
                const priceValue = totalPriceDisplay ? parseFloat(totalPriceDisplay.textContent.replace(/[^\d]/g, '')) : currentPrice;
                formData.append('order_amount', priceValue);

                fetch(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=apply_voucher`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        voucherStatus.textContent = data.message;
                        voucherStatus.className = 'voucher-status success';
                        isProgrammaticNavigation = true;
                        setTimeout(() => { window.location.reload(); }, 1000);
                    } else {
                        voucherStatus.textContent = data.message || 'Mã giảm giá không hợp lệ.';
                        voucherStatus.className = 'voucher-status error';
                        applyBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Voucher application error:', error);
                    voucherStatus.textContent = 'Lỗi kết nối, vui lòng thử lại.';
                    voucherStatus.className = 'voucher-status error';
                    applyBtn.disabled = false;
                });
            });
        }

        // Sự kiện khi nhấn nút "Xóa" voucher
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                // ... (Logic xóa voucher giữ nguyên như cũ)
                removeBtn.disabled = true;
                const formData = new FormData();
                formData.append('context', isRenewal ? 'renewal' : 'purchase');
                formData.append('csrf_token', csrfToken);
                fetch(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=remove_voucher`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        isProgrammaticNavigation = true;
                        window.location.reload();
                    } else {
                        alert(data.message || 'Không thể xóa mã giảm giá. Vui lòng thử lại.');
                        removeBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Voucher removal error:', error);
                    alert('Lỗi kết nối, vui lòng thử lại.');
                    removeBtn.disabled = false;
                });
            });
        }
    }

    // --- Chức năng sao chép thông tin chuyển khoản ---
    // ... (Logic sao chép giữ nguyên như cũ)
    const copyButtons = document.querySelectorAll('.bank-details code[data-copy-target]');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetSelector = this.getAttribute('data-copy-target');
            const targetElement = document.querySelector(targetSelector);
            if (targetElement) {
                let textToCopy = targetElement.innerText.trim();
                if (targetSelector === '#payment-amount') {
                    textToCopy = textToCopy.replace(/[đ.,\s]/g, '');
                }
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = this.innerText;
                    this.innerText = 'Đã chép!';
                    this.style.backgroundColor = 'var(--success-100, #D1FAE5)';
                    setTimeout(() => { this.innerText = originalText; this.style.backgroundColor = ''; }, 1500);
                }).catch(err => { console.error('Lỗi sao chép: ', err); });
            }
        });
    });

    // --- Logic xác nhận và dọn dẹp khi rời khỏi trang ---
    // Đánh dấu khi người dùng hoàn tất giao dịch để không xóa voucher
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            isProgrammaticNavigation = true;
        });
    });

    const paymentConfirmBtn = document.querySelector('.btn-payment-confirm');
    if (paymentConfirmBtn) {
        paymentConfirmBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetHref = this.getAttribute('data-href');
            if (confirm('Bạn xác nhận đã hoàn tất thanh toán và muốn tải lên minh chứng?')) {
                isProgrammaticNavigation = true;
                window.location.href = targetHref;
            }
        });
    }

    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    sidebarLinks.forEach(link => {
        if (link.href.includes('logout') || link.classList.contains('active')) {
            return;
        }
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetHref = this.getAttribute('href');
            if (confirm('Bạn có chắc chắn muốn rời khỏi trang thanh toán? Mã giảm giá đã áp dụng sẽ bị xóa.')) {
                // Đặt isProgrammaticNavigation = true để không kích hoạt beacon
                // vì chúng ta sẽ tự xóa voucher và điều hướng
                isProgrammaticNavigation = true;
                
                // Gửi yêu cầu xóa voucher trước khi điều hướng
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('context', isRenewal ? 'renewal' : 'purchase');
                fetch(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=clear_session_voucher`, {
                    method: 'POST',
                    body: formData,
                    keepalive: true // Giúp yêu cầu có khả năng hoàn thành cao hơn
                }).finally(() => {
                    // Điều hướng đi ngay cả khi fetch lỗi
                    window.location.href = targetHref;
                });
            }
        });
    });

    // Sự kiện beforeunload sẽ xử lý các trường hợp còn lại: đóng tab, gõ URL mới, back/forward
    window.addEventListener('beforeunload', function(e) {
        // Chỉ chạy logic dọn dẹp và cảnh báo nếu đây là một hành động thoát trang không mong muốn
        if (!isProgrammaticNavigation) {
            const voucherInfo = document.getElementById('voucher-info');
            // Chỉ xóa voucher nếu nó đang được áp dụng
            if (voucherInfo && window.getComputedStyle(voucherInfo).display !== 'none') {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('context', isRenewal ? 'renewal' : 'purchase');
                // Sử dụng navigator.sendBeacon() - cách đáng tin cậy nhất
                navigator.sendBeacon(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=clear_session_voucher`, formData);
            }
            
            // Hiển thị cảnh báo cho người dùng
            const confirmationMessage = 'Bạn có chắc chắn muốn rời khỏi trang thanh toán?';
            e.preventDefault();
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
});