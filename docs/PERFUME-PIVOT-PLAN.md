# Hassence.com — Perfume Pivot: Audit & Launch Plan

**Audit date:** 5 November 2026
**Prepared for:** hassence.com (Hassence)

---

## 1. Snapshot of the live site

| Item | Value |
| --- | --- |
| Domain | https://hassence.com (HTTPS OK) |
| Host / IP | EasyHost Pakistan — `srv31.easyhost.pk` / `170.249.216.178` |
| Server | LiteSpeed |
| cPanel | https://srv31.easyhost.pk:2083 (or https://srv31.easyhost.pk/cpanel) |
| WordPress | 7.1.4 |
| WooCommerce | 11.2.1 |
| Active theme | **Hassence 3.6.0** (custom theme) |
| Currency / country | PKR / Pakistan |
| Published products | **82** |
| Perfume products | **21** (all in one category: `Fragrance`) |
| Other products | **61** (clothing, furniture, toys, padel, etc.) still published |
| Product categories | `Fragrance` (21) and `user` (61, likely hidden) |
| Payments | Cash on Delivery + Easypaisa / Raast QR (till `998904576`) |
| Marketplace plugin | **Dokan Lite** active (vendor/marketplace features still present) |
| Other plugins | Jetpack, OneSignal, StoreGrowth, Reddit/Snapchat for WooCommerce, WooPayments, PayPal, Mastercard |
| GitHub | https://github.com/hassanmemon1985-sketch/Hassence — connected, push works |
| `www.hassence.com` | **Does not resolve** — DNS record missing |
| Local disk `C:` | **Full (0 GB free)** — blocks builds/backups, clean it up |

### What is already good
- The custom theme already has perfume-aware features: fragrance detection, perfume courier charge, product notes, COD/Easypaisa QR, WhatsApp button, country/currency modules.
- 21 perfumes already have SKUs (`HS-001001` … `HS-001023`), prices and images.
- GitHub push access is working, so source changes can be versioned.
- A perfume-only plugin draft already exists locally: `05-11-26 hassence-perfume.zip` (v1.0.0), but it is **not installed on the live site** (verified: `/wp-content/plugins/hassence-perfume/` returns 404).

### What is blocking the pivot
1. The live shop still shows all **82** products, including fashion/furniture/toys.
2. Perfumes are **not split** into Men / Women / Unisex — everything sits in `Fragrance`.
3. The homepage still promotes “Sell on Hassence”, Daraz and general-store categories.
4. Dokan marketplace and vendor signup are still active.
5. Footer/about copy still says “fragrances, fashion, beauty, electronics and more”.
6. `www.hassence.com` is not configured.
7. The local theme source in `extracted_theme/` is **v2.7.0**, but live is **v3.6.0** — source of truth is `30 -10 hassence-theme.zip`. The repo must be refreshed before editing theme files.
8. `C:` drive is full.

---

## 2. What is connected right now

| Platform | Status | What I can do |
| --- | --- | --- |
| GitHub | ✅ Connected (read + push) | Commit/push theme and plugin source |
| WordPress public REST API | ✅ Read-only | Read products, categories, pages, prices, stock |
| WordPress admin (write) | ❌ No credentials | Needs an Application Password |
| cPanel / FTP / SSH | ❌ No credentials | Needs EasyHost cPanel login or FTP details |
| Live deployment | ❌ No path yet | Either you upload the ZIP in WP Admin, or give cPanel/FTP access |

**The live site does not auto-deploy from GitHub.** Pushing code to GitHub only saves the source; something must upload it to WordPress (plugin/theme ZIP install, or file copy through cPanel/FTP).

---

## 3. What I need from you to connect everything

### A. WordPress (safest — do not send your main password)
1. Log in to https://hassence.com/wp-admin
2. Go to **Users → Profile → Application Passwords**
3. Name it `OpenCode` → click **Add New Application Password**
4. Copy the generated password (format `xxxx xxxx xxxx xxxx xxxx xxxx`)
5. Send me:
   - WordPress admin **username**
   - that **Application Password**
   - the **email** on the admin account

This lets me update products, categories, menus, pages and settings through the official WordPress API. It can be revoked at any time.

