<?php
/**
 * Payment Interface
 * 
 * Defines the contract for all payment gateways
 */

interface PaymentInterface
{
    /**
     * Initialize a payment
     * 
     * @param array $paymentData Payment details
     * @return array Response with payment status and details
     */
    public function initializePayment(array $paymentData);
    
    /**
     * Verify a payment
     * 
     * @param string $transactionId Transaction ID
     * @return array Payment verification result
     */
    public function verifyPayment(string $transactionId);
    
    /**
     * Process a refund
     * 
     * @param string $transactionId Original transaction ID
     * @param float $amount Refund amount
     * @return array Refund result
     */
    public function refundPayment(string $transactionId, float $amount);
    
    /**
     * Handle webhook/callback
     * 
     * @return array Parsed webhook data
     */
    public function handleWebhook();
    
    /**
     * Get payment status
     * 
     * @param string $transactionId Transaction ID
     * @return string Payment status
     */
    public function getPaymentStatus(string $transactionId);
}
