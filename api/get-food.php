<?php
/**
 * API: Fetch Single Food Item Details
 */
require_once dirname(__DIR__) . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    json_response('error', 'Invalid food ID parameter.');
}

try {
    $stmt = $pdo->prepare("SELECT f.*, c.name as category_name, i.current_stock 
                           FROM foods f 
                           LEFT JOIN categories c ON f.category_id = c.id
                           LEFT JOIN inventory i ON f.id = i.food_id
                           WHERE f.id = ?");
    $stmt->execute([$id]);
    $food = $stmt->fetch();

    if (!$food) {
        json_response('error', 'Food item not found.');
    }

    // Fetch supporting images if any
    $img_stmt = $pdo->prepare("SELECT image_url FROM food_images WHERE food_id = ? ORDER BY is_primary DESC");
    $img_stmt->execute([$id]);
    $images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($images) && !empty($food['image_url'])) {
        $images = [$food['image_url']];
    }

    json_response('success', 'Food details retrieved successfully', [
        'food' => $food,
        'images' => $images
    ]);

} catch (\Exception $e) {
    json_response('error', 'Failed to retrieve food item details: ' . $e->getMessage(), [], 500);
}
