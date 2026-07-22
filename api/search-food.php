<?php
/**
 * API: Food Search Autocomplete Suggestions
 */
require_once dirname(__DIR__) . '/includes/auth.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    json_response('success', 'Query too short', ['suggestions' => []]);
}

try {
    $stmt = $pdo->prepare("SELECT id, name, price, veg_nonveg, image_url 
                           FROM foods 
                           WHERE name LIKE ? OR description LIKE ? 
                           LIMIT 8");
    $search = "%{$q}%";
    $stmt->execute([$search, $search]);
    $suggestions = $stmt->fetchAll();

    json_response('success', 'Suggestions retrieved', ['suggestions' => $suggestions]);
} catch (\Exception $e) {
    json_response('error', 'Search failed: ' . $e->getMessage(), [], 500);
}
