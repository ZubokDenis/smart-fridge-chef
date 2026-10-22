<?php
/**
 * Plugin Name: Умный Шеф: Холодильник, Меню и Магазины
 * Description: Загрузите фото холодильника или список продуктов: система определит ингредиенты, составит рецепты, сформирует меню на 7 дней, создаст список покупок и порекомендует ближайшие супермаркеты.
 * Version: 2.0.0
 * Author: Denis
 * Text Domain: smart-fridge-chef
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SFC_VERSION', '2.0.0' );
define( 'SFC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SFC_URL', plugin_dir_url( __FILE__ ) );

require_once SFC_PATH . 'includes/class-post-types.php';
require_once SFC_PATH . 'includes/class-image-handler.php';
require_once SFC_PATH . 'includes/class-ai-engine.php';
require_once SFC_PATH . 'includes/class-store-locator.php';
require_once SFC_PATH . 'includes/class-api-controller.php';
require_once SFC_PATH . 'includes/class-admin-settings.php';

add_action( 'plugins_loaded', 'sfc_init_plugin' );
function sfc_init_plugin() {
    SFC_Post_Types::init();
    SFC_API_Controller::init();
    SFC_Admin_Settings::init();
}

add_action( 'init', 'sfc_disable_wp_emojis' );
function sfc_disable_wp_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}

add_action( 'wp_enqueue_scripts', 'sfc_enqueue_assets' );
function sfc_enqueue_assets() {
    wp_enqueue_style( 'sfc-google-fonts', 'https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap', [], null );
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );

    $css_ver = file_exists( SFC_PATH . 'assets/css/app.css' ) ? filemtime( SFC_PATH . 'assets/css/app.css' ) : time();
    $js_ver  = file_exists( SFC_PATH . 'assets/js/app.js' ) ? filemtime( SFC_PATH . 'assets/js/app.js' ) : time();

    wp_enqueue_style( 'sfc-app-css', SFC_URL . 'assets/css/app.css', [ 'sfc-google-fonts', 'leaflet-css' ], $css_ver );
    wp_enqueue_script( 'sfc-app-js', SFC_URL . 'assets/js/app.js', [ 'leaflet-js' ], $js_ver, true );

    $ai_config = SFC_AI_Engine::get_ai_config();
    $provider = $ai_config['provider'];
    $ai_label = 'Ollama Cloud';
    if ( $provider === 'ollama_cloud' || $provider === 'ollama' ) $ai_label = 'Ollama Cloud (' . $ai_config['ollama_model'] . ')';
    if ( $provider === 'claude' ) $ai_label = 'Anthropic Claude (' . $ai_config['claude_model'] . ')';
    if ( $provider === 'gemini' ) $ai_label = 'Google Gemini (' . $ai_config['gemini_model'] . ')';
    if ( $provider === 'builtin' ) $ai_label = 'Экспертный кулинарный движок';

    $stored_city = get_option( 'sfc_default_city', '' );
    $stored_lat  = get_option( 'sfc_default_lat', '' );
    $stored_lon  = get_option( 'sfc_default_lon', '' );

    wp_localize_script( 'sfc-app-js', 'sfcConfig', [
        'restUrl'      => esc_url_raw( rest_url( 'fridge-chef/v1/' ) ),
        'nonce'        => wp_create_nonce( 'wp_rest' ),
        'defaultCity'  => ! empty( $stored_city ) ? $stored_city : '',
        'defaultLat'   => ! empty( $stored_lat ) ? floatval( $stored_lat ) : null,
        'defaultLon'   => ! empty( $stored_lon ) ? floatval( $stored_lon ) : null,
        'siteUrl'      => home_url(),
        'aiProvider'   => $provider,
        'aiLabel'      => $ai_label,
        'adminUrl'     => admin_url( 'admin.php?page=smart-fridge-chef' ),
    ]);
}

add_shortcode( 'smart_fridge_chef', 'sfc_render_shortcode' );
function sfc_render_shortcode() {
    ob_start();
    include SFC_PATH . 'templates/app-interface.php';
    return ob_get_clean();
}
