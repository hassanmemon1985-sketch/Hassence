# Hassence

**hassence.com** — a perfume house in Karachi, Pakistan. Men’s, women’s and unisex
fragrances, with Cash on Delivery and Easypaisa / Raast QR. Built for Pakistan first,
ready for international orders later.

This repository holds the WordPress theme source, the perfume-mode plugin, the
product catalogue and the build/deployment notes.

## Contents

| Folder / File | Purpose |
| --- | --- |
| `plugins/hassence-perfume/` | **Hassence Perfume Mode** plugin: black & gold styling, perfume home page, Men/Women/Unisex categories, safe product clean-up |
| `dist/hassence-perfume-2.0.0.zip` | Upload this ZIP in WordPress → Plugins → Add New → Upload Plugin |
| `docs/PERFUME-PIVOT-PLAN.md` | Full audit, connection status and launch plan |
| `docs/GO-LIVE-CHECKLIST.md` | Step-by-step go-live instructions |
| `docs/perfume-catalog.csv` | The 21 perfumes with gender/collection mapping |
| `extracted_theme/hassence/` | WordPress theme source (synced with live v3.6.0) |
| `website/` | Static-site clone of the old storefront (reference only) |
| `product-catalog.json` | Product catalog export (legacy, all 82 products) |
| `hassence-sample-products.csv`, `bulk-products-template.csv` | CSV import templates |
| `build*.py`, `extract_products.py`, `clone_site.py`, `download_images.py`, `fix_images.py` | Legacy scraping/build helpers |
| `.gitignore` | Excludes secrets and binaries (see below) |

## Live stack

- WordPress 7.1.4 + WooCommerce 11.2.1
- Theme: Hassence 3.6.0 (custom)
- Host: EasyHost Pakistan (`srv31.easyhost.pk`, cPanel port 2083)
- Currency: PKR · Country: Pakistan
- Payments: Cash on Delivery, Easypaisa / Raast QR (till `998904576`)

## Deploy a change

1. **Plugin:** zip `plugins/hassence-perfume/` and upload it in **Plugins → Add New → Upload Plugin**.
2. **Theme:** zip `extracted_theme/hassence/` and upload it in **Appearance → Themes → Add New → Upload Theme**.
3. **Content:** edit products/pages in WordPress admin.
4. **Cache:** LiteSpeed Cache → Purge All after every deploy.

See `docs/GO-LIVE-CHECKLIST.md` for the full process, including the product clean-up and DNS fix.

## Local setup

```bash
git clone https://github.com/hassanmemon1985-sketch/Hassence.git
cd Hassence
python build.py            # rebuild the static reference site from product-catalog.json
```

## Security notes

The following are **intentionally excluded** via `.gitignore` and must never be committed:

- `signing.keystore` / `signing-key-info.txt` — Android app signing key (contains the keystore password)
- `Hassence.apk`, `Hassence.aab` — signed app bundles
- `localhost.sql` — full WordPress database dump (customer emails, orders, addresses)
- `hassence.WordPress.2026-10-29.xml` — full WordPress content export
- All `.zip` theme/plugin bundles and product images

If the keystore password has ever been shared or pushed elsewhere, rotate it before signing any future app release.

## Automated backups

`scripts/backup.py` exports the live WordPress database (`mysqldump`) and content
(WP-CLI) to `backups/`, then commits a `BACKUP_CHANGELOG.md` entry and pushes.
DB dumps and XML stay gitignored — only the changelog + timestamp is committed.

1. Copy `backups/.env.example` → `backups/.env` and fill in real DB credentials.
2. Register a daily scheduled task:

```cmd
schtasks /TN HassenceBackup /TR "D:\Hassence\scripts\backup.bat" /SC DAILY /ST 02:00 /RL HIGHEST
```

3. Run it once manually to verify:

```cmd
scripts\backup.bat "Manual test backup"
```

Credentials are read from `backups/.env` and are gitignored — never commit them.

## Payments

- Cash on Delivery
- Easypaisa / Raast QR (merchant till `998904576`)

## Contact

- Email: info@hassence.com
- WhatsApp: +92 333 3848131
- Location: Gurumandir, Jamsheed Quarters, Karachi, Pakistan
