<?php
/**
 * @author naxvog <naxvog@users.noreply.github.com>
 * @version 1.0.0
 */

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class Config {
	const PATH = '/wpr-redis-config/config.php';

	const SETTINGS_SLUG = 'wpr-redis';

	private $options_page = null;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
	}

	public static function url() {
		$url = add_query_arg(
			[
				'page' => self::SETTINGS_SLUG,
			],
			admin_url( 'options-general.php' )
		);
		return $url;
	}

	public function add_settings_link( $links ) {
		$url  = self::url();
		$link = "<a href=\"{$url}\">" . __( 'Settings', 'wpr-redis' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	public function add_menu() {
		$this->options_page = add_options_page(
			__( 'WP Rocket Redis Settings', 'wpr-redis' ),
			__( 'WP Rocket Redis' ),
			apply_filters( 'wpr_redis_config_capability', 'manage_options' ),
			self::SETTINGS_SLUG,
			[ $this, 'page_template' ]
		);
		if ( $this->options_page ) {
			add_filter(
				'plugin_action_links_' . WPR_REDIS_BASENAME,
				[ $this, 'add_settings_link' ]
			);
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_resources' ] );
			add_action( 'admin_init', [ $this, 'register_settings' ], 10 );
			add_action( 'admin_init', [ $this, 'settings_fields' ], 11 );
			add_action( 'current_screen', [ $this, 'process_actions' ] );
		}
	}

	public function enqueue_resources() {
		$handle       = 'wpr-redis-admin-style';
		$partial_path = '/assets/css/admin.css';
		$version      = dechex( filemtime( WPR_REDIS_PATH . $partial_path ) );
		wp_register_style(
			$handle,
			WPR_REDIS_URL . $partial_path,
			[],
			$version
		);
		wp_enqueue_style( $handle );
	}

	public function settings_fields() {
		$section = 'wpr_redis';
		add_settings_section(
			$section,
			'Redis Connection Parameters',
			[ $this, 'settings_section' ],
			$this->options_page
		);
		foreach ( self::settings() as $name => $args ) {
			add_settings_field(
				$name,
				"<label for=\"{$name}\">{$args['label']}</label>",
				[ $this, 'settings_field' ],
				$this->options_page,
				$section,
				array_merge( [ 'name' => $name ], $args )
			);
		}
	}

	public function settings_section() {
		_e( 'Setup your Redis connection', 'wpr-redis' );
	}

	public function settings_field( $setting ) {
		$constant = strtoupper( "WPR_{$setting['name']}" );
		$extra    = '';
		$type     = 'integer' === $setting['setting'][0]
			? 'number'
			: 'text';
		if ( 'number' === $type ) {
			$extra .= ' min="0" step="1"';
		}
		if ( defined( $constant ) ) {
			$value  = constant( $constant );
			$extra .= ' disabled';
		} else {
			$value = get_option( $setting['name'] );
		}
		?>
		<input type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $setting['name'] ); ?>"
			name="<?php echo esc_attr( $setting['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php echo trim( $extra ); ?>/>
		<?php
	}

	public function page_template( $echo = true ) {
		$path = WPR_REDIS_VIEW_PATH . '/admin-settings.php';
		if ( false === $echo ) {
			ob_start();
			require $path;
			return ob_get_clean();
		}
		return require $path;
	}

	public function process_actions( $screen ) {
		if ( false
			|| $this->options_page !== $screen->id
			|| ! isset( $_REQUEST['action'] )
			|| ! current_user_can( apply_filters( 'wpr_redis_config_capability', 'manage_options' ) )
		) {
			return;
		}
		if ( 'integrate' === $_REQUEST['action'] ) {
			rocket_generate_advanced_cache_file();
			rocket_clean_cache_dir();
			wp_safe_redirect( remove_query_arg( 'action' ) );
			exit;
		} elseif ( 'remove_integration' === $_REQUEST['action'] ) {
			remove_filter(
				'rocket_advanced_cache_file',
				[ WPR_Redis::class, 'maybe_alter_adv_cache' ]
			);
			rocket_generate_advanced_cache_file();
			wp_safe_redirect( remove_query_arg( 'action' ) );
			exit;
		}
	}

	public static function settings() {
		$settings = [
			'wpr_redis_scheme' => [
				'label'   => __( 'Scheme', 'wpr-redis' ),
				'setting' => [ 'string', 'sanitize_text_field', null ],
			],
			'wpr_redis_host'   => [
				'label'   => __( 'Host', 'wpr-redis' ),
				'setting' => [ 'string', 'sanitize_text_field', 'localhost' ],
			],
			'wpr_redis_port'   => [
				'label'   => __( 'Port', 'wpr-redis' ),
				'setting' => [ 'integer', 'sanitize_text_field', 6379 ],
			],
			'wpr_redis_db'     => [
				'label'   => __( 'Database', 'wpr-redis' ),
				'setting' => [ 'integer', 'sanitize_text_field', 0 ],
			],
			'wpr_redis_pwd'    => [
				'label'   => __( 'Password', 'wpr-redis' ),
				'setting' => [ 'string', 'sanitize_text_field', null ],
			],
		];
		return $settings;
	}

	public function register_settings() {
		$group = 'wpr_redis';
		foreach ( self::settings() as $name => $args ) {
			list( $type, $sanitize_cb, $default ) = $args['setting'];
			register_setting(
				$group,
				$name,
				[
					'type'              => $type,
					'sanitize_callback' => $sanitize_cb,
					'default'           => $default,
				]
			);
			add_action( "update_option_$name", [ $this, 'trigger_save' ] );
		}
	}

	public function trigger_save() {
		var_dump( 'trigger' );
		static $once = false;
		if ( ! $once ) {
			$once = true;
			add_action( 'shutdown', [ Config::class, 'save' ] );
		}
	}

	public static function path() {
		return WP_CONTENT_DIR . self::PATH;
	}

	public static function get( $path = null ) {
		$path = $path ?? self::path();
		if ( is_readable( $path ) ) {
			return require $path;
		}
		return null;
	}

	public static function save() {
		var_dump( __LINE__, __METHOD__ );
		$keys = array_keys( self::settings() );
		$repl = [];
		foreach ( $keys as $key ) {
			$repl[ "###$key###" ] = get_option( $key );
		}
		$tpl_path = WPR_REDIS_INCLUDE_PATH . '/config-template.php';
		$template = file_get_contents( $tpl_path );
		$contents = strtr( $template, $repl );

		$path = self::path();
		wp_mkdir_p( dirname( $path ) );
		$fh = fopen( $path, 'w+' );
		fwrite( $fh, $contents );
		fclose( $fh );

		rocket_generate_advanced_cache_file();
	}

	private static function contents() {
		$path = WPR_REDIS_INCLUDE_PATH . '/config-template.php';
		return file_get_contents( $path );
	}
} // Config
