// Script JS để xử lý các chức năng voucher và xác nhận chuyển trang
document.addEventListener('DOMContentLoaded', function() {
    // Biến cờ để kiểm soát việc hiển thị hộp thoại xác nhận trình duyệt
    let allowBrowserDialog = true;
    // Biến cờ để xác định xem có phải là tải lại trang bằng bàn phím không
    let isKeyboardReload = false;
    // Biến cờ để xác định xem có phải điều hướng có chủ đích sau khi xác nhận dialog tùy chỉnh không
    // hoặc tải lại trang có chủ đích từ mã JS (ví dụ sau khi áp dụng/xóa voucher)
    let isProgrammaticNavigation = false;

    // --- Chức năng áp dụng voucher ---
    const isTrial = JS_IS_TRIAL; // Được thay thế bởi PHP
    const isRenewal = JS_IS_RENEWAL; // Được thay thế bởi PHP
    const basePrice = JS_BASE_PRICE; // Giá cơ bản trước thuế
    const vatValue = JS_VAT_VALUE; // Tỷ lệ VAT
    const currentPrice = JS_CURRENT_PRICE; // Đã bao gồm VAT
    const orderDescription = JS_ORDER_DESCRIPTION; // Được thay thế bởi PHP
    const baseUrl = JS_BASE_URL; // Được thay thế bởi PHP
    
    // Hàm cập nhật mã QR với số tiền mới
    function updateQRCode(amount) {
        if (isTrial) return; // Không cần cập nhật QR nếu là gói dùng thử
        
        const newVietQRURL = `https://img.vietqr.io/image/${JS_VIETQR_BANK_ID}-${JS_VIETQR_ACCOUNT_NO}-${JS_VIETQR_IMAGE_TEMPLATE}.png?amount=${Math.round(amount)}&addInfo=${encodeURIComponent(orderDescription)}&accountName=${encodeURIComponent(JS_VIETQR_ACCOUNT_NAME)}`;
        
        const qrcodeImg = document.querySelector('#qrcode img');
        if (qrcodeImg) {
            qrcodeImg.src = newVietQRURL;
        }
    }
    
    let appliedVoucherId = null;
    
    if (!isTrial) {
        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
        };

        const voucherSections = document.querySelectorAll('.voucher-section');
        
        voucherSections.forEach(section => {
            const voucherInput = section.querySelector('.voucher-input');
            const applyBtn = section.querySelector('.voucher-btn');
            const removeBtn = section.querySelector('.voucher-remove');
            const voucherStatus = section.querySelector('.voucher-status');
            const voucherInfo = section.querySelector('.voucher-info');
            const totalPriceDisplay = document.querySelector('.summary-total strong');
            
            if (applyBtn && voucherInput) {
                applyBtn.addEventListener('click', function() {
                    const voucherCode = voucherInput.value.trim();
                    if (!voucherCode) {
                        voucherStatus.textContent = 'Vui lòng nhập mã giảm giá';
                        voucherStatus.className = 'voucher-status error';
                        return;
                    }
                    
                    applyBtn.disabled = true;
                    if (voucherStatus) {
                        voucherStatus.textContent = 'Đang kiểm tra...';
                        voucherStatus.className = 'voucher-status';
                    }
                    
                    const formData = new FormData();
                    formData.append('voucher_code', voucherCode);
                    
                    const displayedPrice = totalPriceDisplay ? 
                        parseFloat(totalPriceDisplay.textContent.replace(/[^\d]/g, '')) : 
                        currentPrice;
                        
                    formData.append('order_amount', displayedPrice || currentPrice);
                    formData.append('context', isRenewal ? 'renewal' : 'purchase');
                    formData.append('csrf_token', JS_CSRF_TOKEN);
                    
                    fetch(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=apply_voucher`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Voucher response:', data);
                        
                        if (data.status) {
                            if (voucherStatus) {
                                voucherStatus.textContent = data.message;
                                voucherStatus.className = 'voucher-status success';
                            }
                            appliedVoucherId = data.data.voucher_id;
                            const appliedVoucherCode = section.querySelector('[id^="applied-voucher-code"]');
                            if (appliedVoucherCode) appliedVoucherCode.textContent = data.data.voucher_code;
                            
                            const discountInfo = section.querySelector('[id^="discount-info"]');
                            if (discountInfo) {
                                let discountMessage = '';
                                if (data.data.voucher_type === 'percentage_discount') discountMessage = `Giảm giá: ${formatCurrency(data.data.discount_value)}`;
                                else if (data.data.voucher_type === 'fixed_discount') discountMessage = `Giảm giá cố định: ${formatCurrency(data.data.discount_value)}`;
                                else if (data.data.voucher_type === 'extend_duration') discountMessage = `Tăng thêm ${data.data.additional_months} tháng sử dụng`;
                                discountInfo.textContent = discountMessage;
                            }
                            
                            if (totalPriceDisplay) totalPriceDisplay.textContent = formatCurrency(data.data.new_amount);
                            
                            const paymentAmountElement = document.getElementById('payment-amount');
                            if (paymentAmountElement) paymentAmountElement.textContent = formatCurrency(data.data.new_amount);
                            updateQRCode(data.data.new_amount);
                            
                            // Đánh dấu là tải lại trang có chủ đích trước khi reload
                            isProgrammaticNavigation = true;
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000); // Giữ lại timeout nếu cần thiết cho UX

                            if (voucherInfo) voucherInfo.style.display = 'block';
                            voucherInput.value = '';
                            voucherInput.style.display = 'none';
                            applyBtn.style.display = 'none';
                            
                        } else {
                            if (voucherStatus) {
                                voucherStatus.textContent = data.message;
                                voucherStatus.className = 'voucher-status error';
                            }
                        }
                    })
                    .catch(error => {
                        if (voucherStatus) {
                            voucherStatus.textContent = 'Lỗi khi kiểm tra mã giảm giá. Vui lòng thử lại sau.';
                            voucherStatus.className = 'voucher-status error';
                        }
                        console.error('Voucher application error:', error);
                    })
                    .finally(() => {
                        applyBtn.disabled = false;
                    });
                });
            }
            
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    removeBtn.disabled = true;
                    const formData = new FormData();
                    formData.append('context', isRenewal ? 'renewal' : 'purchase');
                    formData.append('csrf_token', JS_CSRF_TOKEN);
                    
                    fetch(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=remove_voucher`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            if (voucherStatus) {
                                voucherStatus.textContent = data.message;
                                voucherStatus.className = 'voucher-status';
                            }
                            if (voucherInfo) voucherInfo.style.display = 'none';
                            if (voucherInput && applyBtn) {
                                voucherInput.style.display = '';
                                applyBtn.style.display = '';
                            }
                            if (totalPriceDisplay) totalPriceDisplay.textContent = formatCurrency(data.data.original_amount);
                            const paymentAmountElement = document.getElementById('payment-amount');
                            if (paymentAmountElement) paymentAmountElement.textContent = formatCurrency(data.data.original_amount);
                            updateQRCode(data.data.original_amount);
                            appliedVoucherId = null;
                            
                            // Đánh dấu là tải lại trang có chủ đích trước khi reload
                            isProgrammaticNavigation = true;
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000); // Giữ lại timeout nếu cần thiết cho UX
                        } else {
                            if (voucherStatus) {
                                voucherStatus.textContent = data.message;
                                voucherStatus.className = 'voucher-status error';
                            }
                        }
                    })
                    .catch(error => {
                        if (voucherStatus) {
                            voucherStatus.textContent = 'Lỗi khi xóa mã giảm giá. Vui lòng thử lại sau.';
                            voucherStatus.className = 'voucher-status error';
                        }
                        console.error('Voucher removal error:', error);
                    })
                    .finally(() => {
                        removeBtn.disabled = false;
                    });
                });
            }
        });
        
        const copyButtons = document.querySelectorAll('.bank-details code[data-copy-target]');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetSelector = this.getAttribute('data-copy-target');
                const targetElement = document.querySelector(targetSelector);
                if (targetElement) {
                    let textToCopy = targetElement.innerText.trim();
                    if (targetSelector === '#payment-amount') {
                        textToCopy = textToCopy.replace(/đ|\.|,/g, '');
                    }
                    navigator.clipboard.writeText(textToCopy)
                        .then(() => {
                            const originalText = this.innerText;
                            this.innerText = 'Đã chép!';
                            this.style.backgroundColor = 'var(--success-100, #D1FAE5)';
                            this.style.borderColor = 'var(--success-300, #6EE7B7)';
                            setTimeout(() => {
                                this.innerText = originalText;
                                this.style.backgroundColor = '';
                                this.style.borderColor = '';
                            }, 1500);
                        })
                        .catch(err => {
                            console.error('Lỗi sao chép: ', err);
                            prompt('Không thể tự động sao chép. Vui lòng sao chép thủ công:', textToCopy);
                        });
                }
            });
        });
    } // End of if (!isTrial)

    // --- Navigation Confirmation Logic ---

    // Lắng nghe sự kiện nhấn phím để phát hiện tải lại trang (F5, Ctrl+R, Cmd+R)
    window.addEventListener('keydown', function(event) {
        if (((event.ctrlKey || event.metaKey) && (event.key === 'r' || event.key === 'R')) || event.key === 'F5') {
            isKeyboardReload = true;
        }
    });

    // Xử lý xác nhận khi người dùng cố gắng rời khỏi trang
    window.addEventListener('beforeunload', function(e) {
        // Trường hợp 1: Tải lại bằng bàn phím (F5, Ctrl+R)
        if (isKeyboardReload) {
            isKeyboardReload = false; // Reset cờ cho lần tương tác tiếp theo
            // Không hiển thị hộp thoại xác nhận, cho phép tải lại tự nhiên
            return; // Hoặc return undefined;
        }

        // Trường hợp 2: Điều hướng có chủ đích từ mã JS (sau khi xác nhận dialog tùy chỉnh,
        // hoặc sau khi áp dụng/xóa voucher và trang tự reload)
        if (isProgrammaticNavigation) {
            isProgrammaticNavigation = false; // Reset cờ
            // Không hiển thị hộp thoại xác nhận
            return; // Hoặc return undefined;
        }

        // Trường hợp 3: Các hành động khác (nhấn back/forward, đóng tab, gõ URL mới,
        // nhấn nút reload của trình duyệt) và dialog tùy chỉnh KHÔNG đang được hiển thị
        if (allowBrowserDialog) {
            const confirmationMessage = 'Bạn có chắc chắn muốn rời khỏi trang thanh toán?';
            e.preventDefault(); // Cần thiết cho một số trình duyệt để hiển thị thông báo tùy chỉnh
            e.returnValue = confirmationMessage; // Dành cho các trình duyệt cũ hơn
            return confirmationMessage; // Dành cho các trình duyệt hiện đại
        }
        // Nếu allowBrowserDialog = false, nghĩa là một dialog tùy chỉnh đang được hiển thị
        // hoặc vừa được xử lý, không cần hộp thoại của trình duyệt nữa.
    });
    
    function setupNavigationConfirmation() {
        // Hàm chung để xử lý khi người dùng hủy/đóng dialog tùy chỉnh
        const commonOnCancelOrClose = () => {
            allowBrowserDialog = true; // Cho phép hộp thoại trình duyệt hiển thị lại
            isProgrammaticNavigation = false; // Đảm bảo reset cờ này nếu hủy
        };

        // Xác nhận khi nhấp vào các liên kết trong sidebar
        const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetHref = this.getAttribute('href');
                
                allowBrowserDialog = false; // Tạm thời tắt hộp thoại trình duyệt
                
                showConfirmationDialog(
                    'Xác nhận chuyển trang',
                    'Bạn có chắc chắn muốn rời khỏi trang thanh toán?',
                    function() { // onConfirm
                        isProgrammaticNavigation = true; // Đánh dấu là điều hướng có chủ đích
                        window.location.href = targetHref;
                    },
                    commonOnCancelOrClose // onCancelOrClose
                );
            });
        });

        // Xác nhận khi nhấp vào nút "Đã thanh toán"
        const paymentConfirmBtn = document.querySelector('.btn-payment-confirm');
        if (paymentConfirmBtn) {
            paymentConfirmBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetHref = this.getAttribute('data-href');
                
                allowBrowserDialog = false; // Tạm thời tắt hộp thoại trình duyệt
                
                showConfirmationDialog(
                    'Xác nhận đã thanh toán',
                    'Bạn xác nhận đã hoàn tất thanh toán và muốn tải lên minh chứng?',
                    function() { // onConfirm
                        isProgrammaticNavigation = true; // Đánh dấu là điều hướng có chủ đích
                        window.location.href = targetHref;
                    },
                    commonOnCancelOrClose // onCancelOrClose
                );
            });
        }
    }
    
    // Hàm hiển thị hộp thoại xác nhận tùy chỉnh
    function showConfirmationDialog(title, message, onConfirm, onCancelOrClose) {
        const dialogOverlay = document.createElement('div');
        dialogOverlay.style.position = 'fixed';
        dialogOverlay.style.top = '0';
        dialogOverlay.style.left = '0';
        dialogOverlay.style.width = '100%';
        dialogOverlay.style.height = '100%';
        dialogOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        dialogOverlay.style.display = 'flex';
        dialogOverlay.style.justifyContent = 'center';
        dialogOverlay.style.alignItems = 'center';
        dialogOverlay.style.zIndex = '10000';
        
        const dialogContent = document.createElement('div');
        dialogContent.style.backgroundColor = 'white';
        dialogContent.style.padding = '1.5rem';
        dialogContent.style.borderRadius = '0.5rem';
        dialogContent.style.maxWidth = '400px';
        dialogContent.style.width = '90%';
        dialogContent.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
        dialogContent.style.position = 'relative';
        dialogContent.style.zIndex = '10001';
        
        const titleEl = document.createElement('h4');
        titleEl.style.marginTop = '0';
        titleEl.style.color = 'var(--gray-800)';
        titleEl.style.fontWeight = 'var(--font-semibold)';
        titleEl.textContent = title;
        
        const messageEl = document.createElement('p');
        messageEl.style.color = 'var(--gray-600)';
        messageEl.textContent = message;
        
        const buttonsDiv = document.createElement('div');
        buttonsDiv.style.display = 'flex';
        buttonsDiv.style.justifyContent = 'space-between';
        buttonsDiv.style.marginTop = '1.5rem';
        
        const cancelBtn = document.createElement('button');
        cancelBtn.textContent = 'Hủy';
        cancelBtn.style.backgroundColor = 'var(--gray-200)';
        cancelBtn.style.color = 'var(--gray-700)';
        cancelBtn.style.border = 'none';
        cancelBtn.style.padding = '8px 16px';
        cancelBtn.style.borderRadius = '4px';
        cancelBtn.style.cursor = 'pointer';
        
        const confirmBtn = document.createElement('button');
        confirmBtn.textContent = 'Xác nhận';
        confirmBtn.style.backgroundColor = 'var(--primary-600)';
        confirmBtn.style.color = 'white';
        confirmBtn.style.border = 'none';
        confirmBtn.style.padding = '8px 16px';
        confirmBtn.style.borderRadius = '4px';
        confirmBtn.style.cursor = 'pointer';
        
        buttonsDiv.appendChild(cancelBtn);
        buttonsDiv.appendChild(confirmBtn);
        dialogContent.appendChild(titleEl);
        dialogContent.appendChild(messageEl);
        dialogContent.appendChild(buttonsDiv);
        dialogOverlay.appendChild(dialogContent);
        document.body.appendChild(dialogOverlay);

        const removeDialog = () => {
            if (dialogOverlay.parentNode) {
                document.body.removeChild(dialogOverlay);
            }
        };

        cancelBtn.addEventListener('click', function() {
            removeDialog();
            if (typeof onCancelOrClose === 'function') {
                onCancelOrClose();
            }
        });
        
        confirmBtn.addEventListener('click', function() {
            // Việc xóa dialog có thể được xử lý bởi onConfirm nếu nó điều hướng.
            // Tuy nhiên, để chắc chắn, gọi removeDialog() ở đây.
            removeDialog(); 
            if (typeof onConfirm === 'function') {
                onConfirm(); // Hàm này sẽ đặt isProgrammaticNavigation = true và điều hướng
            }
        });
        
        dialogOverlay.addEventListener('click', function(e) {
            if (e.target === dialogOverlay) { // Chỉ đóng khi click vào overlay, không phải content
                removeDialog();
                if (typeof onCancelOrClose === 'function') {
                    onCancelOrClose();
                }
            }
        });
    }
    
    // Chạy thiết lập xác nhận chuyển trang
    setupNavigationConfirmation();
});