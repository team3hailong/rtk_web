<?php
session_start();
require_once __DIR__ . '/../../../private/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $is_company = isset($_POST['is_company']) ? 1 : 0;
        $company_name = $_POST['company_name'] ?? null;
        $tax_code = $_POST['tax_code'] ?? null;
        $old_password = $_POST['old_password'] ?? null;
        $new_password = $_POST['new_password'] ?? null;
        $confirm_password = $_POST['confirm_password'] ?? null;

        // Validate required fields
        if (empty($username) || empty($email)) {
            throw new Exception("Vui lòng nhập tên người dùng và email");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email không hợp lệ");
        }

        // Validate phone number format if provided
        if (!empty($phone) && !preg_match("/^[0-9]{10,15}$/", $phone)) {
            throw new Exception("Số điện thoại phải có 10-15 chữ số");
        }

        // If company account, require company name and tax code
        if ($is_company) {
            if (empty($company_name) || empty($tax_code)) {
                throw new Exception("Vui lòng nhập đầy đủ TÊN CÔNG TY và MÃ SỐ THUẾ");
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

        // Password change validation
        if (!empty($old_password) || !empty($new_password) || !empty($confirm_password)) {
            // Fetch current password
            $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $current_password = $row['password'];

                // Verify old password
                if (empty($old_password)) {
                    throw new Exception("Vui lòng nhập mật khẩu cũ");
                } elseif (!password_verify($old_password, $current_password)) {
                    throw new Exception("Mật khẩu cũ không đúng");
                }

                // Validate new password
                if (empty($new_password)) {
                    throw new Exception("Vui lòng nhập mật khẩu mới");
                } elseif (strlen($new_password) < 6) {
                    throw new Exception("Mật khẩu mới phải có ít nhất 6 ký tự");
                }

                // Validate confirm password
                if (empty($confirm_password)) {
                    throw new Exception("Vui lòng xác nhận mật khẩu mới");
                } elseif ($new_password !== $confirm_password) {
                    throw new Exception("Mật khẩu xác nhận không khớp");
                }
            } else {
                throw new Exception("Không tìm thấy người dùng");
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
            updated_at = NOW()" . 
            (!empty($new_password) ? ", password = ?" : "") . 
            " WHERE id = ?");

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bind_param("sssissi", 
                $username, 
                $email, 
                $phone, 
                $is_company, 
                $company_name, 
                $tax_code, 
                $hashed_password, 
                $_SESSION['user_id']
            );
        } else {
            $stmt->bind_param("sssissi", 
                $username, 
                $email, 
                $phone, 
                $is_company, 
                $company_name, 
                $tax_code, 
                $_SESSION['user_id']
            );
        }

        if ($stmt->execute()) {
            $stmt->close();

            // Log activity
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id) 
                                    VALUES (?, 'update', 'user', ?)");
            $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            header("Location: ../../../public/pages/setting/profile.php?success=1");
            exit();
        } else {
            $stmt->close();
            throw new Exception("Cập nhật thông tin thất bại");
        }
    } else {
        header("Location: ../../../public/pages/setting/profile.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../../../public/pages/setting/profile.php");
    exit();
}
?>