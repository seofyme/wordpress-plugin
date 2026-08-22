=== Seofyme SEO ===
Contributors: cacherocket, seofyme
Tags: seo, sitemap, schema, redirects, meta
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.2
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

= Does IndexNow run automatically? =

No. IndexNow is off by default. An administrator must enable it under Seofyme SEO → Settings. Until then, no URLs are submitted.

= How can I get support? =

Email [wordpress-plugin@seofyme.com](mailto:wordpress-plugin@seofyme.com).

== External services ==

This plugin can connect to third-party services. Core SEO (titles, sitemaps, schema, redirects, and on-page analysis) works without any remote call. Remote services are used only when an administrator opts in (Cloud keys, IndexNow toggle, Search Console OAuth, or a BYO AI API key), or when WordPress 7.0+ has a site-level AI provider configured.

= Seofyme Cloud / CacheRocket =

Optional hosted AI drafting, plan/usage status, and connected-install heartbeat. Used when you save Seofyme Cloud API keys under Seofyme SEO → Account.

Data sent: your public/secret API keys, site URL and domain, plugin/WordPress/PHP versions (heartbeat), and — when generating drafts — the post title, stripped post content, and focus keyphrase. On uninstall, a disconnect notice is sent so the connected install can be marked disconnected. CacheRocket may also request the public REST ping endpoint on this site (`/wp-json/cacherocket/v1/ping`) to confirm the plugin is still installed.

This service is provided by CacheRocket: [Terms](https://cacherocket.com/terms-and-conditions), [Privacy Policy](https://cacherocket.com/privacy-policy).

= WordPress AI Client =

On WordPress 7.0 or later, AI drafting prefers the core AI Client when Seofyme Cloud keys are empty. The site owner chooses and configures the provider once (Settings → AI / Connectors). Credentials are managed by WordPress, not by this plugin. Data sent is the drafting prompt (post title, excerpt, and focus keyphrase) to whichever provider the site owner has configured.

= OpenAI =

Optional fallback for AI title/meta suggestions when Seofyme Cloud keys are empty and the WordPress AI Client has no provider. Used only if an administrator pastes an OpenAI API key under Settings → BYO AI and selects OpenAI.

Data sent: the API key and a prompt containing the post title, a short content excerpt, and focus keyphrase, when an editor clicks Generate in the AI draft box.

This service is provided by OpenAI: [Terms](https://openai.com/policies/terms-of-use), [Privacy Policy](https://openai.com/policies/privacy-policy).

= Anthropic =

Optional fallback for AI title/meta suggestions when Seofyme Cloud keys are empty and the WordPress AI Client has no provider. Used only if an administrator pastes an Anthropic API key under Settings → BYO AI and selects Anthropic.

Data sent: the API key and a prompt containing the post title, a short content excerpt, and focus keyphrase, when an editor clicks Generate in the AI draft box.

This service is provided by Anthropic: [Terms](https://www.anthropic.com/legal/terms), [Privacy Policy](https://www.anthropic.com/legal/privacy).

= Google Search Console =

Optional rank sync. Used only after an administrator saves Google OAuth client credentials and completes the Connect Search Console flow.

Data sent: OAuth client ID/secret, refresh token (to `https://oauth2.googleapis.com/token` and `https://accounts.google.com/o/oauth2/v2/auth`), the connected Google account email (`https://www.googleapis.com/oauth2/v2/userinfo`), and Search Analytics queries for tracked keywords (`https://www.googleapis.com/webmasters/v3/`).

This service is provided by Google: [Terms](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy), [Google APIs Terms](https://developers.google.com/terms).

= IndexNow =

Optional instant URL submission to participating search engines. Off by default. Used only after an administrator enables IndexNow under Settings.

Data sent: the site host, the IndexNow key, the key file URL, and the published public post URL, to `https://api.indexnow.org/indexnow` when a public post is published.

This service is provided by IndexNow (Microsoft Bing, Yandex, and other participating engines): [Terms](https://www.indexnow.org/terms), [Privacy Policy](https://privacy.microsoft.com/privacystatement).

== Changelog ==

= 0.1.2 =
* WordPress.org review: sanitize advanced schema JSON, encode JSON-LD with JSON_HEX_TAG, IndexNow opt-in off by default
* Prefer the WordPress AI Client when available; document all external services
* Tested up to WordPress 7.1

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

== Upgrade Notice ==

= 0.1.2 =
IndexNow is off until you enable it under Settings. JSON-LD output and advanced schema input handling are hardened.
