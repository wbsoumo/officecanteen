<?php
/**
 * API: Fetch and Update site settings (Admin configuration)
 */
require_once dirname(__DIR__) . '/includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Update requires admin
        if (!is_admin_logged_in(['admin'])) {
            json_response('error', 'Unauthorized. Settings can only be updated by Admins.', [], 403);
        }

        // Read raw body if JSON request, else use $_POST
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($content_type, 'application/json') !== false) {
            $raw_input = file_get_contents('php://input');
            $input = json_decode($raw_input, true);
        } else {
            $input = $_POST;
        }

        $allowed_keys = [
            'company_name', 'gst_rate', 'delivery_charge', 'canteen_status',
            'canteen_address', 'order_timings', 'lunch_timings', 'theme_primary', 'theme_accent'
        ];

        // Process bulk update
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($input as $key => $val) {
            if (in_array($key, $allowed_keys)) {
                $stmt->execute([$key, sanitize($val)]);
            }
        }

        log_activity($pdo, 'Update Settings', 'Global canteen parameters updated');
        json_response('success', 'Canteen configurations updated successfully.');

    } else {
        // GET Settings: Publicly fetchable (for app headers)
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        json_response('success', 'Settings retrieved successfully', ['settings' => $settings]);
    }

} catch (\Exception $e) {
    json_response('error', 'Settings operation failed: ' . $e->getMessage(), [], 500);
}
?>
