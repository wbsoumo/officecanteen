<?php
/**
 * API: Handle Login for Employee and Admin
 */
require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response('error', 'Invalid request method. Only POST is allowed.', [], 405);
}

// Read raw body if JSON request, else use $_POST
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($content_type, 'application/json') !== false) {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
} else {
    $input = $_POST;
}

$login_type = isset($input['login_type']) ? trim($input['login_type']) : 'employee';
$password = isset($input['password']) ? trim($input['password']) : '';

if ($login_type === 'admin') {
    $username = isset($input['username']) ? trim($input['username']) : '';
    
    if (empty($username) || empty($password)) {
        json_response('error', 'Username and password are required.');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Success: Set Admin Sessions
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role']; // admin, chef, kitchen
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Log activity
            log_activity($pdo, 'Login', 'Admin portal login successful');

            // Set redirect path based on role
            $redirect = '/admin/dashboard.php';
            if ($admin['role'] === 'chef') {
                $redirect = '/admin/chef.php';
            } elseif ($admin['role'] === 'kitchen') {
                $redirect = '/admin/chef.php';
            }

            json_response('success', 'Admin login successful', ['redirect' => $redirect]);
        } else {
            json_response('error', 'Invalid username or password.');
        }
    } catch (\Exception $e) {
        json_response('error', 'Login failed: ' . $e->getMessage(), [], 500);
    }

} else {
    // Employee Login
    $employee_id = isset($input['employee_id']) ? trim($input['employee_id']) : '';

    if (empty($employee_id) || empty($password)) {
        json_response('error', 'Employee ID and password are required.');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ? AND status = 'active'");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();

        if ($employee && password_verify($password, $employee['password_hash'])) {
            // Success: Set Employee Sessions
            $_SESSION['employee_logged_in'] = true;
            $_SESSION['employee_db_id'] = $employee['id'];
            $_SESSION['employee_id'] = $employee['employee_id'];
            $_SESSION['employee_name'] = $employee['name'];
            $_SESSION['employee_dept'] = $employee['department'];
            $_SESSION['wallet_balance'] = $employee['wallet_balance'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Log activity
            log_activity($pdo, 'Login', 'Employee dashboard login successful');

            json_response('success', 'Employee login successful', ['redirect' => '/employee/dashboard.php']);
        } else {
            json_response('error', 'Invalid Employee ID or password.');
        }
    } catch (\Exception $e) {
        json_response('error', 'Login failed: ' . $e->getMessage(), [], 500);
    }
}
