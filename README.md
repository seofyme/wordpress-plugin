# Seofyme SEO — WordPress Plugin

**Contributors:** cacherocket, seofyme  
**Tags:** seo, sitemap, schema, redirects, meta, local-seo  
**Requires at least:** 6.0  
**Requires PHP:** 7.4  
**Tested up to:** 7.1  
**Stable tag:** 0.1.2  
**License:** GPLv3 or later  
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html

Seofyme SEO is an **original** all-in-one WordPress SEO plugin: on-page guidance, XML sitemaps, Schema.org JSON-LD, redirects, multi-keyphrase analysis, internal linking, social previews, AI drafting, Local/Video/News SEO, IndexNow, and more.

> WordPress.org uses [`readme.txt`](readme.txt). This `README.md` is the public GitHub documentation.
>
> Plugin slug / install folder: **`seofyme-seo`** (text domain: `seofyme-seo`). Main file: `seofyme-seo.php`.

## Description

Seofyme owns its meta keys (`_seofyme_*`), PHP namespaces (`SeofymeSEO\`), admin UI, scoring rules, and feature modules. Feature goals may overlap with other SEO plugins, but the implementation is Seofyme’s — **not a fork or copy of any competitor**.

### Admin pages

| Menu               | What it does                                                                                                                                    |
| ------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| **Dashboard**      | Quick links to sitemap, schema graph, settings, redirects, bulk editor, workouts                                                                |
| **Settings**       | Titles, organization, optional BYO AI provider/key, AI bot blocker, llms.txt, IndexNow, Search Console OAuth, white-label, weekly email reports |
| **Site audit**     | Technical health checks for the whole site                                                                                                      |
| **Redirects**      | Create/manage 301/302/307/410/451 redirects, regex rules, CSV import                                                                            |
| **404 monitor**    | Logs missing URLs and turns them into redirects                                                                                                 |
| **Link assistant** | Suggests internal links across existing content                                                                                                 |
| **Rank tracker**   | Manual keyword → position tracking with history; optional Google Search Console sync                                                            |
| **Bulk editor**    | Draft or approve SEO titles/descriptions in bulk (AI-assisted)                                                                                  |
| **Image SEO**      | Find and fix missing image alt text                                                                                                             |
| **Workouts**       | Orphaned content, cornerstone list, stale cornerstone reminders                                                                                 |
| **Account**        | Connect Seofyme Cloud API keys, view subscription and monthly AI usage, refresh plan status                                                     |

### Editor (posts & pages)

In the block/classic editor Seofyme adds:

- **Seofyme SEO** metabox — focus keyphrase, SEO title, meta description, canonical, robots, cornerstone flag, SERP preview, live content analysis
- **Related keyphrases** — up to 5 extra keyphrases with synonyms (word-form aware)
- **Internal linking** — refreshable suggestions while you write
- **Social preview** — Open Graph (Facebook) and X (Twitter) title/description/image
- **AI draft** — generate titles, metas, social copy, optimize tips, summarize
- **Advanced schema** — FAQ, HowTo, Product, Recipe, Course, Event
- **Headline analyzer** — score post titles for clarity and punch
- **SEO revisions** — restore previous Seofyme title/description values
- **Related internal links** Gutenberg block — related reading list on the front end

Logged-in editors also get a **front-end SEO inspector** to tweak title/description without opening wp-admin.

### Public endpoints (after Permalinks flush)

| URL                            | Purpose                                                       |
| ------------------------------ | ------------------------------------------------------------- |
| `/sitemap.xml`                 | XML sitemap index                                             |
| `/sitemap-{post_type}.xml`     | Per-type sitemaps                                             |
| `/seofyme-schema.json`         | Aggregated Schema.org graph                                   |
| `/llms.txt`                    | AI-discovery file (cornerstone / important pages)             |
| `/video-sitemap.xml`           | Video sitemap (when videos are detected)                      |
| News sitemap routes            | News SEO module                                               |
| `/{indexnow-key}.txt`          | IndexNow ownership key file                                   |
| `/wp-json/cacherocket/v1/ping` | Public install probe for CacheRocket (plugin still installed) |

### Feature map

**On-page SEO**

- SEO title, meta description, canonical, robots
- Focus keyphrase + readability/content analysis
- Word forms and synonym matching for focus + related keyphrases
- SERP preview in the editor

**Technical SEO**

- XML sitemaps
- Schema.org JSON-LD (Organization, WebSite, Article/WebPage, plus advanced types)
- Schema aggregation endpoint
- IndexNow pings on publish (opt-in, off by default)
- `llms.txt` publishing
- AI training bot blocker via `robots.txt` rules
- Site audit

**Site structure**

- Redirect manager (plain + regex) with slug-change prompts
- CSV redirect import
- 404 monitor → redirect workflow
- Internal linking suggestions + Gutenberg related-links block
- Link assistant
- Orphaned content + cornerstone + stale cornerstone workouts

**Content & AI**

- AI titles, meta descriptions, social titles/descriptions via WordPress AI Client (WP 7.0+), Seofyme Cloud, or optional OpenAI/Anthropic API key
- Offline heuristic fallbacks when no provider is configured
- Optimize tips + summarize
- Bulk meta editor with approve/apply
- Content planner / starter drafts

**Social & local & media**

- Open Graph / X previews and tags
- Local SEO locations CPT + `[seofyme_store_locator]` shortcode
- Video SEO detection + video sitemap
- News SEO sitemap support
- Image SEO alt tooling
- WooCommerce product brand/GTIN fields + product schema helpers (when WooCommerce is active)

**Agency extras**

- White-label menu name
- Weekly email SEO summary
- Author E-E-A-T profile fields + Person schema
- Manual rank tracker + Google Search Console connect/sync

## Installation

### Method 1: Upload via WordPress Admin

1. Download a release zip from [GitHub Releases](https://github.com/seofyme/wordpress-plugin/releases) (or zip the `seofyme-seo` folder yourself).
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the zip, install, and activate **Seofyme SEO**.

### Method 2: Copy into `wp-content/plugins`

1. Clone or copy this repository into `/wp-content/plugins/seofyme-seo/`.
2. Activate **Seofyme SEO** under **Plugins**.

### Method 3: WordPress Plugin Directory (when published)

1. Go to **Plugins → Add New**.
2. Search for `Seofyme SEO`.
3. Install and activate.

### After activation (required once)

1. Open **Settings → Permalinks** and click **Save Changes** (no need to change anything).  
   This flushes rewrite rules for sitemaps, schema JSON, `llms.txt`, and IndexNow.
2. Open **Seofyme SEO → Settings** and set your homepage title/description and organization name.
3. Open **Seofyme SEO → Account** to connect Seofyme Cloud and sync your plan, or optionally add an OpenAI/Anthropic fallback under **Settings → BYO AI**.

## Usage

### First-hour checklist

1. **Seofyme SEO → Settings** — homepage title/description, organization name/logo, XML sitemap + schema toggles.
2. **Seofyme SEO → Account** — connect Cloud API keys and confirm the correct subscription and usage limits.
3. **Settings → Permalinks → Save** — if you have not already.
4. Visit `/sitemap.xml` and `/seofyme-schema.json` in a browser to confirm they load.
5. Edit a post — set a **focus keyphrase**, SEO title, and meta description; watch the analysis score update.
6. Mark important guides as **Cornerstone content**.
7. Open **Workouts** to find orphaned pages that need internal links.

### Optimizing a single post

1. Enter a **focus keyphrase**.
2. Write or generate an SEO title (use `%%title%%`, `%%sitename%%`, `%%sep%%`, `%%focuskw%%` if you like).
3. Write or generate a meta description (aim ~70–160 characters).
4. Add up to **5 related keyphrases** and comma-separated synonyms.
5. Use **Refresh suggestions** under Internal linking and link to related posts.
6. Fill **Social preview** fields (or use AI → Social titles / Social descriptions).
7. Optionally pick an **Advanced schema** type for FAQ/HowTo/etc.
8. Publish — if IndexNow is enabled under Settings, Seofyme will ping when the post becomes published.

### Redirects & 404s

1. Open **Seofyme SEO → Redirects**.
2. Add a plain path (`/old-page`) or a regex rule.
3. Import CSV when migrating many URLs.
4. When you change a published slug, Seofyme prompts to create a redirect.
5. Review **404 monitor** regularly and convert high-hit misses into redirects.

### AI drafting

1. Connect Seofyme Cloud under **Seofyme SEO → Account**. Use **Refresh plan** there to update the displayed subscription and monthly request/token usage.
2. On WordPress 7.0+, configure a site-level provider under **Settings → AI / Connectors** (WordPress AI Client). Seofyme uses this when Cloud keys are empty.
3. Optionally choose OpenAI or Anthropic under **Settings → BYO AI** as a last-resort fallback when Cloud credentials are empty and no WordPress AI provider is configured.
4. In the editor, open **AI draft** and generate titles, descriptions, social copy, optimize tips, or a summary.
5. Click a suggestion to apply it to the matching field (nothing saves until you update the post).
6. Use **Bulk editor** for many posts at once.

Without Cloud keys, a WordPress AI provider, or a BYO API key, Seofyme still offers offline heuristic suggestions so you can try the workflow.

### Local SEO

1. Create a **Location** under Seofyme SEO (Locations CPT).
2. Fill business details (address, geo, hours, maps embed).
3. Embed the store locator with:

```
[seofyme_store_locator]
```

### Search Console (rank sync)

1. In Google Cloud Console, create an OAuth client and enable the **Search Console API**.
2. Set the redirect URI to your site’s `…/wp-json/seofyme/v1/gsc/callback` (shown under **Settings → Google Search Console**).
3. Paste the Client ID and Client Secret in **Seofyme SEO → Settings**, then **Save settings**.
4. Click **Connect Search Console** (on Settings or Rank tracker) and authorize Google.
5. Pick the Search Console property, then use **Sync positions** on Rank tracker to update tracked keywords from the last ~28 days of GSC data.

### AI visibility controls

- **llms.txt** — enable under Settings → AI visibility. Lists cornerstone (or recent) pages for AI tools.
- **AI bot blocker** — toggle known training crawlers; Seofyme appends `Disallow` rules to `robots.txt`.

### WooCommerce

When WooCommerce is active, Seofyme adds brand/GTIN fields on products, product schema helpers, and noindex hints for cart/checkout/account.

## Repository layout

```
seofyme-seo.php           # Main plugin bootstrap
readme.txt                # WordPress.org directory readme
README.md                 # This GitHub documentation
wpml-config.xml           # WPML field/option translation map
uninstall.php             # Cleanup on plugin delete
license.txt               # GPL-3.0
includes/                 # Core: admin, analysis, frontend head, sitemap, schema, options, WPML
modules/                  # Feature modules (redirects, AI, local, video, news, …)
assets/css|js             # Admin + front-end inspector + Gutenberg block assets
languages/                # Translation template (.pot); community translations via translate.wordpress.org
```

### Notable modules

| Path                             | Module                                             |
| -------------------------------- | -------------------------------------------------- |
| `modules/Redirects/`             | Redirect runtime + admin + CSV                     |
| `modules/Keyphrases/`            | Related keyphrases + synonyms                      |
| `modules/InternalLinking/`       | Suggestions, orphaned content, related-links block |
| `modules/Social/`                | Open Graph / X                                     |
| `modules/AI/`                    | Generator + bulk meta                              |
| `modules/BotBlocker/`            | AI crawler robots rules                            |
| `modules/LlmsTxt/`               | `/llms.txt`                                        |
| `modules/IndexNow/`              | IndexNow key + ping                                |
| `modules/LocalSEO/`              | Locations + store locator                          |
| `modules/VideoSEO/` / `NewsSEO/` | Media/news sitemaps                                |
| `modules/RankTracker/`           | Keyword position log + GSC sync                    |
| `modules/SearchConsole/`         | Google Search Console OAuth + property             |
| `modules/WooCommerce/`           | Product SEO helpers                                |

## FAQ

### Is this a fork of Yoast, Rank Math, or AIOSEO?

No. Seofyme SEO is original code under the `SeofymeSEO` namespace with `_seofyme_*` post meta keys only.

### Is Seofyme SEO compatible with WPML?

Yes. `wpml-config.xml` tells WPML which SEO titles, descriptions, social fields, schema, locations, and homepage settings to translate. With WPML active, Seofyme outputs hreflang tags (unless WPML already does) and language alternates in the XML sitemap. Use **WPML String Translation** for site-wide options such as the homepage title.

### How are translations handled?

The plugin is internationalized with the `seofyme-seo` text domain (no manual `load_plugin_textdomain()`). Translation files are **not** bundled. After publication on WordPress.org, community translations are managed at [translate.wordpress.org](https://translate.wordpress.org/) and delivered through the standard WordPress translation update system.

### Why do sitemaps or llms.txt 404?

Save **Settings → Permalinks** once after install or update. That flushes rewrite rules.

### Do I need an AI API key?

No. Core SEO works without it. On WordPress 7.0+, Seofyme prefers the site-level WordPress AI Client. You can also connect Seofyme Cloud or paste an OpenAI/Anthropic key under BYO AI. Otherwise offline heuristics are used.

### Is IndexNow on by default?

No. Enable it under **Seofyme SEO → Settings**. Until then, no URLs are submitted to IndexNow.

### Will Seofyme conflict with another SEO plugin?

Running two full SEO plugins usually duplicates titles, sitemaps, and schema. Prefer one SEO plugin per site.

### Where is data stored?

- Site options: `seofyme_seo_options`
- Post meta: `_seofyme_*`
- Custom tables: redirects + 404 monitor (created on activate/update)

## External services

This plugin can connect to third-party services. Core SEO (titles, sitemaps, schema, redirects, and on-page analysis) works without any remote call. Remote services are used only when an administrator opts in (Cloud keys, IndexNow toggle, Search Console OAuth, or a BYO AI API key), or when WordPress 7.0+ has a site-level AI provider configured.

### Seofyme Cloud / CacheRocket

Optional hosted AI drafting, plan/usage status, and connected-install heartbeat. Used when you save Seofyme Cloud API keys under Seofyme SEO → Account.

Data sent: your public/secret API keys, site URL and domain, plugin/WordPress/PHP versions (heartbeat), and — when generating drafts — the post title, stripped post content, and focus keyphrase. On uninstall, a disconnect notice is sent so the connected install can be marked disconnected. CacheRocket may also request the public REST ping endpoint on this site (`/wp-json/cacherocket/v1/ping`) to confirm the plugin is still installed.

This service is provided by CacheRocket: [Terms](https://cacherocket.com/terms-and-conditions), [Privacy Policy](https://cacherocket.com/privacy-policy).

### WordPress AI Client

On WordPress 7.0 or later, AI drafting prefers the core AI Client when Seofyme Cloud keys are empty. The site owner chooses and configures the provider once (Settings → AI / Connectors). Credentials are managed by WordPress, not by this plugin. Data sent is the drafting prompt (post title, excerpt, and focus keyphrase) to whichever provider the site owner has configured.

### OpenAI

Optional fallback for AI title/meta suggestions when Seofyme Cloud keys are empty and the WordPress AI Client has no provider. Used only if an administrator pastes an OpenAI API key under Settings → BYO AI and selects OpenAI.

Data sent: the API key and a prompt containing the post title, a short content excerpt, and focus keyphrase, when an editor clicks Generate in the AI draft box.

This service is provided by OpenAI: [Terms](https://openai.com/policies/terms-of-use), [Privacy Policy](https://openai.com/policies/privacy-policy).

### Anthropic

Optional fallback for AI title/meta suggestions when Seofyme Cloud keys are empty and the WordPress AI Client has no provider. Used only if an administrator pastes an Anthropic API key under Settings → BYO AI and selects Anthropic.

Data sent: the API key and a prompt containing the post title, a short content excerpt, and focus keyphrase, when an editor clicks Generate in the AI draft box.

This service is provided by Anthropic: [Terms](https://www.anthropic.com/legal/terms), [Privacy Policy](https://www.anthropic.com/legal/privacy).

### Google Search Console

Optional rank sync. Used only after an administrator saves Google OAuth client credentials and completes the Connect Search Console flow.

Data sent: OAuth client ID/secret, refresh token (to `https://oauth2.googleapis.com/token` and `https://accounts.google.com/o/oauth2/v2/auth`), the connected Google account email (`https://www.googleapis.com/oauth2/v2/userinfo`), and Search Analytics queries for tracked keywords (`https://www.googleapis.com/webmasters/v3/`).

This service is provided by Google: [Terms](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy), [Google APIs Terms](https://developers.google.com/terms).

### IndexNow

Optional instant URL submission to participating search engines. Off by default. Used only after an administrator enables IndexNow under Settings.

Data sent: the site host, the IndexNow key, the key file URL, and the published public post URL, to `https://api.indexnow.org/indexnow` when a public post is published.

This service is provided by IndexNow (Microsoft Bing, Yandex, and other participating engines): [Terms](https://www.indexnow.org/terms), [Privacy Policy](https://privacy.microsoft.com/privacystatement).

## Changelog

### 0.1.2

- WordPress.org review: sanitize advanced schema JSON, encode JSON-LD with `JSON_HEX_TAG`, IndexNow opt-in off by default.
- Prefer the WordPress AI Client when available; document all external services.
- Tested up to WordPress 7.1.

### 0.1.1

- WordPress.org review readiness: Plugin/Author URI on seofyme.com, enqueue admin menu CSS via `wp_add_inline_style`.
- Remove bundled `.po`/`.mo` locale files (keep `.pot` template only); community translations via translate.wordpress.org.
- Remove manual `load_plugin_textdomain()`; WordPress.org loads translations for the `seofyme-seo` slug automatically.
- Escape sitemap URL-set attributes; add ABSPATH guard to Plugin.php; silence WPML third-party hook false positives.

### 0.1.0

- Public GitHub release of Seofyme SEO.
- Core on-page SEO, sitemaps, schema, redirects, multi-keyphrase, linking, social, AI drafting, Local/Video/News SEO, IndexNow, llms.txt, workouts, audits, and related bundled modules.
- Public REST ping endpoint (`/wp-json/cacherocket/v1/ping`) so CacheRocket can verify the plugin is still installed.
- Notify CacheRocket on uninstall so connected installs are marked disconnected.

### Earlier development tags

Internal milestones `1.0.0`–`1.1.1` covered UI polish, premium-parity modules, word-form analysis, and AI hardening before the public `0.1.0` tag. See [`readme.txt`](readme.txt) for the detailed list.

## Upgrade Notice

### 0.1.2

IndexNow is now off until you enable it under Settings. JSON-LD output and advanced schema input handling are hardened.

### 0.1.1

WordPress.org packaging fixes: no bundled locale translations, safer asset enqueue and sitemap escaping. Clear any page cache after updating.

### 0.1.0

First public GitHub release. After updating, open **Settings → Permalinks** and save once so sitemap, schema, and `llms.txt` routes refresh.

## Support

- Email: [wordpress-plugin@seofyme.com](mailto:wordpress-plugin@seofyme.com)
- Site: [seofyme.com](https://seofyme.com)
- GitHub: [github.com/seofyme/wordpress-plugin](https://github.com/seofyme/wordpress-plugin)
- Issues: [github.com/seofyme/wordpress-plugin/issues](https://github.com/seofyme/wordpress-plugin/issues)

## License

This plugin is licensed under the GPLv3 (or later): https://www.gnu.org/licenses/gpl-3.0.html
