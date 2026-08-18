=== Seofyme SEO ===
Contributors: cacherocket, seofyme
Tags: seo, sitemap, schema, redirects, meta
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Original all-in-one WordPress SEO by Seofyme — titles, sitemaps, schema, redirects, linking, and AI drafting.

== Description ==

Seofyme SEO is an original WordPress SEO plugin. It helps you optimize titles and meta descriptions, generate XML sitemaps and structured data, manage redirects, suggest internal links, and draft metadata with optional AI providers.

== Installation ==

1. Upload the `seofyme-seo` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins screen
3. Open Seofyme SEO in the admin menu
4. Save Permalinks once to flush rewrite rules

== Frequently Asked Questions ==

= Is this a fork of another SEO plugin? =

No. Seofyme SEO is original code under the SeofymeSEO namespace with `_seofyme_*` meta keys.

= Is Seofyme SEO compatible with WPML? =

Yes. A `wpml-config.xml` registers SEO titles, descriptions, social fields, schema, locations, and homepage settings for translation. With WPML active, Seofyme also outputs hreflang tags (unless WPML already does) and language alternates in the XML sitemap. Install WPML String Translation to translate site-wide options such as the homepage title.

= How are translations handled? =

The plugin is internationalized with the `seofyme-seo` text domain. Translation files are not bundled. After the plugin is published on WordPress.org, community translations are managed at [translate.wordpress.org](https://translate.wordpress.org/) and delivered automatically through the WordPress translation update system.

== Changelog ==

= 0.1.1 =
* WordPress.org review readiness: Plugin/Author URI on seofyme.com, enqueue admin menu CSS via `wp_add_inline_style`
* Remove bundled `.po`/`.mo` locale files (keep `.pot` template only); community translations via translate.wordpress.org
* Remove manual `load_plugin_textdomain()`; WordPress.org loads translations for the `seofyme-seo` slug automatically
* Escape sitemap URL-set attributes; add ABSPATH guard to Plugin.php; silence WPML third-party hook false positives

= 0.1.0 =
* Public GitHub release of Seofyme SEO
* Public REST ping endpoint (`/wp-json/cacherocket/v1/ping`) so CacheRocket can verify the plugin is still installed
* Notify CacheRocket on uninstall so connected installs are marked disconnected

= 1.1.1 =
* Yoast free-vs-Premium comparison gaps: word-form keyphrase analysis, full checks per related keyphrase/synonym, llms.txt, stale cornerstone workout, related-links Gutenberg block, AI social titles/descriptions
* Harden AI drafting: JSON object responses, clearer API errors, more reliable suggestion parsing

= 1.1.0 =
* Competitor-premium parity modules: 404 monitor, regex redirects, image SEO, headline analyzer, advanced schema, author E-E-A-T, SEO revisions, site audit, WooCommerce SEO, rank tracker, link assistant, AI optimize/summarize, white-label, weekly email reports

= 1.0.1 =
* Modernized admin and editor UI

= 1.0.0 =
* Initial release
