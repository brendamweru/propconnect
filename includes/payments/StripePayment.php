<?php
/**
 * Stripe Payment Gateway Integration
 * 
 * Stripe API implementation for card payments
 * Supports Payment Intents, Subscriptions, and Webhooks
 */

require_once __DIR__ . '/PaymentInterface.php';
require_once __DIR__ . '/../EnvLoader.php';

class StripePayment implements PaymentInterface
{
    private $config;
    private $stripe;
    
    /**
     * Constructor - Initialize Stripe
     */
    public function __construct()
    {
        $this->config = EnvLoader::getStripeConfig();
        $this->initializeStripe();
    }
    
    /**
     * Initialize Stripe library
     */
    private function initializeStripe()
    {
        // Check if Stripe SDK is installed
        if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../../vendor/autoload.php';
            \Stripe\Stripe::setApiKey($this->config['secret']);
            \Stripe\Stripe::setApiVersion('2023-10-16');
        }
    }
    
    /**
     * Check if Stripe is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['key']) && !empty($this->config['secret']);
    }
    
    /**
     * Get Stripe publishable key for frontend
     */
    public function getPublishableKey(): string
    {
        return $this->config['key'];
    }
    
    /**
     * Create a Payment Intent
     * 
     * @param array $paymentData Payment details
     * @return array Response with client secret
     */
    public function initializePayment(array $paymentData)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Stripe is not configured'
            ];
        }
        
        try {
            // Create or retrieve customer
            $customer = $this->createOrGetCustomer($paymentData['email'] ?? '', $paymentData['name'] ?? '');
            
            // Create payment intent
            $intent = \Stripe\PaymentIntent::create([
                'amount' => (int) ($paymentData['amount'] * 100), // Convert to cents
                'currency' => $this->config['currency'],
                'customer' => $customer->id,
                'metadata' => [
                    'reference' => $paymentData['reference'] ?? '',
                    'property_id' => $paymentData['property_id'] ?? '',
                    'plan' => $paymentData['plan'] ?? 'premium',
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
            
            return [
                'success' => true,
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create or retrieve Stripe customer
     */
    private function createOrGetCustomer(string $email, string $name)
    {
        try {
            // Search for existing customer
            $customers = \Stripe\Customer::all([
                'email' => $email,
                'limit' => 1
            ]);
            
            if (count($customers->data) > 0) {
                return $customers->data[0];
            }
            
            // Create new customer
            return \Stripe\Customer::create([
                'email' => $email,
                'name' => $name,
            ]);
            
        } catch (\Exception $e) {
            throw new Exception('Failed to create customer: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify payment status
     * 
     * @param string $paymentIntentId Payment Intent ID
     * @return array Payment verification result
     */
    public function verifyPayment(string $paymentIntentId)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Stripe is not configured'
            ];
        }
        
        try {
            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            
            return [
                'success' => $intent->status === 'succeeded',
                'status' => $intent->status,
                'amount' => $intent->amount / 100,
                'currency' => $intent->currency,
                'receipt_url' => $intent->charges->data[0]->receipt_url ?? '',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle Stripe Webhook
     * 
     * @return array Parsed webhook data
     */
    public function handleWebhook()
    {
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->config['webhook_secret']
            );
            
            $result = [
                'success' => true,
                'event_type' => $event->type,
                'event_id' => $event->id,
            ];
            
            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $result['payment_status'] = 'succeeded';
                    $result['data'] = $event->data->object;
                    break;
                    
                case 'payment_intent.payment_failed':
                    $result['payment_status'] = 'failed';
                    $result['data'] = $event->data->object;
                    break;
                    
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $result['subscription_status'] = $event->data->object->status;
                    $result['data'] = $event->data->object;
                    break;
                    
                default:
                    $result['data'] = $event->data->object;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Webhook error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process a refund
     * 
     * @param string $paymentIntentId Original Payment Intent ID
     * @param float $amount Refund amount (optional - full refund if not specified)
     * @return array Refund result
     */
    public function refundPayment(string $paymentIntentId, float $amount = null)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Stripe is not configured'
            ];
        }
        
        try {
            $refundData = [
                'payment_intent' => $paymentIntentId,
            ];
            
            // Add amount for partial refund (in cents)
            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }
            
            $refund = \Stripe\Refund::create($refundData);
            
            return [
                'success' => $refund->status === 'succeeded',
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payment status
     * 
     * @param string $paymentIntentId Payment Intent ID
     * @return string Payment status
     */
    public function getPaymentStatus(string $paymentIntentId): string
    {
        if (!$this->isConfigured()) {
            return 'NOT_CONFIGURED';
        }
        
        try {
            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            return strtoupper($intent->status);
        } catch (\Exception $e) {
            return 'ERROR';
        }
    }
    
    /**
     * Create a subscription
     * 
     * @param string $customerId Stripe Customer ID
     * @param string $priceId Stripe Price ID
     * @return array Subscription result
     */
    public function createSubscription(string $customerId, string $priceId)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Stripe is not configured'
            ];
        }
        
        try {
            $subscription = \Stripe\Subscription::create([
                'customer' => $customerId,
                'items' => [
                    ['price' => $priceId],
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ]);
            
            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel a subscription
     * 
     * @param string $subscriptionId Subscription ID
     * @return array Cancellation result
     */
    public function cancelSubscription(string $subscriptionId)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Stripe is not configured'
            ];
        }
        
        try {
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $canceled = $subscription->cancel();
            
            return [
                'success' => true,
                'status' => $canceled->status,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get Stripe checkout session URL
     * 
     * @param array $checkoutData Checkout details
     * @return string Checkout URL
     */
    public function createCheckoutSession(array $checkoutData)
    {
        if (!$this->isConfigured()) {
            return null;
        }
        
        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $this->config['currency'],
                            'product_data' => [
                                'name' => $checkoutData['product_name'],
                                'description' => $checkoutData['description'] ?? '',
                            ],
                            'unit_amount' => (int) ($checkoutData['amount'] * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $checkoutData['success_url'],
                'cancel_url' => $checkoutData['cancel_url'],
                'metadata' => $checkoutData['metadata'] ?? [],
            ]);
            
            return $session->url;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}
