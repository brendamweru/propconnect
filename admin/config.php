<?php
/**
 * Admin Database Configuration - Modernized with Environment Variables
 * 
 * Home Park Real Estate System
 */

// Load environment variables
require_once __DIR__ . '/../includes/EnvLoader.php';

// Get database configuration from environment
$dbConfig = EnvLoader::getDatabaseConfig();

// Establish database connection
$con = mysqli_connect(
    $dbConfig['host'],
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['database'],
    $dbConfig['port']
);

// Check connection
if (mysqli_connect_errno()) {
    if (EnvLoader::isDebug()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    } else {
        echo "Database connection error. Please contact administrator.";
    }
    exit;
}

// Set charset
mysqli_set_charset($con, "utf8mb4");

// Session configuration
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
}

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Error reporting based on environment
if (EnvLoader::isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Helper function for getting site URL
function getSiteUrl(): string 
{
    return EnvLoader::get('APP_URL', 'http://localhost');
}

// Helper function for getting payment settings
function getPaymentSettings(): array 
{
    return EnvLoader::getPaymentSettings();
}

// Helper function for currency
function getCurrency(): string 
{
    return EnvLoader::get('PAYMENT_CURRENCY', 'KES');
}

function getCurrencySymbol(): string 
{
    return EnvLoader::get('PAYMENT_CURRENCY_SYMBOL', 'KSh');
}
