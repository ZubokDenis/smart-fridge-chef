<?php
define('WP_USE_THEMES', false);
try {
    require_once __DIR__ . '/wordpress/wp-load.php';
    echo "WP Loaded successfully!\n";
    global $wpdb;
    var_dump($wpdb);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
