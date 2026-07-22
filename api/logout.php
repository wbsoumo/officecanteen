<?php
/**
 * API: Session Logout
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Capture logout type for redirection
$redirect = '/employee/login.php';
if (is_admin_logged_in()) {
    log_activity($pdo, 'Logout', 'Admin logged out');
    $redirect = '/admin/login.php';
} else if (is_employee_logged_in()) {
    log_activity($pdo, 'Logout', 'Employee logged out');
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect user
header("Location: " . $redirect);
exit;
