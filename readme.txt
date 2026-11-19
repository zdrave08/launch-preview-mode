=== Launch Preview Mode ===
Contributors: zdrave08
Tags: catalog, preview, launch, product visibility, badge
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows selected products to remain visible before stock arrives, disables purchase, hides stock notices, and shows a NOVO badge.

== Description ==

Use this plugin when you want selected products to be visible online before stock is available.

Features:
- selected products remain visible even when out of stock
- purchase is disabled for selected products
- stock notices are hidden for selected products
- a NOVO badge is displayed
- optional preview note can be shown on the single product page
- admin column in product list
- quick enable/disable toggle
- bulk actions
- filter by preview status
- sortable preview column
- uninstall cleanup

This plugin is designed to work per product, without changing your normal hidden-out-of-stock workflow for the rest of the catalog.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate the plugin
3. Edit a product or use the Products list
4. Enable "Launch preview mode"

== Changelog ==

= 1.3.0 =
* Requires PHP 8.0 or newer (was 7.4)
* Tested up to WordPress 7.1 and WooCommerce 11.3
* Fixed the Plugin URI (pointed to a repository that does not exist)

= 1.2.3 =
* Fix: out-of-stock products that are not preview products are now excluded in the product query itself. Previously they were counted and paginated and only hidden at render time, which left empty slots on category pages and wrong result/page counts.

= 1.2.1 =
* Removed manual load_plugin_textdomain call
* Added nonce verification for product save handler
* Added translators comments for plural strings
* Added readme.txt with current Tested up to value
* Added languages directory for Domain Path consistency

= 1.2.0 =
* Added admin column in product list
* Added quick enable/disable toggle
* Added bulk actions
* Added filter dropdown and sortable column
* Added uninstall cleanup

= 1.0.0 =
* Initial release
