import xml.etree.ElementTree as ET
import urllib.request
import os
import re
import json
import ssl

XML_PATH = r"C:\Users\User\Desktop\Hassence\hassence.WordPress.2026-10-29.xml"
IMG_DIR = r"C:\Users\User\Desktop\Hassence\website\images"
os.makedirs(IMG_DIR, exist_ok=True)

# Create SSL context that doesn't verify certificates (for Windows)
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

tree = ET.parse(XML_PATH)
root = tree.getroot()
ns = {'content': 'http://purl.org/rss/1.0/modules/content/', 'excerpt': 'http://wordpress.org/export/1.2/excerpt/', 'dc': 'http://purl.org/dc/elements/1.1/', 'wp': 'http://wordpress.org/export/1.2/'}

# Build attachment ID -> URL map
id_to_url = {}
for item in root.findall('channel/item'):
    post_type = item.findtext('wp:post_type', '', ns)
    if post_type == 'attachment':
        post_id = item.findtext('wp:post_id', '', ns)
        attach_url = item.findtext('wp:attachment_url', '', ns)
        if post_id and attach_url:
            id_to_url[post_id] = attach_url

print(f"Found {len(id_to_url)} attachments")

# Get all product thumbnail IDs
products = []
for item in root.findall('channel/item'):
    post_type = item.findtext('wp:post_type', '', ns)
    status = item.findtext('wp:status', '', ns)
    if post_type == 'product' and status == 'publish':
        title = item.findtext('title', '', ns)
        pid = item.findtext('wp:post_id', '', ns)
        thumb_id = None
        for pm in item.findall('wp:postmeta', ns):
            k = pm.findtext('wp:meta_key', '', ns)
            v = pm.findtext('wp:meta_value', '', ns)
            if k == '_thumbnail_id':
                thumb_id = v
        products.append({'id': pid, 'title': title, 'thumb_id': thumb_id})

# Collect unique image URLs needed
images_to_download = set()
for p in products:
    if p['thumb_id'] and p['thumb_id'] in id_to_url:
        images_to_download.add(id_to_url[p['thumb_id']])

# Also add banner images
for item in root.findall('channel/item'):
    post_type = item.findtext('wp:post_type', '', ns)
    if post_type == 'attachment':
        url = item.findtext('wp:attachment_url', '', ns)
        if url and ('banner' in url.lower() or 'download' in url.lower() or 'golden' in url.lower() or 'hassence' in url.lower()):
            images_to_download.add(url)

print(f"Need to download {len(images_to_download)} images")

# Download images
downloaded = {}
for url in images_to_download:
    try:
        filename = url.split('/')[-1]
        # Clean filename
        filename = re.sub(r'[^a-zA-Z0-9._-]', '_', filename)
        if not filename:
            continue
        filepath = os.path.join(IMG_DIR, filename)
        
        if os.path.exists(filepath):
            downloaded[url] = filename
            continue
        
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, context=ctx, timeout=30) as response:
            data = response.read()
            with open(filepath, 'wb') as f:
                f.write(data)
            downloaded[url] = filename
            print(f"  Downloaded: {filename}")
    except Exception as e:
        print(f"  Failed: {url} - {e}")

# Save mapping
with open(os.path.join(IMG_DIR, 'mapping.json'), 'w') as f:
    json.dump({'id_to_url': id_to_url, 'downloaded': downloaded, 'products': products}, f, indent=2)

print(f"\nDownloaded {len(downloaded)} images to {IMG_DIR}")