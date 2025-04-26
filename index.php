<?php
/**
 * SOCIALKOCH.CO - رصد، پایش و تحلیل شبکه‌های اجتماعی
 * Main Entry Point
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';
require_once 'config/constants.php';
require_once 'config/db.php';

// Load helper functions
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/openai.php';

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Get the requested URL
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$request_uri = substr($request_uri, strlen($base_path));
$request_uri = trim($request_uri, '/');

// Get the request method
$request_method = $_SERVER['REQUEST_METHOD'];

// Default route
if (empty($request_uri)) {
    $request_uri = 'landing';
}

// Parse the URL
$url_parts = explode('/', $request_uri);
$controller = $url_parts[0];
$action = isset($url_parts[1]) ? $url_parts[1] : 'index';
$params = array_slice($url_parts, 2);

// Check if AJAX request
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Handle API requests
if ($controller == 'api') {
    header('Content-Type: application/json');
    require_once 'controllers/api.php';
    exit;
}

// Handle authentication
if ($controller == 'auth') {
    require_once 'controllers/auth.php';
    exit;
}

// Protected routes that require authentication
$protected_routes = ['dashboard', 'user', 'admin', 'instagram', 'subscription', 'payment'];

// Check if route requires authentication
if (in_array($controller, $protected_routes)) {
    if (!isLoggedIn()) {
        // Redirect to login page
        if ($is_ajax) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['status' => 'error', 'message' => 'شما باید وارد حساب کاربری خود شوید.']);
            exit;
        } else {
            redirect('auth/login');
        }
    }

    // Check for admin routes
    if ($controller == 'admin' && !isAdmin()) {
        if ($is_ajax) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['status' => 'error', 'message' => 'شما دسترسی به این بخش را ندارید.']);
            exit;
        } else {
            redirect('dashboard');
        }
    }
}

// Load language file based on session or default
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : DEFAULT_LANGUAGE;
if (file_exists("config/languages/{$lang}.php")) {
    require_once "config/languages/{$lang}.php";
} else {
    // Create a simple language array if language file doesn't exist
    $lang = [];
}

// Map controller to file
switch ($controller) {
    case 'landing':
        include 'views/landing.php';
        break;

    case 'dashboard':
        require_once 'controllers/dashboard.php';
        break;

    case 'user':
        require_once 'controllers/user.php';
        break;

    case 'admin':
        require_once 'controllers/admin.php';
        break;

    case 'instagram':
        require_once 'controllers/instagram.php';
        break;
    case 'content':
        require_once 'controllers/content.php';
        break;
    case 'subscription':
        require_once 'controllers/subscription.php';
        break;

    case 'payment':
        require_once 'controllers/payment.php';
        break;

    case 'blog':
        require_once 'controllers/blog.php';
        break;

    case 'support':
        require_once 'controllers/support.php';
        break;

    case 'terms':
        include 'views/terms.php';
        break;

    case 'privacy':
        include 'views/privacy.php';
        break;

    default:
        // 404 page
        header("HTTP/1.0 404 Not Found");
        include 'views/404.php';
        break;
}
