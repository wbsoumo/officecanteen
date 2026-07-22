<?php
/**
 * Authentication and Security Helper Library
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure database connection is present if needed
require_once dirname(__DIR__) . '/config/db.php';

/**
 * Check if an employee is logged in
 */
function is_employee_logged_in() {
    return isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true && isset($_SESSION['employee_id']);
}

/**
 * Check if an admin is logged in (handles role check optionally)
 */
function is_admin_logged_in($allowed_roles = ['admin', 'chef', 'kitchen']) {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && in_array($_SESSION['admin_role'], $allowed_roles);
}

/**
 * Require employee authentication, redirect to login page if unauthorized
 */
function require_employee() {
    if (!is_employee_logged_in()) {
        header("Location: /employee/login.php");
        exit;
    }
}

/**
 * Require admin authentication, redirect to admin login page if unauthorized
 */
function require_admin($allowed_roles = ['admin', 'chef', 'kitchen']) {
    if (!is_admin_logged_in($allowed_roles)) {
        header("Location: /admin/login.php");
        exit;
    }
}

/**
 * Sanitize user input to prevent XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get active user ID and role for logging
 */
function get_active_session_info() {
    if (is_admin_logged_in()) {
        return [
            'id' => $_SESSION['admin_id'],
            'role' => $_SESSION['admin_role'],
            'name' => $_SESSION['admin_name']
        ];
    } elseif (is_employee_logged_in()) {
        return [
            'id' => $_SESSION['employee_db_id'],
            'role' => 'employee',
            'name' => $_SESSION['employee_name']
        ];
    }
    return [
        'id' => null,
        'role' => 'guest',
        'name' => 'Guest'
    ];
}

/**
 * Log user activity into database
 */
function log_activity($pdo, $action, $details = '') {
    $session = get_active_session_info();
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_role, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$session['id'], $session['role'], $action, $details]);
    } catch (\Exception $e) {
        // Fail silently to avoid breaking execution flow for logging
    }
}

/**
 * Create a notification for a user
 */
function create_notification($pdo, $user_id, $role, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, user_role, message, status) VALUES (?, ?, ?, 'unread')");
        $stmt->execute([$user_id, $role, $message]);
    } catch (\Exception $e) {
        // Fail silently
    }
}

/**
 * Helper to respond with JSON format
 */
function json_response($status, $message, $data = [], $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit;
}
