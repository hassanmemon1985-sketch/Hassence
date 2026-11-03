import urllib.request, ssl, os, re

BASE_URL = "https://hassence.com"
OUT_DIR = r"C:\Users\User\Desktop\Hassence\clone"
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

# Theme assets to download
assets = [
    "/wp-content/themes/hassence/style.css",
    "/wp-content/themes/hassence/main.js",
    "/wp-content/themes/hassence/vendor.js",
    "/wp-content/themes/hassence/assets/easypaisa-qr.jpg",
    "/wp-content/themes/hassence/assets/icon-192.png",
    "/wp-content/themes/hassence/assets/icon-512.png",
    "/wp-content/themes/hassence/assets/og-default.jpg",
    "/wp-content/uploads/2026/10/download.png",
]

print("Downloading theme assets...")
for asset in assets:
    url = BASE_URL + asset
    filepath = os.path.join(OUT_DIR, asset.lstrip('/'))
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, context=ctx, timeout=30) as resp:
            data = resp.read()
            os.makedirs(os.path.dirname(filepath), exist_ok=True)
            with open(filepath, 'wb') as f:
                f.write(data)
            print(f"  OK: {asset}")
    except Exception as e:
        print(f"  Failed: {asset} - {e}")

print("Done downloading assets.")