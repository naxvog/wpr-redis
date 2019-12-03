<?php
/**
 * @author naxvog <naxvog@users.noreply.github.com>
 * @version 1.0.0
 */

namespace WP_Rocket\Buffer;
use \WPR_Redis\Redis;

defined( '\\ABSPATH' ) || exit;

/**
 * Spoof for internal is_readable function.
 *
 * @since 1.0.0
 * @param string $filename
 * @return bool
 */
function is_readable( $filename ) {
	if ( Redis::is_active() ) {
		return Redis::exists( $filename );
	}
	return \is_readable( $filename );
}

/**
 * Spoof for internal file_exits function.
 *
 * @since 1.0.0
 * @param string $filename
 * @return bool
 */
function file_exists( $filename ) {
	if ( Redis::is_active() ) {
		return Redis::exists( $filename );
	}
	return \file_exists( $filename );
}

/**
 * Spoof for internal filemtime function.
 *
 * @since 1.0.0
 * @param string $filename
 * @return int
 */
function filemtime( $filename ) {
	if ( Redis::is_active() ) {
		return Redis::mtime( $filename );
	}
	return \filemtime( $filename );
}

/**
 * Spoof for internal readfile functon.
 *
 * @since 1.0.0
 * @param string $filename
 * @param bool $use_include_path
 * @param resource $context
 * @return int
 */
function readfile( $filename, $use_include_path = false, $context = null ) {
	if ( Redis::is_active() ) {
		$content = Redis::get( $filename );
		echo $content;
		return strlen( $content ) ?: false;
	}
	return \readfile( $filename, $use_include_path, $context );
}

/**
 * Spoof for internal readgzfile function.
 *
 * @since 1.0.0
 * @param string $filename
 * @param int $use_include_path
 * @return int
 */
function readgzfile( $filename, $use_include_path = 0 ) {
	if ( Redis::is_active() ) {
		$content = Redis::get( $filename );
		echo gzdecode( $content );
		return strlen( $content ) ?: false;
	}
	return \readgzfile( $filename, $use_include_path );
}

/**
 * Spoof for the rocket internal function.
 *
 * @since 1.0.0
 * @param string $filename
 * @param string $content
 * @return bool
 */
function rocket_put_content( $filename, $content ) {
	if ( Redis::is_active() ) {
		return Redis::add( $filename, $content );
	}
	return \rocket_put_content( $filename, $content );
}
