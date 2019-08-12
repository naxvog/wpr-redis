<?php
/**
 * @author naxvog <naxvog@users.noreply.github.com>
 * @version 1.0.1
 */

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class WPR_Redis {
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		register_activation_hook(
			WPR_REDIS_FILE,
			[ WPR_Redis::class, 'activation' ]
		);
		register_deactivation_hook(
			WPR_REDIS_FILE,
			[ WPR_Redis::class, 'deactivation' ]
		);
		add_action( 'plugins_loaded', [ $this, 'init' ], 11 );
	}

	/**
	 * Retrieves plugin metadata
	 *
	 * @since 1.0.1
	 * @param string $key
	 * @return string
	 */
	public static function meta( $key ) {
		static $metadata = null;
		if ( null === $metadata ) {
			$metadata = get_file_data(
				WPR_REDIS_FILE,
				array(
					'Name'        => 'Plugin Name',
					'PluginURI'   => 'Plugin URI',
					'Version'     => 'Version',
					'Description' => 'Description',
					'Author'      => 'Author',
					'AuthorURI'   => 'Author URI',
					'TextDomain'  => 'Text Domain',
					'DomainPath'  => 'Domain Path',
					'Network'     => 'Network',
					'RequiresWP'  => 'Requires at least',
					'RequiresPHP' => 'Requires PHP',
				)
			);
		}
		return isset( $metadata[ $key ] ) ? $metadata[ $key ] : null;
	}

	/**
	 * Initialization method
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		$check = $this->check_dependencies();
		if ( true !== $check ) {
			self::admin_notice( 'missing dependencies: %s', 'error', $check );
			return;
		}
		$filter    = 'rocket_advanced_cache_file';
		$filter_fn = [ WPR_Redis::class, 'maybe_alter_adv_cache' ];
		if ( ! has_filter( $filter, $filter_fn ) ) {
			add_filter( $filter, $filter_fn );
		}
		$connection_error = get_transient( 'wpr_redis_connection_exception' );
		if ( $connection_error ) {
			if ( Redis::is_active() ) {
				delete_transient( 'wpr_redis_connection_exception' );
			} else {
				self::admin_notice( $connection_error, 'error', [], 'wpr-ex' );
			}
		}
		add_action(
			'admin_enqueue_scripts',
			[ $this, 'enqueue_admin_resources' ]
		);
		add_action(
			'wp_ajax_wpr_redis_notice_handler',
			[ $this, 'ajax_clear_notice' ]
		);
		new Config();
		new Update_Checker();
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Loads the plugin textdomain
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wpr-redis',
			false,
			basename( WPR_REDIS_PATH ) . '/languages'
		);
	}

	/**
	 * Checks wheather the advanced cache integration is set.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_integrated() {
		$adv_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
		$filesystem     = rocket_direct_filesystem();
		if ( $filesystem->exists( $adv_cache_path ) ) {
			$contents = $filesystem->get_contents( $adv_cache_path );
			return ! ! strpos( $contents, '$wpr_redis_path' );
		}
		return false;
	}

	/**
	 * Enqueues admin resources
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_admin_resources() {
		wp_register_script(
			'wpr_redis_admin',
			WPR_REDIS_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPR_Redis::meta( 'Version' ),
			true
		);
	}

	/**
	 * Ajax handler to clear notices
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_clear_notice() {
		if ( ! isset( $_POST['id'] ) ) {
			wp_die();
		}
		$id = $_POST['id'];
		if ( 'wpr-ex' === $id ) {
			delete_transient( 'wpr_redis_connection_exception' );
		}
		wp_send_json_success();
		wp_die();
	}

	/**
	 * Prints an admin notice
	 *
	 * @since 1.0.0
	 * @param  string $msg
	 * @param  string $type
	 * @param  array  $args
	 * @param  string $id
	 * @return string Id calculated.
	 */
	public static function admin_notice(
		$msg,
		$type = 'success',
		$args = null,
		$id = null
	) {
		$id   = $id ?? md5( $msg );
		$args = $args ?? [];
		add_action(
			'admin_notices',
			function() use ( $msg, $type, $args, $id ) {
				wp_enqueue_script( 'wpr_redis_admin' );
				$id = esc_attr( $id );
				echo "<div class=\"notice notice-wpr-redis notice-{$type} is-dismissible\" data-id=\"{$id}\">";
				echo '<p>';
				echo '<strong>';
				_e( 'WP Rocket Redis:', 'wpr-redis' );
				echo '</strong> ';
				vprintf( $msg, $args );
				echo '</p>';
				echo '</div>';
			}
		);
		return $id;
	}

	/**
	 * Checks if all dependencies are installed
	 *
	 * @since 1.0.0
	 * @return true|string[] Array of errors in case of missing dependencies
	 */
	private function check_dependencies() {
		$errors = [];
		if ( ! is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
			$errors[] = 'WP Rocket not installed or inactive';
		}
		return ! ! $errors ? $errors : true;
	}

	/**
	 * Checks if the soon to be written WP Rocket advanced cache file contains
	 * the standard WP Rocket buffer class.
	 *
	 * @since 1.0.0
	 * @param string $buffer
	 * @return string
	 */
	public static function maybe_alter_adv_cache(
		$buffer
	) {
		if ( ! Redis::is_active() && ! Redis::init() ) {
			return $buffer;
		}
		$regex = "/^(\s*)('(WP_Rocket[\\\]{2}Buffer[\\\]{2}Cache)'.+)=>.+class-cache.php',$/im";
		if ( preg_match( $regex, $buffer, $matches ) ) {
			$buffer = self::alter_adv_cache_classes( $buffer, $matches );
		};
		$buffer = preg_replace(
			[
				'/^((\s*)\$rocket_path\s*=.+;\n)/im',
				'/^((\s*)["\']cache_dir_path["\']\s*=.+,\n)/im',
			],
			[
				"$1$2\$wpr_redis_path = '" . \WPR_REDIS_PATH . "';\n",
				"$1$2'wpr_config_path' => '" . Config::path() . "',\n",
			],
			$buffer
		);
		return $buffer;
	}

	/**
	 * Changes the soon to be written content of the advanced cache file.
	 *
	 * @since 1.0.0
	 * @param string $buffer
	 * @param string[] $matches
	 * @return string
	 */
	protected static function alter_adv_cache_classes(
		$buffer,
		$matches
	) {
		list( $line, $indent, $key_full, $key ) = $matches;

		$classes = [
			Buffer\Redis_Cache::class      => 'overrides/wp-rocket/buffer/class-redis-cache.php',
			Redis_Connection_Params::class => 'includes/class-redis-connection-params.php',
			Config::class                  => 'includes/class-config.php',
			Redis::class                   => 'includes/class-redis.php',
		];

		$baselen  = strlen( $indent . $key_full );
		$old_inst = 'new \\' . strtr( $key, [ '\\\\' => '\\' ] );
		$new_inst = 'new \\' . strtr( Buffer\Redis_Cache::class, [ '\\\\' => '\\' ] );

		$lines = [ $line ];
		foreach ( $classes as $fq_class_name => $path ) {
			$class   = strtr( $fq_class_name, [ '\\' => '\\\\' ] );
			$key     = "{$indent}'{$class}'";
			$offset  = str_repeat( ' ', $baselen - strlen( $key ) );
			$lines[] = "{$key}{$offset}=> \$wpr_redis_path . '/{$path}',";
		}

		$replace = [
			$old_inst => $new_inst,
			$line     => implode( "\n", $lines ),
		];

		return strtr( $buffer, $replace );
	}

	/**
	 * Activation hook regenerates the advanced cache file after including our
	 * filter to edit it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activation() {
		if ( function_exists( 'rocket_generate_advanced_cache_file' ) ) {
			add_filter(
				'rocket_advanced_cache_file',
				[ WPR_Redis::class, 'maybe_alter_adv_cache' ]
			);
			rocket_generate_advanced_cache_file();
			rocket_clean_cache_dir();
		}
	}

	/**
	 * Deactivation hook removes our edits to the advanced cache file.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivation() {
		if ( function_exists( 'rocket_generate_advanced_cache_file' ) ) {
			remove_filter(
				'rocket_advanced_cache_file',
				[ WPR_Redis::class, 'maybe_alter_adv_cache' ]
			);
			rocket_generate_advanced_cache_file();
		}
	}
} // WPR_Redis
