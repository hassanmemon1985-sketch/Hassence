# Hassence

**hassence.com** — online store in Pakistan selling fragrances, fashion (men's & women's),
home & lifestyle, toys, electronics and more. Cash on Delivery and Easypaisa / Raast QR payments.

This repository holds the local source and build tooling for the store.

## Contents

| Folder / File | Purpose |
| --- | --- |
| `extracted_theme/hassence/` | WordPress theme source (`style.css`, `functions.php`, template files, gateway integrations) |
| `website/` | Static-site clone of the storefront (`index.html`, `data.json`, `parts.json`) |
| `product-catalog.json` | Product catalog export (id, price, slug, content, categories) |
| `hassence-sample-products.csv`, `bulk-products-template.csv`, `product-import-template.csv` | CSV import templates and sample data |
| `build.py`, `build_v3.py`, `build_with_images.py` | Static-site build scripts |
| `extract_products.py`, `clone_site.py`, `download_images.py`, `fix_images.py`, `gen.py`, `make_page*.py` | Scraping, extraction and page-generation helpers |
| `.gitignore` | Excludes secrets and binaries (see below) |

## Local setup

```bash
git clone https://github.com/hassanmemon1985-sketch/Hassence.git
cd Hassence
python build.py            # rebuild the static site from product-catalog.json
```

## Security notes

The following are **intentionally excluded** via `.gitignore` and must never be committed:

- `signing.keystore` / `signing-key-info.txt` — Android app signing key (contains the keystore password)
- `Hassence.apk`, `Hassence.aab` — signed app bundles
- `localhost.sql` — full WordPress database dump (customer emails, orders, addresses)
- `hassence.WordPress.2026-10-29.xml` — full WordPress content export
- All `.zip` theme bundles and product images

If the keystore password has ever been shared or pushed elsewhere, rotate it before signing any future app release.

## Payments

- Cash on Delivery
- Easypaisa / Raast QR (merchant till `998904576`)

## Contact

- Email: info@hassence.com
- Location: Gurumandir, Jamsheed Quarters, Karachi, Pakistan