<?php
/**
 * Vercel PHP front controller.
 * Routes non-API requests to the traditional multi-page PHP app files.
 */
$webRoot = dirname(__DIR__);

if (isset($_GET['__path'])) {
    $uriPath = '/' . ltrim((string) $_GET['__path'], '/');
    unset($_GET['__path']);
    // Keep query string usable by target scripts.
    if (isset($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $qs);
        unset($qs['__path']);
        $_SERVER['QUERY_STRING'] = http_build_query($qs);
        foreach ($qs as $key => $value) {
            $_GET[$key] = $value;
        }
    }
} else {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uriPath = rawurldecode(is_string($uriPath) ? $uriPath : '/');
}

if ($uriPath === '/api/index.php' || $uriPath === '/api' || $uriPath === '/api/') {
    $uriPath = '/';
}

if ($uriPath === '/' || $uriPath === '') {
    $relative = 'index.php';
} else {
    $relative = ltrim($uriPath, '/');
}

// Prevent path traversal outside the web root.
$candidate = $webRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

if (is_dir($candidate)) {
    $candidate = rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
}

$realRoot = realpath($webRoot);
$realFile = realpath($candidate);

if ($realRoot === false || $realFile === false || strpos($realFile, $realRoot) !== 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
}

if (!is_file($realFile) || strtolower(pathinfo($realFile, PATHINFO_EXTENSION)) !== 'php') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
}

// Match Apache/XAMPP behavior for relative includes (../dB/config.php, etc.).
chdir(dirname($realFile));
require $realFile;
