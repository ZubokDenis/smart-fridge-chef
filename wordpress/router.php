<?php
/**
 * Built-in PHP server router for WordPress.
 */
$root = __DIR__;
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = $root . $uri;

if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false; // Serve file as-is
}

if ($uri !== '/' && is_dir($filePath) && file_exists($filePath . '/index.php')) {
    include $filePath . '/index.php';
    return;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
include $root . '/index.php';
