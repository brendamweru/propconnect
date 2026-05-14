<?php
/**
 * Payment Factory
 * 
 * Factory class to create payment gateway instances
 */

require_once __DIR__ . '/PaymentInterface.php';
require_once __DIR__ . '/MpesaPayment.php';
require_once __DIR__ . '/StripePayment.php';

class PaymentFactory
{
    const MPESA = 'mpesa';
    const STRIPE = 'stripe';
    
    /**
     * Create payment gateway instance
     * 
     * @param string $gateway Payment gateway name
     * @return PaymentInterface|null Payment gateway instance
     */
    public static function create(string $gateway): ?PaymentInterface
    {
        switch (strtolower($gateway)) {
            case self::MPESA:
                return new MpesaPayment();
                
            case self::STRIPE:
                return new StripePayment();
                
            default:
                return null;
        }
    }
    
    /**
     * Create M-Pesa payment instance
     */
    public static function mpesa(): MpesaPayment
    {
        return new MpesaPayment();
    }
    
    /**
     * Create Stripe payment instance
     */
    public static function stripe(): StripePayment
    {
        return new StripePayment();
    }
    
    /**
     * Get all available payment gateways
     */
    public static function getAvailableGateways(): array
    {
        $gateways = [];
        
        $settings = EnvLoader::getPaymentSettings();
        
        if ($settings['enable_mpesa']) {
            $gateways[] = self::MPESA;
        }
        
        if ($settings['enable_stripe']) {
            $gateways[] = self::STRIPE;
        }
        
        return $gateways;
    }
    
    /**
     * Check if a gateway is enabled
     */
    public static function isGatewayEnabled(string $gateway): bool
    {
        $available = self::getAvailableGateways();
        return in_array(strtolower($gateway), $available);
    }
}
