import xml.etree.ElementTree as ET
import re, os, json

XML = r"C:\Users\User\Desktop\Hassence\hassence.WordPress.2026-10-29.xml"
OUT = r"C:\Users\User\Desktop\Hassence\website"
os.makedirs(OUT, exist_ok=True)

tree = ET.parse(XML)
root = tree.getroot()
ns = {'content': 'http://purl.org/rss/1.0/modules/content/', 'excerpt': 'http://wordpress.org/export/1.2/excerpt/', 'dc': 'http://purl.org/dc/elements/1.1/', 'wp': 'http://wordpress.org/export/1.2/'}

items = root.findall('channel/item')
products, pages, posts = [], [], []
cats_set, brands_set = set(), set()

for item in items:
    pt = item.findtext('wp:post_type', '', ns)
    title = item.findtext('title', '', ns)
    link = item.findtext('link', '', ns)
    pid = item.findtext('wp:post_id', '', ns)
    status = item.findtext('wp:status', '', ns)
    name = item.findtext('wp:post_name', '', ns)
    content = item.findtext('content:encoded', '', ns)
    excerpt = item.findtext('excerpt:encoded', '', ns)
    pub = item.findtext('pubDate', '', ns)
    
    cats = []
    for cat in item.findall('category'):
        d = cat.get('domain', '')
        cn = cat.text or ''
        if d == 'product_cat':
            cats.append(cn)
            cats_set.add(cn)
        elif d == 'product_brand':
            brands_set.add(cn)
    
    meta = {}
    for pm in item.findall('wp:postmeta', ns):
        k = pm.findtext('wp:meta_key', '', ns)
        v = pm.findtext('wp:meta_value', '', ns)
        meta[k] = v
    
    clean = lambda c: re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', ' ', c or '')).strip()
    
    if pt == 'product' and status == 'publish':
        products.append({
            'id': pid, 'title': title, 'slug': name, 'link': link,
            'content': clean(content), 'excerpt': excerpt, 'pub_date': pub,
            'categories': cats,
            'regular_price': meta.get('_regular_price', ''),
            'sale_price': meta.get('_sale_price', ''),
            'price': meta.get('_price', ''),
            'sku': meta.get('_sku', ''),
            'stock_status': meta.get('_stock_status', ''),
            'thumbnail_id': meta.get('_thumbnail_id', ''),
            'total_sales': meta.get('total_sales', '0'),
            'rating': meta.get('_wc_average_rating', '0')
        })
    elif pt == 'page' and status == 'publish':
        pages.append({
            'id': pid, 'title': title, 'slug': name, 'link': link,
            'content': clean(content), 'excerpt': excerpt, 'pub_date': pub
        })
    elif pt == 'post' and status == 'publish':
        posts.append({
            'id': pid, 'title': title, 'slug': name, 'link': link,
            'content': clean(content), 'excerpt': excerpt, 'pub_date': pub
        })

print(f"Products: {len(products)}, Pages: {len(pages)}, Posts: {len(posts)}")

with open(os.path.join(OUT, 'data.json'), 'w', encoding='utf-8') as f:
    json.dump({'products': products, 'pages': pages, 'posts': posts, 'categories': sorted(list(cats_set)), 'brands': sorted(list(brands_set))}, f, ensure_ascii=False, indent=2)

print("Data saved to data.json")