<?php
/**
 * API: Fetch Order details with items
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Auth validation
if (!is_employee_logged_in() && !is_admin_logged_in()) {
    json_response('error', 'Authentication required.', [], 401);
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    json_response('error', 'Invalid order ID parameter.');
}

try {
    // Fetch base order details
    $order_stmt = $pdo->prepare("SELECT o.*, e.name as employee_name, e.employee_id as emp_code, e.phone as employee_phone, e.email as employee_email 
                                 FROM orders o
                                 JOIN employees e ON o.employee_id = e.id
                                 WHERE o.id = ?");
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch();

    if (!$order) {
        json_response('error', 'Order not found.');
    }

    // Access control check for employee
    if (is_employee_logged_in() && $order['employee_id'] != $_SESSION['employee_db_id']) {
        json_response('error', 'Access denied. You do not own this order.', [], 403);
    }

    // Fetch items ordered
    $items_stmt = $pdo->prepare("SELECT oi.*, f.image_url, f.veg_nonveg 
                                 FROM order_items oi
                                 JOIN foods f ON oi.food_id = f.id
                                 WHERE oi.order_id = ?");
    $items_stmt->execute([$order_id]);
    $items = $items_stmt->fetchAll();

    json_response('success', 'Order details retrieved successfully', [
        'order' => $order,
        'items' => $items
    ]);

} catch (\Exception $e) {
    json_response('error', 'Failed to retrieve order details: ' . $e->getMessage(), [], 500);
}
