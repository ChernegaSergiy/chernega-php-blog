<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$documentRoot = __DIR__;
$requestedPath = realpath($documentRoot . $uri);
$pathInsideRoot = false !== $requestedPath && 0 === strpos($requestedPath, $documentRoot);

if ($uri !== '/' && $pathInsideRoot && is_file($requestedPath)) {
    return false;
}

if ($uri !== '/' && $pathInsideRoot && is_dir($requestedPath)) {
    $indexFile = $requestedPath . DIRECTORY_SEPARATOR . 'index.php';
    if (file_exists($indexFile)) {
        require $indexFile;
        return true;
    }

    return false;
}

$slug = trim($uri, '/');
$hasExtension = (bool) preg_match('/\.[^\/]+$/', $slug);

$reserved = [
    '',
    'index',
    'index.php',
    'post',
    'post.php',
    'post_slug',
    'post_slug.php',
    'posts',
    'posts.php',
    'admin',
    'about',
    'about.php',
    'contact',
    'contact.php',
    'assets',
    'media',
    'uploads',
    'vendor',
];

if ('' !== $slug && ! $hasExtension && ! in_array($slug, $reserved, true)) {
    $_GET['slug'] = $slug;
    require __DIR__ . '/post_slug.php';
    return true;
}

require __DIR__ . '/index.php';
return true;
