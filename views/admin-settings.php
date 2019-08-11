<?php

defined( '\\ABSPATH' ) || exit;

$is_connected = WPR_Redis\Redis::init();
$is_active    = WPR_Redis\WPR_Redis::is_integrated();

$integrate_action = add_query_arg( 'action', 'integrate' );
$remove_action    = add_query_arg( 'action', 'remove_integration' );

?>
<div class="wrap">
	<h1><?php _e( 'WP-Rocket Redis Settings', 'wpr-redis' ); ?></h1>
	<?php if ( $is_connected && $is_active ) : ?>
		<a href="<?php echo $remove_action; ?>" class="button button-secondary">
			<?php _e( 'Remove WPR Redis Caching Integration', 'wpr-redis' ); ?>
		</a>
	<?php elseif ( $is_connected ) : ?>
		<a href="<?php echo $integrate_action; ?>" class="button button-secondary">
			<?php _e( 'Integrate WPR Redis Caching', 'wpr-redis' ); ?>
		</a>
	<?php endif; ?>
	<div class="connection-status">
		<h2><?php _e( 'Status', 'wpr-redis' ); ?></h2>
		<table>
			<tr>
				<td><?php _e( 'Redis Connection Status:', 'wpr-redis' ); ?></td>
				<td>
					<?php if ( $is_connected ) : ?>
						<span class="pill pill-active">
							<?php _e( 'Connected', 'wpr-redis' ); ?>
						</span>
					<?php else : ?>
						<span class="pill pill-inactive">
							<?php _e( 'Disconnected', 'wpr-redis' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Extension Status:' ); ?></td>
				<td>
					<?php if ( $is_active ) : ?>
						<span class="pill pill-active">
							<?php _e( 'Integrated', 'wpr-redis' ); ?>
						</span>
					<?php else : ?>
						<span class="pill pill-inactive">
							<?php _e( 'Pending Integration', 'wpr-redis' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	</div>
	<form method="post" action="options.php">
		<?php settings_fields( 'wpr_redis' ); ?>
		<?php do_settings_sections( $this->options_page ); ?>
		<?php submit_button(); ?>
	</form>
</div>
