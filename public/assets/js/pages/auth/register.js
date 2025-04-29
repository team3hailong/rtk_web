/**
 * JavaScript for Register Page
 */
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('successMessage');
    if (successMessage) {
        setTimeout(() => {
            window.location.href = 'login.php'; // Redirect to login page
        }, 1000); // 1000 milliseconds = 1 second
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