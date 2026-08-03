# Seofyme SEO

All-in-one WordPress SEO plugin with **free + premium parity features** in a single package.

## What’s included

### Core (forked from Yoast SEO free, GPL v3)
- On-page SEO & readability analysis
- XML sitemaps
- Schema.org structured data
- Canonical URLs, breadcrumbs, meta templates
- Content type controls, crawl settings, llms.txt helpers

See [ATTRIBUTION.md](./ATTRIBUTION.md).

### Premium-parity modules (original Seofyme code in `/premium`)
| Feature | Module |
|--------|--------|
| Redirect manager + CSV import + slug-change prompts | `premium/redirects` |
| Up to 5 keyphrases + synonyms | `premium/keyphrases` |
| Internal linking suggestions | `premium/internal-linking` |
| Orphaned content workout | `premium/internal-linking`, `premium/workouts` |
| Social previews (Facebook / X) | `premium/social` |
| AI title & meta drafting (+ bulk approve) | `premium/ai` |
| Content planner / starter drafts | `premium/content-planner` |
| AI bot blocker (robots.txt) | `premium/bot-blocker` |
| IndexNow | `premium/indexnow` |
| Schema aggregation graph | `premium/schema` |
| Local SEO (locations, maps, schema) | `premium/local-seo` |
| Video SEO (schema + video sitemap) | `premium/video-seo` |
| News SEO (news sitemap + NewsArticle) | `premium/news-seo` |
| Front-end SEO inspector | `premium/frontend-inspector` |

> Yoast SEO Premium is proprietary. Seofyme reimplements equivalent capabilities; it does **not** include Yoast Premium source code.

## Requirements
- WordPress 6.8+
- PHP 7.4+

## Install
1. Copy `seofyme-seo` into `wp-content/plugins/`
2. Activate **Seofyme SEO** in wp-admin
3. Open **SEO → Seofyme Tools** for AI keys, bot blocker, IndexNow, schema aggregation
4. Visit **Settings → Permalinks** once (flush) so video/news/schema endpoints work

## AI drafting
Set an OpenAI or Anthropic API key under **SEO → Seofyme Tools**. Without a key, the plugin still returns useful offline draft suggestions.

## Endpoints
- `/video-sitemap.xml`
- `/news-sitemap.xml`
- `/seofyme-schema.json`
- REST: `/wp-json/seofyme/v1/*`

## License
GPL-3.0-or-later
