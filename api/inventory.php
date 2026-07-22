<?php
/**
 * API: Fetch and update food inventory stock details
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Route guard
if (!is_admin_logged_in()) {
    json_response('error', 'Authentication required. Admin/Chef only.', [], 401);
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

        $food_id = isset($input['food_id']) ? (int)$input['food_id'] : 0;
        $current_stock = isset($input['current_stock']) ? (int)$input['current_stock'] : 0;
        $status = isset($input['status']) ? trim($input['status']) : '';

        $valid_statuses = ['available', 'unavailable', 'out_of_stock'];

        if ($food_id <= 0 || !in_array($status, $valid_statuses)) {
            json_response('error', 'Invalid inventory arguments.');
        }

        // Lock transaction
        $pdo->beginTransaction();

        // Update inventory record
        $stmt = $pdo->prepare("INSERT INTO inventory (food_id, current_stock, status) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE current_stock = VALUES(current_stock), status = VALUES(status)");
        $stmt->execute([$food_id, $current_stock, $status]);

        // Also sync stock_status to foods table
        $food_update = $pdo->prepare("UPDATE foods SET stock_status = ? WHERE id = ?");
        $food_update->execute([$status, $food_id]);

        $pdo->commit();

        // Fetch food name for logger details
        $name_stmt = $pdo->prepare("SELECT name FROM foods WHERE id = ?");
        $name_stmt->execute([$food_id]);
        $food_name = $name_stmt->fetchColumn() ?: "Unknown Food";

        log_activity($pdo, 'Update Inventory', "Adjusted stock for '{$food_name}' to {$current_stock} ({$status})");

        json_response('success', 'Inventory updated successfully.', [
            'food_id' => $food_id,
            'current_stock' => $current_stock,
            'status' => $status
        ]);

    } else {
        // GET Request: Retrieve inventory list
        $stmt = $pdo->prepare("SELECT f.id as food_id, f.name, f.price, f.veg_nonveg, c.name as category_name, 
                                      i.current_stock, COALESCE(i.status, f.stock_status) as stock_status, i.last_restocked 
                               FROM foods f 
                               LEFT JOIN categories c ON f.category_id = c.id
                               LEFT JOIN inventory i ON f.id = i.food_id 
                               ORDER BY c.sort_order ASC, f.name ASC");
        $stmt->execute();
        $inventory = $stmt->fetchAll();

        json_response('success', 'Inventory details retrieved successfully', ['inventory' => $inventory]);
    }

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response('error', 'Inventory operation failed: ' . $e->getMessage(), [], 500);
}
?>
