<?php

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class Redis_Connection_Params {

	/**
	 * @var string|null Connection scheme
	 */
	public $scheme = null;

	/**
	 * @var string Connection host
	 */
	public $host = 'localhost';

	/**
	 * @var int|null Connection port
	 */
	public $port = 6379;

	/**
	 * @var int|null Connection database
	 */
	public $db = 0;

	/**
	 * @var string|null Connection password
	 */
	public $pwd = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param string|null (Optional) Configuration path.
	 */
	public function __construct( $config_path = null ) {
		$this->get_saved_config( $config_path );
		$this->get_constants();
	}

	/**
	 * Retrieves the config and sets the connection parameters
	 *
	 * @since 1.0.0
	 * @param string|null (Optional) Configuration path.
	 * @return void
	 */
	private function get_saved_config( $config_path = null ) {
		$cfg = Config::get( $config_path );
		if ( null === $cfg ) {
			return;
		}

		$this->scheme = $cfg->scheme;
		$this->host   = $cfg->host;
		$this->port   = $cfg->port;
		$this->db     = $cfg->db;
		$this->pwd    = $cfg->pwd;
	}

	/**
	 * Checks all connection related constants
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function get_constants() {
		$keys = [ 'scheme', 'host', 'port', 'db', 'pwd' ];
		foreach ( $keys as $key ) {
			$constant = "WPR_REDIS_{$key}";
			$value    = $this->get_constant( $constant, $this->{ $key } );

			$this->{ $key } = $value;
		}
	}

	/**
	 * Utility function to retrieve a specific constant
	 *
	 * @since 1.0.0
	 * @param string $name
	 * @param mixed $default (Optional)
	 * @return mixed
	 */
	private function get_constant(
		$name,
		$default = null
	) {
		$name = strtoupper( $name );
		if ( defined( $name ) ) {
			return constant( $name ) ?? $default;
		}
		return $default;
	}
} // Redis_Connection_Params
