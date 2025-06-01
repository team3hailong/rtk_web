<?php
/**
 * OTP Helper Functions
 * Contains functions for generating, validating, and managing OTP codes
 */

/**
 * Generate a new 6-digit OTP code
 * 
 * @return string 6-digit numeric OTP code
 */
function generate_otp() {
    return str_pad(strval(mt_rand(0, 999999)), 6, '0', STR_PAD_LEFT);
}

/**
 * Create and store a new email verification OTP for a user
 * 
 * @param object $conn Database connection
 * @param string $email User's email address
 * @return array Associative array with 'success' boolean and other relevant data
 */
function create_email_verification_otp($conn, $email) {
    try {
        // Generate OTP
        $otp = generate_otp();
        
        // Set expiry time (15 minutes)
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Store OTP in database
        $stmt = $conn->prepare("UPDATE user SET 
                                email_verify_otp = ?, 
                                email_verify_otp_expires_at = ? 
                                WHERE email = ? AND deleted_at IS NULL");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("sss", $otp, $expires_at, $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execution error: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            return [
                'success' => false,
                'error' => 'email_not_found'
            ];
        }
        
        // Get user details for sending OTP
        $stmt_user = $conn->prepare("SELECT id, username FROM user WHERE email = ? AND deleted_at IS NULL");
        $stmt_user->bind_param("s", $email);
        $stmt_user->execute();
        $user_result = $stmt_user->get_result();
        
        if ($user_result->num_rows === 0) {
            return [
                'success' => false,
                'error' => 'email_not_found'
            ];
        }
        
        $user = $user_result->fetch_assoc();
        
        return [
            'success' => true,
            'otp' => $otp,
            'expires_at' => $expires_at,
            'user_id' => $user['id'],
            'username' => $user['username']
        ];
        
    } catch (Exception $e) {
        error_log("Error creating email verification OTP: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'system_error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Create and store a new password reset OTP for a user
 * 
 * @param object $conn Database connection
 * @param string $email User's email address
 * @return array Associative array with 'success' boolean and other relevant data
 */
function create_password_reset_otp($conn, $email) {
    try {
        // Generate OTP
        $otp = generate_otp();
        
        // Set expiry time (15 minutes)
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Store OTP in database
        $stmt = $conn->prepare("UPDATE user SET 
                                password_reset_otp = ?, 
                                password_reset_otp_expires_at = ? 
                                WHERE email = ? AND deleted_at IS NULL");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("sss", $otp, $expires_at, $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execution error: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            return [
                'success' => false,
                'error' => 'email_not_found'
            ];
        }
        
        // Get user details for sending OTP
        $stmt_user = $conn->prepare("SELECT id, username FROM user WHERE email = ? AND deleted_at IS NULL");
        $stmt_user->bind_param("s", $email);
        $stmt_user->execute();
        $user_result = $stmt_user->get_result();
        
        if ($user_result->num_rows === 0) {
            return [
                'success' => false,
                'error' => 'email_not_found'
            ];
        }
        
        $user = $user_result->fetch_assoc();
        
        return [
            'success' => true,
            'otp' => $otp,
            'expires_at' => $expires_at,
            'user_id' => $user['id'],
            'username' => $user['username']
        ];
        
    } catch (Exception $e) {
        error_log("Error creating password reset OTP: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'system_error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Verify an email verification OTP
 * 
 * @param object $conn Database connection
 * @param string $email User's email address
 * @param string $otp OTP code entered by user
 * @return array Associative array with 'success' boolean and other relevant data
 */
function verify_email_otp($conn, $email, $otp) {
    try {
        if (empty($otp) || strlen($otp) !== 6 || !is_numeric($otp)) {
            return [
                'success' => false,
                'error' => 'invalid_otp_format'
            ];
        }
        
        // Get user with this OTP
        $stmt = $conn->prepare("SELECT id, email_verify_otp, email_verify_otp_expires_at, email_verified 
                               FROM user 
                               WHERE email = ? AND deleted_at IS NULL");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'error' => 'email_not_found'
            ];
        }
        
        $user = $result->fetch_assoc();
        
        // Check if email is already verified
        if ($user['email_verified']) {
            return [
                'success' => false,
                'error' => 'already_verified'
            ];
        }
        
        // Check if OTP matches
        if ($user['email_verify_otp'] !== $otp) {
            return [
                'success' => false,
                'error' => 'invalid_otp'
            ];
        }
        
        // Check if OTP is expired
        $now = new DateTime();
        $expires = new DateTime($user['email_verify_otp_expires_at']);
        
        if ($now > $expires) {
            return [
                'success' => false,
                'error' => 'expired_otp'
            ];
        }
        
        // OTP is valid, update user record to mark email as verified
        $update = $conn->prepare("UPDATE user 
                                 SET email_verified = 1, 
                                     email_verify_otp = NULL, 
                                     email_verify_otp_expires_at = NULL 
                                 WHERE id = ?");
        $update->bind_param("i", $user['id']);
        
        if (!$update->execute()) {
            throw new Exception("Failed to update verification status: " . $update->error);
        }
        
        return [
            'success' => true,
            'user_id' => $user['id']
        ];
        
    } catch (Exception $e) {
        error_log("Error verifying email OTP: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'system_error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Verify a password reset OTP
 * 
 * @param object $conn Database connection
 * @param string $email User's email address
 * @param string $otp OTP code entered by user
 * @return array Associative array with 'success' boolean and other relevant data
 */
function verify_password_reset_otp($conn, $email, $otp) {
    try {
        if (empty($otp) || strlen($otp) !== 6 || !is_numeric($otp)) {
            return [
                'success' => false,
                'error' => 'invalid_otp_format'
            ];
        }
        
        // Get user with this OTP
        $stmt = $conn->prepare("SELECT id, password_reset_otp, password_reset_otp_expires_at 
                               FROM user 
                               WHERE email = ? AND deleted_at IS NULL");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'error' => 'email_not_found'
            ];
        }          $user = $result->fetch_assoc();
        
        // Simple OTP comparison
        if ($user['password_reset_otp'] !== $otp) {
            return [
                'success' => false,
                'error' => 'invalid_otp'
            ];
        }
        
        // Check if OTP is expired
        $now = new DateTime();
        $expires = new DateTime($user['password_reset_otp_expires_at']);
        
        if ($now > $expires) {
            return [
                'success' => false,
                'error' => 'expired_otp'
            ];
        }
        
        return [
            'success' => true,
            'user_id' => $user['id']
        ];
        
    } catch (Exception $e) {
        error_log("Error verifying password reset OTP: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'system_error',
            'message' => $e->getMessage()
        ];
    }
}
