<?php
// Script to programmatically install WordPress
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['SERVER_PORT'] = '8000';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('WP_INSTALLING', true);
require_once __DIR__ . '/wordpress/wp-load.php';
require_once __DIR__ . '/wordpress/wp-admin/includes/upgrade.php';
require_once __DIR__ . '/wordpress/wp-includes/pluggable.php';

echo "Installing WordPress tables...\n";
$site_title = 'Умный Шеф: Фото холодильника & План питания';
$admin_user = 'admin';
$admin_pass = 'admin12345';
$admin_email = 'admin@example.com';
$is_public = true;

$result = wp_install( $site_title, $admin_user, $admin_email, $is_public, '', $admin_pass, 'ru_RU' );
echo "Installation result:\n";
print_r($result);

// Set siteurl and home
update_option('siteurl', 'http://localhost:8000');
update_option('home', 'http://localhost:8000');
update_option('blogdescription', 'Умный помощник по холодильнику, меню на неделю и поиску магазинов');

echo "\nWordPress installed successfully!\n";
echo "Admin user: $admin_user\n";
echo "Admin password: $admin_pass\n";
echo "URL: http://localhost:8000\n";
