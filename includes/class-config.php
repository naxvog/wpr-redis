<?php
/**
 * @author naxvog <naxvog@users.noreply.github.com>
 * @version 1.0.0
 */

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class Config {
	/**
	 * @var string Configuration path
	 */
	const PATH = '/wpr-redis-config/config.php';

	const SETTINGS_SLUG = 'wpr-redis';

	private $options_page = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
	}

	/**
	 * Returns the settings page url
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function url() {
		$url = add_query_arg(
			[
				'page' => self::SETTINGS_SLUG,
			],
			admin_url( 'options-general.php' )
		);
		return $url;
	}

	/**
	 * Adds a plugin action link to the settings page
	 *
	 * @since 1.0.0
	 * @param string[]
	 * @return string[]
	 */
	public function add_settings_link( $links ) {
		$url  = self::url();
		$link = "<a href=\"{$url}\">" . __( 'Settings', 'wpr-redis' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Adds the options page to the menu and registers all necessary hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
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

	/**
	 * Enqueues all necessary resources
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_resources() {
		$handle = 'wpr-redis-admin-style';
		wp_register_style(
			$handle,
			WPR_REDIS_URL . 'assets/css/admin.css',
			[],
			WPR_Redis::meta( 'Version' )
		);
		wp_enqueue_style( $handle );
	}

	/**
	 * Adds all setting fields
	 *
	 * @since 1.0.0
	 * @return void
	 */
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

	/**
	 * Description for the connection parameters section
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function settings_section() {
		_e( 'Setup your Redis connection', 'wpr-redis' );
	}

	/**
	 * Renders a settings field
	 *
	 * @since 1.0.0
	 * @param array $setting
	 * @return void
	 */
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

	/**
	 * Renders the page template
	 *
	 * @since 1.0.0
	 * @param bool $echo
	 * @return mixed
	 */
	public function page_template( $echo = true ) {
		$path = WPR_REDIS_VIEW_PATH . '/admin-settings.php';
		if ( false === $echo ) {
			ob_start();
			require $path;
			return ob_get_clean();
		}
		return require $path;
	}

	/**
	 * Processes settings actions
	 *
	 * @since 1.0.0
	 * @param WP_Screen $screen
	 * @return void
	 */
	public function process_actions( $screen ) {
		// TODO: Add nonce verification
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

	/**
	 * Returns all defined settings
	 *
	 * @since 1.0.0
	 * @return array
	 */
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

	/**
	 * Registers all settings fields
	 *
	 * @since 1.0.0
	 * @return void
	 */
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

	/**
	 * Triggers a config save on shutdown if a setting was modified
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function trigger_save() {
		static $once = false;
		if ( ! $once ) {
			$once = true;
			add_action( 'shutdown', [ Config::class, 'save' ] );
		}
	}

	/**
	 * Returns the full config file path
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function path() {
		return WP_CONTENT_DIR . self::PATH;
	}

	/**
	 * Retrieves the config path
	 *
	 * @since 1.0.0
	 * @param string|null $path (Optional) Configuration path.
	 * @return mixed|null
	 */
	public static function get( $path = null ) {
		$path = $path ?? self::path();
		if ( is_readable( $path ) ) {
			return require $path;
		}
		return null;
	}

	/**
	 * Saves the configuration to its own file using the config template
	 *
	 * @since 1.0.0
	 */
	public static function save() {
		$keys = array_keys( self::settings() );
		$repl = [];
		foreach ( $keys as $key ) {
			$repl[ "###$key###" ] = get_option( $key );
		}
		$filesystem = rocket_direct_filesystem();

		$tpl_path = WPR_REDIS_INCLUDE_PATH . '/config-template.php';
		$template = $filesystem->get_contents( $tpl_path );
		$contents = strtr( $template, $repl );

		$path = self::path();
		wp_mkdir_p( dirname( $path ) );
		$filesystem->put_contents( $path, $contents );

		rocket_generate_advanced_cache_file();
	}
} // Config
