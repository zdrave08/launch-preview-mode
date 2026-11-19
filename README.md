# Launch Preview Mode

Small WordPress plugin for product launch previews.

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.3.0-blue.svg)](https://github.com/zdrave08/launch-preview-mode)
[![WordPress Compatibility](https://img.shields.io/badge/wordpress-6.0%2B-brightgreen.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/php-8.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPLv2-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## What it does

This plugin lets selected products stay publicly visible before stock is available, while preventing purchase.

For products with **Launch preview mode** enabled, it will:

- keep the product visible in catalog and search
- hide the standard out-of-stock notice
- remove purchase actions
- show a **NOVO** badge
- optionally show a short preview note on the single product page

For all other products, your regular out-of-stock behavior stays unchanged.

## Use case

Ideal when you want to announce upcoming products online for a short period, but do not want customers to add them to cart yet.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- WooCommerce active

## Installation

1. Copy the `launch-preview-mode` folder into `wp-content/plugins/`
2. Activate **Launch Preview Mode**
3. Go to **Products**
4. Either:
   - edit a product and enable **Launch preview mode** in the Inventory tab, or
   - bulk enable it from the Products list

## Product settings

Each product gets two extra fields:

- **Launch preview mode** - enables preview behavior
- **Preview note** - optional text shown on the single product page

## Admin tools

The plugin also adds:

- **Preview** column in the Products list
- quick **Enable / Disable** action in that column
- bulk actions for multiple products
- filter dropdown:
  - All preview statuses
  - Preview enabled
  - Preview disabled
- sortable preview status column
- plugin action link to quickly open preview-enabled products

## Frontend behavior

When preview mode is enabled on a product:

- the product remains visible even if it is out of stock
- add-to-cart actions are disabled
- stock message is hidden
- the **NOVO** label is shown
- optional preview note is shown on the product page

## Files

```text
launch-preview-mode/
├── assets/
│   └── css/
│       └── frontend.css
├── LICENSE
├── launch-preview-mode.php
├── readme.txt
├── README.md
└── uninstall.php
```

## Notes

- Tested against standard WooCommerce hooks.
- Some custom themes may add their own stock labels or buttons. In that case, a small theme-specific CSS or hook adjustment may still be needed.

## License

GPL v2 or later
