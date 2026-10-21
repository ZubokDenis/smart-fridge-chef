import urllib.request
import re

url = "https://windows.php.net/downloads/releases/archives/"
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
try:
    html = urllib.request.urlopen(req).read().decode('utf-8')
    matches = [m.group(1) for m in re.finditer(r'href="([^"]*php-8\.[23]\.[0-9]+-Win32-vs16-x64\.zip)"', html, re.IGNORECASE)]
    print("Found in archives:", matches[-5:])
except Exception as e:
    print("Error:", e)
