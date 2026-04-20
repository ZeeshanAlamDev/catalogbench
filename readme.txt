=== CatalogBench – Bulk AI Product Editor for WooCommerce ===
Contributors: catalogbench
Tags: woocommerce, ai, product descriptions, bulk edit, seo
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use your own AI API keys to bulk-generate and optimize WooCommerce product descriptions, SEO titles, and meta descriptions.

== Description ==

CatalogBench uses your own AI API keys (Google Gemini, OpenAI) to generate high-quality product content in bulk. No subscriptions — bring your own key and pay only for API usage.

**Key Features:**

* **Bring Your Own Key (BYOK)** — Use your existing Google Gemini or OpenAI API keys. No middleman, no markup.
* **Bulk Generation** — Generate content for hundreds of products at once using WooCommerce Action Scheduler. No timeouts, even on shared hosting.
* **Visual Preview** — Side-by-side comparison of current vs AI-generated content. Accept or reject per field, per product.
* **Multiple Content Types** — Long descriptions, short descriptions, SEO titles, and SEO meta descriptions.
* **Tone Selection** — Professional, Casual, Persuasive, Technical, Luxury, or custom free-text instructions.
* **SEO Plugin Sync** — Auto-writes to Yoast SEO and Rank Math meta fields.
* **Cost Estimator** — Know your API costs before running a bulk job.

**Free Version Includes:**

* Google Gemini AI integration (free tier available)
* Long description generation
* Bulk generation for up to 5 products
* Visual preview with accept/reject
* Professional and Casual tones

**Pro Version Adds:**

* OpenAI integration (GPT-4o-mini, GPT-4o)
* All 4 content types (long desc, short desc, SEO title, SEO meta)
* Bulk generation for up to 500 products
* All 5 tones + custom free-text
* Custom prompt templates
* SEO plugin sync (Yoast + Rank Math)
* Cost estimator

== Installation ==

1. Upload the `catalogbench` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu.
3. Go to **CatalogBench → Settings** and enter your AI API key.
4. Click **Test Connection** to verify your key works.
5. Start generating content!

== Frequently Asked Questions ==

= What AI providers are supported? =

Google Gemini (free tier available) and OpenAI (GPT-4o-mini, GPT-4o). You provide your own API keys.

= Does the free version work without paying anything? =

Yes. Google Gemini offers a free tier (15 requests per minute, 1 million tokens per day). The free version of CatalogBench works with Gemini at zero cost.

= Will bulk generation cause my site to time out? =

No. CatalogBench uses WooCommerce Action Scheduler to process products in the background. Even if you close your browser, generation continues.

= Which SEO plugins are supported? =

Yoast SEO and Rank Math. CatalogBench auto-detects which plugin you have active and writes to the correct meta fields.

= Can I preview content before applying it? =

Yes. Every generation produces a side-by-side visual preview. You can accept or reject each field individually.

== Screenshots ==

1. Dashboard with quick stats and action buttons.
2. Single product generator with tone selection.
3. Side-by-side visual preview.
4. Bulk generator with product selection table.
5. Settings page with API key management.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release of CatalogBench.
