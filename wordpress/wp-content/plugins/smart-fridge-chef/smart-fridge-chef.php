<?php
/**
 * Plugin Name: Умный Шеф: Холодильник, Меню и Магазины
 * Description: Загрузите фото холодильника или список продуктов: система определит ингредиенты, составит рецепты, сформирует меню на 7 дней, создаст список покупок и порекомендует ближайшие супермаркеты через ИИ (Ollama / Claude).
 * Version: 2.0.0
 * Author: Antigravity AI
 * Text Domain: smart-fridge-chef
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SFC_VERSION', '2.0.0' );
define( 'SFC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SFC_URL', plugin_dir_url( __FILE__ ) );

// Require classes
require_once SFC_PATH . 'includes/class-post-types.php';
require_once SFC_PATH . 'includes/class-ai-engine.php';
require_once SFC_PATH . 'includes/class-store-locator.php';
require_once SFC_PATH . 'includes/class-api-controller.php';
require_once SFC_PATH . 'includes/class-admin-settings.php';

// Initialize
add_action( 'plugins_loaded', 'sfc_init_plugin' );
function sfc_init_plugin() {
    SFC_Post_Types::init();
    SFC_API_Controller::init();
    SFC_Admin_Settings::init();
}

// Register frontend assets
add_action( 'wp_enqueue_scripts', 'sfc_enqueue_assets' );
function sfc_enqueue_assets() {
    // Google Fonts: Inter & Roboto
    wp_enqueue_style( 'google-font-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap', [], null );

    // Google Material Symbols Outlined
    wp_enqueue_style( 'google-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0', [], null );

    // Leaflet CSS and JS for interactive store map
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );

    // Custom App CSS
    wp_enqueue_style( 'sfc-app-css', SFC_URL . 'assets/css/app.css', [ 'google-material-symbols', 'leaflet-css' ], SFC_VERSION );

    // Custom App JS
    wp_enqueue_script( 'sfc-app-js', SFC_URL . 'assets/js/app.js', [ 'leaflet-js' ], SFC_VERSION, true );

    $ai_config = SFC_AI_Engine::get_ai_config();
    $provider = $ai_config['provider'];
    $ai_label = 'Ollama Cloud';
    if ( $provider === 'ollama_cloud' || $provider === 'ollama' ) $ai_label = 'Ollama Cloud (' . $ai_config['ollama_model'] . ')';
    if ( $provider === 'claude' ) $ai_label = 'Anthropic Claude (' . $ai_config['claude_model'] . ')';
    if ( $provider === 'gemini' ) $ai_label = 'Google Gemini (' . $ai_config['gemini_model'] . ')';
    if ( $provider === 'builtin' ) $ai_label = 'Встроенный экспертный интеллект';

    wp_localize_script( 'sfc-app-js', 'sfcConfig', [
        'restUrl'      => esc_url_raw( rest_url( 'fridge-chef/v1/' ) ),
        'nonce'        => wp_create_nonce( 'wp_rest' ),
        'defaultCity'  => get_option( 'sfc_default_city', 'Москва' ),
        'defaultLat'   => floatval( get_option( 'sfc_default_lat', 55.7558 ) ),
        'defaultLon'   => floatval( get_option( 'sfc_default_lon', 37.6173 ) ),
        'siteUrl'      => home_url(),
        'aiProvider'   => $provider,
        'aiLabel'      => $ai_label,
        'adminUrl'     => admin_url( 'admin.php?page=smart-fridge-chef' ),
    ]);
}

// Shortcode: [smart_fridge_chef]
add_shortcode( 'smart_fridge_chef', 'sfc_render_shortcode' );
function sfc_render_shortcode() {
    ob_start();
    include SFC_PATH . 'templates/app-interface.php';
    return ob_get_clean();
}
