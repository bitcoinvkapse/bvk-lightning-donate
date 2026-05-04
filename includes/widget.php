<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bvkld_render_widget() {
	$address     = bvkld_get_lightning_address();
	$title       = get_option( 'bvkld_title', __( 'Did you enjoy this article? Send some sats ⚡', 'bvk-lightning-donate' ) );
	$subtitle    = get_option( 'bvkld_subtitle', '' );
	$amounts_raw = get_option( 'bvkld_amounts', '1000,5000,21000' );
	$amounts     = bvkld_parse_amounts( $amounts_raw );
	$wallet_text = get_option( 'bvkld_wallet_link_text', __( 'No wallet? Download one here', 'bvk-lightning-donate' ) );
	$wallet_url  = get_option( 'bvkld_wallet_link_url', '' );

	ob_start();
	?>
	<div class="bvkld-wrap">
		<h3 class="bvkld-title"><?php echo esc_html( $title ); ?></h3>
		<?php if ( $subtitle !== '' ) : ?>
			<p class="bvkld-subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<div class="bvkld-box">
			<p class="bvkld-steps">
				<strong>1.</strong> <?php esc_html_e( 'Choose amount', 'bvk-lightning-donate' ); ?>
				<span class="bvkld-dot">·</span>
				<strong>2.</strong> <?php esc_html_e( 'Click "Send via Lightning"', 'bvk-lightning-donate' ); ?>
			</p>

			<div class="bvkld-amounts">
				<?php foreach ( $amounts as $i => $sats ) : ?>
					<button type="button" class="bvkld-amt <?php echo $i === 0 ? 'bvkld-active' : ''; ?>"
						data-sats="<?php echo esc_attr( $sats ); ?>">
						<?php echo esc_html( number_format_i18n( $sats ) ); ?> sats
					</button>
				<?php endforeach; ?>
				<button type="button" class="bvkld-amt bvkld-custom-toggle" data-sats="0">
					<?php esc_html_e( 'custom', 'bvk-lightning-donate' ); ?>
				</button>
			</div>

			<div class="bvkld-custom-input" hidden>
				<input type="number" min="1" step="1" class="bvkld-custom-amount"
					placeholder="<?php esc_attr_e( 'Enter amount in sats', 'bvk-lightning-donate' ); ?>" />
			</div>

			<button type="button" class="bvkld-send">
				<span class="bvkld-send-label">⚡ <?php esc_html_e( 'Send via Lightning', 'bvk-lightning-donate' ); ?></span>
				<span class="bvkld-send-loading" hidden><?php esc_html_e( 'Loading…', 'bvk-lightning-donate' ); ?></span>
			</button>

			<div class="bvkld-error" role="alert" hidden></div>

			<div class="bvkld-qr-area" hidden>
				<p class="bvkld-qr-caption"><?php esc_html_e( 'Scan QR with your mobile Lightning wallet', 'bvk-lightning-donate' ); ?></p>
				<div class="bvkld-qr"></div>
				<button type="button" class="bvkld-invoice" title="<?php esc_attr_e( 'Click to copy', 'bvk-lightning-donate' ); ?>"></button>
			</div>

			<p class="bvkld-address-line">
				<?php esc_html_e( 'or send directly to:', 'bvk-lightning-donate' ); ?>
				<button type="button" class="bvkld-address" title="<?php esc_attr_e( 'Click to copy', 'bvk-lightning-donate' ); ?>">
					<?php echo esc_html( $address ); ?>
				</button>
			</p>

			<?php if ( ! empty( $wallet_url ) ) : ?>
				<p class="bvkld-wallet-link">
					<a href="<?php echo esc_url( $wallet_url ); ?>" target="_blank" rel="noopener">
						<?php echo esc_html( $wallet_text ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

		<div class="bvkld-toast" role="status" aria-live="polite" hidden></div>
	</div>
	<?php
	return ob_get_clean();
}
