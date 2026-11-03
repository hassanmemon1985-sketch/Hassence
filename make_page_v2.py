import json, os

with open(r"C:\Users\User\Desktop\Hassence\website\parts.json", "r", encoding="utf-8") as f:
    parts = json.load(f)

grid = parts["grid"]
tabs = parts["tabs"]
prod_pages = parts["products"]

css = """
:root{--bg:#fff;--card:#f6f6f6;--tx:#111;--mut:#777;--bd:#e5e5e5;--pri:#111;--sale:#e0245e;--on:#fff}
*{box-sizing:border-box;margin:0}
body{background:var(--bg);color:var(--tx);font-family:system-ui,sans-serif;line-height:1.4}
.wrap{max-width:1300px;margin:auto;padding:0 16px}
header{background:#111;color:#fff;padding:14px 0;position:sticky;top:0;z-index:10}
.logo{font-weight:900;font-size:26px;letter-spacing:3px}
.flashbar{background:linear-gradient(90deg,#e0245e,#ff6a3d);color:#fff;padding:8px 0}
.hero{position:relative;overflow:hidden;border-radius:0}
.slide{min-width:100%;height:380px;background-size:cover;background-position:center}
.tabs{display:flex;gap:10px;overflow-x:auto;padding:12px 30px}
.tab{padding:8px 16px;border:1px solid var(--bd);border-radius:30px;background:var(--card);font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}
.tab.on{background:var(--pri);color:var(--on)}
.tab:hover{border-color:var(--sale);color:var(--sale)}
.grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-top:18px}
.p{position:relative;cursor:pointer}
.img{aspect-ratio:1/1;border-radius:4px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden}
.img img{max-width:100%;max-height:100%;object-fit:contain}
.badge{position:absolute;top:8px;left:8px;background:var(--sale);color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:3px}
.nm{font-size:13px;margin-top:8px;height:36px;overflow:hidden}
.pr{font-weight:800;font-size:17px;color:var(--sale)}
.more{text-align:center;margin:26px 0}
footer{background:#111;color:#bbb;margin-top:40px;padding:34px 0}
.sortbox{display:flex;align-items:center;gap:8px;margin:14px 0;font-weight:700}
.sortbox select{border:1px solid var(--bd);background:var(--card);color:var(--tx);padding:8px 10px;border-radius:8px;font-size:13px}
@media(max-width:1000px){.grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:600px){.grid{grid-template-columns:repeat(2,1fr)}}
"""

html = """<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hassence - Fragrances, Fashion, Beauty, Electronics</title>
<meta name="description" content="Hassence is an online store in Pakistan for fragrances, fashion, beauty, electronics and more.">
<style>""" + css + """</style>
</head>
<body>
<header><div class="wrap"><div class="logo">HAS<span style="color:#e0245e">SENCE</span></div></div></header>
<div class="flashbar"><div class="wrap"><div class="fmq"><div class="mq"><span>FLASH SALE - up to 70% OFF</span><span>Shipping charges may apply</span><span>Cash on Delivery</span></div></div></div></div>
<div class="hero"><div class="slide" style="background-image:url('download.png');background-size:cover;background-position:center;height:400px"></div></div>
<main class="wrap">
<div class="sortbox"><select id="sortSel" aria-label="Sort products">
<option value="new" selected>Sort: New added</option>
<option value="az">Sort: A to Z</option>
<option value="low">Sort: Price low to high</option>
<option value="high">Sort: Price high to low</option>
</select></div>
<div class="tabwrap"><div class="tabs" id="tabs">""" + tabs + """</div></div>
<div class="grid" id="grid">""" + grid + """</div>
<div class="more"><button onclick="document.getElementById('grid').scrollIntoView()"><a href="#">VIEW ALL PRODUCTS</a></button></div>
</main>
<footer><div class="wrap"><div><h5>Hassence</h5><p>hassence.com - fragrances, fashion, beauty, electronics and more.</p><p>info@hassence.com</p><p>Karachi, Pakistan</p></div><div><h5>Customer Care</h5><p>Help Center</p><p>How to Buy</p><p>Returns & Refunds</p></div><div><h5>About</h5><p>About Us</p><p>Careers</p><p>Contact</p></div><div><h5>Payments</h5><p>Cash on Delivery</p><p>Easypaisa / Raast QR</p></div></div></footer>
""" + prod_pages + """
<script>
document.querySelectorAll('.tab').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.tab').forEach(function(x){x.classList.toggle('on',x===b)});var s=b.dataset.t;document.querySelectorAll('#grid .p').forEach(function(p){var ok=s==='all'||(' '+p.dataset.cat+' ').indexOf(' '+s+' ')>-1;p.style.display=ok?'':'none'})})});
document.getElementById('sortSel').addEventListener('change',function(){var g=document.getElementById('grid');var items=[].slice.call(g.querySelectorAll('.p'));var fn={az:function(a,b){return a.dataset.name.localeCompare(b.dataset.name)},low:function(a,b){return parseFloat(a.dataset.price)-parseFloat(b.dataset.price)},high:function(a,b){return parseFloat(b.dataset.price)-parseFloat(a.dataset.price)}}[this.value];if(fn){items.sort(fn).forEach(function(p){g.appendChild(p)})}});
</script>
</body>
</html>"""

with open(r"C:\Users\User\Desktop\Hassence\website\index.html", "w", encoding="utf-8") as f:
    f.write(html)

print("Created index.html with {} products and images".format(grid.count('<div class="p"')))