import urllib.request
import json
import base64

# Simple 1x1 GIF / PNG base64
sample_base64 = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="

req_data = json.dumps({
    "type": "photo",
    "image_base64": sample_base64,
    "mime_type": "image/png"
}).encode('utf-8')

req = urllib.request.Request(
    "http://127.0.0.1:8000/wp-json/fridge-chef/v1/analyze-input",
    data=req_data,
    headers={'Content-Type': 'application/json'}
)

with urllib.request.urlopen(req, timeout=5) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    print("Photo analyze result success:", res.get("success"))
    print("Detected products count:", res.get("count"))
    for p in res.get("products", [])[:3]:
        print(" -", p.get("name"), p.get("quantity"))
