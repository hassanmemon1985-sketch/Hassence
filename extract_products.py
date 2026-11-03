import xml.etree.ElementTree as ET
import re
import os
import json

XML_PATH = r"C:\Users\User\Desktop\Hassence\hassence.WordPress.2026-10-29.xml"
OUT_DIR = r"C:\Users\User\Desktop\Hassence\website"

os.makedirs(OUT_DIR, exist_ok=True)
os.makedirs(os.path.join(OUT_DIR, "assets"), exist_ok=True)

tree = ET.parse(XML_PATH)
root = tree.getroot()

ns = {
    'content': 'http://purl.org/rss/1.0/modules/content/',
    'excerpt': 'http://wordpress.org/export/1.2/excerpt/',
    'dc': 'http://purl.org/dc/elements/1.1/',
    'wp': 'http://wordpress.org/export/1.2/'
}

items = root.findall('channel/item')
print(f"Found {len(items)} items")

products = []
pages = []
posts = []
categories = set()
brands = set()

for item in items:
    post_type = item.findtext('wp:post_type', '', ns)
    title = item.findtext('title', '', ns)
    link = item.findtext('link', '', ns)
    post_id = item.findtext('wp:post_id', '', ns)
    status = item.findtext('wp:status', '', ns)
    post_name = item.findtext('wp:post_name', '', ns)
    content = item.findtext('content:encoded', '', ns)
    excerpt = item.findtext('excerpt:encoded', '', ns)
    pub_date = item.findtext('pubDate', '', ns)
    
    # Get categories
    cats = []
    for cat in item.findall('category'):
        domain = cat.get('domain', '')
        cat_name = cat.text or ''
        if domain == 'product_cat':
            cats.append(cat_name)
            categories.add(cat_name)
        elif domain == 'product_brand':
            brands.add(cat_name)
    
    # Get post meta
    meta = {}
    for pm in item.findall('wp:postmeta', ns):
        key = pm.findtext('wp:meta_key', '', ns)
        val = pm.findtext('wp:meta_value', '', ns)
        meta[key] = val
    
    if post_type == 'product' and status == 'publish':
        regular_price = meta.get('_regular_price', '')
        sale_price = meta.get('_sale_price', '')
        price = meta.get('_price', '')
        sku = meta.get('_sku', '')
        stock_status = meta.get('_stock_status', '')
        thumbnail_id = meta.get('_thumbnail_id', '')
        total_sales = meta.get('total_sales', '0')
        rating = meta.get('_wc_average_rating', '0')
        
        # Clean content
        content_clean = re.sub(r'<[^>]+>', ' ', content or '')
        content_clean = re.sub(r'\s+', ' ', content_clean).strip()
        
        products.append({
            'id': post_id,
            'title': title,
            'slug': post_name,
            'link': link,
            'content': content_clean,
            'excerpt': excerpt,
            'categories': cats,
            'regular_price': regular_price,
            'sale_price': sale_price,
            'price': price,
            'sku': sku,
            'stock_status': stock_status,
            'thumbnail_id': thumbnail_id,
            'total_sales': total_sales,
            'rating': rating,
            'pub_date': pub_date
        })
    elif post_type == 'page' and status == 'publish':
        content_clean = re.sub(r'<[^>]+>', ' ', content or '')
        content_clean = re.sub(r'\s+', ' ', content_clean).strip()
        
        pages.append({
            'id': post_id,
            'title': title,
            'slug': post_name,
            'link': link,
            'content': content_clean,
            'excerpt': excerpt,
            'pub_date': pub_date
        })
    elif post_type == 'post' and status == 'publish':
        content_clean = re.sub(r'<[^>]+>', ' ', content or '')
        content_clean = re.sub(r'\s+', ' ', content_clean).strip()
        
        posts.append({
            'id': post_id,
            'title': title,
            'slug': post_name,
            'link': link,
            'content': content_clean,
            'excerpt': excerpt,
            'pub_date': pub_date
        })

print(f"Products: {len(products)}")
print(f"Pages: {len(pages)}")
print(f"Posts: {len(posts)}")
print(f"Categories: {sorted(categories)}")
print(f"Brands: {sorted(brands)}")

# Save data as JSON for reference
with open(os.path.join(OUT_DIR, 'data.json'), 'w', encoding='utf-8') as f:
    json.dump({
        'products': products,
        'pages': pages,
        'posts': posts,
        'categories': sorted(list(categories)),
        'brands': sorted(list(brands))
    }, f, ensure_ascii=False, indent=2)

# Print first few products
for p in products[:5]:
    print(f"  - {p['title']} (Rs {p['price']}, cat: {p['categories']})")