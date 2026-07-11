# WordPress Performance Optimizer

A modular, object-oriented WordPress performance optimization plugin designed to improve **Core Web Vitals (LCP, INP, CLS)** through safe, standards-compliant optimizations without breaking your website.

## ✨ Features

### 🚀 One-Click Auto Optimize
- Applies a recommended performance profile instantly.
- Enables safe, high-impact optimizations.
- Includes JavaScript optimization, LCP preload, font optimization, heartbeat tuning, and WordPress cleanup.

### 🖼️ Real LCP Image Preloading
- Automatically detects and preloads hero images.
- Supports:
  - `<img>` elements
  - CSS background images
  - Elementor
  - Beaver Builder
  - Divi
- Manual preload URLs supported.
- Adds `fetchpriority="high"` where appropriate.

### ⚡ JavaScript Optimization
- Safe JavaScript defer.
- Delay third-party scripts until user interaction.
- Sequential loading to preserve script order.
- Supports:
  - Google Analytics
  - Google Tag Manager
  - Meta Pixel
  - Microsoft Clarity
  - Hotjar
  - Chat widgets

### 🎨 CSS Optimization
- Inline CSS minification.
- Optional asynchronous CSS loading.
- Preload + onload swap.
- `<noscript>` fallback included.
- Helps eliminate render-blocking CSS.

### 🖼 Image Optimization
- Native lazy loading.
- Automatic `decoding="async"`.
- Smart fetch priority.
- YouTube facade (click-to-load embeds).

### 🔤 Font Optimization
- Force `font-display: swap`.
- Font preloading.
- DNS preconnect.
- Resource hints.

### 🧹 WordPress Cleanup
Remove unnecessary WordPress features including:

- Emojis
- Generator tag
- RSD links
- WLW manifest
- Shortlinks
- oEmbed discovery
- Self pingbacks
- Optional XML-RPC disabling

### 🛒 WooCommerce Optimization
- Disable cart fragments when unnecessary.
- Conditional WooCommerce asset loading.
- Smart cart detection.

### 🎯 Elementor Optimization
- Conditional icon loading.
- Font optimization.
- Native experiment recommendations.

### 🗄 Database Cleanup
- Delete revisions
- Delete transients
- Delete spam comments
- Delete trashed comments
- Delete auto drafts
- Optimize database tables
- Detect orphan metadata

### ☁ Cache Detection
Automatically detects:

- Redis
- Memcached
- Object Cache
- LiteSpeed Cache
- Cloudflare
- Popular page caching plugins

Also provides Apache and Nginx caching recommendations.

### 🌍 CDN Support
Supports:

- Cloudflare
- Bunny CDN
- Amazon CloudFront
- KeyCDN
- Custom CDN URLs

### 📊 Diagnostics
- Server information
- PHP configuration
- Plugin conflict detection
- Performance coverage estimate
- Core Web Vitals recommendations
- Missing image ALT scan

### 🛠 Utilities
- Export settings
- Import settings
- Reset settings
- Safe Mode
- Debug Mode
- Activity log

---

# Honest About What It Cannot Do

This plugin intentionally avoids fake optimizations.

The following require browser rendering or server-level configuration and therefore are **not falsely advertised**:

- Critical CSS generation
- Remove Unused CSS
- Remove Unused JavaScript
- Full Page Cache
- Brotli Compression
- WebP/AVIF conversion

Instead, the plugin detects these opportunities and provides recommendations.

---

# Installation

1. Download or clone this repository.

2. Upload the plugin folder into:

```
wp-content/plugins/
```

3. Activate the plugin from:

```
WordPress Admin → Plugins
```

4. Navigate to:

```
Performance Optimizer
```

5. Click:

```
Auto Optimize
```

6. Fine tune individual optimizations as required.

---

# Compatibility

Compatible with:

- WordPress 6.5+
- PHP 7.4+
- WooCommerce
- Elementor
- Beaver Builder
- Divi
- LiteSpeed Cache
- WP Rocket
- Cloudflare
- Redis Object Cache

---

# Safe By Design

Every optimization is:

- Optional
- Modular
- Easily reversible

Safe Mode instantly disables frontend optimizations for troubleshooting.

---

# Changelog

## Version 2.0.2

- Fixed LCP auto detection for entity-encoded background images.
- Improved inline style detection.
- Better preload generation for CSS hero images.

## Version 2.0.1

- Added Auto Optimize button to the WordPress admin toolbar.

## Version 2.0.0

- Added dedicated LCP optimization module.
- Added automatic hero image detection.
- Added asynchronous CSS loading.
- Improved diagnostics.
- Improved fetch priority handling.

## Version 1.0.0

- Initial release.

---

# Author

**Siddhant Srivastava**

Portfolio: https://portfolio-sid-virid.vercel.app/

GitHub: https://github.com/siddhant7701

---

# License

Licensed under the GPL v2 or later.

https://www.gnu.org/licenses/gpl-2.0.html