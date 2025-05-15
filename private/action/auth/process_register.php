<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/email_helper.php';
require_once __DIR__ . '/../../utils/error_handler.php';

$errors = [];
$formData = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {    // Lấy và làm sạch dữ liệu
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_company = isset($_POST['is_company']) ? 1 : 0;
    $company_name = $is_company ? trim($_POST['company_name'] ?? '') : null;
    $tax_code = $is_company ? trim($_POST['tax_code'] ?? '') : null;
    $tax_registered = ($is_company && isset($_POST['tax_registered'])) ? 1 : null;
    $referral_code = trim($_POST['referral_code'] ?? '');

    // Lưu dữ liệu form vào session để hiển thị lại nếu có lỗi
    $formData = $_POST;
    $_SESSION['form_data'] = $formData;


    // --- Validation ---
    if (empty($username)) {
        $errors[] = "Tên người dùng không được để trống.";
    }
    if (empty($email)) {
        $errors[] = "Email không được để trống.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Định dạng email không hợp lệ.";
    }
    if (empty($phone)) {
        $errors[] = "Số điện thoại không được để trống.";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
         $errors[] = "Số điện thoại phải có 10 hoặc 11 chữ số.";
    }
    if (empty($password)) {
        $errors[] = "Mật khẩu không được để trống.";
    } elseif (strlen($password) < 6) { // Ví dụ: yêu cầu mật khẩu tối thiểu 6 ký tự
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu và xác nhận mật khẩu không khớp.";
    }
    if ($is_company && empty($tax_code)) {
         $errors[] = "Mã số thuế không được để trống đối với công ty.";
    }
     // Nếu không có tên công ty riêng, dùng tên username
    if ($is_company && empty($company_name)) {
        $company_name = $username;
    }


    // Kiểm tra email và số điện thoại đã tồn tại chưa (nếu không có lỗi validation cơ bản)
    if (empty($errors)) {
        // Kiểm tra Email
        $stmt_check = $conn->prepare("SELECT id FROM user WHERE email = ?");
        if ($stmt_check === false) {
            $errors[] = "Lỗi chuẩn bị câu lệnh kiểm tra email: " . $conn->error;
        } else {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Email này đã được sử dụng.";
            }
            $stmt_check->close();
        }

        // Kiểm tra Số điện thoại
        $stmt_check = $conn->prepare("SELECT id FROM user WHERE phone = ?");
         if ($stmt_check === false) {
            $errors[] = "Lỗi chuẩn bị câu lệnh kiểm tra điện thoại: " . $conn->error;
        } else {
            $stmt_check->bind_param("s", $phone);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Số điện thoại này đã được sử dụng.";
            }
            $stmt_check->close();
        }
    }

    // --- Nếu không có lỗi ---
    if (empty($errors)) {
        // Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Tạo token xác thực email
        $verification_token = bin2hex(random_bytes(32));

        // Bắt đầu transaction
        $conn->begin_transaction();

        try {
            // Chuẩn bị câu lệnh INSERT cho bảng user với thêm token xác thực
            $sql_user = "INSERT INTO user (username, email, password, phone, is_company, company_name, tax_code, tax_registered, email_verify_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt_user = $conn->prepare($sql_user);
            if ($stmt_user === false) {
                throw new Exception("Lỗi chuẩn bị câu lệnh user: " . $conn->error);
            }
            $stmt_user->bind_param("ssssisiss", 
                $username, 
                $email, 
                $hashed_password, 
                $phone, 
                $is_company, 
                $company_name, 
                $tax_code, 
                $tax_registered,
                $verification_token
            );

            // Thực thi câu lệnh user
            if (!$stmt_user->execute()) {
                 throw new Exception("Lỗi khi thêm người dùng: " . $stmt_user->error);
            }

            // Lấy ID của người dùng vừa được thêm
            $user_id = $stmt_user->insert_id;
            $stmt_user->close();

             // Chuẩn bị câu lệnh INSERT cho bảng user_settings
            $sql_settings = "INSERT INTO user_settings (user_id, created_at) VALUES (?, NOW())";
            $stmt_settings = $conn->prepare($sql_settings);
             if ($stmt_settings === false) {
                throw new Exception("Lỗi chuẩn bị câu lệnh settings: " . $conn->error);
            }
            $stmt_settings->bind_param("i", $user_id);

             // Thực thi câu lệnh settings
            if (!$stmt_settings->execute()) {
                 throw new Exception("Lỗi khi thêm cài đặt người dùng: " . $stmt_settings->error);
            }
            $stmt_settings->close();

            // Gửi email xác nhận
            $emailSent = sendVerificationEmail($email, $username, $verification_token);
            
            if (!$emailSent) {
                // Log lỗi nhưng không throw exception vì user vẫn được tạo thành công
                error_log("Failed to send verification email to: $email");
            }            // Commit transaction nếu mọi thứ thành công
            $conn->commit();
            
            // Log successful registration
            log_activity($conn, $user_id, 'register', 'user', $user_id, null, [
                'email' => $email,
                'username' => $username,
                'registration_time' => date('Y-m-d H:i:s'),
                'is_company' => $is_company
            ]);
            
            // Process referral if a code was provided
            if (!empty($referral_code)) {
                require_once __DIR__ . '/../../classes/Referral.php';
                require_once __DIR__ . '/../../classes/Database.php';
                
                // Initialize Referral service with PDO connection
                $db = new Database();
                $referralService = new Referral($db);
                  // Track the referral
                $referralResult = $referralService->trackReferral($user_id, $referral_code);
                
                // If referral tracking was successful, log the referral activity
                if ($referralResult) {
                    // Get referrer information
                    $stmt = $conn->prepare("SELECT r.user_id as referrer_id, u.username as referrer_name 
                                           FROM referral r 
                                           JOIN user u ON r.user_id = u.id 
                                           WHERE r.referral_code = ?");
                    $stmt->bind_param("s", $referral_code);
                    $stmt->execute();
                    $referrer = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($referrer) {                        // Log referral activity (the function will handle JSON encoding with proper settings)
                        $log_data = [
                            'referrer_id' => $referrer['referrer_id'],
                            'referrer_name' => $referrer['referrer_name'],
                            'referred_user_id' => $user_id,
                            'referred_username' => $username,
                            'referred_email' => $email,
                            'referral_code' => $referral_code,
                            'referral_time' => date('Y-m-d H:i:s')
                        ];
                        
                        log_activity($conn, $referrer['referrer_id'], 'referral', 'user', $user_id, null, $log_data);
                    }
                }
            }
            
            // Xóa dữ liệu form khỏi session và đặt thông báo thành công
            unset($_SESSION['form_data']);
            $_SESSION['success_message'] = "Đăng ký thành công! Vui lòng kiểm tra email của bạn để xác nhận tài khoản.";
            
            // Chuyển hướng về trang đăng ký để hiển thị thông báo thành công
            // JavaScript sẽ đếm ngược 7 giây trước khi chuyển hướng đến trang đăng nhập
            header("Location: ../../../public/pages/auth/register.php");
            exit();        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            $conn->rollback();
            
            // Log the detailed error to both file and database
            log_error($conn, 'auth', "Registration Error: " . $e->getMessage(), $e->getTraceAsString(), null);
            
            // Set a generic error message for the user
            $errors[] = "Đã xảy ra lỗi trong quá trình đăng ký. Vui lòng thử lại sau.";
            
            // Lưu lỗi vào session và chuyển hướng lại form
            $_SESSION['errors'] = $errors;
            header("Location: ../../../public/pages/auth/register.php");
            exit();
        }    } else {
        // Lưu lỗi vào session và chuyển hướng lại form
        $_SESSION['errors'] = $errors;
        $conn->close(); // Đóng kết nối trước khi chuyển hướng
        header("Location: ../../../public/pages/auth/register.php");
        exit();
    }
} else {
    // Nếu không phải POST request, chuyển hướng về trang đăng ký
    header("Location: ../../../public/pages/auth/register.php");
    exit();
}
?>