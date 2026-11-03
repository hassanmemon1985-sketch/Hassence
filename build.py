import json, os

with open(r"C:\Users\User\Desktop\Hassence\website\data.json", "r", encoding="utf-8") as f:
    data = json.load(f)

products = data["products"]
pages = data["pages"]

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
    
    grid_items.append(
        '<div class="p" data-cat="{}" data-name="{}" data-price="{}" data-date="0">'
        '<div class="img">📦</div>{}'
        '<div class="nm"><a href="product.html?id={}">{}</a></div>'
        '<div class="pr">{}</div>'
        '</div>'.format(cats, p["title"].lower(), price, badge, p["id"], p["title"], price_html)
    )

grid_html = "\n".join(grid_items)

cat_map = {}
for p in products:
    for c in p["categories"]:
        slug = c.lower().replace(" ", "-").replace("&amp;", "and").replace("'", "")
        cat_map[slug] = c

tabs_html = '<button class="tab on" data-t="all">All</button>'
for slug, name in sorted(cat_map.items()):
    tabs_html += '<button class="tab" data-t="{}">{}</button>'.format(slug, name)

pages_html = ""
for pg in pages:
    pages_html += '<div class="page-section"><h1>{}</h1><div class="page-content">{}</div></div>'.format(pg["title"], pg["content"])

prod_pages = ""
for p in products:
    price_html = "Rs {}".format(p["price"])
    if p["regular_price"] and p["sale_price"] and float(p["regular_price"]) > float(p["sale_price"]):
        price_html = '<span class="old">Rs {}</span> <span class="pr">Rs {}</span>'.format(p["regular_price"], p["sale_price"])
    elif p["regular_price"]:
        price_html = '<span class="pr">Rs {}</span>'.format(p["regular_price"])
    
    prod_pages += '<div id="prod-{}" style="display:none"><h1>{}</h1><div class="product-price">{}</div><div class="product-desc">{}</div></div>'.format(p["id"], p["title"], price_html, p["content"][:500])

print("Generated {} products, {} pages".format(len(products), len(pages)))

with open(r"C:\Users\User\Desktop\Hassence\website\parts.json", "w", encoding="utf-8") as f:
    json.dump({"grid": grid_html, "tabs": tabs_html, "pages": pages_html, "products": prod_pages}, f, ensure_ascii=False)

print("Parts saved to parts.json")