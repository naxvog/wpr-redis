<?php
/**
 * @author naxvog <naxvog@users.noreply.github.com>
 * @version 1.0.0
 */

defined( '\\ABSPATH' ) || exit;

return (object) [
	'scheme' => '###wpr_redis_scheme###' ?: null,
	'host'   => '###wpr_redis_host###',
	'port'   => intval( '###wpr_redis_port###' ) ?: null,
	'db'     => intval( '###wpr_redis_db###' ) ?: 0,
	'pwd'    => '###wpr_redis_pwd###' ?: null,
];
