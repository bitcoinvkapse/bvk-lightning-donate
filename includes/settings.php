<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', 'bvkld_register_settings' );
function bvkld_register_settings() {
	register_setting( 'bvkld_settings_group', 'bvkld_enabled', array(
		'type'              => 'boolean',
		'sanitize_callback' => 'bvkld_sanitize_bool',
		'default'           => 0,
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_lightning_address', array(
		'type'              => 'string',
		'sanitize_callback' => 'bvkld_sanitize_lightning_address',
		'default'           => '',
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_amounts', array(
		'type'              => 'string',
		'sanitize_callback' => 'bvkld_sanitize_amounts',
		'default'           => '1000,5000,21000',
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_title', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => __( 'Did you enjoy this article? Send some sats ⚡', 'bvk-lightning-donate' ),
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_subtitle', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_post_types', array(
		'type'              => 'array',
		'sanitize_callback' => 'bvkld_sanitize_post_types',
		'default'           => array( 'post' ),
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_primary_color', array(
		'type'              => 'string',
		'sanitize_callback' => 'bvkld_sanitize_color_primary',
		'default'           => '#f7931a',
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_background_color', array(
		'type'              => 'string',
		'sanitize_callback' => 'bvkld_sanitize_color_background',
		'default'           => '#fdf4e3',
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_wallet_link_text', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => __( 'No wallet? Download one here', 'bvk-lightning-donate' ),
	) );

	register_setting( 'bvkld_settings_group', 'bvkld_wallet_link_url', array(
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	) );
}

function bvkld_sanitize_bool( $value ) {
	return ! empty( $value ) ? 1 : 0;
}

function bvkld_sanitize_lightning_address( $value ) {
	$value = strtolower( trim( sanitize_text_field( (string) $value ) ) );
	if ( $value === '' ) {
		return '';
	}
	if ( preg_match( '/^[a-z0-9._-]+@[a-z0-9.-]+\.[a-z]{2,}$/', $value ) ) {
		return $value;
	}
	add_settings_error(
		'bvkld_lightning_address',
		'bvkld_invalid_address',
		__( 'Invalid Lightning address format. Expected format: user@domain.com', 'bvk-lightning-donate' )
	);
	return get_option( 'bvkld_lightning_address', '' );
}

function bvkld_sanitize_amounts( $value ) {
	$parts    = array_map( 'trim', explode( ',', (string) $value ) );
	$clean    = array();
	foreach ( $parts as $p ) {
		$n = intval( $p );
		if ( $n > 0 ) {
			$clean[] = $n;
		}
	}
	if ( empty( $clean ) ) {
		add_settings_error(
			'bvkld_amounts',
			'bvkld_invalid_amounts',
			__( 'Enter at least one valid amount (positive number).', 'bvk-lightning-donate' )
		);
		return get_option( 'bvkld_amounts', '1000,5000,21000' );
	}
	return implode( ',', $clean );
}

function bvkld_sanitize_post_types( $value ) {
	if ( ! is_array( $value ) ) {
		return array( 'post' );
	}
	$valid = array_keys( get_post_types( array( 'public' => true ) ) );
	$clean = array();
	foreach ( $value as $pt ) {
		$pt = sanitize_key( $pt );
		if ( in_array( $pt, $valid, true ) ) {
			$clean[] = $pt;
		}
	}
	return empty( $clean ) ? array( 'post' ) : array_values( array_unique( $clean ) );
}

function bvkld_sanitize_color_primary( $value ) {
	return bvkld_sanitize_hex_color( $value, '#f7931a' );
}

function bvkld_sanitize_color_background( $value ) {
	return bvkld_sanitize_hex_color( $value, '#fdf4e3' );
}

add_action( 'admin_menu', 'bvkld_add_settings_page' );
function bvkld_add_settings_page() {
	add_options_page(
		__( 'BVK Lightning Donate', 'bvk-lightning-donate' ),
		__( 'BVK Lightning Donate', 'bvk-lightning-donate' ),
		'manage_options',
		'bvk-lightning-donate',
		'bvkld_render_settings_page'
	);
}

add_action( 'admin_enqueue_scripts', 'bvkld_admin_enqueue' );
function bvkld_admin_enqueue( $hook ) {
	if ( $hook !== 'settings_page_bvk-lightning-donate' ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script(
		'wp-color-picker',
		'jQuery(function($){ $(".bvkld-color-picker").wpColorPicker(); });'
	);
}

function bvkld_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'bvk-lightning-donate' ) );
	}

	$enabled         = (int) get_option( 'bvkld_enabled', 0 );
	$address         = get_option( 'bvkld_lightning_address', '' );
	$amounts         = get_option( 'bvkld_amounts', '1000,5000,21000' );
	$title           = get_option( 'bvkld_title', __( 'Did you enjoy this article? Send some sats ⚡', 'bvk-lightning-donate' ) );
	$subtitle        = get_option( 'bvkld_subtitle', '' );
	$selected_pt     = (array) get_option( 'bvkld_post_types', array( 'post' ) );
	$primary         = get_option( 'bvkld_primary_color', '#f7931a' );
	$background      = get_option( 'bvkld_background_color', '#fdf4e3' );
	$wallet_text     = get_option( 'bvkld_wallet_link_text', __( 'No wallet? Download one here', 'bvk-lightning-donate' ) );
	$wallet_url      = get_option( 'bvkld_wallet_link_url', '' );

	$public_types = get_post_types( array( 'public' => true ), 'objects' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'BVK Lightning Donate – Settings', 'bvk-lightning-donate' ); ?></h1>

		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'bvkld_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Widget active', 'bvk-lightning-donate' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="bvkld_enabled" value="1" <?php checked( 1, $enabled ); ?> />
							<?php esc_html_e( 'Show widget on the site', 'bvk-lightning-donate' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Disabled until you configure the plugin. Check this to start showing the widget according to the settings below.', 'bvk-lightning-donate' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_lightning_address"><?php esc_html_e( 'Lightning address', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<input name="bvkld_lightning_address" id="bvkld_lightning_address" type="text"
							value="<?php echo esc_attr( $address ); ?>" class="regular-text"
							placeholder="tvujucet@walletofsatoshi.com" />
						<p class="description">
							<?php
							printf(
								/* translators: %s is a link to lightningaddress.com */
								esc_html__( 'Format: user@wallet.com. What is %s?', 'bvk-lightning-donate' ),
								'<a href="https://lightningaddress.com/" target="_blank" rel="noopener">Lightning Address</a>'
							);
							?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_amounts"><?php esc_html_e( 'Preset amounts (sats)', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<input name="bvkld_amounts" id="bvkld_amounts" type="text"
							value="<?php echo esc_attr( $amounts ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Comma-separated, e.g. 1000,5000,21000', 'bvk-lightning-donate' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_title"><?php esc_html_e( 'Widget title', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<input name="bvkld_title" id="bvkld_title" type="text"
							value="<?php echo esc_attr( $title ); ?>" class="regular-text" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_subtitle"><?php esc_html_e( 'Subtitle', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<textarea name="bvkld_subtitle" id="bvkld_subtitle" rows="2" class="large-text"><?php echo esc_textarea( $subtitle ); ?></textarea>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Display below', 'bvk-lightning-donate' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $public_types as $pt ) : ?>
								<label style="display:block; margin-bottom:4px;">
									<input type="checkbox" name="bvkld_post_types[]"
										value="<?php echo esc_attr( $pt->name ); ?>"
										<?php checked( in_array( $pt->name, $selected_pt, true ) ); ?> />
									<?php echo esc_html( $pt->labels->name ); ?>
									<code><?php echo esc_html( $pt->name ); ?></code>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Content type below which the widget will be automatically added.', 'bvk-lightning-donate' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_primary_color"><?php esc_html_e( 'Primary color', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<input name="bvkld_primary_color" id="bvkld_primary_color" type="text"
							value="<?php echo esc_attr( $primary ); ?>" class="bvkld-color-picker"
							data-default-color="#f7931a" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_background_color"><?php esc_html_e( 'Widget background', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<input name="bvkld_background_color" id="bvkld_background_color" type="text"
							value="<?php echo esc_attr( $background ); ?>" class="bvkld-color-picker"
							data-default-color="#fdf4e3" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_wallet_link_text"><?php esc_html_e( 'Wallet link – text', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<input name="bvkld_wallet_link_text" id="bvkld_wallet_link_text" type="text"
							value="<?php echo esc_attr( $wallet_text ); ?>" class="regular-text" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="bvkld_wallet_link_url"><?php esc_html_e( 'Wallet link – URL', 'bvk-lightning-donate' ); ?></label>
					</th>
					<td>
						<input name="bvkld_wallet_link_url" id="bvkld_wallet_link_url" type="url"
							value="<?php echo esc_attr( $wallet_url ); ?>" class="regular-text"
							placeholder="https://www.walletofsatoshi.com/" />
						<p class="description"><?php esc_html_e( 'Leave empty to hide the link.', 'bvk-lightning-donate' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'How it works', 'bvk-lightning-donate' ); ?></h2>
		<p><?php esc_html_e( 'The widget is automatically displayed below the selected content types. For manual placement, use the shortcode:', 'bvk-lightning-donate' ); ?></p>
		<p><code>[bvk-lightning-donate]</code></p>
		<p><?php esc_html_e( 'If the shortcode is used in a post, automatic insertion will be disabled for that post.', 'bvk-lightning-donate' ); ?></p>
	</div>
	<?php
}
