<?php
/**
 * Migration Script: Migrate Payment Proof Images from Local Storage to Cloudinary
 * 
 * This script will:
 * 1. Find all payment proof images stored locally
 * 2. Upload them to Cloudinary
 * 3. Update database records with Cloudinary URLs and public_ids
 * 
 * Usage: Run this script via command line: php migrate_to_cloudinary.php
 * 
 * IMPORTANT: 
 * - Make sure Cloudinary is properly configured in .env
 * - Make a database backup before running
 * - Test with a few records first
 */

// Include required files
require_once dirname(__DIR__) . '/private/config/config.php';
require_once dirname(__DIR__) . '/private/classes/Database.php';
require_once dirname(__DIR__) . '/private/classes/CloudinaryService.php';

echo "=== Payment Proof Migration to Cloudinary ===\n";

// Check if Cloudinary is configured
if (!is_cloudinary_configured()) {
    echo "ERROR: Cloudinary is not properly configured. Please check your .env file.\n";
    exit(1);
}

echo "Cloudinary configuration: OK\n";

// Initialize services
$db = new Database();
$conn = $db->getConnection();
$cloudinaryService = new CloudinaryService();

// Get local upload directory
$project_root = dirname(__DIR__);
$local_upload_dir = $project_root . '/public/uploads/payment_proofs/';

echo "Local upload directory: {$local_upload_dir}\n";

try {
    // Get all transactions with local payment images
    $sql = "SELECT id, registration_id, user_id, payment_image, payment_image_public_id 
            FROM transaction_history 
            WHERE payment_image IS NOT NULL 
            AND payment_image != '' 
            AND (payment_image_public_id IS NULL OR payment_image_public_id = '')
            AND payment_image NOT LIKE '%cloudinary.com%'
            ORDER BY id ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_count = count($transactions);
    echo "Found {$total_count} transactions with local payment images\n";
    
    if ($total_count === 0) {
        echo "No local images to migrate. Exiting.\n";
        exit(0);
    }
    
    // Ask for confirmation
    echo "\nThis will migrate {$total_count} images to Cloudinary.\n";
    echo "Continue? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $confirm = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirm) !== 'y') {
        echo "Migration cancelled.\n";
        exit(0);
    }
    
    $success_count = 0;
    $error_count = 0;
    $not_found_count = 0;
    
    foreach ($transactions as $index => $transaction) {
        $current = $index + 1;
        echo "\n[{$current}/{$total_count}] Processing transaction ID: {$transaction['id']}\n";
        
        $local_file_path = $local_upload_dir . $transaction['payment_image'];
        
        // Check if local file exists
        if (!file_exists($local_file_path)) {
            echo "  WARNING: Local file not found: {$local_file_path}\n";
            $not_found_count++;
            continue;
        }
        
        try {
            $conn->beginTransaction();
            
            // Upload to Cloudinary
            $metadata = [
                'registration_id' => $transaction['registration_id'],
                'user_id' => $transaction['user_id'],
                'transaction_id' => $transaction['id'],
                'migrated_from' => 'local_storage'
            ];
            
            echo "  Uploading to Cloudinary...";
            $cloudinary_result = $cloudinaryService->uploadPaymentProof($local_file_path, $metadata);
            
            if (!$cloudinary_result['success']) {
                throw new Exception('Cloudinary upload failed');
            }
            
            echo " OK\n";
            echo "  Cloudinary URL: {$cloudinary_result['secure_url']}\n";
            echo "  Public ID: {$cloudinary_result['public_id']}\n";
            
            // Update database
            $update_sql = "UPDATE transaction_history 
                          SET payment_image = :cloudinary_url,
                              payment_image_public_id = :public_id,
                              updated_at = NOW()
                          WHERE id = :transaction_id";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':cloudinary_url', $cloudinary_result['secure_url']);
            $update_stmt->bindParam(':public_id', $cloudinary_result['public_id']);
            $update_stmt->bindParam(':transaction_id', $transaction['id']);
            $update_stmt->execute();
            
            $conn->commit();
            
            echo "  Database updated successfully\n";
            
            // Optional: Remove local file after successful migration
            // Uncomment the next lines if you want to delete local files after migration
            /*
            if (unlink($local_file_path)) {
                echo "  Local file deleted\n";
            } else {
                echo "  WARNING: Could not delete local file\n";
            }
            */
            
            $success_count++;
            
        } catch (Exception $e) {
            $conn->rollBack();
            echo "  ERROR: " . $e->getMessage() . "\n";
            $error_count++;
        }
        
        // Small delay to avoid rate limiting
        usleep(500000); // 0.5 seconds
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Total processed: {$total_count}\n";
    echo "Successful: {$success_count}\n";
    echo "Errors: {$error_count}\n";
    echo "Files not found: {$not_found_count}\n";
    echo "Migration completed.\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
