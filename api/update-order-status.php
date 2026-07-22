<?php
/**
 * API: Update Order Status (Admin/Chef view)
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Route guards
if (!is_admin_logged_in()) {
    json_response('error', 'Authentication required. Admin/Chef only.', [], 401);
}

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

$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$status = isset($input['status']) ? trim($input['status']) : '';

$valid_statuses = ['received', 'confirmed', 'preparing', 'ready', 'out_of_delivery', 'delivered', 'cancelled'];

if ($order_id <= 0 || !in_array($status, $valid_statuses)) {
    json_response('error', 'Invalid parameters provided.');
}

try {
    $pdo->beginTransaction();

    // Fetch order details with employee reference
    $order_stmt = $pdo->prepare("SELECT order_number, employee_id, status FROM orders WHERE id = ? FOR UPDATE");
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        json_response('error', 'Order not found.');
    }

    if ($order['status'] === $status) {
        $pdo->rollBack();
        json_response('success', 'Order status is already set to ' . $status);
    }

    // Update status
    $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update_stmt->execute([$status, $order_id]);

    // Handle inventory refund if order is cancelled
    if ($status === 'cancelled') {
        // Fetch items to refund stock
        $items_stmt = $pdo->prepare("SELECT food_id, quantity FROM order_items WHERE order_id = ?");
        $items_stmt->execute([$order_id]);
        $items = $items_stmt->fetchAll();

        $refund_stock_stmt = $pdo->prepare("UPDATE inventory SET current_stock = current_stock + ? WHERE food_id = ?");
        foreach ($items as $item) {
            $refund_stock_stmt->execute([$item['quantity'], $item['food_id']]);
            
            // Re-mark available if stock went positive
            $check_stock = $pdo->prepare("UPDATE inventory SET status = 'available' WHERE food_id = ? AND current_stock > 0");
            $check_stock->execute([$item['food_id']]);
            
            $check_food = $pdo->prepare("UPDATE foods SET stock_status = 'available' WHERE id = ?");
            $check_food->execute([$item['food_id']]);
        }
    }

    // Format human-friendly notification messages
    $status_messages = [
        'confirmed' => "Your order {$order['order_number']} has been confirmed by the canteen.",
        'preparing' => "Your order {$order['order_number']} is being prepared by Chef.",
        'ready' => "Your order {$order['order_number']} is ready! Pick it up or prepare for delivery.",
        'out_of_delivery' => "Your order {$order['order_number']} is out for delivery to your cabin/desk.",
        'delivered' => "Your order {$order['order_number']} has been delivered. Enjoy your meal!",
        'cancelled' => "Your order {$order['order_number']} has been cancelled."
    ];

    $notification_msg = isset($status_messages[$status]) ? $status_messages[$status] : "Order {$order['order_number']} status updated to {$status}.";
    
    // Notify employee
    create_notification($pdo, $order['employee_id'], 'employee', $notification_msg);

    // Commit Transaction
    $pdo->commit();

    // Log admin activity
    log_activity($pdo, 'Update Order Status', "Changed order {$order['order_number']} status from '{$order['status']}' to '{$status}'");

    json_response('success', 'Order status updated successfully.', ['new_status' => $status]);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response('error', 'Failed to update order status: ' . $e->getMessage(), [], 500);
}
?>
