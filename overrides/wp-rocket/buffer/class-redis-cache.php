<?php

namespace WPR_Redis\Buffer;
use \WP_Rocket\Buffer\Cache;
use \WP_Rocket\Buffer\Tests;
use \WP_Rocket\Buffer\Config;
use \WPR_Redis\Redis;

defined( '\\ABSPATH' ) || exit;

class Redis_Cache extends Cache {
	/**
	 * @var string Caching method name
	 */
	protected $process_id = 'redis caching process';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @param Tests  $tests  Tests instance.
	 * @param Config $config Config instance.
	 * @param array  $args   {
	 *                       An array of arguments.
	 *
	 *     @type string $cache_dir_path  Path to the directory containing the cache files.
	 * }
	 */
	public function __construct(
		Tests $tests,
		Config $config,
		array $args
	) {
		Redis::init( $args['wpr_config_path'] );

		require_once dirname( __FILE__ ) . '/functions.php';

		add_filter( 'rocket_clean_domain_urls', [ Redis::class, 'clean_domains' ], 10, 2 );
		add_filter( 'rocket_clean_files', [ Redis::class, 'clean_files' ], 10, 1 );
		add_action( 'after_rocket_clean_home', [ Redis::class, 'clean_home' ], 10, 2 );

		parent::__construct( $tests, $config, $args );
	}
}
