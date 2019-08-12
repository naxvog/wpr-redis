<?php
/**
 * @author naxvog <naxvog@users.noreply.github.com>
 * @version 1.0.1
 */

namespace WPR_Redis;

defined( '\\ABSPATH' ) || exit;

class Update_Checker {

	/**
	 * @var string URL to the GitHub repository
	 */
	const GITHUB_REPO = 'https://github.com/naxvog/wpr-redis/';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$slug = 'wpr-redis';
		add_filter(
			"puc_manual_check_link-{$slug}",
			[ $this, 'manual_check_link' ]
		);
		require_once WPR_REDIS_VENDOR_PATH . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
		$update_checker = \Puc_v4_Factory::buildUpdateChecker(
			self::GITHUB_REPO,
			WPR_REDIS_FILE,
			$slug
		);
	}

	/**
	 * Returns the translatable check for updates link label
	 *
	 * @since 1.0.1
	 * @return string
	 */
	public function manual_check_link() {
		return __( 'Check for updates', 'wpr-redis' );
	}

} // Update_Checker
