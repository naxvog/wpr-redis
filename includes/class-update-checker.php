<?php

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class Update_Checker {

	const GITHUB_REPO = 'https://github.com/naxvog/wpr-redis/';

	public function __construct() {
		require_once WPR_REDIS_VENDOR_PATH . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
		$update_checker = \Puc_v4_Factory::buildUpdateChecker(
			self::GITHUB_REPO,
			WPR_REDIS_FILE,
			'wpr-redis'
		);
	}

} // Update_Checker
