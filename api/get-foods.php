<?php
/**
 * API: Fetch Menu Foods list (with filters and search parameters)
 */
require_once dirname(__DIR__) . '/includes/auth.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$veg_nonveg = isset($_GET['veg_nonveg']) ? trim($_GET['veg_nonveg']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$featured = isset($_GET['featured']) ? (int)$_GET['featured'] : 0;
$popular = isset($_GET['popular']) ? (int)$_GET['popular'] : 0;

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
$offset = ($page - 1) * $limit;

try {
    $query = "SELECT f.*, c.name as category_name, i.current_stock 
              FROM foods f 
              LEFT JOIN categories c ON f.category_id = c.id
              LEFT JOIN inventory i ON f.id = i.food_id
              WHERE 1=1";
    $params = [];

    if ($category_id > 0) {
        $query .= " AND f.category_id = ?";
        $params[] = $category_id;
    }

    if ($veg_nonveg === 'veg' || $veg_nonveg === 'nonveg') {
        $query .= " AND f.veg_nonveg = ?";
        $params[] = $veg_nonveg;
    }

    if ($featured > 0) {
        $query .= " AND f.is_featured = 1";
    }

    if ($popular > 0) {
        $query .= " AND f.is_popular = 1";
    }

    if (!empty($search)) {
        $query .= " AND (f.name LIKE ? OR f.description LIKE ? OR f.ingredients LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Append Ordering
    $query .= " ORDER BY f.is_featured DESC, f.name ASC";

    // Count Total (for pagination details)
    $count_query = "SELECT COUNT(*) FROM (" . $query . ") AS count_tbl";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetchColumn();
    $total_pages = ceil($total_items / $limit);

    // Append limit and offset
    $query .= " LIMIT {$limit} OFFSET {$offset}";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $foods = $stmt->fetchAll();

    // Fetch categories for unified loading
    $cat_stmt = $pdo->query("SELECT * FROM categories WHERE visibility = 1 ORDER BY sort_order ASC, name ASC");
    $categories = $cat_stmt->fetchAll();

    json_response('success', 'Foods retrieved successfully', [
        'foods' => $foods,
        'categories' => $categories,
        'pagination' => [
            'total_items' => (int)$total_items,
            'total_pages' => (int)$total_pages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);

} catch (\Exception $e) {
    json_response('error', 'Failed to retrieve foods list: ' . $e->getMessage(), [], 500);
}
?>
