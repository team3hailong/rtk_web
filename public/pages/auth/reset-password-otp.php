<?php
session_start();
require_once __DIR__ . '/../../../private/config/config.php';

// Initialize variables
$email = $_SESSION['reset_email'] ?? '';
$error = $_SESSION['reset_otp_error'] ?? '';

// Clear session variables
unset($_SESSION['reset_otp_error']);

// Check if we have email in session
if (empty($email)) {
    header('Location: /public/pages/auth/forgot_password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Mã OTP - RTK Web</title>
    <link rel="stylesheet" href="/public/assets/css/base.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f7f6;
        }
        .verification-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 450px;
            width: 90%;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }
        .otp-input {
            width: 3rem;
            height: 3rem;
            font-size: 1.5rem;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .success {
            color: #2e7d32;
        }
        .error {
            color: #c62828;
        }
        .info {
            color: #555;
        }        .button {
            display: inline-block;
            background-color: #4caf50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 1rem;
            width: auto;
            min-width: 120px;
            text-align: center;
        }
        .button:hover {
            background-color: #388e3c;
        }
        .secondary-button {
            background-color: #f1f1f1;
            color: #333;
            border: 1px solid #ddd;
            margin-right: 10px;
            transition: all 0.3s;
        }
        .secondary-button:hover {
            background-color: #e0e0e0;
        }
        .secondary-button:disabled {
            opacity: 1;
            cursor: not-allowed;
            background-color: #f1f1f1;
            color: #333;
            border: 1px solid #ddd;
            box-shadow: none;
            pointer-events: none;
        }
        .timer {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2>Xác Thực Mã OTP</h2>
        
        <p class="info">Mã xác thực đã được gửi đến email <strong><?php echo htmlspecialchars($email); ?></strong></p>
        
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
          <form action="/public/handlers/action_handler.php?module=auth&action=verify-reset-otp" method="POST" id="otpForm">
            <div class="form-group">
                <label for="otp_code">Vui lòng nhập mã 6 số:</label>
                <div class="otp-input-container">
                    <input type="text" id="otp1" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required>
                    <input type="text" id="otp2" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" id="otp3" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" id="otp4" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" id="otp5" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    <input type="text" id="otp6" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                    
                    <input type="hidden" id="full_otp" name="otp_code" required>
                </div>
                <div class="timer">
                    <p>Mã xác thực có hiệu lực trong <span id="countdown">15:00</span></p>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="button">Xác thực</button>
            </div>
        </form>
          <form id="resendForm" action="/public/handlers/action_handler.php?module=auth&action=resend-reset-otp" method="POST">
    <div class="form-group">
        <button type="submit" id="resendBtn" class="button secondary-button">Gửi lại mã</button>
        <button type="button" onclick="window.location.href='/public/pages/auth/forgot_password.php'" class="button secondary-button" id="backBtn">Quay lại</button>
    </div>
</form>
    </div>
      <script>        // OTP input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const fullOtpInput = document.getElementById('full_otp');
        const otpForm = document.getElementById('otpForm');        // Add form submission handler to ensure OTP is combined properly
        otpForm.addEventListener('submit', function(e) {
            updateFullOtp(); // Update the hidden field before submission
            
            if (fullOtpInput.value.length !== 6) {
                e.preventDefault();
                alert('Vui lòng nhập đầy đủ 6 chữ số OTP');
                return false;
            }
        });
        
        otpInputs.forEach((input, index) => {
            // Auto-focus the first input on page load
            if (index === 0) {
                input.focus();
            }
              // Handle input
            input.addEventListener('input', function(e) {
                // Allow only digits and limit to 1 character
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
                
                // Move to next input field if this one is filled
                if (this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                // Update the hidden full OTP field
                updateFullOtp();
            });
            
            // Support pasting OTP
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                
                if (pasteData) {
                    // Fill all inputs with respective digits
                    for (let i = 0; i < Math.min(pasteData.length, otpInputs.length); i++) {
                        otpInputs[i].value = pasteData[i] || '';
                    }
                    
                    // Focus appropriate field
                    const focusIndex = Math.min(pasteData.length, otpInputs.length - 1);
                    otpInputs[focusIndex].focus();
                    
                    // Update hidden field
                    updateFullOtp();
                }
            });
            
            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });        // Update the hidden full OTP input
        function updateFullOtp() {
            let otp = '';
            // Force the correct order of inputs
            for (let i = 0; i < otpInputs.length; i++) {
                otp += otpInputs[i].value || '';
            }
            fullOtpInput.value = otp;
            return otp.length === 6; // Return true if we have a complete OTP
        }          // Countdown timer
        const fifteenMinutes = 15 * 60; // 15 minutes in seconds
        let timeLeft = fifteenMinutes; // Initialize with 15 minutes
        let countdownInterval; // Define countdownInterval variable
        const countdownEl = document.getElementById('countdown');
        const resendBtn = document.getElementById('resendBtn');
        let cooldownActive = false;
        let cooldownSeconds = 0;
        let cooldownTimer = null;
          // Start countdown timer
        function startCountdown() {
            // Clear any existing interval
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            
            // Only reset the timer when it's the first time or has expired
            if (!timeLeft || timeLeft <= 0) {
                timeLeft = fifteenMinutes; // Reset to 15 minutes
            }
            updateCountdownDisplay(); // Update display immediately
            
            // Start the countdown
            countdownInterval = setInterval(function() {
                timeLeft--; // Reduce time left
                updateCountdownDisplay(); // Update display
                
                // Check if time is up
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    handleExpiredOTP();
                }
            }, 1000);
        }
        
        // Update countdown display
        function updateCountdownDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Handle expired OTP
        function handleExpiredOTP() {
            countdownEl.textContent = '00:00';
            if (!cooldownActive) {
                resendBtn.disabled = false;
                resendBtn.classList.remove('secondary-button');
                resendBtn.classList.add('button');
                // Thêm thông báo cho người dùng biết có thể gửi lại mã
                document.querySelector('.timer p').innerHTML = 'Mã xác thực đã hết hạn. Vui lòng <strong>gửi lại mã</strong>.';
            }
        }
          // Xử lý cooldown sau khi gửi lại mã
        function startCooldown() {
            cooldownActive = true;
            cooldownSeconds = 30; // Thời gian chờ 30 giây            resendBtn.disabled = true;
            // Không đổi class hay style, chỉ set disabled và đổi text
            updateCooldownButton();
            
            // Bắt đầu đếm ngược cho cooldown
            if (cooldownTimer) clearInterval(cooldownTimer);
            cooldownTimer = setInterval(updateCooldownTime, 1000);
        }
          function updateCooldownTime() {
            cooldownSeconds--;
            updateCooldownButton();
            
            if (cooldownSeconds <= 0) {
                clearInterval(cooldownTimer);
                cooldownActive = false;
                resendBtn.disabled = false;
                resendBtn.textContent = 'Gửi lại mã';
                // Không thay đổi class nữa, chỉ reset text
            }
        }        function updateCooldownButton() {
            resendBtn.textContent = `Gửi lại mã (${cooldownSeconds}s)`;
            // Đảm bảo nút luôn có style đúng trong quá trình cooldown
            resendBtn.className = 'button secondary-button';
        }
          // Initialize the countdown
        startCountdown();
        
        // Bắt đầu cooldown ban đầu
        startCooldown();
          // Xử lý sự kiện khi form gửi lại OTP được submit
        document.getElementById('resendForm').addEventListener('submit', function(event) {
            if (cooldownActive) {
                event.preventDefault();
                return false;
            }
            // Bắt đầu cooldown khi form được gửi đi
            startCooldown();
            // Khởi động lại đếm ngược 15 phút cho mã OTP mới khi người dùng nhận được mã mới
            startCountdown();
        });
    </script>
</body>
</html>
