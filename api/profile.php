<?php
/**
 * API: Get and update active profile details (Employee or Admin)
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Auth check
if (!is_employee_logged_in() && !is_admin_logged_in()) {
    json_response('error', 'Authentication required.', [], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Read raw body if JSON request, else use $_POST
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($content_type, 'application/json') !== false) {
            $raw_input = file_get_contents('php://input');
            $input = json_decode($raw_input, true);
        } else {
            $input = $_POST;
        }

        // Validate CSRF token
        $csrf_token = isset($input['csrf_token']) ? $input['csrf_token'] : '';
        if (!validate_csrf_token($csrf_token)) {
            json_response('error', 'Security check failed. Invalid CSRF token.');
        }

        if (is_employee_logged_in()) {
            // Update employee profile
            $id = $_SESSION['employee_db_id'];
            $name = isset($input['name']) ? trim($input['name']) : '';
            $phone = isset($input['phone']) ? trim($input['phone']) : '';
            $email = isset($input['email']) ? trim($input['email']) : '';
            $floor = isset($input['floor']) ? (int)$input['floor'] : 1;
            $cabin = isset($input['cabin']) ? trim($input['cabin']) : '';
            $desk = isset($input['desk_number']) ? trim($input['desk_number']) : '';
            
            $curr_pass = isset($input['current_password']) ? trim($input['current_password']) : '';
            $new_pass = isset($input['new_password']) ? trim($input['new_password']) : '';

            if (empty($name) || empty($phone) || empty($email)) {
                json_response('error', 'Name, phone, and email are required fields.');
            }

            // Verify email uniqueness (except current employee)
            $check_stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $id]);
            if ($check_stmt->fetch()) {
                json_response('error', 'This email is already in use by another employee.');
            }

            // Check if password change is requested
            $pass_update_query = "";
            $pass_params = [];
            if (!empty($new_pass)) {
                if (empty($curr_pass)) {
                    json_response('error', 'Current password is required to change password.');
                }
                
                // Fetch existing password hash
                $hash_stmt = $pdo->prepare("SELECT password_hash FROM employees WHERE id = ?");
                $hash_stmt->execute([$id]);
                $old_hash = $hash_stmt->fetchColumn();

                if (!password_verify($curr_pass, $old_hash)) {
                    json_response('error', 'Current password verification failed.');
                }

                $pass_update_query = ", password_hash = ?";
                $pass_params[] = password_hash($new_pass, PASSWORD_DEFAULT);
            }

            $update_sql = "UPDATE employees SET name = ?, phone = ?, email = ?, floor = ?, cabin = ?, desk_number = ? {$pass_update_query} WHERE id = ?";
            $update_params = array_merge([$name, $phone, $email, $floor, $cabin, $desk], $pass_params, [$id]);

            $stmt = $pdo->prepare($update_sql);
            $stmt->execute($update_params);

            // Sync session name
            $_SESSION['employee_name'] = $name;

            log_activity($pdo, 'Update Profile', 'Employee updated profile details');
            json_response('success', 'Profile updated successfully.');

        } else {
            // Update admin profile
            $id = $_SESSION['admin_id'];
            $name = isset($input['name']) ? trim($input['name']) : '';
            $email = isset($input['email']) ? trim($input['email']) : '';
            
            $curr_pass = isset($input['current_password']) ? trim($input['current_password']) : '';
            $new_pass = isset($input['new_password']) ? trim($input['new_password']) : '';

            if (empty($name) || empty($email)) {
                json_response('error', 'Name and email are required fields.');
            }

            $check_stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $id]);
            if ($check_stmt->fetch()) {
                json_response('error', 'This email is already in use.');
            }

            $pass_update_query = "";
            $pass_params = [];
            if (!empty($new_pass)) {
                if (empty($curr_pass)) {
                    json_response('error', 'Current password is required to change password.');
                }
                
                $hash_stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
                $hash_stmt->execute([$id]);
                $old_hash = $hash_stmt->fetchColumn();

                if (!password_verify($curr_pass, $old_hash)) {
                    json_response('error', 'Current password verification failed.');
                }

                $pass_update_query = ", password_hash = ?";
                $pass_params[] = password_hash($new_pass, PASSWORD_DEFAULT);
            }

            $update_sql = "UPDATE admins SET name = ?, email = ? {$pass_update_query} WHERE id = ?";
            $update_params = array_merge([$name, $email], $pass_params, [$id]);

            $stmt = $pdo->prepare($update_sql);
            $stmt->execute($update_params);

            $_SESSION['admin_name'] = $name;

            log_activity($pdo, 'Update Profile', 'Admin updated profile details');
            json_response('success', 'Console profile updated successfully.');
        }

    } else {
        // GET Request: Retrieve active profile details
        if (is_employee_logged_in()) {
            $stmt = $pdo->prepare("SELECT id, employee_id, name, department, phone, email, floor, cabin, desk_number, wallet_balance, created_at FROM employees WHERE id = ?");
            $stmt->execute([$_SESSION['employee_db_id']]);
            $profile = $stmt->fetch();
            json_response('success', 'Employee profile retrieved', ['profile' => $profile]);
        } else {
            $stmt = $pdo->prepare("SELECT id, username, name, email, role, status, created_at FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $profile = $stmt->fetch();
            json_response('success', 'Console profile retrieved', ['profile' => $profile]);
        }
    }

} catch (\Exception $e) {
    json_response('error', 'Profile operation failed: ' . $e->getMessage(), [], 500);
}
?>
