import urllib.request, ssl, os, re, json
from html.parser import HTMLParser
from urllib.parse import urljoin, urlparse

BASE_URL = "https://hassence.com"
CLONE_DIR = r"C:\Users\User\Desktop\Hassence\clone"
IMG_DIR = os.path.join(CLONE_DIR, "wp-content", "uploads")
os.makedirs(IMG_DIR, exist_ok=True)

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

class ImageExtractor(HTMLParser):
    def __init__(self):
        super().__init__()
        self.images = []
        self.css_files = []
        self.js_files = []
    
    def handle_starttag(self, tag, attrs):
        attrs_dict = dict(attrs)
        if tag == 'img' and 'src' in attrs_dict:
            self.images.append(attrs_dict['src'])
        elif tag == 'link' and 'href' in attrs_dict and 'stylesheet' in attrs_dict.get('rel', ''):
            self.css_files.append(attrs_dict['href'])
        elif tag == 'script' and 'src' in attrs_dict:
            self.js_files.append(attrs_dict['src'])

def download_file(url, filepath):
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

# Find all HTML files
html_files = []
for root, dirs, files in os.walk(CLONE_DIR):
    for f in files:
        if f.endswith('.html'):
            html_files.append(os.path.join(root, f))

print(f"Found {len(html_files)} HTML files")

# Extract all image URLs
all_images = set()
all_css = set()
all_js = set()

for hf in html_files:
    with open(hf, 'r', encoding='utf-8', errors='replace') as f:
        content = f.read()
    
    parser = ImageExtractor()
    parser.feed(content)
    
    for img in parser.images:
        if img.startswith('http'):
            all_images.add(img)
        elif img.startswith('//'):
            all_images.add('https:' + img)
        elif img.startswith('/'):
            all_images.add(BASE_URL + img)
    
    for css in parser.css_files:
        if css.startswith('/'):
            all_css.add(BASE_URL + css)
    
    for js in parser.js_files:
        if js.startswith('/'):
            all_js.add(BASE_URL + js)

print(f"Found {len(all_images)} unique images")
print(f"Found {len(all_css)} CSS files")
print(f"Found {len(all_js)} JS files")

# Download images
print("\nDownloading images...")
downloaded = {}
for i, img_url in enumerate(all_images):
    parsed = urlparse(img_url)
    filename = os.path.basename(parsed.path)
    if not filename:
        continue
    # Keep directory structure
    path_parts = parsed.path.split('/')
    if 'uploads' in path_parts:
        idx = path_parts.index('uploads')
        rel_path = '/'.join(path_parts[idx:])
    else:
        rel_path = 'wp-content/uploads/' + filename
    
    filepath = os.path.join(CLONE_DIR, rel_path)
    
    if img_url in downloaded:
        continue
    
    if download_file(img_url, filepath):
        downloaded[img_url] = '/' + rel_path
        if (i+1) % 20 == 0:
            print(f"  Downloaded {i+1}/{len(all_images)}")

# Download CSS
print("\nDownloading CSS files...")
for css_url in all_css:
    parsed = urlparse(css_url)
    rel_path = parsed.path.lstrip('/')
    filepath = os.path.join(CLONE_DIR, rel_path)
    download_file(css_url, filepath)

# Download JS
print("\nDownloading JS files...")
for js_url in all_js:
    parsed = urlparse(js_url)
    rel_path = parsed.path.lstrip('/')
    filepath = os.path.join(CLONE_DIR, rel_path)
    download_file(js_url, filepath)

# Fix HTML files to use local paths
print("\nFixing HTML references...")
for hf in html_files:
    with open(hf, 'r', encoding='utf-8', errors='replace') as f:
        content = f.read()
    
    # Replace remote URLs with local paths
    for remote_url, local_path in downloaded.items():
        content = content.replace(remote_url, local_path)
    
    # Also replace any remaining hassence.com URLs
    content = content.replace('https://hassence.com', '')
    
    with open(hf, 'w', encoding='utf-8') as f:
        f.write(content)

print(f"\nDone! Downloaded {len(downloaded)} images, fixed {len(html_files)} HTML files.")