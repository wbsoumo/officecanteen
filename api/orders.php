<?php
/**
 * API: Fetch Orders List (Employee or Admin context)
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Auth validation
if (!is_employee_logged_in() && !is_admin_logged_in()) {
    json_response('error', 'Authentication required.', [], 401);
}

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $query = "";

    if (is_employee_logged_in()) {
        // Employee Context: Get self orders only
        $employee_db_id = $_SESSION['employee_db_id'];
        $query = "SELECT o.* FROM orders o WHERE o.employee_id = ?";
        $params[] = $employee_db_id;

        if (!empty($status)) {
            // Group active states or filter specific
            if ($status === 'active') {
                $query .= " AND o.status IN ('received', 'confirmed', 'preparing', 'ready', 'out_of_delivery')";
            } else {
                $query .= " AND o.status = ?";
                $params[] = $status;
            }
        }

        if (!empty($search)) {
            $query .= " AND o.order_number LIKE ?";
            $params[] = "%{$search}%";
        }

    } else {
        // Admin / Chef Context: Get all orders
        $query = "SELECT o.*, e.name as employee_name, e.employee_id as emp_code 
                  FROM orders o 
                  JOIN employees e ON o.employee_id = e.id 
                  WHERE 1=1";

        if (!empty($status)) {
            if ($status === 'active') {
                $query .= " AND o.status IN ('received', 'confirmed', 'preparing', 'ready', 'out_of_delivery')";
            } else {
                $query .= " AND o.status = ?";
                $params[] = $status;
            }
        }

        if (!empty($search)) {
            $query .= " AND (o.order_number LIKE ? OR e.name LIKE ? OR o.department LIKE ?)";
            $search_param = "%{$search}%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
    }

    // Sort order (active items first, then newest orders first)
    $query .= " ORDER BY CASE 
                WHEN o.status = 'received' THEN 1
                WHEN o.status = 'confirmed' THEN 2
                WHEN o.status = 'preparing' THEN 3
                WHEN o.status = 'ready' THEN 4
                WHEN o.status = 'out_of_delivery' THEN 5
                ELSE 6
              END ASC, o.created_at DESC";

    // Count total
    $count_query = "SELECT COUNT(*) FROM (" . $query . ") AS count_tbl";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetchColumn();
    $total_pages = ceil($total_items / $limit);

    // Apply limits
    $query .= " LIMIT {$limit} OFFSET {$offset}";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    json_response('success', 'Orders retrieved successfully', [
        'orders' => $orders,
        'pagination' => [
            'total_items' => (int)$total_items,
            'total_pages' => (int)$total_pages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);

} catch (\Exception $e) {
    json_response('error', 'Failed to retrieve orders: ' . $e->getMessage(), [], 500);
}
?>
