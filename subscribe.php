<?php 
/**
 * Subscription Plans & Payment Page
 * 
 * Home Park Real Estate - Payment Integration
 */

session_start();
include("config.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location:login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$settings = getPaymentSettings();
$currency_symbol = getCurrencySymbol();

// Get current user's subscription info
$subscription_query = mysqli_query($con, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($subscription_query);
$current_plan = $user_data['subscription_plan'] ?? 'basic';
$subscription_status = $user_data['subscription_status'] ?? 'none';

// Get available gateways
$gateways = [];
if ($settings['enable_mpesa']) $gateways[] = 'mpesa';
if ($settings['enable_stripe']) $gateways[] = 'stripe';

// Process payment request
$message = '';
$message_type = '';

if (isset($_POST['initiate_payment'])) {
    $plan = $_POST['plan'] ?? '';
    $gateway = $_POST['gateway'] ?? '';
    
    // Validate plan
    $valid_plans = ['premium', 'enterprise'];
    if (!in_array($plan, $valid_plans)) {
        $message = 'Invalid subscription plan selected.';
        $message_type = 'danger';
    } elseif (!in_array($gateway, $gateways)) {
        $message = 'Invalid payment gateway selected.';
        $message_type = 'danger';
    } else {
        // Get plan price
        $plan_price = ($plan === 'premium') ? $settings['premium_price'] : $settings['enterprise_price'];
        
        if ($gateway === 'mpesa') {
            $phone = $_POST['phone'] ?? '';
            
            if (empty($phone)) {
                $message = 'Please enter your phone number.';
                $message_type = 'danger';
            } else {
                // Include M-Pesa payment class
                require_once 'includes/payments/MpesaPayment.php';
                
                $mpesa = new MpesaPayment();
                $formatted_phone = MpesaPayment::formatPhone($phone);
                
                $paymentData = [
                    'amount' => $plan_price,
                    'phone' => $formatted_phone,
                    'reference' => 'SUB_' . strtoupper($plan) . '_' . $user_id . '_' . time(),
                    'description' => ucfirst($plan) . ' Subscription - ' . ucfirst($gateway)
                ];
                
                $response = $mpesa->initializePayment($paymentData);
                
                if (isset($response['CheckoutRequestCode'])) {
                    // Store payment info in session for verification
                    $_SESSION['pending_payment'] = [
                        'gateway' => 'mpesa',
                        'plan' => $plan,
                        'amount' => $plan_price,
                        'checkout_request_id' => $response['CheckoutRequestCode'],
                        'reference' => $paymentData['reference']
                    ];
                    
                    $message = 'Please check your phone and enter your M-Pesa PIN to complete payment.';
                    $message_type = 'success';
                    
                    // Check payment status after a delay
                    echo '<script>setTimeout(function(){ checkMpesaStatus(); }, 5000);</script>';
                } else {
                    $message = 'Payment initialization failed: ' . ($response['errorMessage'] ?? 'Unknown error');
                    $message_type = 'danger';
                }
            }
        } elseif ($gateway === 'stripe') {
            // Redirect to Stripe checkout or process payment
            require_once 'includes/payments/StripePayment.php';
            
            $stripe = new StripePayment();
            
            $paymentData = [
                'amount' => $plan_price,
                'email' => $user_data['email'],
                'name' => $user_data['name'],
                'reference' => 'SUB_' . strtoupper($plan) . '_' . $user_id . '_' . time(),
                'plan' => $plan
            ];
            
            $response = $stripe->initializePayment($paymentData);
            
            if ($response['success']) {
                // Store payment intent for verification
                $_SESSION['pending_payment'] = [
                    'gateway' => 'stripe',
                    'plan' => $plan,
                    'amount' => $plan_price,
                    'payment_intent_id' => $response['payment_intent_id'],
                    'client_secret' => $response['client_secret']
                ];
                
                // Redirect to Stripe checkout (simplified - in production use Stripe.js)
                $checkoutUrl = "https://checkout.stripe.com/pay/" . $response['payment_intent_id'];
                header("Location: " . $checkoutUrl);
                exit;
            } else {
                $message = 'Payment initialization failed: ' . ($response['error'] ?? 'Unknown error');
                $message_type = 'danger';
            }
        }
    }
}

// Verify pending payment
if (isset($_SESSION['pending_payment']) && isset($_POST['verify_payment'])) {
    $pending = $_SESSION['pending_payment'];
    
    if ($pending['gateway'] === 'mpesa') {
        require_once 'includes/payments/MpesaPayment.php';
        
        $mpesa = new MpesaPayment();
        $result = $mpesa->verifyPayment($pending['checkout_request_id']);
        
        if (isset($result['ResultCode']) && $result['ResultCode'] == 0) {
            // Payment successful - update subscription
            $plan = $pending['plan'];
            $property_limit = ($plan === 'premium') ? $settings['premium_properties'] : $settings['enterprise_properties'];
            
            mysqli_query($con, "UPDATE users SET 
                subscription_plan = '$plan',
                subscription_status = 'active',
                properties_limit = '$property_limit'
                WHERE id = '$user_id'");
            
            // Record payment
            mysqli_query($con, "INSERT INTO payments (user_id, gateway, transaction_id, checkout_request_id, amount, status, phone) 
                VALUES ('$user_id', 'mpesa', '" . ($result['MpesaReceiptNumber'] ?? '') . "', '" . $pending['checkout_request_id'] . "', '" . $pending['amount'] . "', 'completed', '')");
            
            $message = 'Payment successful! Your ' . ucfirst($plan) . ' subscription is now active.';
            $message_type = 'success';
            unset($_SESSION['pending_payment']);
            
            // Refresh user data
            $subscription_query = mysqli_query($con, "SELECT * FROM users WHERE id = '$user_id'");
            $user_data = mysqli_fetch_assoc($subscription_query);
            $current_plan = $user_data['subscription_plan'];
            $subscription_status = $user_data['subscription_status'];
        } else {
            $message = 'Payment verification pending. Please wait...';
            $message_type = 'warning';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription Plans - Home Park Real Estate</title>
    
    <!-- CSS Links -->
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/font-awesome.min.css">
</head>
<body>

<!-- Header -->
<?php include('header.php'); ?>

<!-- Subscription Section -->
<div class="full-row bg-gray">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Choose Your Subscription Plan</h2>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Basic Plan -->
            <div class="col-md-4">
                <div class="pricing-table <?php echo $current_plan === 'basic' ? 'active' : ''; ?>">
                    <div class="pricing-header">
                        <h3>Basic</h3>
                        <div class="price"><?php echo $currency_symbol; ?>0<span>/month</span></div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> 1 Property Listing</li>
                        <li><i class="fa fa-check"></i> Basic Support</li>
                        <li><i class="fa fa-check"></i> Standard Visibility</li>
                        <li><i class="fa fa-times text-muted"></i> Featured Listings</li>
                        <li><i class="fa fa-times text-muted"></i> Analytics</li>
                    </ul>
                    <div class="pricing-footer">
                        <?php if ($current_plan === 'basic'): ?>
                            <button class="btn btn-secondary" disabled>Current Plan</button>
                        <?php else: ?>
                            <button class="btn btn-primary">Current Plan</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Premium Plan -->
            <div class="col-md-4">
                <div class="pricing-table <?php echo $current_plan === 'premium' ? 'active' : ''; ?>">
                    <div class="pricing-badge">Popular</div>
                    <div class="pricing-header">
                        <h3>Premium</h3>
                        <div class="price"><?php echo $currency_symbol; ?><?php echo $settings['premium_price']; ?><span>/month</span></div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> <?php echo $settings['premium_properties']; ?> Property Listings</li>
                        <li><i class="fa fa-check"></i> Priority Support</li>
                        <li><i class="fa fa-check"></i> Featured Listings</li>
                        <li><i class="fa fa-check"></i> Basic Analytics</li>
                        <li><i class="fa fa-times text-muted"></i> Unlimited</li>
                    </ul>
                    <div class="pricing-footer">
                        <?php if ($current_plan === 'premium' && $subscription_status === 'active'): ?>
                            <button class="btn btn-success" disabled>Active</button>
                        <?php else: ?>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#paymentModal" data-plan="premium">Upgrade Now</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Enterprise Plan -->
            <div class="col-md-4">
                <div class="pricing-table <?php echo $current_plan === 'enterprise' ? 'active' : ''; ?>">
                    <div class="pricing-header">
                        <h3>Enterprise</h3>
                        <div class="price"><?php echo $currency_symbol; ?><?php echo $settings['enterprise_price']; ?><span>/month</span></div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> Unlimited Properties</li>
                        <li><i class="fa fa-check"></i> 24/7 Dedicated Support</li>
                        <li><i class="fa fa-check"></i> Featured Listings</li>
                        <li><i class="fa fa-check"></i> Advanced Analytics</li>
                        <li><i class="fa fa-check"></i> API Access</li>
                    </ul>
                    <div class="pricing-footer">
                        <?php if ($current_plan === 'enterprise' && $subscription_status === 'active'): ?>
                            <button class="btn btn-success" disabled>Active</button>
                        <?php else: ?>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#paymentModal" data-plan="enterprise">Upgrade Now</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Payment</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="plan" id="selectedPlan" value="">
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="gateway" class="form-control" required>
                            <?php if ($settings['enable_mpesa']): ?>
                                <option value="mpesa">M-Pesa (Kenya)</option>
                            <?php endif; ?>
                            <?php if ($settings['enable_stripe']): ?>
                                <option value="stripe">Credit/Debit Card (Stripe)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="phoneGroup">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="e.g., 254712345678">
                        <small class="text-muted">Enter M-Pesa registered number</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <p><strong>Plan:</strong> <span id="planName"></span></p>
                        <p><strong>Amount:</strong> <span id="planAmount"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="initiate_payment" class="btn btn-primary">Proceed to Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include('footer.php'); ?>

<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<script>
$('#paymentModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var plan = button.data('plan');
    var modal = $(this);
    
    modal.find('#selectedPlan').val(plan);
    modal.find('#planName').text(plan.charAt(0).toUpperCase() + plan.slice(1));
    
    <?php if ($settings['enable_mpesa']): ?>
    var prices = {
        'premium': '<?php echo $currency_symbol . $settings['premium_price']; ?>',
        'enterprise': '<?php echo $currency_symbol . $settings['enterprise_price']; ?>'
    };
    <?php else: ?>
    var prices = {
        'premium': '<?php echo $currency_symbol . $settings['premium_price']; ?>',
        'enterprise': '<?php echo $currency_symbol . $settings['enterprise_price']; ?>'
    };
    <?php endif; ?>
    
    modal.find('#planAmount').text(prices[plan]);
});

function checkMpesaStatus() {
    // This would typically use AJAX to check payment status
    // For demo, we'll show a verification form
    <?php if (isset($_SESSION['pending_payment']) && $_SESSION['pending_payment']['gateway'] === 'mpesa'): ?>
    $('#paymentModal').modal('show');
    $('#paymentModal .modal-title').text('Verify Payment');
    <?php endif; ?>
}
</script>

</body>
</html>
