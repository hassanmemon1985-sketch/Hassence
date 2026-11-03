import json, os

with open(r"C:\Users\User\Desktop\Hassence\website\data.json", "r", encoding="utf-8") as f:
    data = json.load(f)

products = data["products"]
categories = data["categories"]

# Build attachment ID -> URL map
import xml.etree.ElementTree as ET
XML_PATH = r"C:\Users\User\Desktop\Hassence\hassence.WordPress.2026-10-29.xml"
tree = ET.parse(XML_PATH)
root = tree.getroot()
ns = {'content': 'http://purl.org/rss/1.0/modules/content/', 'excerpt': 'http://wordpress.org/export/1.2/excerpt/', 'dc': 'http://purl.org/dc/elements/1.1/', 'wp': 'http://wordpress.org/export/1.2/'}

id_to_url = {}
for item in root.findall('channel/item'):
    pt = item.findtext('wp:post_type', '', ns)
    if pt == 'attachment':
        pid = item.findtext('wp:post_id', '', ns)
        url = item.findtext('wp:attachment_url', '', ns)
        if pid and url:
            id_to_url[pid] = url

# Get thumbnail URLs for products
for item in root.findall('channel/item'):
    pt = item.findtext('wp:post_type', '', ns)
    status = item.findtext('wp:status', '', ns)
    if pt == 'product' and status == 'publish':
        pid = item.findtext('wp:post_id', '', ns)
        for pm in item.findall('wp:postmeta', ns):
            k = pm.findtext('wp:meta_key', '', ns)
            v = pm.findtext('wp:meta_value', '', ns)
            if k == '_thumbnail_id':
                for p in products:
                    if p['id'] == pid:
                        p['thumb_url'] = id_to_url.get(v, '')
                        break

# Group products by category
from collections import defaultdict
cat_products = defaultdict(list)
for p in products:
    for c in p['categories']:
        slug = c.lower().replace(' ', '-').replace('&amp;', 'and').replace("'", '')
        cat_products[slug].append(p)

# Category icons
cat_icons = {
    'fragrance': '🛍️', 'women': '👗', 'men': '👔', 
    'womens-fashion': '👗', 'mens-fashion': '👔',
    'tv-home-appliances': '📺', 'home-lifestyle': '🛋️',
    'beauty': '💄', 'thrift': '⚡', 'daraz-deals': '⚡',
    'health-beauty': '💄', 'other': '📦'
}

# Generate category sections HTML
cat_sections = ""
for slug, prods in cat_products.items():
    icon = cat_icons.get(slug, '🛍️')
    cat_name = prods[0]['categories'][0] if prods else slug
    
    items_html = ""
    for p in prods[:8]:
        price = p['price']
        regular = p['regular_price']
        sale = p['sale_price']
        badge = ''
        if regular and sale and float(regular) > float(sale):
            pct = round((1 - float(sale) / float(regular)) * 100)
            badge = '-{}%'.format(pct)
        
        price_html = 'Rs {}'.format(price)
        if regular and sale and float(regular) > float(sale):
            price_html = '<span class="old">Rs {}</span> <span class="pr">Rs {}</span>'.format(regular, sale)
        elif regular:
            price_html = '<span class="pr">Rs {}</span>'.format(regular)
        
        img_url = p.get('thumb_url', '')
        img_html = '<img src="{}" alt="{}">'.format(img_url, p['title']) if img_url else '📦'
        
        items_html += '''<div class="dpi"><a href="product.html?id={}"><div class="im">{}</div><b>{}</b></a></div>'''.format(p['id'], img_html, price_html)
    
    cat_sections += '''<section class="deal">
<h5>{} {} <small>›</small></h5>
<div class="dp">{}</div>
</section>'''.format(icon, cat_name, items_html)

# Generate product grid for main section
grid_items = ""
for p in products:
    price = p['price']
    regular = p['regular_price']
    sale = p['sale_price']
    cats = ' '.join(p['categories'])
    badge = ''
    if regular and sale and float(regular) > float(sale):
        pct = round((1 - float(sale) / float(regular)) * 100)
        badge = '<span class="badge">-{}%</span>'.format(pct)
    
    price_html = 'Rs {}'.format(price)
    if regular and sale and float(regular) > float(sale):
        price_html = '<span class="old">Rs {}</span> <span class="pr">Rs {}</span>'.format(regular, sale)
    elif regular:
        price_html = '<span class="pr">Rs {}</span>'.format(regular)
    
    img_url = p.get('thumb_url', '')
    img_html = '<img src="{}" alt="{}" style="width:100%;height:100%;object-fit:contain;padding:6px">'.format(img_url, p['title']) if img_url else '📦'
    
    grid_items += '''<div class="p" data-cat="{}" data-name="{}" data-price="{}" data-date="0">
<div class="img">{}</div>{}
<div class="nm"><a href="product.html?id={}">{}</a></div>
<div class="pr">{}</div>
</div>'''.format(cats, p['title'].lower(), price, img_html, badge, p['id'], p['title'], price_html)

# Generate category tabs
tabs_html = '<button class="tab on" data-t="all">All</button>'
for slug, prods in cat_products.items():
    icon = cat_icons.get(slug, '🛍️')
    cat_name = prods[0]['categories'][0] if prods else slug
    tabs_html += '<button class="tab" data-t="{}">{} {}</button>'.format(slug, icon, cat_name)

# Save parts
with open(r"C:\Users\User\Desktop\Hassence\website\parts.json", "w", encoding="utf-8") as f:
    json.dump({"grid": grid_items, "tabs": tabs_html, "cat_sections": cat_sections}, f, ensure_ascii=False)

print("Generated {} products, {} categories".format(len(products), len(cat_products)))
print("Category sections:", len(cat_sections))