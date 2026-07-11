=== Organic Kratom USA Performance Optimizer ===
Contributors: siddhantsrivastava
Tags: performance, core web vitals, lcp, defer javascript, database cleanup
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Safe, modular speed optimizations that improve Core Web Vitals (LCP, INP, CLS) — with real LCP preloading and one-click setup — without breaking your site.

== Description ==

Organic Kratom USA Performance Optimizer is a modular, object-oriented performance suite built to WordPress Coding Standards. It focuses on real, safe optimizations and refuses to fake anything it cannot do reliably in PHP.

Made by Siddhant Srivastava — Portfolio: https://portfolio-sid-virid.vercel.app/ · GitHub: https://github.com/siddhant7701git

Highlights:

* One-click Auto-Optimize — applies the safe, high-impact profile (defer + delay third-party JS, LCP preload, font-display swap, head cleanup, heartbeat tuning) in a single step.
* Real LCP preloading — preloads hero/background images with fetchpriority="high", including CSS `background-image` heroes (Beaver Builder, Elementor, Divi) that a normal <img> hint can never reach. Manual URL and automatic detection.
* wp_head cleanup — emojis, generator, RSD, WLW manifest, shortlink, oEmbed discovery, self pingbacks, optional XML-RPC.
* JavaScript — safe defer and interaction-based delay (Analytics, GTM, Pixel, Hotjar, Clarity, chat widgets), sequential loading to preserve order.
* CSS — inline minification plus optional async delivery (preload + onload swap with noscript fallback) to eliminate render-blocking stylesheets.
* Images — native lazy loading, decoding="async", guarded first-image fetchpriority, YouTube facade (click-to-load).
* Fonts — force font-display: swap, preconnect, preload.
* Resource hints — preconnect, dns-prefetch.
* WooCommerce — conditional cart fragments and asset unloading with real cart detection.
* Elementor — conditional icon/font handling plus native experiment recommendations.
* Database — scheduled and on-demand cleanup (revisions, transients, spam, trash, auto-drafts) via core delete APIs, table optimization, orphaned metadata report.
* Caching — detects Redis, Memcached, object cache, Cloudflare, LiteSpeed and page-cache plugins; provides copy-ready Apache/Nginx rules.
* CDN — safe URL rewriting for Cloudflare, BunnyCDN, CloudFront, KeyCDN or custom.
* Diagnostics — server/PHP info, conflict detector, coverage estimate, Core Web Vitals suggestions, missing-alt scan.
* Tools — export / import / reset, Safe Mode, Debug Mode, activity log.

== Honesty first ==

Some "features" cannot be done safely in PHP alone. Instead of pretending, this plugin detects and reports them:

* Critical CSS generation and Unused CSS/JS removal (require real browser rendering).
* Full-page caching (server/host responsibility).
* Brotli compression and WebP/AVIF conversion (server-level).

== Installation ==

1. Upload the `ultimate-performance-optimizer` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Open Performance in the admin menu and click "Auto-Optimize now" on the Dashboard for the recommended safe profile, then fine-tune per tab.
4. If your LCP is a page-builder background image, paste its URL under Fonts → Preload LCP image and re-test with PageSpeed Insights.

== Frequently Asked Questions ==

= Will this break my site? =
Every risky option is off by default and clearly flagged. Use Safe Mode to instantly suspend all frontend optimizations while troubleshooting.

= Is it compatible with WP Rocket / LiteSpeed / Cloudflare? =
Yes. The Diagnostics tab detects overlapping plugins so you can avoid enabling the same optimization twice.

= Does it work on multisite? =
Yes.

== Changelog ==

= 2.0.2 =
* Fixed: LCP auto-detection now handles entity-encoded quotes (&quot;) inside inline style backgrounds, so hero preload / fetchpriority is emitted for those heroes.
* Detection prefers element inline-style backgrounds before <style> blocks.
* White-labelled as Organic Kratom USA Performance Optimizer.

= 2.0.1 =
* The one-click Auto-Optimize button is now in the admin top bar, visible from every tab.

= 2.0.0 =
* New: dedicated LCP module — preloads hero/background images with fetchpriority="high", including CSS background heroes (Beaver Builder/Elementor/Divi). Supports up to 3 manual URLs and automatic detection.
* New: one-click "Auto-Optimize" applies the recommended safe profile from the Dashboard.
* New: optional async CSS delivery to eliminate render-blocking stylesheets (preload + onload swap, noscript fallback).
* Fix: guarded the first-image fetchpriority so it no longer promotes tiny logos/thumbnails or fights a configured LCP preload (this could previously slow LCP).
* Improved: diagnostics now flag render-blocking CSS and Cloudflare Rocket Loader main-thread cost.
* Rebranded to Organic Kratom USA Performance Optimizer by Siddhant Srivastava.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.0.0 =
Adds real LCP background-image preloading, one-click optimization and async CSS delivery, and fixes a fetchpriority behaviour that could hurt LCP. Re-run Auto-Optimize after updating.

= 1.0.0 =
Initial release.
