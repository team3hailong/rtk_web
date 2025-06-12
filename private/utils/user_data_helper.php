<?php
/**
 * Helper file to get user data
 * 
 * This file provides functions to access common user data from the database
 */

/**
 * Get user invoice information
 * 
 * @param int $user_id The ID of the user
 * @return array|false Array with user company data or false if not found
 */
function get_user_invoice_data($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT company_name, tax_code, company_address FROM user WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get user profile data 
 * 
 * @param int $user_id The ID of the user
 * @param array $fields Optional array of fields to retrieve (default: all)
 * @return array|false Array with user data or false if not found
 */
function get_user_profile_data($user_id, $fields = ['*']) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $field_list = is_array($fields) && $fields[0] !== '*' ? implode(', ', $fields) : '*';
    
    $stmt = $conn->prepare("SELECT {$field_list} FROM user WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