### B. cPanel (for theme files, backups, DNS)
Send one of:
- cPanel URL + username + password (https://srv31.easyhost.pk:2083), **or**
- FTP/SFTP host + username + password + port, **or**
- SSH access

If you prefer not to share it, you can upload the files yourself — I will give you a ZIP and click-by-click steps.

### C. Decisions I need before building
1. **Old 61 products:** hide them (reversible, recommended) or delete them?
2. **Brand look:** dark luxury (black + gold) or clean minimal (cream + white)?
3. **Product names:** keep the current “inspired by” names (Aventus, Gucci Guilty, Sauvage, Yara, Erba Pura…) or rename them to original Hassence names? *Using other brands’ names is a trademark risk, especially when you expand internationally.*
4. **Categories:** OK with `Men`, `Women`, `Unisex` + collections (`Best Sellers`, `New Arrivals`, `Oud & Attar`, `Gift Sets`)?
5. **Dokan/vendors:** remove the marketplace entirely? (Recommended for a single-brand perfume store.)
6. **Shipping:** keep the Rs. 270 perfume courier charge, or free delivery over a threshold?

---

## 4. Proposed build (once you confirm)

### Phase 1 — Clean the catalogue (safe, reversible)
- Create categories: **Men’s Perfume, Women’s Perfume, Unisex Perfume, Oud & Attar, Gift Sets**.
- Assign the 21 existing perfumes using `docs/perfume-catalog.csv`.
- Set the 61 non-perfume products to **hidden** (not deleted).
- Hide the old categories from menus and homepage.

### Phase 2 — Perfume homepage
- Luxury hero (desktop + mobile image).
- “Shop by” tiles: Men / Women / Unisex.
- Best Sellers, New Arrivals, Oud & Attar rows.
- Fragrance-family section (Fresh, Woody, Floral, Oriental, Sweet).
- “How to order” (COD + Easypaisa) and WhatsApp help.
- Remove Sell on Hassence, Daraz, vendor blocks.

### Phase 3 — Product pages
- Clean titles and descriptions (remove “Product Title: …” leftovers).
- Scent notes (top / heart / base), size, longevity, occasion.
- Trust badges: 100% original, COD, delivery across Pakistan.
- Customer reviews (only verified buyers).

### Phase 4 — Brand pages
- About, Contact, Shipping & Delivery, Returns, Privacy, Terms.
- Footer rewritten for perfume only.
- Logo, favicon, OG image, WhatsApp number.

### Phase 5 — Launch & growth
- Fix `www` DNS.
- Google Search Console + sitemap, Meta pixel, GA4.
- Pakistan launch first; multi-currency/country ready for international orders.

---

## 5. How changes go live (3 routes)

### Route A — WordPress Admin (recommended for products, pages, plugin ZIP)
1. https://hassence.com/wp-admin
2. **Plugins → Add New → Upload Plugin** → choose the ZIP → **Install Now → Activate**
3. For theme: **Appearance → Themes → Add New → Upload Theme**
4. Content: **Products**, **Pages**, **Appearance → Menus / Customize**

### Route B — cPanel File Manager / FTP (theme code, backups)
1. https://srv31.easyhost.pk:2083
2. **File Manager → public_html**
3. Theme folder: `wp-content/themes/hassence/`
4. Plugin folder: `wp-content/plugins/`
5. Always download a backup before replacing files.

### Route C — GitHub (source control only)
- Repo: https://github.com/hassanmemon1985-sketch/Hassence
- I commit/push changes there. You (or I, with cPanel) deploy the updated ZIP to WordPress.

---

## 6. Immediate risks / cleanup

- **Trademark risk:** product names reference Creed, Gucci, Lattafa, Sospiro, Dior Sauvage. Rename before international expansion.
- **Security:** `localhost.sql`, `signing.keystore`, WordPress XML export and APK/AAB are in the local folder. They are gitignored — keep them that way.
- **Disk:** `C:` has 0 GB free; free at least 5–10 GB before builds/backups.
- **Dokan:** a multivendor marketplace on a single-brand perfume store adds attack surface and confusing menus; remove it.
- **`www`:** add a CNAME/A record and 301 redirect to `https://hassence.com`.
