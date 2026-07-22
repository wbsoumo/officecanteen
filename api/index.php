<?php
/**
 * Vercel Front Controller Router
 */

// Disable direct execution loop
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize root path
if ($path === '/' || $path === '') {
    $path = '/index.php';
}

if ($path === '/api/index.php') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$file = dirname(__DIR__) . $path;

if (file_exists($file) && is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    
    if ($ext === 'php') {
        // Change working directory to mimic standard server context
        chdir(dirname($file));
        require_once $file;
        exit;
    } else {
        // Serve static file helper (backup in case routing falls through)
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'json' => 'application/json'
        ];
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        readfile($file);
        exit;
    }
}

// Return 404 if file does not exist
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($path);
exit;
