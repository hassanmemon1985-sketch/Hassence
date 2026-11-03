import urllib.request
import urllib.parse
import ssl
import os
import re
from html.parser import HTMLParser

BASE_URL = "https://hassence.com"
OUT_DIR = r"C:\Users\User\Desktop\Hassence\clone"
os.makedirs(OUT_DIR, exist_ok=True)

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def download(url, filepath):
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, context=ctx, timeout=30) as resp:
            data = resp.read()
            os.makedirs(os.path.dirname(filepath), exist_ok=True)
            with open(filepath, 'wb') as f:
                f.write(data)
            return True
    except Exception as e:
        print(f"  Failed: {url} - {e}")
        return False

def get_local_path(url):
    parsed = urllib.parse.urlparse(url)
    path = parsed.path
    if not path or path == '/':
        path = '/index.html'
    if not path.endswith('.html') and not path.endswith('.php') and '.' not in os.path.basename(path):
        path = path.rstrip('/') + '/index.html'
    return path

pages = [
    "",
    "/shop/",
    "/product-category/fragrance/",
    "/product-category/womens-fashion/",
    "/product-category/mens-fashion/",
    "/product-category/home-lifestyle/",
    "/product-category/thrift/",
    "/help-center/",
    "/how-to-buy/",
    "/return-refund-policy/",
    "/about-us/",
    "/contact-us/",
    "/cart/",
    "/my-account/"
]

print("Cloning hassence.com...")
downloaded = 0

for page in pages:
    url = BASE_URL + page
    local_path = get_local_path(url)
    filepath = os.path.join(OUT_DIR, local_path.lstrip('/'))
    
    print(f"Downloading: {url}")
    if download(url, filepath):
        # Fix URLs in HTML
        with open(filepath, 'r', encoding='utf-8', errors='replace') as f:
            html = f.read()
        html = html.replace('https://hassence.com', '')
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(html)
        downloaded += 1

print(f"\nCloned {downloaded} pages to {OUT_DIR}")