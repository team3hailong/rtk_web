<?php
/**
 * Error Handler Utility
 * This file provides functions for logging errors and activities consistently
 * across the application. It handles both database and file logging.
 */

/**
 * Log an error to both the error_logs database table and the error.log file
 * 
 * @param string $error_type The type of error (e.g., 'auth', 'database', 'api')
 * @param string $error_message The error message
 * @param string|null $stack_trace The stack trace (optional)
 * @param int|null $user_id The user ID (if available)
 * @return bool True if successful, false otherwise
 */
function log_error($conn, $error_type, $error_message, $stack_trace = null, $user_id = null) {
    // Always log to error.log file
    $log_message = date('[Y-m-d H:i:s]') . " [{$error_type}] " . $error_message;
    if ($stack_trace) {
        $log_message .= "\nStack Trace: " . $stack_trace;
    }
    if ($user_id) {
        $log_message .= " [User ID: {$user_id}]";
    }
    $log_message .= " [IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "]";
    
    error_log($log_message);
    
    // Log to database if connection is provided
    if ($conn) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, user_id, ip_address, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssss", $error_type, $error_message, $stack_trace, $user_id, $ip_address);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        } catch (Exception $e) {
            // If we can't log to the database, at least log to the file
            error_log("Failed to log error to database: " . $e->getMessage());
        }
    }
    
    return false;
}

/**
 * Log an activity to the activity_logs database table
 * 
 * @param object $conn Database connection
 * @param int $user_id The user ID
 * @param string $action The action performed (e.g., 'login', 'register', 'password_reset')
 * @param string $entity_type The type of entity (e.g., 'user', 'registration')
 * @param string $entity_id The ID of the entity
 * @param array|null $old_values Old values before the action (for updates)
 * @param array|null $new_values New values after the action (for updates)
 * @return bool True if successful, false otherwise
 */
function log_activity($conn, $user_id, $action, $entity_type, $entity_id, $old_values = null, $new_values = null) {
    if (!$conn) {
        error_log("No database connection provided for activity logging");
        return false;
    }
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
          // Convert arrays to JSON for storage with proper Unicode support for Vietnamese characters
        $old_values_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
        $new_values_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;
        
        $sql = "INSERT INTO activity_logs 
                (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isssssss", 
                $user_id, 
                $action, 
                $entity_type, 
                $entity_id, 
                $old_values_json, 
                $new_values_json, 
                $ip_address, 
                $user_agent
            );
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
    
    return false;
}