import os
import sys
import urllib.request
import zipfile
import shutil

TOOLS_DIR = os.path.abspath("tools")
PHP_DIR = os.path.join(TOOLS_DIR, "php")
WP_DIR = os.path.abspath("wordpress")

os.makedirs(TOOLS_DIR, exist_ok=True)

def download_file(url, target_path):
    print(f"Downloading {url} -> {target_path}...")
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=30) as response, open(target_path, 'wb') as out_file:
        total_size = int(response.info().get('Content-Length', 0))
        read_so_far = 0
        chunk_size = 1024 * 64
        while True:
            chunk = response.read(chunk_size)
            if not chunk:
                break
            out_file.write(chunk)
            read_so_far += len(chunk)
            if total_size > 0:
                percent = read_so_far * 100.0 / total_size
                sys.stdout.write(f"\rDownloading: {percent:.1f}% ({read_so_far / (1024*1024):.1f} MB / {total_size / (1024*1024):.1f} MB)")
                sys.stdout.flush()
    print("\nDownload complete.")

# 1. Download PHP
if not os.path.exists(os.path.join(PHP_DIR, "php.exe")):
    php_url = "https://windows.php.net/downloads/releases/archives/php-8.3.35-Win32-vs16-x64.zip"
    php_zip_path = os.path.join(TOOLS_DIR, "php-8.3.35-Win32-vs16-x64.zip")
    if not os.path.exists(php_zip_path):
        download_file(php_url, php_zip_path)
    print("Extracting PHP...")
    os.makedirs(PHP_DIR, exist_ok=True)
    with zipfile.ZipFile(php_zip_path, 'r') as zip_ref:
        zip_ref.extractall(PHP_DIR)
    print("PHP extracted successfully.")
else:
    print("PHP already installed.")

# 2. Configure php.ini
php_ini = os.path.join(PHP_DIR, "php.ini")
if not os.path.exists(php_ini):
    php_ini_dev = os.path.join(PHP_DIR, "php.ini-development")
    if os.path.exists(php_ini_dev):
        with open(php_ini_dev, "r", encoding="utf-8") as f:
            content = f.read()
        # Enable extensions
        extensions_to_enable = [
            "curl", "fileinfo", "gd", "mbstring", "openssl",
            "pdo_sqlite", "sqlite3", "zip"
        ]
        # Set extension_dir = "ext"
        content = content.replace(";extension_dir = \"ext\"", "extension_dir = \"ext\"")
        for ext in extensions_to_enable:
            content = content.replace(f";extension={ext}", f"extension={ext}")
        
        # Also increase upload limits
        content = content.replace("upload_max_filesize = 2M", "upload_max_filesize = 64M")
        content = content.replace("post_max_size = 8M", "post_max_size = 64M")
        content = content.replace("memory_limit = 128M", "memory_limit = 512M")
        
        with open(php_ini, "w", encoding="utf-8") as f:
            f.write(content)
        print("Configured php.ini with required extensions.")

# 3. Download WordPress
wp_zip_path = os.path.join(TOOLS_DIR, "latest-ru_RU.zip")
if not os.path.exists(WP_DIR):
    wp_url = "https://ru.wordpress.org/latest-ru_RU.zip"
    if not os.path.exists(wp_zip_path):
        download_file(wp_url, wp_zip_path)
    print("Extracting WordPress...")
    with zipfile.ZipFile(wp_zip_path, 'r') as zip_ref:
        zip_ref.extractall(os.path.abspath("."))
    print("WordPress extracted successfully.")
else:
    print("WordPress directory already exists.")

# 4. Download SQLite integration plugin
sqlite_plugin_dir = os.path.join(WP_DIR, "wp-content", "plugins", "sqlite-database-integration")
sqlite_zip_path = os.path.join(TOOLS_DIR, "sqlite-database-integration.zip")
if not os.path.exists(sqlite_plugin_dir):
    sqlite_url = "https://downloads.wordpress.org/plugin/sqlite-database-integration.zip"
    if not os.path.exists(sqlite_zip_path):
        try:
            download_file(sqlite_url, sqlite_zip_path)
        except Exception as e:
            print("SQLite plugin download failed:", e)
    if os.path.exists(sqlite_zip_path):
        print("Extracting SQLite database integration...")
        with zipfile.ZipFile(sqlite_zip_path, 'r') as zip_ref:
            zip_ref.extractall(os.path.join(WP_DIR, "wp-content", "plugins"))
        print("SQLite integration extracted.")

# 5. Place db.php in wp-content
db_copy = os.path.join(sqlite_plugin_dir, "db.copy")
db_dest = os.path.join(WP_DIR, "wp-content", "db.php")
if os.path.exists(db_copy) and not os.path.exists(db_dest):
    shutil.copyfile(db_copy, db_dest)
    print("db.php drop-in installed in wp-content/db.php.")

print("All dependencies downloaded and prepared successfully!")
