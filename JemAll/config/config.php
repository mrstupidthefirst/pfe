<?php
/**
 * Application Configuration
 * Main configuration file for JemAll Marketplace
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Application settings
define('APP_NAME', 'JemAll Marketplace');
define('BASE_URL', '/PFE/JemALL/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('PROFILE_UPLOAD_DIR', __DIR__ . '/../uploads/profiles/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!file_exists(PROFILE_UPLOAD_DIR)) {
    mkdir(PROFILE_UPLOAD_DIR, 0777, true);
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to get current user role
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Helper function to check if user has specific role
function hasRole($role) {
    return getUserRole() === $role;
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Helper function to require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

// Helper function to sanitize output
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Helper function to redirect
function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit;
}

// Language functions
function getCurrentLanguage() {
    return $_SESSION['lang'] ?? 'fr';
}

function setLanguage($lang) {
    $allowed_langs = ['fr', 'en', 'ar'];
    if (in_array($lang, $allowed_langs)) {
        $_SESSION['lang'] = $lang;
    }
}

function loadLanguage($lang = null) {
    global $lang;
    if ($lang === null) {
        // Check if language is set via GET parameter
        if (isset($_GET['lang'])) {
            setLanguage($_GET['lang']);
            // Remove lang from URL and redirect to clean URL
            $url = $_SERVER['REQUEST_URI'];
            $url = preg_replace('/[?&]lang=[^&]*/', '', $url);
            if (strpos($url, '?') === false && strpos($url, '&') !== false) {
                $url = str_replace('&', '?', $url);
            }
            header('Location: ' . $url);
            exit;
        }
        $lang = getCurrentLanguage();
    }

    $lang_file = __DIR__ . '/../languages/' . $lang . '.php';
    if (file_exists($lang_file)) {
        include $lang_file;
    } else {
        // Fallback to French
        include __DIR__ . '/../languages/fr.php';
    }
}

function __($key) {
    global $lang;
    return $lang[$key] ?? $key;
}

/**
 * Create a new notification for a user
 * @param int $user_id The ID of the user to receive the notification
 * @param string $type The type of notification (e.g., 'order_update', 'account_status')
 * @param string $message The notification message
 * @param string|null $link Optional link associated with the notification
 */
function createNotification($user_id, $type, $message, $link = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type, $message, $link]);
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}
