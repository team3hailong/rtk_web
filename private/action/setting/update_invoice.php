<?php
session_start();
require_once __DIR__ . '/../../../private/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vui lòng đăng nhập để tiếp tục";
    header("Location: login.php");
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get and sanitize form data
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $is_company = isset($_POST['is_company']) ? 1 : 0;
        $company_name = trim($_POST['company_name'] ?? '');
        $tax_code = trim($_POST['tax_code'] ?? '');
        $tax_registered = isset($_POST['tax_registered']) ? 1 : 0;

        // Clear any previous messages
        unset($_SESSION['success_message']);
        unset($_SESSION['error_message']);

        // Validate required fields
        if (empty($username) || empty($email)) {
            throw new Exception("Vui lòng nhập tên người dùng và email");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email không hợp lệ");
        }

        // Validate phone format if provided
        if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
            throw new Exception("Số điện thoại phải có 10-11 chữ số");
        }

        // If company account, validate company fields
        if ($is_company) {
            if (empty($company_name) || empty($tax_code)) {
                throw new Exception("Vui lòng nhập đầy đủ TÊN CÔNG TY và MÃ SỐ THUẾ");
            }
            
            // Additional validation for tax code if needed
            if (!preg_match('/^[0-9]{10,13}$/', $tax_code)) {
                throw new Exception("Mã số thuế phải có từ 10-13 chữ số");
            }
        }

        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            throw new Exception("Email đã được sử dụng bởi tài khoản khác");
        }
        $stmt->close();

        // Check if phone is already taken by another user
        if (!empty($phone)) {
            $stmt = $conn->prepare("SELECT id FROM user WHERE phone = ? AND id != ?");
            $stmt->bind_param("si", $phone, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_assoc()) {
                throw new Exception("Số điện thoại đã được sử dụng bởi tài khoản khác");
            }
            $stmt->close();
        }

        // Update user information
        $stmt = $conn->prepare("UPDATE user SET 
            username = ?, 
            email = ?, 
            phone = ?, 
            is_company = ?, 
            company_name = ?, 
            tax_code = ?, 
            tax_registered = ?, 
            updated_at = NOW() 
            WHERE id = ?");
        $stmt->bind_param("sssissii", 
            $username, 
            $email, 
            $phone, 
            $is_company, 
            $company_name, 
            $tax_code, 
            $tax_registered, 
            $_SESSION['user_id']
        );

        if ($stmt->execute()) {
            $stmt->close();

            // Update session data if needed
            $_SESSION['user_data']['username'] = $username;
            $_SESSION['user_data']['email'] = $email;
            $_SESSION['user_data']['phone'] = $phone;
            $_SESSION['user_data']['is_company'] = $is_company;
            $_SESSION['user_data']['company_name'] = $company_name;
            $_SESSION['user_data']['tax_code'] = $tax_code;

            // Log activity
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id) 
                                  VALUES (?, 'update', 'user', ?)");
            $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success_message'] = "Cập nhật thông tin thành công!";
            header("Location: ../../../public/pages/setting/invoice.php");
            exit();
        } else {
            $stmt->close();
            throw new Exception("Cập nhật thông tin thất bại: " . $conn->error);
        }
    } else {
        $_SESSION['error_message'] = "Phương thức yêu cầu không hợp lệ";
        header("Location: ../../../public/pages/setting/invoice.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../../../public/pages/setting/invoice.php");
    exit();
}
