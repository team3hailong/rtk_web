// Show notification for auto-applied device vouchers
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have a voucher auto-applied (this variable is set by PHP if a voucher was auto-applied)
    if (typeof autoAppliedVoucher !== 'undefined' && autoAppliedVoucher) {
        // Get the voucher code
        const voucherCode = document.querySelector('#applied-voucher-code')?.textContent || 
                          document.querySelector('.voucher-code')?.textContent;
          if (voucherCode) {
            let message = `Voucher <strong>${voucherCode}</strong> đã được tự động áp dụng cho đơn hàng của bạn!`;
            
            // Add discount information if available
            if (typeof autoAppliedVoucherDiscount !== 'undefined') {
                const formattedDiscount = new Intl.NumberFormat('vi-VN').format(autoAppliedVoucherDiscount);
                message += `<br>Giảm giá: <strong>${formattedDiscount} đ</strong>`;
            }
            
            // Show notification
            showNotification(message, 'success');
        }
    }
    
    // Function to show a notification
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'auto-notification ' + type;
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-info-circle"></i>'}
                </div>
                <div class="notification-message">${message}</div>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .auto-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                max-width: 350px;
                padding: 15px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                transition: all 0.3s ease;
                opacity: 0;
                transform: translateY(-20px);
            }
            
            .auto-notification.show {
                opacity: 1;
                transform: translateY(0);
            }
            
            .auto-notification.success {
                border-left: 4px solid #48bb78;
            }
            
            .auto-notification.info {
                border-left: 4px solid #4299e1;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
            }
            
            .notification-icon {
                margin-right: 12px;
                font-size: 20px;
            }
            
            .auto-notification.success .notification-icon {
                color: #48bb78;
            }
            
            .auto-notification.info .notification-icon {
                color: #4299e1;
            }
            
            .notification-message {
                flex: 1;
                font-size: 14px;
            }
            
            .notification-close {
                background: none;
                border: none;
                cursor: pointer;
                font-size: 16px;
                color: #a0aec0;
                padding: 0;
                margin-left: 8px;
            }
        `;
        
        // Add to document
        document.head.appendChild(style);
        document.body.appendChild(notification);
        
        // Show notification after a short delay
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
                style.remove();
            }, 300);
        });
        
        // Auto close after 8 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        notification.remove();
                        style.remove();
                    }
                }, 300);
            }
        }, 8000);
    }
});
