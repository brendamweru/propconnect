<?php
/**
 * M-Pesa Payment Callback Handler
 * 
 * Safaricom Daraja API callback endpoint
 */

header('Content-Type: application/json');

// Include configuration
$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/config.php';
require_once $rootPath . '/includes/EnvLoader.php';
require_once $rootPath . '/includes/payments/MpesaPayment.php';

// Log the callback for debugging
$logFile = $rootPath . '/logs/mpesa_callback.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$rawData = file_get_contents('php://input');
$timestamp = date('Y-m-d H:i:s');
file_put_contents($logFile, "[$timestamp] Received: $rawData\n", FILE_APPEND);

// Process callback
$mpesa = new MpesaPayment();
$result = $mpesa->handleWebhook();

// Log the result
file_put_contents($logFile, "[$timestamp] Processed: " . json_encode($result) . "\n", FILE_APPEND);

if ($result['success']) {
    // Extract payment details
    $checkoutRequestId = $result['checkout_request_id'];
    $amount = $result['amount'] ?? 0;
    $receiptNumber = $result['receipt_number'] ?? '';
    $phone = $result['phone'] ?? '';
    
    // Find pending payment in database
    $query = mysqli_query($con, "SELECT * FROM payments WHERE checkout_request_id = '$checkoutRequestId' AND status = 'pending'");
    
    if (mysqli_num_rows($query) > 0) {
        $payment = mysqli_fetch_assoc($query);
        $userId = $payment['user_id'];
        
        // Update payment status
        mysqli_query($con, "UPDATE payments SET 
            status = 'completed',
            transaction_id = '$receiptNumber',
            callback_data = '" . json_encode($result) . "'
            WHERE checkout_request_id = '$checkoutRequestId'");
        
        // Update user subscription
        $plan = $payment['plan'] ?? 'premium';
        $propertyLimit = ($plan === 'premium') ? 5 : 999999;
        
        mysqli_query($con, "UPDATE users SET 
            subscription_plan = '$plan',
            subscription_status = 'active',
            properties_limit = '$propertyLimit'
            WHERE id = '$userId'");
        
        // Create subscription record
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));
        
        mysqli_query($con, "INSERT INTO subscriptions (user_id, plan, status, start_date, end_date, property_limit) 
            VALUES ('$userId', '$plan', 'active', '$startDate', '$endDate', '$propertyLimit')");
        
        echo json_encode(['status' => 'success']);
    } else {
        // Payment might already be processed
        echo json_encode(['status' => 'duplicate']);
    }
} else {
    // Payment failed
    if (!empty($checkoutRequestId)) {
        mysqli_query($con, "UPDATE payments SET 
            status = 'failed',
            callback_data = '" . json_encode($result) . "'
            WHERE checkout_request_id = '$checkoutRequestId'");
    }
    
    echo json_encode(['status' => 'failed']);
}
