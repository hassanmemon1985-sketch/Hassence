# Hassence — Go-Live Checklist

**Plugin file:** `dist/hassence-perfume-2.0.0.zip`
**WordPress:** https://hassence.com/wp-admin
**cPanel:** https://srv31.easyhost.pk:2083

---

## Step 0 — Back up first (10 minutes)

Choose one:

- **cPanel → Backup → Download a Full Account Backup**, or
- **cPanel → phpMyAdmin → Export** the WordPress database, or
- Use **UpdraftPlus** in WordPress (Plugins → Add New → UpdraftPlus → Backup Now).

Do not skip this. The clean-up deletes the 61 non-perfume products permanently when you press the red button.

---

## Step 1 — Upload the plugin

1. Download `hassence-perfume-2.0.0.zip` from the project's `dist/` folder.
2. WordPress → **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP → **Install Now** → **Activate**.
4. A new **Perfume Mode** menu appears in the left sidebar.

The plugin does **not** delete anything on activation. It only creates categories and assigns your 21 perfumes.

---

## Step 2 — Organise the catalogue

1. Open **Perfume Mode**.
2. Click **Create categories & organise perfumes**.
3. You should see:
   - Men's Perfume, Women's Perfume, Unisex Perfume, Oud & Attar, Gift Sets created.
   - 21 perfumes assigned.
   - Old categories hidden.

Check: **Products → Categories** — the five perfume categories should be there.

---

## Step 3 — Back up the old products

1. On the **Perfume Mode** screen, click **Export CSV backup**.
2. Keep the downloaded CSV file somewhere safe.
3. Open it once to confirm it lists the 61 non-perfume products.

---

## Step 4 — Remove the old products

You chose permanent deletion. Recommended order:

1. Click **Hide them** first.
2. Open the homepage and the shop. Confirm only perfumes are shown.
3. If everything looks right, click **Move to Trash**.
4. Check **Products → Trash**.
5. Only then type `DELETE` in the confirmation box and click **Delete permanently**.

If something looks wrong at any point, click **Restore hidden / trashed products**.

---

## Step 5 — Remove Dokan

1. On the **Perfume Mode** screen, click **Deactivate Dokan Lite**.
2. Or go to **Plugins → Installed Plugins → Dokan Lite → Deactivate**.
3. Check **Appearance → Menus** and remove any "Sell on Hassence", "Vendor Dashboard" or "Store Listing" links.

---

## Step 6 — Clear the cache

LiteSpeed Cache is active on the server.

1. WordPress admin bar → **LiteSpeed Cache → Purge All**.
2. Or cPanel → LiteSpeed Web Cache Manager → Flush.
3. Open https://hassence.com in a private/incognito window.

---

## Step 7 — Test the store

Open these pages on mobile and desktop:

- [ ] https://hassence.com — black & gold hero, Men/Women/Unisex tiles, Best Sellers, New Arrivals.
- [ ] https://hassence.com/shop/ — only perfumes, no clothing/furniture.
- [ ] A product page — price, notes, **Buy now**, trust badges, description, shipping info.
- [ ] Add to cart → cart page — quantity, coupon, totals.
- [ ] Checkout — phone, address, city, Cash on Delivery, Easypaisa / Raast QR.
- [ ] Place a test order (you can cancel it in WooCommerce → Orders).
- [ ] https://hassence.com/my-account/ — no vendor/seller dashboard.
- [ ] Footer — no Daraz, no "Get the app", perfume-only description.
- [ ] Mobile bottom bar — Home, Shop, Cart, Account.

---

## Step 8 — Fix `www`

`www.hassence.com` currently does not resolve.

In cPanel → **Zone Editor** (or your DNS provider):

- Add a **CNAME** record: `www` → `hassence.com`
- Or add an **A** record: `www` → `170.249.216.178`

Then in cPanel → **Domains → Redirects**, redirect `www.hassence.com` → `https://hassence.com` (301).

Wait 15–60 minutes for DNS, then test.

---

## Step 9 — SEO & tracking (after launch)

- Google Search Console: add `hassence.com`, submit `https://hassence.com/wp-sitemap.xml`.
- Google Analytics 4 / Meta Pixel: add via **Site Kit** or your preferred plugin.
- Update the site title and tagline: **Settings → General** → “Hassence — Perfumes for Men, Women & Unisex”.
- Add a proper logo and favicon: **Appearance → Customize → Site Identity**.
- Write the About page for a perfume house (not a general store).

---

## Step 10 — Future changes

### Add a perfume
1. **Products → Add New**.
2. Set name, price, sale price, stock, image.
3. Choose **Men's Perfume**, **Women's Perfume** or **Unisex Perfume**.
4. Add **Best Sellers** / **New Arrivals** tags if needed.
5. Publish.

### Change the homepage banners
The hero uses the first image banner from **Banners** (the theme's custom post type). Add/replace a banner with:
- Featured image (wide, 1920×700 or similar).
- Link to `/shop/` or a category.

### Change colours
The black & gold palette lives in `plugins/hassence-perfume/assets/perfume.css` at the top:

```css
--hsp-black: #080808;
--hsp-panel: #111110;
--hsp-gold: #c8a24a;
--hsp-gold-light: #e3c87e;
--hsp-text: #f4efe4;
```

Change those values, save, re-zip the plugin, and re-upload (or edit the file via cPanel → File Manager → `wp-content/plugins/hassence-perfume/assets/perfume.css`).

### Add international currency/country
The theme already includes `inc/country.php` and `inc/currency.php`. Configure them in **Appearance → Customize** after the Pakistan launch is stable.

---

## Emergency rollback

1. **Plugins → Installed Plugins → Hassence Perfume Mode → Deactivate.**
   - The old theme returns immediately.
   - Products are not restored automatically if deleted; use the CSV backup or your full cPanel backup.
2. Restore the cPanel backup if needed.
3. Ask me to rebuild from the GitHub source.

---

## Files in this project

| Path | Purpose |
| --- | --- |
| `plugins/hassence-perfume/` | Plugin source |
| `dist/hassence-perfume-2.0.0.zip` | Upload this to WordPress |
| `docs/PERFUME-PIVOT-PLAN.md` | Full audit and plan |
| `docs/perfume-catalog.csv` | The 21 perfumes with gender/collection mapping |
| `docs/GO-LIVE-CHECKLIST.md` | This file |
