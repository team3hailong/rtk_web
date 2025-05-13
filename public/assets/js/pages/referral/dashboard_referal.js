
// Biến toàn cục để lưu trữ các tham số từ PHP
let availableBalance;
let minWithdrawalAmount = 100000; // Giá trị mặc định, sẽ được cập nhật từ PHP
let ajaxUrl;

// Cài đặt các tham số cần thiết
function initializeReferralSystem(config) {
    availableBalance = config.availableBalance;
    minWithdrawalAmount = config.minWithdrawalAmount || minWithdrawalAmount;
    ajaxUrl = config.processWithdrawalUrl;
}

// Cải thiện chức năng copy với thông báo tốt hơn
function copyReferralCode() {
    var copyText = document.getElementById("referral-code");
    if (!copyText) return;
    
    try {
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");
        
        // Hiển thị toast thay vì confirm
        showToast("Đã sao chép mã giới thiệu: " + copyText.value);
    } catch (err) {
        alert("Không thể sao chép: " + err);
    }
}

function copyReferralLink() {
    var copyText = document.getElementById("referral-link-input");
    if (!copyText) return;
    
    try {
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");
        
        // Hiển thị toast thay vì confirm
        showToast("Đã sao chép liên kết giới thiệu!");
    } catch (err) {
        alert("Không thể sao chép: " + err);
    }
}

// Thêm chức năng toast message cho UX tốt hơn
function showToast(message) {
    // Kiểm tra nếu đã có toast container
    var toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '20px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Tạo toast message
    var toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.style.backgroundColor = 'rgba(33, 150, 243, 0.9)';
    toast.style.color = 'white';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '4px';
    toast.style.marginTop = '10px';
    toast.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
    toast.style.minWidth = '250px';
    toast.innerText = message;
    
    toastContainer.appendChild(toast);
    
    // Auto remove sau 3 giây
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s ease';
        
        // Xóa khỏi DOM sau khi hiệu ứng fade out hoàn thành
        setTimeout(function() {
            toastContainer.removeChild(toast);
        }, 500);
    }, 3000);
}

// Script cải tiến để xử lý các tab và hiệu ứng người dùng
$(document).ready(function() {
    // Cải tiến tab implementation - đảm bảo hoạt động trong mọi trường hợp
    $('#referralTabs a').on('click', function (e) {
        e.preventDefault();
        
        // Ẩn tất cả tab panes
        $('.tab-pane').removeClass('show active');
        
        // Loại bỏ active class từ tất cả tabs
        $('#referralTabs a').removeClass('active');
        
        // Thêm active class cho tab hiện tại
        $(this).addClass('active');
        
        // Hiển thị tab pane tương ứng
        var target = $(this).attr('href');
        $(target).addClass('show active');
        
        // Lưu trạng thái tab vào localStorage
        localStorage.setItem('activeReferralTab', target);
    });
    
    // Khôi phục tab đã chọn từ localStorage
    var activeTab = localStorage.getItem('activeReferralTab');
    if (activeTab) {
        $('#referralTabs a[href="' + activeTab + '"]').click();
    }
    
    // Cải tiến auto-dismiss alerts với hiệu ứng mượt
    setTimeout(function() {
        $('.alert:not(#withdrawal-message)').fadeOut('slow');
    }, 5000);
    
    // Form validation & loading - được cải tiến với UX tốt hơn
    $('#withdrawal-form').submit(function(event) {
        event.preventDefault();
        
        var form = $(this);
        var withdrawBtn = $('#withdraw-btn');
        var btnText = $('#withdraw-btn-text');
        var btnLoading = $('#withdraw-btn-loading');
        var messageDiv = $('#withdrawal-message');
        
        var amount = parseFloat($('#amount').val());
        var bankName = $('#bank_name').val().trim();
        var accountNumber = $('#account_number').val().trim();
        var accountHolder = $('#account_holder').val().trim();
        var available = parseFloat(availableBalance);
        var minWithdrawal = minWithdrawalAmount;

        // Reset thông báo
        messageDiv.hide().removeClass('alert-success alert-danger');

        // Kiểm tra form
        if (!amount || !bankName || !accountNumber || !accountHolder) {
            messageDiv.text('Vui lòng điền đầy đủ thông tin yêu cầu rút tiền.').addClass('alert-danger').show();
            return false;
        }
        if (isNaN(amount) || amount <= 0) {
            messageDiv.text('Số tiền không hợp lệ.').addClass('alert-danger').show();
            return false;
        }
        if (amount < minWithdrawal) {
            messageDiv.text('Số tiền rút tối thiểu là ' + minWithdrawal.toLocaleString('vi-VN') + ' VNĐ.').addClass('alert-danger').show();
            return false;
        }
        if (amount > available) {
            messageDiv.text('Số dư khả dụng không đủ!').addClass('alert-danger').show();
            return false;
        }

        // Hiển thị trạng thái loading
        btnText.hide();
        btnLoading.show();
        withdrawBtn.prop('disabled', true);

        // Gửi yêu cầu AJAX
        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.message).removeClass('alert-danger').addClass('alert-success').show();
                    
                    // Hiển thị spinner trên toàn trang khi reload
                    var overlay = $('<div>').css({
                        'position': 'fixed',
                        'top': 0,
                        'left': 0,
                        'width': '100%',
                        'height': '100%',
                        'background-color': 'rgba(255,255,255,0.7)',
                        'z-index': 9999,
                        'display': 'flex',
                        'justify-content': 'center',
                        'align-items': 'center'
                    });
                    
                    var spinner = $('<div>').html('<i class="fas fa-spinner fa-spin fa-3x" style="color:#2196F3"></i>');
                    overlay.append(spinner);
                    $('body').append(overlay);
                    
                    // Reload sau 1.5 giây
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    messageDiv.text(response.message || 'Đã xảy ra lỗi không xác định.').removeClass('alert-success').addClass('alert-danger').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error: ", textStatus, errorThrown, jqXHR.responseText);
                messageDiv.text('Lỗi khi gửi yêu cầu. Vui lòng thử lại.').removeClass('alert-success').addClass('alert-danger').show();
            },
            complete: function() {
                btnText.show();
                btnLoading.hide();
                withdrawBtn.prop('disabled', false);
            }        
        });
    });
    
    // Thêm các tính năng responsive cho bảng
    function adjustTableResponsive() {
        if (window.innerWidth < 768) {
            $('.table-responsive').each(function() {
                var table = $(this).find('table');
                if (!table.hasClass('table-mobile-ready')) {
                    table.addClass('table-mobile-ready');
                    
                    // Thêm data-label attribute cho mỗi cell dựa trên header
                    // Đảm bảo bảng hiển thị tốt trên mobile
                    table.find('thead th').each(function(index) {
                        var headerText = $(this).text();
                        table.find('tbody tr').each(function() {
                            $(this).find('td:eq(' + index + ')').attr('data-label', headerText);
                        });
                    });
                }
            });
        }
    }
    
    // Gọi lần đầu và khi thay đổi kích thước màn hình
    adjustTableResponsive();
    $(window).on('resize', function() {
        adjustTableResponsive();
    });
    
    // Focus input khi click vào label để cải thiện UX
    $('label').on('click', function() {
        var forAttr = $(this).attr('for');
        if (forAttr) {
            $('#' + forAttr).focus();
        }
    });
    
    // Cải thiện UX cho form khi chuyển tab
    $('#withdrawal-tab').on('click', function() {
        setTimeout(function() {
            $('#amount').focus();
        }, 300);
    });
});