<?php
/**
 * Authentication and Security Helper Library
 */

// Ensure database connection is present first
require_once dirname(__DIR__) . '/config/db.php';

// Custom database session handler for serverless (Vercel) deployments
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        try {
            $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetchColumn();
            return $data ? $data : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    public function write($id, $data): bool {
        try {
            $access = time();
            $stmt = $this->db->prepare("REPLACE INTO sessions (id, data, access) VALUES (?, ?, ?)");
            return $stmt->execute([$id, $data, $access]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function destroy($id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function gc($maxlifetime): int|false {
        try {
            $old = time() - $maxlifetime;
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE access < ?");
            $stmt->execute([$old]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (session_status() == PHP_SESSION_NONE) {
    $handler = new DatabaseSessionHandler($pdo);
    session_set_save_handler($handler, true);
    
    // Serverless Session Optimization: bypass database write lock for read-only scripts
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $is_write_session = (strpos($script, 'login.php') !== false || strpos($script, 'logout.php') !== false || strpos($script, 'profile.php') !== false);
    
    if ($is_write_session) {
        session_start();
    } else {
        session_start(['read_and_close' => true]);
    }
}

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
