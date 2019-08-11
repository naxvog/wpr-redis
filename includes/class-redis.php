<?php

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class Redis {

	protected static $engine = null;
	protected static $client = null;

	private static $connected = false;

	public static function init( $config_path = null ) {
		self::$engine = class_exists( '\\Redis' ) ? 'pecl' : 'predis';

		try {
			if ( ! self::is_active() ) {
				$params = new Redis_Connection_Params( $config_path );
				self::connect( $params );
				self::$connected = true;
			}
		} catch ( \Exception $ex ) {
			// Handle connection error
			if ( true
				&& function_exists( 'set_transient' )
				&& did_action( 'init' )
			) {
				self::add_connection_exception_transient( $ex );
			} else {
				add_action(
					'init',
					function() use ( $ex ) {
						self::add_connection_exception_transient( $ex );
					}
				);
			}
		}
		return self::$connected;
	}

	private static function add_connection_exception_transient(
		\Exception $ex
	) {
		set_transient(
			'wpr_redis_connection_exception',
			"Connection failed: {$ex->getMessage()}",
			DAY_IN_SECONDS
		);
	}

	/**
	 * Connects to the redis server using the approprate engine
	 *
	 * @param Redis_Connection_Params $cp
	 * @return void
	 */
	public static function connect(
		Redis_Connection_Params $cp
	) {
		if ( 'pecl' === self::$engine ) {
			self::$client = new \Redis();
			if ( null !== $cp->pwd ) {
				self::$client->auth( $cp->pwd );
			}
			if ( 'unix' === $cp->scheme ) {
				self::$client->connect( $cp->host );
			} else {
				self::$client->connect( $cp->host, $cp->port );
			}
			self::$client->select( $cp->db );
		} else {
			$args = [
				'scheme' => $cp->scheme,
			];
			if ( 'unix' === $cp->scheme ) {
				$args += [
					'path' => $cp->host,
				];
			} else {
				$args += [
					'host' => $cp->host,
					'port' => $cp->port,
				];
			}
			$options = [
				'parameters' => [
					'database' => $cp->db,
				],
			];
			if ( null !== $cp->pwd ) {
				$options['parameters']['password'] = $cp->pwd;
			}
			self::$client = new \Predis\Client( $args, $options );
		}
	}

	public static function is_active() {
		return self::$connected;
	}

	public static function mtime(
		$key
	) {
		return self::get( "{$key}-modified" );
	}

	public static function exists(
		$key
	) {
		$key = self::key( $key );
		return self::$client->exists( $key );
	}

	public static function add(
		$key,
		$value
	) {
		return [
			self::set( $key, $value, self::expiry_time() ),
			self::set( $key . '-modified', time(), self::expiry_time() ),
		];
	}

	public static function get(
		$key
	) {
		return self::$client->get( self::key( $key ) );
	}

	protected static function set(
		$key,
		$value,
		$expiry = 0
	) {
		$key = self::key( $key );
		if ( 0 === $expiry ) {
			return self::$client->set( $key, $value );
		}
		if ( 'pecl' === self::$engine ) {
			return self::$client->setEx( $key, $expiry, $value );
		} else {
			return self::$client->setex( $key, $value, $expiry );
		}
	}

	protected static function key(
		$base
	) {
		global $table_prefix;
		$base = $table_prefix . $base;
		if ( defined( 'WPR_REDIS_SALT' ) ) {
			$base = WPR_REDIS_SALT . $base;
		}
		return $base;
	}


	/**
	 * Filter URLs to delete all caching files from a domain.
	 *
	 * @since 1.0.0
	 * @param array     URLs that will be returned.
	 * @param string    The language code.
	 */
	public static function clean_domains(
		$urls,
		$lang
	) {
		return self::clean_files( $urls );
	}

	/**
	 * Fires after the home cache file was deleted.
	 *
	 * @since 1.0.0
	 * @param string $root The path of home cache file.
	 * @param string $lang The current lang to purge.
	*/
	public static function clean_home(
		$root,
		$lang
	) {
		return self::clear( $root );
	}

	/**
	 * Filter URLs that the cache file to be deleted.
	 *
	 * @since 1.0.0
	 * @param array URLs that will be returned.
	 */
	public static function clean_files(
		$urls
	) {
		foreach ( $urls as $url ) {
			$file = get_rocket_parse_url( $url );
			if ( apply_filters( 'rocket_url_no_dots', false ) ) {
				$file['host'] = str_replace( '.', '_', $file['host'] );
			}
			$root = WP_ROCKET_CACHE_PATH . $file['host'];
			self::clear( $root );
		}
		return [];
	}

	/**
	 * Clears the cache based on a given prefix
	 *
	 * @since 1.0.0
	 * @param string $key
	 * @return void
	 */
	public static function clear(
		$key = ''
	) {
		static $once;
		if ( ! isset( $once ) ) {
			$closure = self::lua_flush_closure( $key );
			$closure();
			$once = true;
		}
	}

	/**
	 * Returns a closure ready to be called to flush selectively.
	 *
	 * @return callable
	 */
	protected static function lua_flush_closure(
		$key = ''
	) {
		$base_key = self::key( $key );
		return function () use ( $base_key ) {
			$script = <<<LUA
                local cur = 0
                local i = 0
                local tmp
                repeat
                    tmp = redis.call('SCAN', cur, 'MATCH', '{$base_key}*')
                    cur = tonumber(tmp[1])
                    if tmp[2] then
                        for _, v in pairs(tmp[2]) do
                            redis.call('del', v)
                            i = i + 1
                        end
                    end
                until 0 == cur
                return i
LUA;

			return call_user_func_array(
				[ self::$client, 'eval' ],
				'predis' === self::$engine ? [ $script, 0 ] : [ $script ]
			);
		};
	}

	/**
	 * Retrieved set WP Rocket expiry time
	 *
	 * @return int
	 */
	protected static function expiry_time() {
		static $expiry;
		if ( ! isset( $expiry ) ) {
			$expiry = get_rocket_purge_cron_interval();
		}
		return $expiry;
	}
} // Redis
