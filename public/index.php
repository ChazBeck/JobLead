<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Load configuration and helpers
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/helpers.php';

// Load classes
require_once '../src/Database.php';

// Initialize database connection (optional for now)
try {
    $db = new Database();
} catch (Exception $e) {
    showError('Database connection failed: ' . $e->getMessage());
}

// Simple router
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

switch ($page) {
    case 'upload':
        require '../src/pages/upload.php';
        break;
    case 'details':
        require '../src/pages/details.php';
        break;
    case 'dashboard':
    default:
        require '../src/pages/dashboard.php';
        break;
}
