<?php
/**
 * Plugin Name:       Organic Kratom USA Performance Optimizer
 * Plugin URI:        https://github.com/siddhant7701git
 * Description:       A modular, safe performance suite that improves Core Web Vitals (LCP, INP, CLS): true LCP preloading (including CSS background heroes), one-click optimization, defer/delay JavaScript, async CSS delivery, and database cleanup — without breaking your site.
 * Version:           2.0.2
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Siddhant Srivastava
 * Author URI:        https://portfolio-sid-virid.vercel.app/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ultimate-performance-optimizer
 * Domain Path:       /languages
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ---------------------------------------------------------------------------
 * Plugin constants.
 * ---------------------------------------------------------------------------
 */
define( 'UPO_VERSION', '2.0.2' );
define( 'UPO_FILE', __FILE__ );
define( 'UPO_BASENAME', plugin_basename( __FILE__ ) );
define( 'UPO_DIR', plugin_dir_path( __FILE__ ) );
define( 'UPO_URL', plugin_dir_url( __FILE__ ) );
define( 'UPO_INC', UPO_DIR . 'includes/' );
define( 'UPO_ASSETS', UPO_URL . 'assets/' );
define( 'UPO_MIN_PHP', '7.4' );
define( 'UPO_MIN_WP', '6.5' );
define( 'UPO_OPTION', 'upo_settings' );
define( 'UPO_TEXTDOMAIN', 'ultimate-performance-optimizer' );

// Branding — displayed throughout the admin UI.
define( 'UPO_NAME', 'Organic Kratom USA Performance Optimizer' );
define( 'UPO_AUTHOR', 'Siddhant Srivastava' );
define( 'UPO_AUTHOR_URL', 'https://portfolio-sid-virid.vercel.app/' );
define( 'UPO_GITHUB_URL', 'https://github.com/siddhant7701git' );

/*
 * ---------------------------------------------------------------------------
 * Environment guard.
 *
 * We refuse to boot on unsupported environments instead of throwing fatals.
 * ---------------------------------------------------------------------------
 */
require_once UPO_INC . 'Requirements.php';

$upo_requirements = new Requirements( UPO_MIN_PHP, UPO_MIN_WP );

if ( ! $upo_requirements->are_met() ) {
	$upo_requirements->render_notice();
	return;
}

/*
 * ---------------------------------------------------------------------------
 * PSR-4 autoloader (no Composer required).
 * ---------------------------------------------------------------------------
 */
require_once UPO_INC . 'Autoloader.php';

Autoloader::register( __NAMESPACE__, UPO_INC );

/*
 * ---------------------------------------------------------------------------
 * Activation / deactivation lifecycle hooks.
 *
 * These must be registered in the main file (WordPress requirement).
 * ---------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, static function (): void {
	Lifecycle\Activator::activate();
} );

register_deactivation_hook( __FILE__, static function (): void {
	Lifecycle\Deactivator::deactivate();
} );

/*
 * ---------------------------------------------------------------------------
 * Boot the plugin after all plugins are loaded so we can safely detect
 * WooCommerce, Elementor, caching plugins, etc.
 * ---------------------------------------------------------------------------
 */
add_action( 'plugins_loaded', static function (): void {
	Plugin::instance()->boot();
}, 5 );

/**
 * Convenience accessor for the main plugin instance.
 *
 * @return Plugin
 */
function upo(): Plugin {
	return Plugin::instance();
}
