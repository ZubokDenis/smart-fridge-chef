import urllib.request
import os

TOOLS_DIR = os.path.abspath("tools")
wp_cli_path = os.path.join(TOOLS_DIR, "wp-cli.phar")

if not os.path.exists(wp_cli_path):
    print("Downloading wp-cli.phar...")
    req = urllib.request.Request("https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar", headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=30) as resp, open(wp_cli_path, "wb") as out:
        out.write(resp.read())
    print("wp-cli.phar downloaded successfully.")
else:
    print("wp-cli.phar already exists.")
