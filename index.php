<?php
/**
 * COPRRA - Smart Root Router
 * This file sits at the web root and intelligently routes requests
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove query string and normalize
$requestPath = strtok($requestPath, '?');

// Define public directory path
$publicDir = __DIR__ . '/public';

// Check if this is a request for a static file
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'webp', 'woff', 'woff2', 'ttf', 'eot', 'json', 'map', 'txt', 'xml', 'pdf'];
$pathInfo = pathinfo($requestPath);
$extension = strtolower($pathInfo['extension'] ?? '');

if (in_array($extension, $staticExtensions)) {
    // Build file path in public directory
    $filePath = $publicDir . $requestPath;
    
    if (file_exists($filePath) && is_file($filePath) && is_readable($filePath)) {
        // Determine MIME type
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'map' => 'application/json',
            'txt' => 'text/plain',
            'xml' => 'application/xml',
            'pdf' => 'application/pdf',
        ];
        
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send headers
        http_response_code(200);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000');
        header('Access-Control-Allow-Origin: *');
        
        // Send file
        readfile($filePath);
        exit(0);
    }
}

// Not a static file or file doesn't exist - pass to Laravel
// Change to public directory and include Laravel's index.php
chdir($publicDir);
require $publicDir . '/index.php';
