<?php
/**
 * Configuration for Smart Fridge Chef WordPress.
 */


// CLI / Cron compatibility
if ( empty( $_SERVER['HTTP_HOST'] ) ) {
    $_SERVER['HTTP_HOST'] = 'localhost:8000';
}
if ( empty( $_SERVER['SERVER_NAME'] ) ) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}
if ( empty( $_SERVER['REQUEST_URI'] ) ) {
    $_SERVER['REQUEST_URI'] = '/';
}

define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'DB_ENGINE', 'sqlite' );
define( 'DB_PATH', __DIR__ . '/wp-content/database/wordpress.sqlite' );

define( 'AUTH_KEY', 'BsX+:Y y8LV ;}{Z &YSjS.bmS6JgSdI6(gn;[d!fH%WX/<!j|Z1fW6NO1@>q`P3' );
define( 'SECURE_AUTH_KEY', 'J;60)NC3|uu`Tb1Q`*zEQU&v1S$z<Yiw`m7>O~:_U}=eKN:h=xSb=,t5j8rp[1 ^' );
define( 'LOGGED_IN_KEY', '17-B=t-tP67rJOxv!HY3NY^A~&h#yWA$2u!Qu*g|vk2$<GtSaX@=uUXTtgx3/Yd8' );
define( 'NONCE_KEY', 'o,/LDBrP6I:YSH4ob~yoC?FKSH:PcMlf8U<gkKYzvE)h!YU8U{m)kEn_&H9Fn)YP' );
define( 'AUTH_SALT', 'QB}N}Z[ VB<!@am}=4*~SxmW+aTfr4|& Kp2,[)I=7Hx#E!MswIvA[K#Y5N7tn2{' );
define( 'SECURE_AUTH_SALT', 'L<}Sn`-r)G^Nc`ZS;)NW[} P%bA:qc(@/P3!@:hmCH`VGF*woE5|@/]N!j?E==K?' );
define( 'LOGGED_IN_SALT', ')wCFL9=~-9Vbo^CnLtz?Y0A>U.l/4mKg?7|+(-,I<0M2M-HUBMPicd8I@5U4b(91' );
define( 'NONCE_SALT', '5s,%p{|AZIQ~*/{P{{k+AMoXAKFF)c5rK->-51=So0fjTv_Iv;:b1KN_XMDeCfyB' );

$table_prefix = 'wp_';

define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );

// Increase memory limit
define( 'WP_MEMORY_LIMIT', '512M' );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
