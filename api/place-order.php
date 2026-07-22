<?php
/**
 * API: Place Food Order
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Route guard
if (!is_employee_logged_in()) {
    json_response('error', 'Authentication required. Please login as employee.', [], 401);
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

// CSRF validation
$csrf_token = isset($input['csrf_token']) ? $input['csrf_token'] : '';
if (!validate_csrf_token($csrf_token)) {
    json_response('error', 'Security check failed. Invalid CSRF token.');
}

$employee_id = $_SESSION['employee_db_id'];
$department = $_SESSION['employee_dept'];

$floor = isset($input['floor']) ? (int)$input['floor'] : 0;
$cabin = isset($input['cabin']) ? trim($input['cabin']) : null;
$desk_number = isset($input['desk_number']) ? trim($input['desk_number']) : null;
$delivery_date = isset($input['delivery_date']) ? trim($input['delivery_date']) : '';
$delivery_time = isset($input['delivery_time']) ? trim($input['delivery_time']) : '';
$special_instructions = isset($input['special_instructions']) ? trim($input['special_instructions']) : null;
$payment_method = 'cash_on_delivery'; // Fixed COD
$is_agreed = isset($input['is_agreed']) ? (int)$input['is_agreed'] : 0;
$items = isset($input['items']) ? $input['items'] : [];

// Basic validations
if ($floor <= 0) {
    json_response('error', 'Please specify a valid delivery floor.');
}
if (empty($delivery_date) || empty($delivery_time)) {
    json_response('error', 'Please select a delivery date and time.');
}
if ($is_agreed !== 1) {
    json_response('error', 'You must agree to the ordering terms.');
}
if (empty($items) || !is_array($items)) {
    json_response('error', 'Your order cart is empty.');
}

try {
    // Start transactional safety block
    $pdo->beginTransaction();

    // Fetch site configurations
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $gst_rate = isset($settings['gst_rate']) ? (float)$settings['gst_rate'] : 5.0;
    
    $subtotal = 0.00;
    $order_items_to_save = [];

    // Loop items to check stock and compute totals
    foreach ($items as $item) {
        $food_id = isset($item['food_id']) ? (int)$item['food_id'] : 0;
        $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
        $notes = isset($item['special_notes']) ? trim($item['special_notes']) : null;

        if ($food_id <= 0 || $qty <= 0) {
            $pdo->rollBack();
            json_response('error', 'Invalid items configuration in cart.');
        }

        // Query food item with inventory stock lock
        $food_stmt = $pdo->prepare("SELECT f.*, i.current_stock, i.status as inv_status 
                                    FROM foods f 
                                    LEFT JOIN inventory i ON f.id = i.food_id 
                                    WHERE f.id = ? FOR UPDATE");
        $food_stmt->execute([$food_id]);
        $food = $food_stmt->fetch();

        if (!$food) {
            $pdo->rollBack();
            json_response('error', 'Food item in cart does not exist.');
        }

        if ($food['stock_status'] !== 'available' || $food['inv_status'] !== 'available') {
            $pdo->rollBack();
            json_response('error', "Sorry, '{$food['name']}' is currently unavailable.");
        }

        if ($food['current_stock'] < $qty) {
            $pdo->rollBack();
            json_response('error', "Insufficient stock for '{$food['name']}'. Only {$food['current_stock']} left.");
        }

        $item_total = $food['price'] * $qty;
        $subtotal += $item_total;

        $order_items_to_save[] = [
            'food_id' => $food['id'],
            'food_name' => $food['name'],
            'price' => $food['price'],
            'quantity' => $qty,
            'special_notes' => $notes
        ];
    }

    $gst = ($subtotal * $gst_rate) / 100;
    $grand_total = $subtotal + $gst;

    // Generate a unique order number sequential-ish format
    $order_number = 'ORD-' . date('ymd') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);

    // Save order
    $order_stmt = $pdo->prepare("INSERT INTO orders (order_number, employee_id, department, floor, cabin, desk_number, delivery_date, delivery_time, special_instructions, subtotal, gst, grand_total, status, payment_method, is_agreed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?, ?)");
    $order_stmt->execute([
        $order_number,
        $employee_id,
        $department,
        $floor,
        $cabin,
        $desk_number,
        $delivery_date,
        $delivery_time,
        $special_instructions,
        $subtotal,
        $gst,
        $grand_total,
        $payment_method,
        $is_agreed
    ]);

    $order_id = $pdo->lastInsertId();

    // Insert order items & deduct inventory stock
    $item_insert_stmt = $pdo->prepare("INSERT INTO order_items (order_id, food_id, food_name, price, quantity, special_notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stock_update_stmt = $pdo->prepare("UPDATE inventory SET current_stock = current_stock - ? WHERE food_id = ?");

    foreach ($order_items_to_save as $item) {
        $item_insert_stmt->execute([
            $order_id,
            $item['food_id'],
            $item['food_name'],
            $item['price'],
            $item['quantity'],
            $item['special_notes']
        ]);

        $stock_update_stmt->execute([
            $item['quantity'],
            $item['food_id']
        ]);

        // If stock hits 0, toggle availability status to out_of_stock
        $check_stock_stmt = $pdo->prepare("SELECT current_stock FROM inventory WHERE food_id = ?");
        $check_stock_stmt->execute([$item['food_id']]);
        $current_stock = $check_stock_stmt->fetchColumn();
        if ($current_stock <= 0) {
            $toggle_status = $pdo->prepare("UPDATE inventory SET status = 'out_of_stock' WHERE food_id = ?");
            $toggle_status->execute([$item['food_id']]);
            $toggle_food = $pdo->prepare("UPDATE foods SET stock_status = 'out_of_stock' WHERE id = ?");
            $toggle_food->execute([$item['food_id']]);
        }
    }

    // Commit Transaction
    $pdo->commit();

    // Create notifications for employee and kitchen/chef
    create_notification($pdo, $employee_id, 'employee', "Your order {$order_number} has been received successfully!");
    
    // Notify all admins and chefs
    $chefs_stmt = $pdo->query("SELECT id FROM admins WHERE role IN ('admin', 'chef')");
    while ($chef = $chefs_stmt->fetch()) {
        create_notification($pdo, $chef['id'], 'chef', "New order {$order_number} received from Floor {$floor}.");
    }

    // Log action
    log_activity($pdo, 'Place Order', "Placed order {$order_number} totaling ₹" . number_format($grand_total, 2));

    json_response('success', 'Order placed successfully.', [
        'order_id' => $order_id,
        'order_number' => $order_number
    ]);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response('error', 'Failed to place order: ' . $e->getMessage(), [], 500);
}
