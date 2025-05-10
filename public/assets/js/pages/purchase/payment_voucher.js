// Script JS để xử lý các chức năng voucher và xác nhận chuyển trang
document.addEventListener('DOMContentLoaded', function() {
    // Biến cờ để kiểm soát việc hiển thị hộp thoại xác nhận
    let allowBrowserDialog = true;
    // --- Chức năng áp dụng voucher ---
    const isTrial = JS_IS_TRIAL; // Được thay thế bởi PHP
    const isRenewal = JS_IS_RENEWAL; // Được thay thế bởi PHP
    const currentPrice = JS_CURRENT_PRICE; // Được thay thế bởi PHP
    const orderDescription = JS_ORDER_DESCRIPTION; // Được thay thế bởi PHP
    const baseUrl = JS_BASE_URL; // Được thay thế bởi PHP
    
    // Hàm cập nhật mã QR với số tiền mới
    function updateQRCode(amount) {
        if (isTrial) return; // Không cần cập nhật QR nếu là gói dùng thử
        
        // Tạo URL mới cho VietQR với số tiền đã cập nhật
        const newVietQRURL = `https://img.vietqr.io/image/${JS_VIETQR_BANK_ID}-${JS_VIETQR_ACCOUNT_NO}-${JS_VIETQR_IMAGE_TEMPLATE}.png?amount=${Math.round(amount)}&addInfo=${encodeURIComponent(orderDescription)}&accountName=${encodeURIComponent(JS_VIETQR_ACCOUNT_NAME)}`;
        
        // Tìm và cập nhật ảnh QR
        const qrcodeImg = document.querySelector('#qrcode img');
        if (qrcodeImg) {
            qrcodeImg.src = newVietQRURL;
        }
    }
    
    // Khởi tạo biến để lưu trữ ID của voucher đã áp dụng
    let appliedVoucherId = null;
    
    if (!isTrial) {
        // Hàm định dạng tiền tệ
        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
        };

        // Tìm tất cả các vùng nhập voucher trên trang
        const voucherSections = document.querySelectorAll('.voucher-section');
        
        voucherSections.forEach(section => {
            const voucherInput = section.querySelector('.voucher-input');
            const applyBtn = section.querySelector('.voucher-btn');
            const removeBtn = section.querySelector('.voucher-remove');
            const voucherStatus = section.querySelector('.voucher-status');
            const voucherInfo = section.querySelector('.voucher-info');
            const totalPriceDisplay = document.querySelector('.summary-total strong');
            
            // Xử lý sự kiện áp dụng voucher
            if (applyBtn && voucherInput) {
                applyBtn.addEventListener('click', function() {
                    const voucherCode = voucherInput.value.trim();
                    if (!voucherCode) {
                        voucherStatus.textContent = 'Vui lòng nhập mã giảm giá';
                        voucherStatus.className = 'voucher-status error';
                        return;
                    }
                    
                    // Disable nút khi đang gửi request
                    applyBtn.disabled = true;
                    if (voucherStatus) {
                        voucherStatus.textContent = 'Đang kiểm tra...';
                        voucherStatus.className = 'voucher-status';
                    }
                    
                    // Tạo FormData
                    const formData = new FormData();
                    formData.append('voucher_code', voucherCode);
                    
                    // Lấy giá hiện tại từ hiển thị
                    const displayedPrice = totalPriceDisplay ? 
                        parseFloat(totalPriceDisplay.textContent.replace(/[^\d]/g, '')) : 
                        currentPrice;
                        
                    formData.append('order_amount', displayedPrice || currentPrice);
                    formData.append('context', isRenewal ? 'renewal' : 'purchase');
                    formData.append('csrf_token', JS_CSRF_TOKEN);
                    
                    // Gửi request
                    fetch(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=apply_voucher`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Voucher response:', data);
                        
                        if (data.status) {
                            // Voucher hợp lệ
                            if (voucherStatus) {
                                voucherStatus.textContent = data.message;
                                voucherStatus.className = 'voucher-status success';
                            }
                            
                            // Lưu ID voucher
                            appliedVoucherId = data.data.voucher_id;
                            
                            // Hiển thị thông tin voucher
                            const appliedVoucherCode = section.querySelector('[id^="applied-voucher-code"]');
                            if (appliedVoucherCode) {
                                appliedVoucherCode.textContent = data.data.voucher_code;
                            }
                            
                            // Hiển thị thông tin giảm giá
                            const discountInfo = section.querySelector('[id^="discount-info"]');
                            if (discountInfo) {
                                let discountMessage = '';
                                if (data.data.voucher_type === 'percentage_discount') {
                                    discountMessage = `Giảm giá: ${formatCurrency(data.data.discount_value)}`;
                                } else if (data.data.voucher_type === 'fixed_discount') {
                                    discountMessage = `Giảm giá cố định: ${formatCurrency(data.data.discount_value)}`;
                                } else if (data.data.voucher_type === 'extend_duration') {
                                    discountMessage = `Tăng thêm ${data.data.additional_months} tháng sử dụng`;
                                }
                                discountInfo.textContent = discountMessage;
                            }
                            
                            // Cập nhật tổng tiền
                            if (totalPriceDisplay) {
                                totalPriceDisplay.textContent = formatCurrency(data.data.new_amount);
                            }
                            
                            // Cập nhật số tiền trong phần thông tin thanh toán
                            const paymentAmountElement = document.getElementById('payment-amount');
                            if (paymentAmountElement) {
                                paymentAmountElement.textContent = formatCurrency(data.data.new_amount);
                            }
                            
                            // Cập nhật mã QR
                            updateQRCode(data.data.new_amount);
                            
                            // Hiển thị box voucher và ẩn form nhập
                            if (voucherInfo) {
                                voucherInfo.style.display = 'block';
                            }
                            
                            voucherInput.value = '';
                            voucherInput.style.display = 'none';
                            applyBtn.style.display = 'none';
                            
                        } else {
                            // Voucher không hợp lệ
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
                        // Enable lại nút
                        applyBtn.disabled = false;
                    });
                });
            }
            
            // Xử lý sự kiện xóa voucher
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    // Disable nút khi đang gửi request
                    removeBtn.disabled = true;
                    
                    // Tạo FormData
                    const formData = new FormData();
                    formData.append('context', isRenewal ? 'renewal' : 'purchase');
                    formData.append('csrf_token', JS_CSRF_TOKEN);
                    
                    // Gửi request
                    fetch(`${baseUrl}/public/handlers/action_handler.php?module=purchase&action=remove_voucher`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            // Xóa voucher thành công
                            if (voucherStatus) {
                                voucherStatus.textContent = data.message;
                                voucherStatus.className = 'voucher-status';
                            }
                            
                            // Ẩn thông tin voucher và hiển thị lại form nhập
                            if (voucherInfo) {
                                voucherInfo.style.display = 'none';
                            }
                            
                            if (voucherInput && applyBtn) {
                                voucherInput.style.display = '';
                                applyBtn.style.display = '';
                            }
                            
                            // Cập nhật tổng tiền về giá ban đầu
                            if (totalPriceDisplay) {
                                totalPriceDisplay.textContent = formatCurrency(data.data.original_amount);
                            }
                            
                            // Cập nhật số tiền trong phần thông tin thanh toán
                            const paymentAmountElement = document.getElementById('payment-amount');
                            if (paymentAmountElement) {
                                paymentAmountElement.textContent = formatCurrency(data.data.original_amount);
                            }
                            
                            // Cập nhật mã QR
                            updateQRCode(data.data.original_amount);
                            
                            // Reset voucher ID
                            appliedVoucherId = null;
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
                        // Enable lại nút
                        removeBtn.disabled = false;
                    });
                });
            }
        });
        
        // Xử lý các nút copy
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
    }      // Thêm xác nhận khi người dùng cố gắng rời khỏi trang
    function setupNavigationConfirmation() {
        // Xác nhận khi tải lại trang hoặc đóng tab, nhưng chỉ khi không có hộp thoại tùy chỉnh nào đang hiển thị
        window.addEventListener('beforeunload', function(e) {
            if (allowBrowserDialog) {
                e.preventDefault();
                e.returnValue = 'Bạn có chắc chắn muốn rời khỏi trang thanh toán?';
                return e.returnValue;
            }
        });
        
        // Xác nhận khi nhấp vào các liên kết trong sidebar
        const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
        sidebarLinks.forEach(link => {            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetHref = this.getAttribute('href');
                
                // Tắt hộp thoại trình duyệt khi hiển thị hộp thoại tùy chỉnh
                allowBrowserDialog = false;
                
                showConfirmationDialog(
                    'Xác nhận chuyển trang',
                    'Bạn có chắc chắn muốn rời khỏi trang thanh toán?',
                    function() {
                        window.location.href = targetHref;
                    }
                );
            });
        });        // Xác nhận khi nhấp vào nút đã thanh toán
        const paymentConfirmBtn = document.querySelector('.btn-payment-confirm');
        if (paymentConfirmBtn) {
            paymentConfirmBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetHref = this.getAttribute('data-href');
                
                // Tắt hộp thoại trình duyệt khi hiển thị hộp thoại tùy chỉnh
                allowBrowserDialog = false;
                
                showConfirmationDialog(
                    'Xác nhận đã thanh toán',
                    'Bạn xác nhận đã hoàn tất thanh toán và muốn tải lên minh chứng?',
                    function() {
                        window.location.href = targetHref;
                    }
                );
            });
        }
    }
      // Hàm hiển thị hộp thoại xác nhận
    function showConfirmationDialog(title, message, onConfirm) {
        // Tạo phần tử dialog
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
        
        // Tạo nội dung dialog
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
        
        // Thêm các phần tử vào dialog
        buttonsDiv.appendChild(cancelBtn);
        buttonsDiv.appendChild(confirmBtn);
        
        dialogContent.appendChild(titleEl);
        dialogContent.appendChild(messageEl);
        dialogContent.appendChild(buttonsDiv);
        
        dialogOverlay.appendChild(dialogContent);
        document.body.appendChild(dialogOverlay);
          // Xử lý sự kiện các nút
        cancelBtn.addEventListener('click', function() {
            document.body.removeChild(dialogOverlay);
            // Kích hoạt lại hộp thoại trình duyệt sau khi đóng hộp thoại tùy chỉnh
            allowBrowserDialog = true;
        });
        
        confirmBtn.addEventListener('click', function() {
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
            document.body.removeChild(dialogOverlay);
        });
          // Đóng dialog khi click bên ngoài
        dialogOverlay.addEventListener('click', function(e) {
            if (e.target === dialogOverlay) {
                document.body.removeChild(dialogOverlay);
                // Kích hoạt lại hộp thoại trình duyệt sau khi đóng hộp thoại tùy chỉnh
                allowBrowserDialog = true;
            }
        });
    }
    
    // Chạy thiết lập xác nhận chuyển trang
    setupNavigationConfirmation();
});
