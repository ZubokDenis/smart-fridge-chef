import urllib.request
import json

base_url = "http://127.0.0.1:8000"

print("1. Testing AI Status Endpoint...")
try:
    with urllib.request.urlopen(f"{base_url}/wp-json/fridge-chef/v1/ai-status", timeout=5) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        print("AI Status:", data)
except Exception as e:
    print("AI status error:", e)

print("\n2. Testing AI Connection Test Endpoint...")
try:
    req = urllib.request.Request(
        f"{base_url}/wp-json/fridge-chef/v1/test-ai",
        data=b"{}",
        headers={'Content-Type': 'application/json'}
    )
    with urllib.request.urlopen(req, timeout=8) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        print("Test AI Result:", data)
except Exception as e:
    print("Test AI error:", e)

print("\n3. Testing Homepage HTML for Google Material Symbols & Clean Architecture...")
try:
    with urllib.request.urlopen(base_url, timeout=5) as resp:
        html = resp.read().decode('utf-8')
        print("Contains 'Material+Symbols+Outlined':", 'Material+Symbols+Outlined' in html)
        print("Contains 'sfc-app-root':", 'sfc-app-root' in html)
        print("Contains 'sfc-ai-status-pill':", 'sfc-ai-status-pill' in html)
        print("No laser scan animation in HTML:", 'sfc-scanner-laser' not in html)
except Exception as e:
    print("Homepage error:", e)
