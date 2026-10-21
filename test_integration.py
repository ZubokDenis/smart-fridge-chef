import urllib.request
import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

base_url = "http://127.0.0.1:8000"

print("1. Testing Homepage...")
try:
    with urllib.request.urlopen(base_url, timeout=5) as resp:
        html = resp.read().decode('utf-8')
        print(f"Status: {resp.status}")
        print("Contains 'smart-fridge-app':", 'smart-fridge-app' in html)
        print("Contains 'Умный Шеф':", 'Умный Шеф' in html)
except Exception as e:
    print("Homepage test error:", e)

print("\n2. Testing REST API analyze-input (Text)...")
sample_prods = []
try:
    req_data = json.dumps({
        "type": "text",
        "text": "3 яйца, молоко 1 л, сыр 200г, помидоры 2 шт, куриное филе 500г, сметана, макароны"
    }).encode('utf-8')
    req = urllib.request.Request(
        f"{base_url}/wp-json/fridge-chef/v1/analyze-input",
        data=req_data,
        headers={'Content-Type': 'application/json'}
    )
    with urllib.request.urlopen(req, timeout=5) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        print("Analyze success:", res.get("success"))
        sample_prods = res.get("products", [])
        print(f"Found {len(sample_prods)} products:")
        for p in sample_prods:
            print(f" - {p.get('icon')} {p.get('name')} ({p.get('quantity')}) [{p.get('category')}]")
except Exception as e:
    print("API analyze error:", e)

print("\n3. Testing REST API generate-recipes...")
try:
    req_data = json.dumps({
        "products": sample_prods
    }).encode('utf-8')
    req = urllib.request.Request(
        f"{base_url}/wp-json/fridge-chef/v1/generate-recipes",
        data=req_data,
        headers={'Content-Type': 'application/json'}
    )
    with urllib.request.urlopen(req, timeout=5) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        print("Recipes success:", res.get("success"))
        print(f"Total: {res.get('total')}, Ready now: {len(res.get('ready_now', []))}")
        for r in res.get("all", [])[:5]:
            print(f" - {r.get('title')} (Match: {r.get('match_score')}%, Ready: {r.get('can_cook_now')})")
except Exception as e:
    print("API recipes error:", e)

print("\n4. Testing REST API generate-weekly-plan...")
shopping_list = []
try:
    req_data = json.dumps({
        "products": sample_prods
    }).encode('utf-8')
    req = urllib.request.Request(
        f"{base_url}/wp-json/fridge-chef/v1/generate-weekly-plan",
        data=req_data,
        headers={'Content-Type': 'application/json'}
    )
    with urllib.request.urlopen(req, timeout=5) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        print("Plan success:", res.get("success"))
        print(f"Days generated: {len(res.get('plan', []))}")
        shopping_list = res.get("shopping_list", [])
        print(f"Shopping list items to buy: {len(shopping_list)}")
        for item in shopping_list[:5]:
            print(f" - {item.get('icon')} {item.get('name')} ({item.get('amount')}) [{item.get('category')}]")
except Exception as e:
    print("API plan error:", e)

print("\n5. Testing REST API find-stores...")
try:
    req_data = json.dumps({
        "lat": 55.7558,
        "lon": 37.6173,
        "shopping_list": shopping_list
    }).encode('utf-8')
    req = urllib.request.Request(
        f"{base_url}/wp-json/fridge-chef/v1/find-stores",
        data=req_data,
        headers={'Content-Type': 'application/json'}
    )
    with urllib.request.urlopen(req, timeout=5) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        print("Stores success:", res.get("success"))
        stores = res.get("stores", [])
        print(f"Found stores: {len(stores)}")
        for s in stores:
            print(f" * {s.get('icon')} {s.get('name')}: {s.get('distance_m')}m (~{s.get('walking_time_mins')} мин) [{s.get('address')}]")
        rec = res.get("recommendation", {})
        if rec and rec.get("store"):
            print(f"\nTop Recommendation: {rec['store']['name']} ({rec['store']['distance_m']}m, ~{rec['store']['walking_time_mins']} min)")
            print(f"Reason: {rec.get('reason')}")
            for t in rec.get('tips', []):
                print(f"  + {t}")
except Exception as e:
    print("API stores error:", e)

print("\nAll integration tests finished successfully!")
