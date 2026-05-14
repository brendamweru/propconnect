<?php
/**
 * Stripe Payment Webhook Handler
 * 
 * Handles Stripe payment events and subscriptions
 */

header('Content-Type: application/json');
http_response_code(200);

// Include configuration
$rootPath = dirname(dirname(dirname(__DIR__)));
require_once $rootPath . '/config.php';
require_once $rootPath . '/includes/EnvLoader.php';
require_once $rootPath . '/includes/payments/StripePayment.php';

// Log the webhook for debugging
$logFile = $rootPath . '/logs/stripe_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$rawData = file_get_contents('php://input');
$timestamp = date('Y-m-d H:i:s');
file_put_contents($logFile, "[$timestamp] Received: $rawData\n", FILE_APPEND);

// Process webhook
$stripe = new StripePayment();
$result = $stripe->handleWebhook();

// Log the result
file_put_contents($logFile, "[$timestamp] Processed: " . json_encode($result) . "\n", FILE_APPEND);

if ($result['success']) {
    $eventType = $result['event_type'];
    
    switch ($eventType) {
        case 'payment_intent.succeeded':
            // Handle successful payment
            $data = $result['data'];
            $paymentIntentId = $data->id;
            $amount = $data->amount / 100;
            $metadata = $data->metadata;
            
            // Find payment in database
            $query = mysqli_query($con, "SELECT * FROM payments WHERE payment_intent_id = '$paymentIntentId'");
            
            if (mysqli_num_rows($query) > 0) {
                $payment = mysqli_fetch_assoc($query);
                $userId = $payment['user_id'];
                $plan = $metadata->plan ?? 'premium';
                
                // Update payment status
                mysqli_query($con, "UPDATE payments SET 
                    status = 'completed',
                    receipt_url = '" . ($data->charges->data[0]->receipt_url ?? '') . "'
                    WHERE payment_intent_id = '$paymentIntentId'");
                
                // Update user subscription
                $propertyLimit = ($plan === 'premium') ? 5 : 999999;
                
                mysqli_query($con, "UPDATE users SET 
                    subscription_plan = '$plan',
                    subscription_status = 'active',
                    properties_limit = '$propertyLimit',
                    stripe_customer_id = '" . ($data->customer ?? '') . "'
                    WHERE id = '$userId'");
                
                // Create subscription record
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime('+30 days'));
                
                mysqli_query($con, "INSERT INTO subscriptions (user_id, plan, status, stripe_subscription_id, start_date, end_date, property_limit) 
                    VALUES ('$userId', '$plan', 'active', '', '$startDate', '$endDate', '$propertyLimit')");
            }
            break;
            
        case 'payment_intent.payment_failed':
            // Handle failed payment
            $data = $result['data'];
            $paymentIntentId = $data->id;
            
            mysqli_query($con, "UPDATE payments SET 
                status = 'failed'
                WHERE payment_intent_id = '$paymentIntentId'");
            break;
            
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            // Handle subscription changes
            $data = $result['data'];
            $subscriptionId = $data->id;
            $status = $result['subscription_status'];
            
            // Update subscription in database
            mysqli_query($con, "UPDATE subscriptions SET 
                status = '$status'
                WHERE stripe_subscription_id = '$subscriptionId'");
            break;
            
        case 'customer.subscription.deleted':
            // Handle subscription cancellation
            $data = $result['data'];
            $subscriptionId = $data->id;
            
            // Downgrade to basic
            mysqli_query($con, "UPDATE subscriptions SET 
                status = 'cancelled'
                WHERE stripe_subscription_id = '$subscriptionId'");
            break;
            
        default:
            // Log unhandled events
            file_put_contents($logFile, "[$timestamp] Unhandled event: $eventType\n", FILE_APPEND);
    }
    
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $result['error']]);
}
