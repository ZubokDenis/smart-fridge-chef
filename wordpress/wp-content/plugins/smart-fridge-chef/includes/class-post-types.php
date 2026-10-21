<?php
/**
 * Custom Post Types for Smart Fridge Chef
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFC_Post_Types {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_post_types' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );
    }

    public static function register_post_types() {
        // 1. Recipes Custom Post Type
        register_post_type( 'sfc_recipe', [
            'labels' => [
                'name'               => 'Рецепты',
                'singular_name'      => 'Рецепт',
                'add_new'            => 'Добавить рецепт',
                'add_new_item'       => 'Добавить новый рецепт',
                'edit_item'          => 'Редактировать рецепт',
                'new_item'           => 'Новый рецепт',
                'view_item'          => 'Просмотреть рецепт',
                'search_items'       => 'Искать рецепты',
                'not_found'          => 'Рецептов не найдено',
                'menu_name'          => 'Шеф: Рецепты',
            ],
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-carrot',
            'supports'            => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt' ],
            'has_archive'         => true,
            'rewrite'             => [ 'slug' => 'recipes' ],
        ]);

        // 2. Meal Plans Custom Post Type
        register_post_type( 'sfc_meal_plan', [
            'labels' => [
                'name'               => 'Планы питания',
                'singular_name'      => 'План питания',
                'add_new'            => 'Создать план',
                'add_new_item'       => 'Создать новый план питания',
                'edit_item'          => 'Редактировать план',
                'menu_name'          => 'Шеф: Планы меню',
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => [ 'title', 'custom-fields' ],
        ]);
    }

    public static function register_taxonomies() {
        // Taxonomy for meal type: Breakfast, Lunch, Dinner, Snack
        register_taxonomy( 'sfc_meal_type', 'sfc_recipe', [
            'labels' => [
                'name'          => 'Прием пищи',
                'singular_name' => 'Прием пищи',
                'menu_name'     => 'Приемы пищи',
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'meal-type' ],
        ]);

        // Taxonomy for cuisine: Russian, Italian, Asian, etc.
        register_taxonomy( 'sfc_cuisine', 'sfc_recipe', [
            'labels' => [
                'name'          => 'Кухня',
                'singular_name' => 'Кухня',
                'menu_name'     => 'Кухни мира',
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'cuisine' ],
        ]);
    }
}
