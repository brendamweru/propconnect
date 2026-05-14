-- Home Park Real Estate - Payment System Database Migration
-- Run this SQL to create payment-related tables

-- =============================================================================
-- SUBSCRIPTIONS TABLE
-- =============================================================================
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `plan` ENUM('basic', 'premium', 'enterprise') NOT NULL DEFAULT 'basic',
    `status` ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'pending',
    `stripe_subscription_id` VARCHAR(255) NULL,
    `stripe_customer_id` VARCHAR(255) NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `property_limit` INT(11) NOT NULL DEFAULT 1,
    `properties_used` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_plan` (`plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- PAYMENTS TABLE
-- =============================================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `subscription_id` INT(11) NULL,
    `gateway` ENUM('mpesa', 'stripe') NOT NULL,
    `transaction_id` VARCHAR(255) NOT NULL,
    `merchant_request_id` VARCHAR(255) NULL,
    `checkout_request_id` VARCHAR(255) NULL,
    `payment_intent_id` VARCHAR(255) NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'KES',
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(255) NULL,
    `reference` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `receipt_url` TEXT NULL,
    `callback_data` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_gateway` (`gateway`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_status` (`status`),
    KEY `idx_subscription_id` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- REFUNDS TABLE
-- =============================================================================
CREATE TABLE IF NOT EXISTS `refunds` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payment_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `gateway` ENUM('mpesa', 'stripe') NOT NULL,
    `refund_id` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `reason` TEXT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payment_id` (`payment_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_refund_id` (`refund_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- PAYOUTS TABLE (For Agent/Builder Earnings)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `payouts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `gateway` ENUM('mpesa', 'bank') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `net_amount` DECIMAL(10,2) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `bank_name` VARCHAR(100) NULL,
    `account_number` VARCHAR(50) NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `transaction_id` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- USER WALLETS TABLE (For tracking balances)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wallets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `pending_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'KES',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- WALLET TRANSACTIONS TABLE
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `wallet_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `reference` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wallet_id` (`wallet_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- PAYMENT SETTINGS TABLE
-- =============================================================================
CREATE TABLE IF NOT EXISTS `payment_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default payment settings
INSERT INTO `payment_settings` (`setting_key`, `setting_value`) VALUES
('currency', 'KES'),
('currency_symbol', 'KSh'),
('enable_mpesa', 'true'),
('enable_stripe', 'true'),
('basic_price', '0'),
('basic_properties', '1'),
('premium_price', '2999'),
('premium_properties', '5'),
('enterprise_price', '9999'),
('enterprise_properties', '999999');

-- =============================================================================
-- UPDATE USERS TABLE TO ADD SUBSCRIPTION INFO
-- =============================================================================
ALTER TABLE `users` 
ADD COLUMN `subscription_id` INT(11) NULL AFTER `email`,
ADD COLUMN `subscription_plan` ENUM('basic', 'premium', 'enterprise') NOT NULL DEFAULT 'basic' AFTER `subscription_id`,
ADD COLUMN `subscription_status` ENUM('active', 'expired', 'none') NOT NULL DEFAULT 'none' AFTER `subscription_plan`,
ADD COLUMN `properties_limit` INT(11) NOT NULL DEFAULT 1 AFTER `subscription_status`,
ADD COLUMN `properties_used` INT(11) NOT NULL DEFAULT 0 AFTER `properties_limit`,
ADD COLUMN `stripe_customer_id` VARCHAR(255) NULL AFTER `properties_used`,
ADD INDEX `idx_subscription_status` (`subscription_status`);
