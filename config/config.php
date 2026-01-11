<?php
/**
 * Configuration loader
 * Detects environment and loads appropriate config
 */

// Check if production config exists
$prodConfig = __DIR__ . '/config.prod.php';
if (file_exists($prodConfig)) {
    // Load production configuration
    require_once $prodConfig;
} else {
    // Development configuration (XAMPP local)
    
    // Base paths
    define('BASE_PATH', dirname(__DIR__));
    define('PUBLIC_PATH', BASE_PATH . '/public');
    define('SRC_PATH', BASE_PATH . '/src');
    define('CONFIG_PATH', BASE_PATH . '/config');

    // Web paths
    define('BASE_URL', '/JobLead/public');
    define('ASSETS_URL', BASE_URL . '/assets');

    // Database Configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'joblead');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

    // Environment
    define('ENVIRONMENT', 'development');

    // Webhook Configuration
    define('WEBHOOKS', [
        'zapier_ai_analysis' => 'https://hooks.zapier.com/hooks/catch/20909251/ugy3ijf/'
    ]);

    // Error reporting (enabled for development)
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
