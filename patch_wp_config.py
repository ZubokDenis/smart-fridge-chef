import os

WP_CONFIG = os.path.abspath("wordpress/wp-config.php")
with open(WP_CONFIG, "r", encoding="utf-8") as f:
    content = f.read()

insert_code = """
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
"""

if "CLI / Cron compatibility" not in content:
    content = content.replace("define( 'DB_NAME', 'wordpress' );", insert_code + "\ndefine( 'DB_NAME', 'wordpress' );")
    with open(WP_CONFIG, "w", encoding="utf-8") as f:
        f.write(content)
    print("wp-config.php updated with CLI compatibility!")
else:
    print("Already updated.")
