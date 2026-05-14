<?php
/**
 * M-Pesa Payment Gateway Integration
 * 
 * Safaricom Daraja API implementation for M-Pesa payments
 * Supports STK Push, C2B, B2C, and Transaction Status queries
 */

require_once __DIR__ . '/PaymentInterface.php';
require_once __DIR__ . '/../EnvLoader.php';

class MpesaPayment implements PaymentInterface
{
    private $config;
    private $baseUrl;
    private $accessToken;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = EnvLoader::getMpesaConfig();
        $this->baseUrl = $this->config['env'] === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }
    
    /**
     * Get access token for API calls
     */
    public function getAccessToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        
        $credentials = base64_encode(
            $this->config['consumer_key'] . ':' . $this->config['consumer_secret']
        );
        
        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            return $this->accessToken;
        }
        
        throw new Exception('Failed to get M-Pesa access token: ' . $response);
    }
    
    /**
     * Generate password for STK Push
     */
    private function generatePassword()
    {
        $timestamp = date('YmdHis');
        $shortcode = $this->config['shortcode'];
        $passkey = $this->config['passkey'];
        
        return base64_encode($shortcode . $passkey . $timestamp);
    }
    
    /**
     * Initialize STK Push Payment
     * 
     * @param array $paymentData Payment details
     * @return array Response
     */
    public function initializePayment(array $paymentData)
    {
        $accessToken = $this->getAccessToken();
        
        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
        
        $timestamp = date('YmdHis');
        
        $payload = [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password' => $this->generatePassword(),
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $paymentData['amount'],
            'PartyA' => $paymentData['phone'],
            'PartyB' => $this->config['shortcode'],
            'PhoneNumber' => $paymentData['phone'],
            'CallBackURL' => $this->config['stk_callback'],
            'AccountReference' => $paymentData['reference'] ?? 'PropertyPayment',
            'TransactionDesc' => $paymentData['description'] ?? 'Property Payment'
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Query STK Push Status
     * 
     * @param string $checkoutRequestId Checkout request ID from STK Push
     * @return array Response
     */
    public function verifyPayment(string $checkoutRequestId)
    {
        $accessToken = $this->getAccessToken();
        
        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
        
        $timestamp = date('YmdHis');
        
        $payload = [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password' => $this->generatePassword(),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Handle STK Push Callback
     * 
     * @return array Parsed callback data
     */
    public function handleWebhook()
    {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data || !isset($data['Body'])) {
            return [
                'success' => false,
                'message' => 'Invalid callback data'
            ];
        }
        
        $body = $data['Body'];
        $stkCallback = $body['stkCallback'] ?? [];
        
        $result = [
            'success' => $body['status'] === 'Success',
            'checkout_request_id' => $stkCallback['CheckoutRequestID'] ?? '',
            'merchant_request_id' => $stkCallback['MerchantRequestID'] ?? '',
            'result_code' => $stkCallback['ResultCode'] ?? '',
            'result_desc' => $stkCallback['ResultDesc'] ?? '',
        ];
        
        // Parse callback metadata
        if (isset($stkCallback['CallbackMetadata'])) {
            $metadata = $stkCallback['CallbackMetadata'];
            $items = $metadata['Item'] ?? [];
            
            foreach ($items as $item) {
                $key = $item['Name'] ?? '';
                $value = $item['Value'] ?? '';
                
                if ($key === 'Amount') $result['amount'] = $value;
                if ($key === 'MpesaReceiptNumber') $result['receipt_number'] = $value;
                if ($key === 'PhoneNumber') $result['phone'] = $value;
                if ($key === 'TransactionDate') $result['transaction_date'] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * C2B Register URLs
     * 
     * Register confirmation and validation URLs
     */
    public function registerUrls()
    {
        $accessToken = $this->getAccessToken();
        
        $url = $this->baseUrl . '/mpesa/c2b/v1/registerurl';
        
        $payload = [
            'ShortCode' => $this->config['shortcode'],
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $this->config['c2b_callback'],
            'ValidationURL' => $this->config['c2b_callback']
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * B2C - Business to Customer (Payouts)
     * 
     * @param array $payoutData Payout details
     * @return array Response
     */
    public function sendPayout(array $payoutData)
    {
        $accessToken = $this->getAccessToken();
        
        $url = $this->baseUrl . '/mpesa/b2c/v1/paymentrequest';
        
        $payload = [
            'InitiatorName' => $this->config['initiator_name'],
            'SecurityCredential' => $this->getSecurityCredential(),
            'CommandID' => 'BusinessPayment',
            'Amount' => (int) $payoutData['amount'],
            'PartyA' => $this->config['shortcode'],
            'PartyB' => $payoutData['phone'],
            'Remarks' => $payoutData['remarks'] ?? 'Property payout',
            'QueueTimeOutURL' => $this->config['b2c_callback'],
            'ResultURL' => $this->config['b2c_callback']
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Generate security credential
     */
    private function getSecurityCredential()
    {
        // This is a simplified version - in production use proper encryption
        return base64_encode($this->config['initiator_pass']);
    }
    
    /**
     * Process refund
     * 
     * @param string $transactionId Original transaction ID
     * @param float $amount Refund amount
     * @return array Refund result
     */
    public function refundPayment(string $transactionId, float $amount)
    {
        // M-Pesa doesn't have a direct refund API
        // This would typically be handled manually or via B2C
        return [
            'success' => false,
            'message' => 'M-Pesa refunds require manual processing or B2C payout'
        ];
    }
    
    /**
     * Get payment status
     * 
     * @param string $transactionId Transaction ID
     * @return string Payment status
     */
    public function getPaymentStatus(string $transactionId)
    {
        $result = $this->verifyPayment($transactionId);
        
        if (isset($result['ResultCode'])) {
            if ($result['ResultCode'] === 0) {
                return 'COMPLETED';
            }
            return 'FAILED';
        }
        
        return 'PENDING';
    }
    
    /**
     * Format phone number to M-Pesa format
     * 
     * @param string $phone Phone number
     * @return string Formatted phone
     */
    public static function formatPhone(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to M-Pesa format (254...)
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }
        
        if (strlen($phone) === 9) {
            return '254' . $phone;
        }
        
        return $phone;
    }
}
