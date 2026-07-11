# Changelog

All notable changes to Organic Kratom USA Performance Optimizer are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [2.0.2] - 2026-07-10

### Fixed
- LCP auto-detection now handles HTML-entity-encoded quotes inside inline `style` backgrounds (e.g. `background-image: url(&quot;…&quot;)`), the normal encoding for `style="…"` attributes. Previously these heroes were missed, so no preload / `fetchpriority="high"` was emitted.

### Changed
- LCP detection now prefers element-level inline `style` backgrounds (a strong hero signal) before falling back to `<style>` blocks, decoding entities and stripping surrounding quotes.
- White-labelled this build as **Organic Kratom USA Performance Optimizer**.

## [2.0.1] - 2026-07-10

### Changed
- The one-click **Auto-Optimize** button now lives in the admin top bar, so it is visible and reachable from every tab (previously Dashboard-only).

## [2.0.0] - 2026-07-10

### Added
- Dedicated **LCP module** (`includes/Modules/Frontend/Lcp.php`): preloads the real Largest Contentful Paint resource with `fetchpriority="high"`, including CSS `background-image` heroes (Beaver Builder, Elementor, Divi) that a plain `<img>` hint can never reach. Supports up to three manual URLs and automatic detection of the first local hero background.
- **One-click Auto-Optimize** on the Dashboard — applies the recommended, safe, high-impact profile (defer + delay third-party JS, LCP preload, font-display swap, head cleanup, heartbeat tuning) via a nonce-protected `admin-post` action.
- **Async CSS delivery** (CSS tab): converts render-blocking stylesheets to `rel="preload"` + `onload` swap with a `<noscript>` fallback and an exclusion list.
- New settings: `lcp_auto_preload`, `css_optimize_delivery`, `css_delivery_exclude`; `preload_lcp_image` upgraded to accept multiple URLs.

### Changed
- Rebranded to **Organic Kratom USA Performance Optimizer** by Siddhant Srivastava (portfolio + GitHub links throughout the admin). Internal `UPO`/`upo_` prefixes, namespace and text domain are unchanged for update safety.
- Diagnostics: coverage now credits auto LCP preload and reduced render-blocking CSS; Core Web Vitals tips now flag render-blocking stylesheets and Cloudflare Rocket Loader main-thread cost.
- LCP image preload moved out of `Resource_Hints` into the dedicated module.

### Fixed
- First-image `fetchpriority="high"` is now guarded: it skips SVGs/icons and images narrower than 200px, and stands down entirely when an LCP preload is configured — previously it could promote a tiny logo/thumbnail and *worsen* LCP.

## [1.0.0] - 2026-07-09

### Added
- Modular, PSR-4 autoloaded, OOP architecture with a schema-driven settings system.
- Frontend cleanup: emojis, generator, RSD, WLW manifest, shortlink, REST/oEmbed discovery, self pingbacks, optional XML-RPC and feed disabling, query-string removal, jQuery Migrate removal, conditional Dashicons.
- JavaScript: safe defer and interaction-based delay with a sequential loader (`assets/js/upo-delay.js`), automatic third-party delay (Analytics, GTM, Pixel, Hotjar, Clarity, chat widgets).
- Images: native lazy loading, `decoding="async"`, first-image `fetchpriority`, YouTube facade with click-to-load.
- Fonts: force `font-display: swap`, preconnect, preload.
- Resource hints: preconnect, dns-prefetch, LCP image preload.
- Heartbeat control and revision/autosave tuning.
- WooCommerce: conditional cart fragments and asset unloading with real cart detection.
- Elementor: conditional icons and native experiment recommendations.
- Database: scheduled + on-demand cleanup, table optimization, orphaned-metadata reporting.
- Caching: detection of Redis/Memcached/object cache/Cloudflare/LiteSpeed/page-cache plugins; copy-ready Apache and Nginx rules; safe static-asset headers.
- CDN: filter-based URL rewriting with exclusions and extension allowlist.
- Diagnostics: server/PHP info, conflict detector, coverage estimate, Core Web Vitals suggestions, missing-alt scan, largest-plugins report.
- Admin: modern tabbed UI, AJAX actions (all nonce + capability guarded), export/import/reset, Safe Mode, Debug Mode, activity log.
- Multisite-aware activation, deactivation and uninstall.

### Notes
- Critical CSS, Unused CSS/JS removal, full-page caching, Brotli and WebP/AVIF are detected and reported rather than faked.
