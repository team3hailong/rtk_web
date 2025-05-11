<?php
/**
 * Test script for referral system
 * This script simulates a completed transaction and checks if commission is automatically approved
 */

// Include necessary files
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once dirname(dirname(__DIR__)) . '/private/classes/Database.php';
require_once dirname(dirname(__DIR__)) . '/private/classes/Referral.php';

// Connect to database
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $referralService = new Referral($db);
    
    echo "===== TESTING REFERRAL COMMISSION AUTO-APPROVAL =====\n\n";
    
    // 1. First check if we have any test data - a referred user with transactions
    $stmt = $pdo->prepare("
        SELECT ru.referrer_id, ru.referred_user_id, 
               u1.username as referrer_username, u2.username as referred_username
        FROM referred_user ru
        JOIN user u1 ON ru.referrer_id = u1.id
        JOIN user u2 ON ru.referred_user_id = u2.id
        LIMIT 1
    ");
    $stmt->execute();
    $referral = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$referral) {
        echo "ERROR: No referral data found in the database. Please create a referred user first.\n";
        exit;
    }
    
    echo "Found referral relationship: \n";
    echo "Referrer: {$referral['referrer_username']} (ID: {$referral['referrer_id']})\n";
    echo "Referred User: {$referral['referred_username']} (ID: {$referral['referred_user_id']})\n\n";
    
    // 2. Check if the referred user has any transactions
    $stmt = $pdo->prepare("
        SELECT id, amount, status, payment_confirmed, created_at
        FROM transaction_history
        WHERE user_id = :user_id
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bindParam(':user_id', $referral['referred_user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo "ERROR: No transactions found for the referred user. Please create a transaction first.\n";
        exit;
    }
    
    echo "Found transaction for referred user: \n";
    echo "Transaction ID: {$transaction['id']}\n";
    echo "Amount: {$transaction['amount']} VND\n";
    echo "Status: {$transaction['status']}\n";
    echo "Payment Confirmed: " . ($transaction['payment_confirmed'] ? 'Yes' : 'No') . "\n";
    echo "Created At: {$transaction['created_at']}\n\n";
    
    // 3. Check if a commission record already exists for this transaction
    $stmt = $pdo->prepare("
        SELECT id, commission_amount, status, created_at
        FROM referral_commission
        WHERE transaction_id = :transaction_id
    ");
    $stmt->bindParam(':transaction_id', $transaction['id'], PDO::PARAM_INT);
    $stmt->execute();
    $commission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($commission) {
        echo "Commission record already exists for this transaction: \n";
        echo "Commission ID: {$commission['id']}\n";
        echo "Amount: {$commission['commission_amount']} VND\n";
        echo "Status: {$commission['status']}\n";
        echo "Created At: {$commission['created_at']}\n\n";
        
        echo "Removing existing commission record to test auto-approval...\n";
        $stmt = $pdo->prepare("DELETE FROM referral_commission WHERE id = :id");
        $stmt->bindParam(':id', $commission['id'], PDO::PARAM_INT);
        $stmt->execute();
        echo "Existing commission record deleted.\n\n";
    }
    
    // 4. Set transaction to completed and payment confirmed
    echo "Setting transaction to completed and payment confirmed...\n";
    $stmt = $pdo->prepare("
        UPDATE transaction_history
        SET status = 'completed', payment_confirmed = 1
        WHERE id = :id
    ");
    $stmt->bindParam(':id', $transaction['id'], PDO::PARAM_INT);
    $stmt->execute();
    echo "Transaction updated.\n\n";
    
    // 5. Calculate commission (should auto-approve)
    echo "Calculating commission (should auto-approve)...\n";
    $result = $referralService->calculateCommission($transaction['id']);
    echo "Commission calculation " . ($result ? "successful" : "failed") . ".\n\n";
    
    // 6. Check the new commission record
    $stmt = $pdo->prepare("
        SELECT id, commission_amount, status, created_at
        FROM referral_commission
        WHERE transaction_id = :transaction_id
    ");
    $stmt->bindParam(':transaction_id', $transaction['id'], PDO::PARAM_INT);
    $stmt->execute();
    $newCommission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($newCommission) {
        echo "New commission record created: \n";
        echo "Commission ID: {$newCommission['id']}\n";
        echo "Amount: {$newCommission['commission_amount']} VND\n";
        echo "Status: {$newCommission['status']}\n";
        echo "Created At: {$newCommission['created_at']}\n\n";
        
        if ($newCommission['status'] === 'approved') {
            echo "SUCCESS: Commission was automatically approved!\n";
        } else {
            echo "ERROR: Commission was created but not automatically approved. Status is: {$newCommission['status']}\n";
        }
    } else {
        echo "ERROR: Commission record was not created.\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
