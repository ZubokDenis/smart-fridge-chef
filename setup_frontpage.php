<?php
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

require_once __DIR__ . '/wordpress/wp-load.php';

// Check if page already exists
$page = get_page_by_path('fridge-chef');
if ( ! $page ) {
    $page_id = wp_insert_post([
        'post_title'   => 'Умный Шеф: Фото Холодильника & План Питания',
        'post_name'    => 'fridge-chef',
        'post_content' => '<!-- wp:shortcode -->[smart_fridge_chef]<!-- /wp:shortcode -->',
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ]);
    echo "Created front page with ID: $page_id\n";
} else {
    $page_id = $page->ID;
    wp_update_post([
        'ID'           => $page_id,
        'post_content' => '<!-- wp:shortcode -->[smart_fridge_chef]<!-- /wp:shortcode -->',
    ]);
    echo "Updated front page with ID: $page_id\n";
}

// Set as front page
update_option('show_on_front', 'page');
update_option('page_on_front', $page_id);

echo "Homepage successfully set to Smart Fridge Chef app!\n";
