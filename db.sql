CREATE DATABASE IF NOT EXISTS gente_vigente CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gente_vigente;

CREATE USER IF NOT EXISTS 'gentevigente_admin'@'localhost' IDENTIFIED BY 'Admin123$';

GRANT ALL PRIVILEGES
  ON gente_vigente.*
  TO 'gentevigente_admin'@'localhost';

FLUSH PRIVILEGES;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    subscription_type ENUM('bronce', 'gold') DEFAULT 'bronce',
    subscription_status ENUM('active', 'inactive', 'cancelled', 'pending') DEFAULT 'pending',
    paypal_payment_id VARCHAR(255) NULL,
    temp_password VARCHAR(100) NULL COMMENT 'Password temporal antes del primer login',
    first_time_login BOOLEAN DEFAULT 1,
    last_login TIMESTAMP NULL,
    profile_image VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    country VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_subscription_status (subscription_status),
    INDEX idx_subscription_type (subscription_type),
    INDEX idx_paypal_payment_id (paypal_payment_id)
);

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_type ENUM('bronce', 'gold') NOT NULL,
    status ENUM('active', 'cancelled', 'past_due', 'expired', 'pending') DEFAULT 'pending',
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    paypal_subscription_id VARCHAR(255) NULL,
    paypal_payment_id VARCHAR(255) NULL,
    current_period_start DATE NULL,
    current_period_end DATE NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_paypal_payment_id (paypal_payment_id)
);

CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email_type ENUM('welcome', 'reset_password', 'notification', 'test') NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email_type (email_type),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);

-- Tabla actividad de usuarios (logs)
CREATE TABLE user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('registration', 'login', 'logout', 'password_change', 'profile_update', 'payment') NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_activity (user_id, activity_type),
    INDEX idx_created_at (created_at)
);

