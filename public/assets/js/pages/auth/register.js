/**
 * JavaScript for Register Page
 */
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('successMessage');
    
    if (successMessage) {
        // Đếm ngược 7 giây trước khi chuyển hướng
        let remainingTime = 7;
        
        // Thêm đoạn văn bản đếm ngược
        const originalMessage = successMessage.textContent;
        const countdownSpan = document.createElement('span');
        countdownSpan.id = 'countdownTimer';
        successMessage.appendChild(document.createElement('br'));
        successMessage.appendChild(document.createTextNode('Chuyển hướng sau '));
        successMessage.appendChild(countdownSpan);
        successMessage.appendChild(document.createTextNode(' giây...'));
        
        // Cập nhật đếm ngược mỗi giây
        const countdownInterval = setInterval(() => {
            countdownSpan.textContent = remainingTime;
            remainingTime--;
            
            if (remainingTime < 0) {
                clearInterval(countdownInterval);
                window.location.href = 'login.php'; // Chuyển hướng sang trang đăng nhập
            }
        }, 1000);
        
        // Hiển thị thời gian ban đầu
        countdownSpan.textContent = remainingTime;
    }

    // Client-side validation for password match
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    form.addEventListener('submit', function(event) {
        if (password.value !== confirmPassword.value) {
            alert('Mật khẩu và xác nhận mật khẩu không khớp!');
            confirmPassword.focus();
            event.preventDefault(); // Prevent form submission
        }
        // Add other validations if needed
    });
});