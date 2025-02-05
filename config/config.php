<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Required environment variables
$required_env_vars = [
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASSWORD',
    'APP_URL',
    'APP_NAME',
    'APP_ENV'
];

foreach ($required_env_vars as $var) {
    if (!isset($_ENV[$var])) {
        die("Error: Missing required environment variable: {$var}");
    }
}

// Database configuration
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']); 
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);

// Application configuration
define('APP_NAME', $_ENV['APP_NAME']);
define('APP_URL', $_ENV['APP_URL']);
define('APP_ENV', $_ENV['APP_ENV']);
define('APP_DEBUG', $_ENV['APP_ENV'] === 'development');

// Email configuration
define('SMTP_HOST', $_ENV['SMTP_HOST']);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME']);
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD']);
define('SMTP_PORT', $_ENV['SMTP_PORT']);
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL']);
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME']);
// Email Configuration on site
define('SITE_EMAIL', 'noreply@yourdomain.com');
define('SITE_NAME', 'Gate Pass System');

// Security configuration
define('OTP_TIMEOUT', $_ENV['OTP_TIMEOUT'] ?? 10);
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 120);