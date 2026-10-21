import os
import secrets
import string

WP_DIR = os.path.abspath("wordpress")
DB_DIR = os.path.join(WP_DIR, "wp-content", "database")
os.makedirs(DB_DIR, exist_ok=True)

def random_salt(length=64):
    chars = string.ascii_letters + string.digits + "!@#$%^&*()-_ []{}<>~`+=,.;:/?|"
    return ''.join(secrets.choice(chars) for _ in range(length))

salts = {
    'AUTH_KEY': random_salt(),
    'SECURE_AUTH_KEY': random_salt(),
    'LOGGED_IN_KEY': random_salt(),
    'NONCE_KEY': random_salt(),
    'AUTH_SALT': random_salt(),
    'SECURE_AUTH_SALT': random_salt(),
    'LOGGED_IN_SALT': random_salt(),
    'NONCE_SALT': random_salt(),
}

salts_php = "\n".join([f"define( '{k}', '{v.replace('\\', '\\\\').replace('\'', '\\\'')}' );" for k, v in salts.items()])

wp_config_content = f"""<?php
/**
 * Configuration for Smart Fridge Chef WordPress.
 */

define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'DB_ENGINE', 'sqlite' );
define( 'DB_PATH', __DIR__ . '/wp-content/database/wordpress.sqlite' );

{salts_php}

$table_prefix = 'wp_';

define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );

// Increase memory limit
define( 'WP_MEMORY_LIMIT', '512M' );

if ( ! defined( 'ABSPATH' ) ) {{
    define( 'ABSPATH', __DIR__ . '/' );
}}

require_once ABSPATH . 'wp-settings.php';
"""

with open(os.path.join(WP_DIR, "wp-config.php"), "w", encoding="utf-8") as f:
    f.write(wp_config_content)

print("wp-config.php successfully created!")
