=== Seofyme SEO ===
Contributors: cacherocket
Tags: seo, sitemap, schema, redirects, woocommerce
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Original all-in-one WordPress SEO. Local toolkit free forever. Connect Seofyme Cloud for hosted AI and audits.

== Description ==

**Seofyme SEO for WordPress — free locally, Cloud optional.**

Titles, sitemaps, schema, redirects, internal linking, Local/Video/News modules, and more ship free in the plugin. Hosted AI and cloud audits are optional Seofyme Cloud plans on CacheRocket billing.

No account required for the plugin. Seofyme Cloud (hosted AI + audits) is optional and metered.

Learn more at [seofyme.com/wordpress](https://seofyme.com/wordpress).

= Local toolkit is free =

Editor SEO, sitemaps, schema, redirects, and linking ship free. Hosted AI credits are a separate Cloud plan. Download once. Activate. Flush Permalinks. Add Cloud later if you want hosted AI quotas.

= What is free vs Cloud =

On-page, technical, linking, and vertical SEO modules run locally. Hosted AI generates and cloud audits need a Seofyme Cloud plan.

* On-page SEO + readability + related keyphrases *(free)*
* XML sitemaps, Schema.org, and advanced schema types *(free)*
* Redirects, regex rules, CSV import, and 404 monitor *(free)*
* Internal linking, Link Assistant, and workouts *(free)*
* Optional BYO AI keys in WordPress *(free; your own OpenAI/Anthropic key)*
* IndexNow, robots enhancements, and llms.txt *(free)*
* Local, Video, and News SEO modules *(free)*
* WooCommerce SEO, local site audit tools, white label UI *(free)*
* Hosted AI drafting and usage metering *(Seofyme Cloud)*
* Cloud plan/status sync from the Account screen *(Seofyme Cloud)*

One plugin. Free local tools. Connect an account when you want hosted AI.

= What’s in the plugin =

* Focus keyphrase analysis with word forms
* SERP preview and social meta (Open Graph / X)
* XML sitemap index + per-type sitemaps
* JSON-LD schema graph + advanced types (FAQ, HowTo, Product, Recipe, Course, Event)
* Redirect manager with regex + CSV import
* 404 monitor → redirect conversion
* Internal link suggestions + related-links Gutenberg block
* AI drafting (WordPress AI Client, Seofyme Cloud, OpenAI / Anthropic, or offline heuristics)
* IndexNow pings and llms.txt
* Local / Video / News SEO
* Image SEO, headline analyzer, site audit
* WooCommerce SEO, workouts, email reports, white label
* Rank tracker with optional Google Search Console sync
* Content planner, author E-E-A-T fields, and front-end inspector

= Editor =

Live checks, SERP preview, and social fields sit beside your draft where you already work.

* Focus keyphrase, SEO title, meta description, canonical, robots, cornerstone flag
* Related keyphrases (up to 5) with synonyms and word-form matching
* Refreshable internal link suggestions while you write
* Open Graph / X title, description, and image
* AI draft for titles, metas, social copy, optimize tips, and summaries
* Headline analyzer and SEO revisions
* Front-end SEO inspector for logged-in editors

= Technical SEO =

XML sitemaps, IndexNow, robots/llms.txt, and structured data — no second plugin tax.

* `/sitemap.xml` plus per-type sitemaps
* `/seofyme-schema.json` aggregated Schema.org graph
* `/llms.txt` for AI discovery of cornerstone pages
* Optional IndexNow pings on publish (off by default)
* AI bot blocker via `robots.txt` rules
* Site audit for technical health checks

After activation, save **Settings → Permalinks** once so these routes flush.

= Redirects, linking, and structure =

* 301/302/307/410/451 redirects, regex rules, and CSV import
* Slug-change prompt to create a redirect
* 404 monitor that turns misses into redirects
* Link Assistant plus orphaned / cornerstone / stale-cornerstone workouts
* Related internal links Gutenberg block

= Local, video, news, and WooCommerce =

* Locations CPT and `[seofyme_store_locator]` shortcode
* Video detection and video sitemap
* News sitemap support
* WooCommerce brand/GTIN fields, product schema helpers, and noindex hints for cart/checkout/account

= AI drafting =

Core SEO works without any remote call.

1. Connect Seofyme Cloud under **Seofyme SEO → Account** for hosted AI quotas.
2. On WordPress 7.0+, configure a site-level provider under **Settings → AI / Connectors**. Seofyme uses this when Cloud keys are empty.
3. Optionally paste an OpenAI or Anthropic key under **Settings → BYO AI** as a last-resort fallback.
4. Without Cloud keys, a WordPress AI provider, or a BYO key, offline heuristics still suggest titles and descriptions.

= Compatibility =

Seofyme SEO is original code under the `SeofymeSEO` namespace with `_seofyme_*` meta keys — **not a fork** of Yoast, Rank Math, or AIOSEO.

Running two full SEO plugins usually duplicates titles, sitemaps, and schema. Prefer one SEO plugin per site.

WPML: `wpml-config.xml` registers SEO titles, descriptions, social fields, schema, locations, and homepage settings. With WPML active, Seofyme outputs hreflang tags (unless WPML already does) and language alternates in the XML sitemap.

= Open source =

GPLv3. Full source on [GitHub](https://github.com/seofyme/wordpress-plugin). Product page and changelog: [seofyme.com/wordpress](https://seofyme.com/wordpress).

== External services ==

This plugin can connect to third-party services. Core SEO (titles, sitemaps, schema, redirects, and on-page analysis) works without any remote call. Remote services are used only when an administrator opts in (Cloud keys, IndexNow toggle, Search Console OAuth, or a BYO AI API key), or when WordPress 7.0+ has a site-level AI provider configured.

= Seofyme Cloud / CacheRocket =

* **What it is / used for:** Optional hosted AI drafting, plan/usage status, and connected-install heartbeat. Used when you save Seofyme Cloud API keys under **Seofyme SEO → Account**.
* **When / what data is sent:** When you save API keys, refresh plan, generate drafts, or uninstall. Typical payloads include your public/secret API keys, site URL and domain, plugin/WordPress/PHP versions (heartbeat), and — when generating drafts — the post title, stripped post content, and focus keyphrase. On uninstall, a disconnect notice is sent so the connected install can be marked disconnected. CacheRocket may also request the public REST ping endpoint on this site (`/wp-json/cacherocket/v1/ping`) to confirm the plugin is still installed.
* **Service:** [Cache Rocket](https://cacherocket.com)
* **Terms of Service:** https://cacherocket.com/terms-and-conditions
* **Privacy Policy:** https://cacherocket.com/privacy-policy

= WordPress AI Client =

* **What it is / used for:** On WordPress 7.0 or later, AI drafting prefers the core AI Client when Seofyme Cloud keys are empty. The site owner chooses and configures the provider once (Settings → AI / Connectors). Credentials are managed by WordPress, not by this plugin.
* **When / what data is sent:** When an editor clicks Generate and no Cloud keys are saved. The drafting prompt (post title, excerpt, and focus keyphrase) is sent to whichever provider the site owner has configured.

= OpenAI =

* **What it is / used for:** Optional fallback for AI title/meta suggestions when Seofyme Cloud keys are empty and the WordPress AI Client has no provider. Used only if an administrator pastes an OpenAI API key under Settings → BYO AI and selects OpenAI.
* **When / what data is sent:** The API key and a prompt containing the post title, a short content excerpt, and focus keyphrase, when an editor clicks Generate in the AI draft box.
* **Service:** OpenAI — https://openai.com/
* **Terms of Service:** https://openai.com/policies/terms-of-use
* **Privacy Policy:** https://openai.com/policies/privacy-policy

= Anthropic =

* **What it is / used for:** Optional fallback for AI title/meta suggestions when Seofyme Cloud keys are empty and the WordPress AI Client has no provider. Used only if an administrator pastes an Anthropic API key under Settings → BYO AI and selects Anthropic.
* **When / what data is sent:** The API key and a prompt containing the post title, a short content excerpt, and focus keyphrase, when an editor clicks Generate in the AI draft box.
* **Service:** Anthropic — https://www.anthropic.com/
* **Terms of Service:** https://www.anthropic.com/legal/terms
* **Privacy Policy:** https://www.anthropic.com/legal/privacy

= Google Search Console =

* **What it is / used for:** Optional rank sync. Used only after an administrator saves Google OAuth client credentials and completes the Connect Search Console flow.
* **When / what data is sent:** OAuth client ID/secret, refresh token (to `https://oauth2.googleapis.com/token` and `https://accounts.google.com/o/oauth2/v2/auth`), the connected Google account email (`https://www.googleapis.com/oauth2/v2/userinfo`), and Search Analytics queries for tracked keywords (`https://www.googleapis.com/webmasters/v3/`).
* **Service:** Google — https://search.google.com/search-console
* **Terms of Service:** https://policies.google.com/terms
* **Privacy Policy:** https://policies.google.com/privacy
* **Google APIs Terms:** https://developers.google.com/terms

= IndexNow =

* **What it is / used for:** Optional instant URL submission to participating search engines. Off by default. Used only after an administrator enables IndexNow under Settings.
* **When / what data is sent:** The site host, the IndexNow key, the key file URL, and the published public post URL, to `https://api.indexnow.org/indexnow` when a public post is published.
* **Service:** IndexNow — https://www.indexnow.org/
* **Terms:** https://www.indexnow.org/terms
* **Privacy Policy:** https://privacy.microsoft.com/privacystatement

== Installation ==

1. Install **Seofyme SEO** from **Plugins → Add New**, or upload the plugin folder to `/wp-content/plugins/seofyme-seo/`.
2. Activate it. Local SEO features are available immediately. No account is required.
3. Open **Settings → Permalinks** and click **Save Changes** (no need to change anything). This flushes rewrite rules for sitemaps, schema JSON, `llms.txt`, and IndexNow.
4. Open **Seofyme SEO → Settings** and set your homepage title/description and organization name.
5. Connect Seofyme Cloud under **Seofyme SEO → Account** only if you want hosted AI quotas. Generate keys on [Account](https://seofyme.com/account) after you register at [seofyme.com/auth/register](https://seofyme.com/auth/register).

Requires WordPress 6.0+ and PHP 7.4+.

== Frequently Asked Questions ==

= Do I need a Seofyme account? =

No. The local toolkit (titles, sitemaps, schema, redirects, linking, Local/Video/News, WooCommerce SEO, site audit) is free forever and works without an account.

Seofyme Cloud adds hosted AI drafting and usage metering. Details: [seofyme.com/wordpress](https://seofyme.com/wordpress).

= Is this a fork of another SEO plugin? =

No. Seofyme SEO is original code under the SeofymeSEO namespace with `_seofyme_*` meta keys.

= Will Seofyme conflict with another SEO plugin? =

Running two full SEO plugins usually duplicates titles, sitemaps, and schema. Prefer one SEO plugin per site.

= Is Seofyme SEO compatible with WPML? =

Yes. A `wpml-config.xml` registers SEO titles, descriptions, social fields, schema, locations, and homepage settings for translation. With WPML active, Seofyme also outputs hreflang tags (unless WPML already does) and language alternates in the XML sitemap. Install WPML String Translation to translate site-wide options such as the homepage title.

= Why do sitemaps or llms.txt 404? =

Save **Settings → Permalinks** once after install or update. That flushes rewrite rules.

= Do I need an AI API key? =

No. Core SEO works without it. On WordPress 7.0+, Seofyme prefers the site-level WordPress AI Client. You can also connect Seofyme Cloud or paste an OpenAI/Anthropic key under BYO AI. Otherwise offline heuristics are used.

= Does IndexNow run automatically? =

No. IndexNow is off by default. An administrator must enable it under Seofyme SEO → Settings. Until then, no URLs are submitted.

= Can I use a nulled or cracked copy? =

No. Nulled plugins are often modified with malware. There is nothing to null — the local plugin is already free. Download only from WordPress.org or [seofyme.com/wordpress](https://seofyme.com/wordpress).

= How are translations handled? =

The plugin is internationalized with the `seofyme-seo` text domain. Translation files are not bundled. Community translations are managed at [translate.wordpress.org](https://translate.wordpress.org/) and delivered automatically through the WordPress translation update system.

= Where can I get support? =

Use the [Seofyme SEO support forum](https://wordpress.org/support/plugin/seofyme-seo/) on WordPress.org, or email [wordpress-plugin@seofyme.com](mailto:wordpress-plugin@seofyme.com).

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
