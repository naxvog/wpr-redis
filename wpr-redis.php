<?php
/**
 * Plugin Name: WP Rocket Redis
 * Plugin URI: https://github.com/naxvog/wpr-redis/
 * Description: Addon to WP Rocket that allows storage of cache files in Redis.
 * Version: 1.0.1
 * Author: naxvog <naxvog@users.noreply.github.com>
 * Author URI: https://github.com/naxvog/
 * Text Domain: wpr-redis
 * Domain Path: languages
 * Requires PHP: 7.0
 * Licence: GPLv3 or later
 *
 * @author naxvog <naxvog@users.noreply.github.com>
 * @wordpress-plugin
 */

defined( '\\ABSPATH' ) || exit;

define( 'WPR_REDIS_FILE', __FILE__ );
define( 'WPR_REDIS_BASENAME', plugin_basename( WPR_REDIS_FILE ) );
define( 'WPR_REDIS_PATH', realpath( plugin_dir_path( WPR_REDIS_FILE ) ) );
define( 'WPR_REDIS_URL', plugin_dir_url( WPR_REDIS_FILE ) );

define( 'WPR_REDIS_INCLUDE_PATH', WPR_REDIS_PATH . '/includes' );
define( 'WPR_REDIS_OVERRIDE_PATH', WPR_REDIS_PATH . '/overrides' );
define( 'WPR_REDIS_VENDOR_PATH', WPR_REDIS_PATH . '/vendor' );
define( 'WPR_REDIS_VIEW_PATH', WPR_REDIS_PATH . '/views' );

require_once WPR_REDIS_VENDOR_PATH . '/autoload.php';

$loader = require WPR_REDIS_INCLUDE_PATH . '/class-autoloader.php';
$loader->register();
$loader->add_namespace( 'WPR_Redis', WPR_REDIS_INCLUDE_PATH );
$loader->add_namespace( 'WP_Rocket', WPR_REDIS_OVERRIDE_PATH . '/wp-rocket' );

function wpr_redis() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new WPR_Redis\WPR_Redis();
	}
	return $instance;
}

wpr_redis();
