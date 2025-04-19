<?php
// filepath: e:\Application\laragon\www\test_web-Long2\private\utils\payment_helper.php

require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Package.php';
require_once dirname(__DIR__) . '/classes/Location.php';

/**
 * Fetches all necessary details for the payment page based on registration ID and user ID.
 *
 * @param int $registration_id The ID of the pending registration.
 * @param int $user_id The ID of the logged-in user.
 * @param float $session_total_price The total price stored in the session for verification.
 * @return array An associative array containing payment details or an error message.
 *               On success: ['success' => true, 'data' => ['registration_id' => ..., 'package_name' => ..., 'quantity' => ..., 'province' => ..., 'verified_total_price' => ...]]
 *               On error:   ['success' => false, 'error' => 'error_message_key']
 */
function getPaymentPageDetails(int $registration_id, int $user_id, float $session_total_price): array
{
    $db = null;
    $package_obj = null;
    $location_obj = null;

    try {
        $db = new Database();
        $conn = $db->connect();
        $package_obj = new Package($conn);
        $location_obj = new Location($conn);

        // --- Fetch Registration Details ---
        $stmt = $conn->prepare("SELECT id, package_id, location_id, num_account, total_price FROM registration WHERE id = :id AND user_id = :user_id AND status = 'pending'");
        $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $registration_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registration_details) {
            error_log("Payment Helper: Pending registration ID $registration_id not found for user $user_id or status not pending.");
            return ['success' => false, 'error' => 'invalid_order_state'];
        }

        // --- Verify Session Price with DB Price ---
        $db_total_price = (float)$registration_details['total_price'];
        if (abs($db_total_price - $session_total_price) > 0.01) {
            error_log("Payment Helper: Price mismatch session ({$session_total_price}) vs DB ({$db_total_price}) for registration ID $registration_id.");
            // Use DB price as the source of truth
            $verified_total_price = $db_total_price;
            // Optionally update session here if needed, though maybe better done in the calling script
            $_SESSION['pending_total_price'] = $verified_total_price;
        } else {
            $verified_total_price = $session_total_price; // Or $db_total_price, they are close enough
        }

        // --- Fetch Package and Location Details ---
        $package_details = $package_obj->getPackageById($registration_details['package_id']);
        $location_details = $location_obj->getLocationById($registration_details['location_id']);

        if (!$package_details || !$location_details) {
            error_log("Payment Helper: Could not fetch package or location details for registration ID $registration_id.");
            return ['success' => false, 'error' => 'data_fetch_error'];
        }

        return [
            'success' => true,
            'data' => [
                'registration_id' => $registration_details['id'],
                'package_name' => $package_details['name'],
                'quantity' => $registration_details['num_account'],
                'province' => $location_details['province'], // Assuming column name is 'province'
                'verified_total_price' => $verified_total_price
            ]
        ];

    } catch (PDOException $e) {
        error_log("Payment Helper DB error: " . $e->getMessage());
        return ['success' => false, 'error' => 'database_error'];
    } catch (Exception $e) {
        error_log("Payment Helper general error: " . $e->getMessage());
        return ['success' => false, 'error' => 'unknown_error'];
    } finally {
        // Close connections
        if ($package_obj) $package_obj->closeConnection();
        if ($location_obj) $location_obj->closeConnection();
        if ($db) $db->close();
    }
}

?>