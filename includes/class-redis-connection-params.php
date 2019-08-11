<?php

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class Redis_Connection_Params {
	public $scheme = null;
	public $host   = 'localhost';
	public $port   = 6379;
	public $db     = 0;
	public $pwd    = null;

	public function __construct( $config_path = null ) {
		$this->get_saved_config( $config_path );
		$this->get_constants();
	}

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

	private function get_constants() {
		$keys = [ 'scheme', 'host', 'port', 'db', 'pwd' ];
		foreach ( $keys as $key ) {
			$constant = "WPR_REDIS_{$key}";
			$value    = $this->get_constant( $constant, $this->{ $key } );

			$this->{ $key } = $value;
		}
	}

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
