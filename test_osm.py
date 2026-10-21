import urllib.request
import json

# Test Nominatim reverse geocode or search for supermarkets in Moscow
url = "https://nominatim.openstreetmap.org/search?format=json&q=supermarket+moscow&limit=3"
req = urllib.request.Request(url, headers={'User-Agent': 'SmartFridgeChefApp/1.0'})
try:
    with urllib.request.urlopen(req, timeout=5) as response:
        data = json.loads(response.read().decode('utf-8'))
        print("Nominatim test success, found:", len(data), "places")
        if data:
            print("Sample place:", data[0].get('display_name'))
except Exception as e:
    print("Nominatim test note:", e)
