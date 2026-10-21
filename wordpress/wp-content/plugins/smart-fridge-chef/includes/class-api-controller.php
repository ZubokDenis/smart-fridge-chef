<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFC_API_Controller {

    const NAMESPACE = 'fridge-chef/v1';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( self::NAMESPACE, '/analyze-input', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_analyze_input' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( self::NAMESPACE, '/generate-recipes', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_generate_recipes' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( self::NAMESPACE, '/generate-weekly-plan', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_generate_weekly_plan' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( self::NAMESPACE, '/find-stores', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_find_stores' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( self::NAMESPACE, '/save-meal-plan', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_save_meal_plan' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( self::NAMESPACE, '/saved-plans', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'handle_get_saved_plans' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( self::NAMESPACE, '/ai-status', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'handle_get_ai_status' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( self::NAMESPACE, '/test-ai', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_test_ai' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle_analyze_input( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $input_type = $params['type'] ?? 'text';
        $products = [];
        $image_info = null;

        if ( $input_type === 'photo' ) {
            $image_base64 = $params['image_base64'] ?? '';
            $mime_type    = $params['mime_type'] ?? 'image/jpeg';

            $image_info = SFC_Image_Handler::process_upload( $image_base64, $mime_type );

            if ( preg_match( '/^data:image\/(\w+);base64,/', $image_base64, $m ) ) {
                $mime_type = 'image/' . $m[1];
                $image_base64 = substr( $image_base64, strpos( $image_base64, ',' ) + 1 );
            }

            $products = SFC_AI_Engine::analyze_fridge_image( $image_base64, $mime_type );
        } else {
            $text = $params['text'] ?? '';
            $products = SFC_AI_Engine::parse_text_products( $text );
        }

        return rest_ensure_response([
            'success'    => true,
            'count'      => count( $products ),
            'products'   => $products,
            'image_info' => $image_info,
        ]);
    }

    public static function handle_generate_recipes( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $products = $params['products'] ?? [];

        $recipes = SFC_AI_Engine::match_recipes( $products );

        $ready_now = array_values( array_filter( $recipes, function( $r ) {
            return ! empty( $r['can_cook_now'] );
        }));

        $need_more = array_values( array_filter( $recipes, function( $r ) {
            return empty( $r['can_cook_now'] );
        }));

        return rest_ensure_response([
            'success'   => true,
            'total'     => count( $recipes ),
            'ready_now' => $ready_now,
            'need_more' => $need_more,
            'all'       => $recipes,
        ]);
    }

    public static function handle_generate_weekly_plan( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $products = $params['products'] ?? [];

        $plan = SFC_AI_Engine::generate_weekly_plan( $products );

        return rest_ensure_response([
            'success'          => true,
            'plan'             => $plan['days'],
            'shopping_list'    => $plan['shopping_list'],
            'grouped_shopping' => $plan['grouped_shopping'],
            'total_to_buy'     => $plan['total_items_to_buy'],
            'recipes_by_type'  => $plan['recipes_by_type'] ?? [],
        ]);
    }

    public static function handle_find_stores( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $lat           = isset( $params['lat'] ) && is_numeric( $params['lat'] ) ? floatval( $params['lat'] ) : null;
        $lon           = isset( $params['lon'] ) && is_numeric( $params['lon'] ) ? floatval( $params['lon'] ) : null;
        $query_addr    = ! empty( $params['query'] ) ? trim( sanitize_text_field( $params['query'] ) ) : ( ! empty( $params['address'] ) ? trim( sanitize_text_field( $params['address'] ) ) : '' );
        $shopping_list = $params['shopping_list'] ?? [];
        $city_detected = '';

        if ( ( empty( $lat ) || empty( $lon ) ) && ! empty( $query_addr ) ) {
            $geocoded = self::geocode_address( $query_addr );
            if ( $geocoded ) {
                $lat = $geocoded['lat'];
                $lon = $geocoded['lon'];
                $city_detected = $geocoded['name'];
            }
        }

        if ( empty( $lat ) || empty( $lon ) ) {
            return rest_ensure_response([
                'success'        => true,
                'needs_location' => true,
                'user_location'  => null,
                'stores'         => [],
                'recommendation' => null,
            ]);
        }

        $result = SFC_Store_Locator::find_and_recommend_stores( $lat, $lon, $shopping_list );

        return rest_ensure_response([
            'success'        => true,
            'user_location'  => $result['user_location'],
            'city_name'      => $city_detected,
            'stores'         => $result['stores'],
            'recommendation' => $result['recommendation'],
            'message'        => $result['message'] ?? null,
        ]);
    }

    public static function geocode_address( $query ) {
        $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode( $query ) . '&limit=1&addressdetails=1';
        $res = wp_remote_get( $url, [
            'timeout'   => 7,
            'headers'   => [
                'User-Agent'      => 'SmartFridgeChef/1.0 (WordPress)',
                'Accept-Language' => 'ru,en;q=0.8'
            ],
            'sslverify' => false,
        ]);

        if ( is_wp_error( $res ) ) {
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( empty( $data ) || ! is_array( $data ) || ! isset( $data[0]['lat'] ) ) {
            return null;
        }

        $item = $data[0];
        $name = $item['address']['city'] ?? ( $item['address']['town'] ?? ( $item['address']['village'] ?? ( explode( ',', $item['display_name'] )[0] ) ) );

        return [
            'lat'  => floatval( $item['lat'] ),
            'lon'  => floatval( $item['lon'] ),
            'name' => trim( $name ),
        ];
    }

    public static function handle_save_meal_plan( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $title = $params['title'] ?? ( 'План питания от ' . current_time( 'd.m.Y H:i' ) );
        $plan_data = $params['plan_data'] ?? [];

        $post_id = wp_insert_post([
            'post_title'   => sanitize_text_field( $title ),
            'post_type'    => 'sfc_meal_plan',
            'post_status'  => 'publish',
            'meta_input'   => [
                '_sfc_plan_data' => wp_json_encode( $plan_data ),
                '_sfc_created_at'=> current_time( 'mysql' ),
            ]
        ]);

        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'save_failed', 'Не удалось сохранить план', [ 'status' => 500 ] );
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'message' => 'План питания успешно сохранен!'
        ]);
    }

    public static function handle_get_saved_plans( WP_REST_Request $request ) {
        $posts = get_posts([
            'post_type'      => 'sfc_meal_plan',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $plans = [];
        foreach ( $posts as $p ) {
            $data = get_post_meta( $p->ID, '_sfc_plan_data', true );
            $plans[] = [
                'id'         => $p->ID,
                'title'      => $p->post_title,
                'date'       => get_the_date( 'd.m.Y H:i', $p ),
                'plan_data'  => json_decode( $data, true ),
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'plans'   => $plans,
        ]);
    }

    public static function handle_get_ai_status( WP_REST_Request $request ) {
        $config = SFC_AI_Engine::get_ai_config();
        $provider = $config['provider'];
        $model_name = $config[$provider . '_model'] ?? '';

        $label = 'Встроенный экспертный движок';
        if ( $provider === 'ollama_cloud' || $provider === 'ollama' ) {
            $label = 'Ollama Cloud (' . ( $config['ollama_model'] ?: 'cloud' ) . ')';
        } elseif ( $provider === 'claude' ) {
            $label = 'Claude (' . ( $config['claude_model'] ?: 'Anthropic' ) . ')';
        } elseif ( $provider === 'gemini' ) {
            $label = 'Google Gemini (' . ( $config['gemini_model'] ?: '1.5 Flash' ) . ')';
        }

        return rest_ensure_response([
            'success'    => true,
            'provider'   => $provider,
            'label'      => $label,
            'model'      => $model_name,
            'settings_url' => admin_url( 'admin.php?page=smart-fridge-chef' ),
        ]);
    }

    public static function handle_test_ai( WP_REST_Request $request ) {
        $result = SFC_AI_Engine::test_ai_connection();
        return rest_ensure_response( $result );
    }
}
