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
        $tax_registered = isset($_POST['tax_registered']) ? 1 : 0;

        // Validate required fields
        if (empty($username) || empty($email)) {
            throw new Exception("Vui lòng nhập tên người dùng và email");
        }

        // Nếu là tài khoản công ty thì yêu cầu nhập tên công ty và mã số thuế
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

            // Log activity
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id) 
                                    VALUES (?, 'update', 'user', ?)");
            $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            header("Location: ../../../public/pages/setting/invoice.php?success=1");
            exit();
        } else {
            $stmt->close();
            throw new Exception("Cập nhật thông tin thất bại");
        }
    } else {
        header("Location: ../../../public/pages/setting/invoice.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../../../public/pages/setting/invoice.php");
    exit();
}