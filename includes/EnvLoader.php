<?php
/**
 * Environment Variables Loader
 * 
 * Loads environment variables from .env file
 * Provides getters for all configuration values
 */

class EnvLoader
{
    private static $instance = null;
    private $variables = [];
    
    /**
     * Constructor - loads .env file
     */
    private function __construct()
    {
        $this->loadEnvFile();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load .env file
     */
    private function loadEnvFile()
    {
        $envFile = __DIR__ . '/../.env';
        
        // Fallback to root directory
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/../../.env';
        }
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes
                    $value = str_replace(['"', "'"], '', $value);
                    
                    $this->variables[$key] = $value;
                }
            }
        }
        
        // Also set superglobals for compatibility
        $this->setSuperGlobals();
    }
    
    /**
     * Set PHP superglobals for compatibility
     */
    private function setSuperGlobals()
    {
        foreach ($this->variables as $key => $value) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    /**
     * Get environment variable
     */
    public static function get($key, $default = null)
    {
        $instance = self::getInstance();
        
        if (isset($instance->variables[$key])) {
            return $instance->variables[$key];
        }
        
        // Check getenv/$_ENV as fallback
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Get database configuration
     */
    public static function getDatabaseConfig()
    {
        return [
            'host'     => self::get('DB_HOST', '127.0.0.1'),
            'port'     => intval(self::get('DB_PORT', '3306')),
            'database' => self::get('DB_DATABASE', 'propconnectphp'),
            'username' => self::get('DB_USERNAME', 'root'),
            'password' => self::get('DB_PASSWORD', ''),
        ];
    }
    
    /**
     * Get M-Pesa configuration
     */
    public static function getMpesaConfig()
    {
        return [
            'env'             => self::get('MPESA_ENV', 'sandbox'),
            'consumer_key'    => self::get('MPESA_CONSUMER_KEY', ''),
            'consumer_secret' => self::get('MPESA_CONSUMER_SECRET', ''),
            'shortcode'       => self::get('MPESA_SHORTCODE', ''),
            'msisdn'          => self::get('MPESA_MSISDN', ''),
            'passkey'         => self::get('MPESA_PASSKEY', ''),
            'initiator_name'  => self::get('MPESA_INITIATOR_NAME', ''),
            'initiator_pass'  => self::get('MPESA_INITIATOR_PASSWORD', ''),
            'stk_callback'    => self::get('MPESA_STK_CALLBACK_URL', ''),
            'c2b_callback'    => self::get('MPESA_C2B_CALLBACK_URL', ''),
            'b2c_callback'    => self::get('MPESA_B2C_CALLBACK_URL', ''),
        ];
    }
    
    /**
     * Get Stripe configuration
     */
    public static function getStripeConfig()
    {
        return [
            'env'           => self::get('STRIPE_ENV', 'test'),
            'key'           => self::get('STRIPE_KEY', ''),
            'secret'        => self::get('STRIPE_SECRET', ''),
            'webhook_secret'=> self::get('STRIPE_WEBHOOK_SECRET', ''),
            'currency'      => self::get('STRIPE_CURRENCY', 'usd'),
        ];
    }
    
    /**
     * Get payment settings
     */
    public static function getPaymentSettings()
    {
        return [
            'currency'          => self::get('PAYMENT_CURRENCY', 'KES'),
            'currency_symbol'   => self::get('PAYMENT_CURRENCY_SYMBOL', 'KSh'),
            'currency_code'     => self::get('PAYMENT_CURRENCY_CODE', '404'),
            'basic_price'       => self::get('SUBSCRIPTION_BASIC_PRICE', 0),
            'basic_properties'  => self::get('SUBSCRIPTION_BASIC_PROPERTIES', 1),
            'premium_price'     => self::get('SUBSCRIPTION_PREMIUM_PRICE', 2999),
            'premium_properties'=> self::get('SUBSCRIPTION_PREMIUM_PROPERTIES', 5),
            'enterprise_price'  => self::get('SUBSCRIPTION_ENTERPRISE_PRICE', 9999),
            'enterprise_properties' => self::get('SUBSCRIPTION_ENTERPRISE_PROPERTIES', 999999),
            'enable_mpesa'      => self::get('ENABLE_MPESA', 'true') === 'true',
            'enable_stripe'     => self::get('ENABLE_STRIPE', 'true') === 'true',
        ];
    }
    
    /**
     * Check if running in production
     */
    public static function isProduction()
    {
        return self::get('APP_ENV') === 'production';
    }
    
    /**
     * Check if debug mode is enabled
     */
    public static function isDebug()
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}

// Helper function for easy access
function env($key, $default = null)
{
    return EnvLoader::get($key, $default);
}
