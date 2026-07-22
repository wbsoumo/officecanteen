<?php
/**
 * API: Fetch Food Categories
 */
require_once dirname(__DIR__) . '/includes/auth.php';

try {
    $stmt = $pdo->prepare("SELECT id, name, icon, sort_order FROM categories WHERE visibility = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    json_response('success', 'Categories retrieved successfully', ['categories' => $categories]);
} catch (\Exception $e) {
    json_response('error', 'Failed to retrieve categories: ' . $e->getMessage(), [], 500);
}
