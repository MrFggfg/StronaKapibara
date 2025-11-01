<?php
// Simple front controller - for now redirect to pages/gallery.php or index.html
$request = $_SERVER['REQUEST_URI'];
$script = basename(parse_url($request, PHP_URL_PATH));

// If no specific page requested, show gallery
if ($script === '' || $script === 'index.php' || $script === '/') {
    header('Location: pages/gallery.php');
    exit;
}

// Fallback: try to include the file under pages
$path = __DIR__ . '/pages/' . $script;
if (file_exists($path)) {
    include $path;
    exit;
}

// 404
http_response_code(404);
echo "404 Not Found";
