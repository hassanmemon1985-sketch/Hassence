import json, os

# Load existing data
with open(r"C:\Users\User\Desktop\Hassence\website\data.json", "r", encoding="utf-8") as f:
    data = json.load(f)

products = data["products"]

# Build attachment ID -> URL map from XML
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

# Get thumbnail IDs for each product
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

# Generate product grid with images
grid_items = []
for p in products:
    price = p["price"]
    regular = p["regular_price"]
    sale = p["sale_price"]
    cats = " ".join(p["categories"])
    badge = ""
    if regular and sale and float(regular) > float(sale):
        pct = round((1 - float(sale) / float(regular)) * 100)
        badge = '<span class="badge">-{}%</span>'.format(pct)
    
    price_html = "Rs {}".format(price)
    if regular and sale and float(regular) > float(sale):
        price_html = '<span class="old">Rs {}</span> <span class="pr">Rs {}</span>'.format(regular, sale)
    elif regular:
        price_html = '<span class="pr">Rs {}</span>'.format(regular)
    
    img_url = p.get('thumb_url', '')
    if img_url:
        img_html = '<img src="{}" alt="{}" style="width:100%;height:100%;object-fit:contain;padding:6px">'.format(img_url, p['title'])
    else:
        img_html = '📦'
    
    grid_items.append(
        '<div class="p" data-cat="{}" data-name="{}" data-price="{}" data-date="0">'
        '<div class="img">{}</div>{}'
        '<div class="nm"><a href="product.html?id={}">{}</a></div>'
        '<div class="pr">{}</div>'
        '</div>'.format(cats, p["title"].lower(), price, img_html, badge, p["id"], p["title"], price_html)
    )

grid_html = "\n".join(grid_items)

# Generate category tabs
cat_map = {}
for p in products:
    for c in p["categories"]:
        slug = c.lower().replace(" ", "-").replace("&amp;", "and").replace("'", "")
        cat_map[slug] = c

tabs_html = '<button class="tab on" data-t="all">All</button>'
for slug, name in sorted(cat_map.items()):
    tabs_html += '<button class="tab" data-t="{}">{}</button>'.format(slug, name)

# Generate product detail pages
prod_pages = ""
for p in products:
    price_html = "Rs {}".format(p["price"])
    if p["regular_price"] and p["sale_price"] and float(p["regular_price"]) > float(p["sale_price"]):
        price_html = '<span class="old">Rs {}</span> <span class="pr">Rs {}</span>'.format(p["regular_price"], p["sale_price"])
    elif p["regular_price"]:
        price_html = '<span class="pr">Rs {}</span>'.format(p["regular_price"])
    
    img_url = p.get('thumb_url', '')
    img_html = ''
    if img_url:
        img_html = '<img src="{}" alt="{}" style="max-width:100%;height:auto">'.format(img_url, p['title'])
    else:
        img_html = '<div style="font-size:100px;text-align:center">📦</div>'
    
    prod_pages += '<div id="prod-{}" style="display:none"><h1>{}</h1>{}</h1><div class="product-price">{}</div><div class="product-desc">{}</div></div>'.format(p["id"], p["title"], img_html, price_html, p["content"][:500])

# Save parts
with open(r"C:\Users\User\Desktop\Hassence\website\parts.json", "w", encoding="utf-8") as f:
    json.dump({"grid": grid_html, "tabs": tabs_html, "pages": "", "products": prod_pages}, f, ensure_ascii=False)

print("Generated {} products with images".format(len(products)))
print("Sample thumb URL:", products[0].get('thumb_url', 'N/A'))