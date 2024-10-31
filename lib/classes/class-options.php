<?php
/**
 * Admin Activation
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Options {

	/**
	 * Options instance
	 *
	 * @var array
	 */
	public static $options;

	/**
	 * Settings Tabs
	 *
	 * @var array
	 */
	private $settings_tabs;

	/**
	 * Current Settings Tab
	 *
	 * @var string
	 */
	private $tab;

	public function __construct() {

		self::$options = get_option( 'rw-elephant-rental-inventory', $this->get_default_options() );

		add_action( 'admin_menu', [ $this, 'settings_menu_item' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_action(
			'wp_ajax_add_form_field',
			function() {
				$key = ! empty( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
				$type = ! empty( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
				ob_start();
				$field_params = [
					'label'    => '&nbsp;', // empty label for appearance purposes.
					'required' => false,
					'default'  => false,
					'type'     => $type,
				];
				if ( in_array( $type, [ 'checkbox', 'radio', 'select' ], true ) ) {
					$field_params['options'] = [];
				}
				self::wishlist_form_field( $key, $field_params );
				$form_field = ob_get_contents();
				ob_get_clean();
				wp_send_json_success( $form_field );
			}
		);

		add_action( 'wp_ajax_get_wishlist_form_field_data', [ $this, 'get_wishlist_form_field_data' ] );
		add_action( 'wp_ajax_add_wishlist_form_field', [ $this, 'add_wishlist_form_field' ] );
		add_action( 'wp_ajax_delete_wishlist_form_field', [ $this, 'delete_wishlist_form_field' ] );
		add_action( 'wp_ajax_update_wishlist_form_field', [ $this, 'update_wishlist_form_field' ] );
		add_action( 'wp_ajax_toggle_cache', [ $this, 'toggle_cache' ] );

		add_action( 'init', [ $this, 'sanitize_settings' ] );
		add_action( 'init', [ $this, 'redirect_not_connected' ] );

		add_action( 'admin_notices', [ $this, 'settings_notice' ] );
		add_action( 'admin_notices', [ $this, 'cache_flush_notice' ] );

		add_filter(
			'removable_query_args',
			function( $args ) {

				$args[] = 'cache-flushed';

				return $args;

			}
		);

		add_action( 'admin_init', [ $this, 'flush_rwe_transients' ] );

		$this->settings_tabs = $this->get_settings_tabs();
		$this->tab           = isset( $_GET['tab'] ) ? $_GET['tab'] : __( 'general', 'rw-elephant-rental-inventory' );

	}

	/**
	 * Retreive the default options array.
	 * Note: Inside it's own callback as we cannot use __() before our constructor
	 *
	 * @since 2.0.0
	 *
	 * @return array Array of default RW Elephant Rental Inventory options.
	 */
	public function get_default_options() {

		return [
			'fresh-install'                  => true,
			'is-connected'                   => false,
			'connection-response'            => false,
			// General Options
			'tenant-id'                      => '',
			'api-key'                        => '',
			'gallery-page'                   => '',
			'wishlist-page'                  => '',
			'thumbnail-size'                 => 'plugin_320px_padded_link',
			'thumbnail-dimensions'           => '320',
			// Gallery Options
			'enable-hover-thumb'             => 1,
			'gallery-theme'                  => 'default',
			'gallery-styles'                 => [
				'primary-button'         => '#48b48f',
				'primary-button-hover'   => '#45ad89',
				'primary-button-text'    => '#ffffff',
				'secondary-button'       => '#2a95cd',
				'secondary-button-hover' => '#288fc5',
				'secondary-button-text'  => '#ffffff',
				'border-color'           => '#eaeaea',
			],
			// Thumbnail Image Replacement Options
			'thumbnails'                     => [],
			// Categories Options
			'category-descriptions'          => [],
			// Item Options
			'item-details'                   => [
				__( 'Price', 'rw-elephant-rental-inventory' )       => [
					'show'  => 1,
					'title' => __( 'Price', 'rw-elephant-rental-inventory' ),
				],
				__( 'Description', 'rw-elephant-rental-inventory' ) => [
					'show'  => 1,
					'title' => __( 'Description', 'rw-elephant-rental-inventory' ),
				],
				__( 'Quantity', 'rw-elephant-rental-inventory' )    => [
					'show'  => 1,
					'title' => __( 'Quantity', 'rw-elephant-rental-inventory' ),
				],
				__( 'Dimensions', 'rw-elephant-rental-inventory' )  => [
					'show'  => 1,
					'title' => __( 'Dimensions', 'rw-elephant-rental-inventory' ),
				],
				__( 'Tags', 'rw-elephant-rental-inventory' )        => [
					'show'  => 1,
					'title' => __( 'Tags', 'rw-elephant-rental-inventory' ),
				],
				__( 'Custom ID', 'rw-elephant-rental-inventory' )   => [
					'show'  => 0,
					'title' => __( 'Custom ID', 'rw-elephant-rental-inventory' ),
				],
				__( 'Custom Field 1', 'rw-elephant-rental-inventory' ) => [
					'show'  => 0,
					'title' => __( 'Custom Field 1', 'rw-elephant-rental-inventory' ),
				],
				__( 'Custom Field 2', 'rw-elephant-rental-inventory' ) => [
					'show'  => 0,
					'title' => __( 'Custom Field 2', 'rw-elephant-rental-inventory' ),
				],
			],
			'item-notes'                     => [],
			'pinterest-button'               => false,
			'contact-button'                 => false,
			'contact-title'                  => __( 'Questions?', 'rw-elephant-rental-inventory' ),
			'contact-email'                  => '',
			'item-page-layout'               => 'default',
			'display-kit-contents'           => true,
			'kit-contents-title'             => '',
			'currency-symbol'                => '$',
			'display-related-items'          => false,
			'related-item-title'             => '',
			// Wishlist Options
			'enable-wishlists'               => 1,
			'gallery-wishlist-add-number'    => 'one',
			'wislist-limit'                  => '',
			'enable-wishlist-gallery-add'    => 1,
			'gallery-wishlist-icon-position' => 'right',
			'gallery-wishlist-icon-style'    => 'plus',
			'gallery-wishlist-icon-color'    => '#000000',
			'gallery-wishlist-icon-hover'    => '#888888',
			'wishlist-form-fields'           => [
				'first_name'    => [
					'label'    => __( 'First Name', 'rw-elephant-rental-inventory' ),
					'required' => true,
					'default'  => true, // non-deletable field
					'type'     => 'text',
				],
				'last_name'     => [
					'label'    => __( 'Last Name', 'rw-elephant-rental-inventory' ),
					'required' => true,
					'default'  => true, // non-deletable field
					'type'     => 'text',
				],
				'email_address' => [
					'label'    => __( 'Email Address', 'rw-elephant-rental-inventory' ),
					'required' => true,
					'default'  => true, // non-deletable field
					'type'     => 'email',
				],
				'phone_number'  => [
					'label'    => __( 'Phone Number', 'rw-elephant-rental-inventory' ),
					'required' => true,
					'default'  => true, // non-deletable field
					'type'     => 'text',
				],
				'event_date'    => [
					'label'    => __( 'Event Date', 'rw-elephant-rental-inventory' ),
					'required' => true,
					'default'  => true, // non-deletable field
					'type'     => 'date',
				],
			],
			'wishlist-additional-info'       => [
				'heading' => '',
				'text'    => '',
			],
			'wishlist-confirmation'          => __( 'Your wishlist has been submitted. Keep an eye out for an email from us.', 'rw-elephant-rental-inventory' ),
			'disable-cache'                  => false,
		];

	}

	/**
	 * Get the available settings tabs.
	 *
	 * @since 2.0.0
	 *
	 * @return array Array of available settings tabs.
	 */
	public function get_settings_tabs() {

		/**
		 * Filter the available settings tabs.
		 * Filter: rw_elephant_settings_tabs
		 *
		 * @var array
		 */
		return (array) apply_filters(
			'rw_elephant_settings_tabs',
			[
				__( 'General', 'rw-elephant-rental-inventory' ),
				__( 'Gallery', 'rw-elephant-rental-inventory' ),
				__( 'Categories', 'rw-elephant-rental-inventory' ),
				__( 'Items', 'rw-elephant-rental-inventory' ),
				__( 'Wishlist', 'rw-elephant-rental-inventory' ),
			]
		);

	}

	/**
	 * Enqueue options scripts and styls.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function enqueue_scripts() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$rtl    = is_rtl() ? '-rtl' : '';

		wp_enqueue_style( 'featherlight', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/featherlight{$suffix}.css", [], '1.7.9', 'all' );
		wp_enqueue_style( 'rwe-admin', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/rw-elephant-admin{$rtl}{$suffix}.css", [ 'featherlight' ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, 'all' );

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_media();

		wp_enqueue_script( 'featherlight', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/featherlight{$suffix}.js", [ 'jquery' ], '1.7.9', true );

		wp_enqueue_script( 'rw-elephant-settings', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/rw-elephant-settings{$suffix}.js", [ 'jquery-ui-sortable', 'wp-color-picker', 'featherlight' ], false, true );

		wp_localize_script(
			'rw-elephant-settings',
			'rwElephant',
			[
				'preloader'               => sprintf(
					'<img src="%s" class="preloader" />',
					admin_url( 'images/wpspin_light.gif' )
				),
				'addFieldBtnText'         => __( 'Add Field', 'rw-elephant-rental-inventory' ),
				'addFieldError'           => __( 'An error occurred when adding the field to the wishlist form. Please try again.', 'rw-elephant-rental-inventory' ),
				'editFieldError'          => __( 'An error occurred retreiving field data. Please try again.', 'rw-elephant-rental-inventory' ),
				'updatefieldError'        => __( 'We were unable to update your form field. Please try again.', 'rw-elephant-rental-inventory' ),
				'removeFieldConfirmation' => __( 'Are you sure you want to remove this field from the wishlist form?', 'rw-elephant-rental-inventory' ),
				'siwtchLabels'            => [
					'default'     => [
						'enabled'  => __( 'Enabled.', 'rw-elephant-rental-inventory' ),
						'disabled' => __( 'Disabled.', 'rw-elephant-rental-inventory' ),
					],
					'toggleCache' => [
						'enabled'  => __( 'Cache is enabled.', 'rw-elephant-rental-inventory' ),
						'disabled' => __( 'Cache is disabled.', 'rw-elephant-rental-inventory' ),
					],
				],
			]
		);

	}

	/**
	 * Render the settings menu item.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function settings_menu_item() {

		add_submenu_page(
			'options-general.php',
			esc_html__( 'RW Elephant Rental Inventory', 'rw-elephant-rental-inventory' ),
			esc_html__( 'RW Elephant', 'rw-elephant-rental-inventory' ),
			'manage_options',
			'rw-elephant',
			[ $this, 'settings_page' ]
		);

	}

	/**
	 * Render the settings page markup.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the settings page.
	 */
	public function settings_page() {

		?>
		<!-- Settings Page Wrap -->
		<div class="wrap">

			<h1 class="settings-title"><?php esc_html_e( 'RW Elephant Rental Inventory', 'rw-elephant-rental-inventory' ); ?><span class="rwe-version-badge"><?php printf( 'v%s', RW_ELEPHANT_RENTAL_INVENTORY_VERSION ); ?></span></h1>

			<?php $this->settings_tabs(); ?>

			<div id="poststuff">

				<div id="post-body" class="metabox-holder columns-2">

					<!-- main content -->
					<div id="post-body-content">

						<div class="meta-box-sortables ui-sortable">

							<div class="postbox">

								<div class="handlediv" title="Click to toggle"><br></div>
								<!-- Toggle -->

								<?php $this->render_settings(); ?>

							</div>
							<!-- .postbox -->

						</div>
						<!-- .meta-box-sortables .ui-sortable -->

					</div>
					<!-- post-body-content -->

					<?php $this->render_sidebar(); ?>

				</div>

			</div>

		</div>

		<?php

	}

	/**
	 * Render the settings tabs.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the settings tabs.
	 */
	private function settings_tabs() {

		if ( empty( $this->settings_tabs ) ) {

			return;

		}

		?>

		<h2 class="nav-tab-wrapper">

			<?php

			$x = 1;

			foreach ( $this->settings_tabs as $settings_tab ) {

				printf(
					'<a href="%1$s" class="nav-tab%2$s %3$s">%4$s</a>',
					esc_url( add_query_arg( 'tab', sanitize_title( $settings_tab ), admin_url( 'options-general.php?page=rw-elephant' ) ) ),
					sanitize_title( $settings_tab ) === $this->tab ? esc_attr( ' nav-tab-active' ) : '',
					( ! self::$options['is-connected'] && $x > 1 ) ? 'disabled' : '',
					esc_html( $settings_tab )
				);

				$x++;

			}

			?>

		</h2>

		<?php

	}

	/**
	 * Render the main content.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the settings content.
	 */
	private function render_settings() {

		?>

		<div class="inside">

			<form method="post" action="options.php" autocomplete="off">

					<input type="hidden" name="tab" value="<?php echo esc_attr( $this->tab ); ?>" />

					<?php

					switch ( $this->tab ) {

						case __( 'general', 'rw-elephant-rental-inventory' ):
							?>
							<h2 class="title"><strong><?php esc_html_e( 'Account', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[tenant-id]"><?php esc_html_e( 'RWE Tenant ID', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="text" name="rw-elephant-rental-inventory[tenant-id]" value="<?php echo esc_attr( self::$options['tenant-id'] ); ?>"  data-lpignore="true" />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[api-key]"><?php esc_html_e( 'RWE API Key', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="<?php echo empty( self::$options['api-key'] ) ? esc_attr( 'text' ) : esc_attr( 'password' ); ?>" name="rw-elephant-rental-inventory[api-key]" value="<?php echo esc_attr( self::$options['api-key'] ); ?>" data-lpignore="true" />
										</td>
									</tr>
									<tr>
										<th scope="row"><label><?php esc_html_e( 'API Connection Status', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<?php
											$class  = self::$options['is-connected'] ? 'success' : 'danger';
											$status = self::$options['is-connected'] ? sprintf(
												/* translators: Dashicon (dashicon-yes) */
												__( '%s Connected', 'rw-elephant-rental-inventory' ),
												'<span class="dashicons dashicons-yes"></span>'
											) : sprintf(
												/* translators: Dashicon (dashicon-no-alt) */
												__( '%s Not Connected', 'rw-elephant-rental-inventory' ),
												'<span class="dashicons dashicons-no-alt"></span>'
											);
											printf(
												'<div class="rwe-alert %s rwe-api-connection-status">
													<p>%s</p>
												</div>
												%s',
												esc_attr( $class ),
												wp_kses_post( $status ),
												! empty( self::$options['connection-response'] ) ? sprintf(
													'<p><small>%s</small></p>',
													self::$options['connection-response']
												) : ''
											);
											?>
										</td>
									</tr>
								</tbody>
							</table>
							<hr />
							<h2 class="title"><strong><?php esc_html_e( 'Pages', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-page]"><?php esc_html_e( 'Gallery Page', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<?php $this->render_page_select( 'gallery-page' ); ?>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[wishlist-page]"><?php esc_html_e( 'Wishlist Page', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<?php $this->render_page_select( 'wishlist-page' ); ?>
										</td>
									</tr>
								</tbody>
							</table>
							<?php
							break;

						case __( 'gallery', 'rw-elephant-rental-inventory' ):
							?>
							<!-- Thumbnail Size -->
							<h2 class="title"><strong><?php esc_html_e( 'Listing Image Thumbnails', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<p class="description"><?php esc_html_e( 'Choose a format for thumbnail images', 'rw-elephant-rental-inventory' ); ?></p>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[thumbnail-size]"><?php esc_html_e( 'Thumbnail Format', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<label>
												<strong><?php esc_html_e( 'Padded', 'rw-elephant-rental-inventory' ); ?></strong>
												<input type="radio" name="rw-elephant-rental-inventory[thumbnail-size]" value="plugin_320px_padded_link" <?php isset( self::$options['thumbnail-size'] ) ? checked( self::$options['thumbnail-size'], 'plugin_320px_padded_link' ) : ''; ?>>
											</label>
											&nbsp;
											<label>
												<strong><?php esc_html_e( 'Cropped', 'rw-elephant-rental-inventory' ); ?></strong>
												<input type="radio" name="rw-elephant-rental-inventory[thumbnail-size]" value="plugin_320px_thumbnail_link" <?php isset( self::$options['thumbnail-size'] ) ? checked( self::$options['thumbnail-size'], 'plugin_320px_thumbnail_link' ) : ''; ?>>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[thumbnail-dimensions]"><?php esc_html_e( 'Thumbnail Size', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="number" name="rw-elephant-rental-inventory[thumbnail-dimensions]" placeholder="320" value="<?php echo esc_attr( self::$options['thumbnail-dimensions'] ); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[enable-hover-thumb]"><?php esc_html_e( 'Enable Secondary Image on Hover', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[enable-hover-thumb]" value="1" <?php isset( self::$options['enable-hover-thumb'] ) ? checked( self::$options['enable-hover-thumb'], '1' ) : ''; ?> />
										</td>
									</tr>
								</tbody>
							</table>
							<hr />
							<!-- Theme -->
							<h2 class="title"><strong><?php esc_html_e( 'Theme', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-theme]"><?php esc_html_e( 'Style', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<select name="rw-elephant-rental-inventory[gallery-theme]" class="widefat">
												<?php
												$gallery_styles = (array) apply_filters(
													'rw_elephant_gallery_themes',
													[
														'default'  => __( 'Default', 'rw-elephant-rental-inventory' ),
														'overlay'  => __( 'Overlay', 'rw-elephant-rental-inventory' ),
														'polaroid' => __( 'Polaroid', 'rw-elephant-rental-inventory' ),
													]
												);
												foreach ( $gallery_styles as $style_key => $style_label ) {
													printf(
														'<option value="%1$s" %2$s>%3$s</option>',
														esc_attr( $style_key ),
														esc_attr( selected( $style_key, self::$options['gallery-theme'], false ) ),
														esc_html( $style_label )
													);
												}
												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
							<hr />
							<!-- Styles -->
							<h2 class="title"><strong><?php esc_html_e( 'Styles', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<p class="description"><?php esc_html_e( 'Gallery Colours (Hex Colour Codes)', 'rw-elephant-rental-inventory' ); ?></p>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-styles][primary-button]"><?php esc_html_e( 'Primary Button', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" name="rw-elephant-rental-inventory[gallery-styles][primary-button]" value="<?php echo esc_attr( self::$options['gallery-styles']['primary-button'] ); ?>" class="rwe-color-picker" >
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-styles][primary-button-hover]"><?php esc_html_e( 'Primary Button Hover', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" name="rw-elephant-rental-inventory[gallery-styles][primary-button-hover]" value="<?php echo esc_attr( self::$options['gallery-styles']['primary-button-hover'] ); ?>" class="rwe-color-picker" >
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-styles][primary-button-text]"><?php esc_html_e( 'Primary Button Text', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" name="rw-elephant-rental-inventory[gallery-styles][primary-button-text]" value="<?php echo esc_attr( self::$options['gallery-styles']['primary-button-text'] ); ?>" class="rwe-color-picker" >
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-styles][secondary-button]"><?php esc_html_e( 'Secondary Button', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" name="rw-elephant-rental-inventory[gallery-styles][secondary-button]" value="<?php echo esc_attr( self::$options['gallery-styles']['secondary-button'] ); ?>" class="rwe-color-picker" >
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-styles][secondary-button-hover]"><?php esc_html_e( 'Secondary Button Hover', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" name="rw-elephant-rental-inventory[gallery-styles][secondary-button-hover]" value="<?php echo esc_attr( self::$options['gallery-styles']['secondary-button-hover'] ); ?>" class="rwe-color-picker" >
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-styles][secondary-button-text]"><?php esc_html_e( 'Secondary Button Text', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" name="rw-elephant-rental-inventory[gallery-styles][secondary-button-text]" value="<?php echo esc_attr( self::$options['gallery-styles']['secondary-button-text'] ); ?>" class="rwe-color-picker" >
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[gallery-styles][border-color]"><?php esc_html_e( 'Border Colour', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" name="rw-elephant-rental-inventory[gallery-styles][border-color]" value="<?php echo esc_attr( self::$options['gallery-styles']['border-color'] ); ?>" class="rwe-color-picker" >
										</td>
									</tr>
								</tbody>
							</table>
							<?php
							break;

						case __( 'categories', 'rw-elephant-rental-inventory' ):
							// Setup the Categories
							$categories = rwe_get_categories();
							?>
							<!-- Items -->
							<h2 class="title"><strong><?php esc_html_e( 'Category Settings', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<?php
							if ( is_wp_error( $categories ) ) {
								echo $categories->get_error_message();
							}

							if ( empty( $categories ) ) {
								echo '<p>' . esc_html__( 'No categories found.', 'rw-elephant-rental-inventory' ) . '</p>';
							}

							?>

							<table class="form-table thumbnail-image-replacement">
								<tbody>
									<tr>
										<th><?php esc_html_e( 'Category Name', 'rw-elephant-rental-inventory' ); ?></th>
										<th class="center"><?php esc_html_e( 'Category SEO Description', 'rw-elephant-rental-inventory' ); ?></th>
										<th class="center"><?php esc_html_e( 'Default Image', 'rw-elephant-rental-inventory' ); ?></th>
										<th class="center"><?php esc_html_e( 'Custom Image', 'rw-elephant-rental-inventory' ); ?></th>
									</tr>
									<?php
									foreach ( $categories as $category ) {
										new Data( $category );
										$custom_image_url = isset( self::$options['thumbnails'][ $category['inventory_type_id'] ]['url'] ) ? self::$options['thumbnails'][ $category['inventory_type_id'] ]['url'] : '';
										$custom_image_id  = isset( self::$options['thumbnails'][ $category['inventory_type_id'] ]['id'] ) ? self::$options['thumbnails'][ $category['inventory_type_id'] ]['id'] : '';
										$button_class     = empty( $custom_image_url ) ? '' : 'hidden';
										$img_wrap_class   = empty( $custom_image_url ) ? 'hidden' : '';
										$category_description = self::$options['category-descriptions'][$category['inventory_type_id']] ?? '';
										?>
										<tr>
											<td><strong><?php echo esc_html( $category['inventory_type_name'] ); ?></strong></td>
											<td class="center">
												<textarea name="rw-elephant-rental-inventory[category-descriptions][<?php echo esc_attr( $category['inventory_type_id'] ); ?>]" placeholder="<?php esc_attr_e( 'Category Description...', 'rw-elephant-rental-inventory' ); ?>"><?php echo esc_html( $category_description ); ?></textarea>
											</td>
											<td class="center"><img src="<?php echo rwe_get_category_image_url( self::$options['thumbnail-size'], false ); ?>" class="thumb" /></td>
											<td class="center">
												<input type="hidden" class="js-image-url" name="rw-elephant-rental-inventory[thumbnails][<?php echo esc_attr( $category['inventory_type_id'] ); ?>][url]" value="<?php echo esc_attr( $custom_image_url ); ?>" />
												<input type="hidden" class="js-image-id" name="rw-elephant-rental-inventory[thumbnails][<?php echo esc_attr( $category['inventory_type_id'] ); ?>][id]" value="<?php echo esc_attr( $custom_image_id ); ?>" />
												<a href="#" class="button button-primary js-add-image <?php echo esc_attr( $button_class ); ?>"><?php esc_html_e( 'Add Image', 'rw-elephant-rental-inventory' ); ?></a>
												<!-- Image preview -->
												<div class="image-wrap <?php echo esc_attr( $img_wrap_class ); ?>">
													<a href="#" class="js-remove-image"><img src="<?php echo esc_url( RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/img/icon-delete.svg' ); ?>" /></a>
													<img src="<?php echo esc_url( $custom_image_url ); ?>" class="image-preview" />
												</div>
											</td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
							<?php
							break;

						case __( 'items', 'rw-elephant-rental-inventory' ):
							?>
							<!-- Items -->
							<h2 class="title"><strong><?php esc_html_e( 'Item Detail', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table js-sortable item-details">
								<tbody>
									<tr>
										<th><?php esc_html_e( 'Field', 'rw-elephant-rental-inventory' ); ?></th>
										<th><?php esc_html_e( 'Show', 'rw-elephant-rental-inventory' ); ?></th>
										<th><?php esc_html_e( 'Title', 'rw-elephant-rental-inventory' ); ?></th>
										<th><?php esc_html_e( 'Reorder', 'rw-elephant-rental-inventory' ); ?></th>
									</tr>
									<!-- Sortable Rows -->
									<?php $this->sortable_item_details(); ?>
								</tbody>
							</table>
							<hr />
							<!-- Item Notes -->
							<h2 class="title">
								<strong><?php esc_html_e( 'Item Notes', 'rw-elephant-rental-inventory' ); ?></strong>
								<a href="#" class="js-add-item-note">
									<?php
										printf(
											/* translators: + icon. */
											esc_html__( '%s Add a note', 'rw-elephant-rental-inventory' ),
											'<span class="dashicons dashicons-plus-alt"></span>'
										);
									?>
									</a>
							</h2>
							<ul class="item-notes js-sortable">
								<?php
								if ( ! empty( self::$options['item-notes'] ) ) {

									$x = 0;

									foreach ( self::$options['item-notes'] as $item_note ) {

										?>
										<li class="item item-note">
											<div class="actions">
											<?php
											// Cannot delete first one
											if ( 0 < $x ) {
												print( '<span class="js-remove-item-note icon dashicons dashicons-no-alt"></span>' );
											}
											print( '<span class="dashicons dashicons-menu icon"></span>' );
											?>
											</div>
											<label class="text-label">
												<strong><?php esc_html_e( 'Title', 'rw-elephant-rental-inventory' ); ?></strong>
												<input type="text" name="rw-elephant-rental-inventory[item-notes][<?php echo esc_attr( $x ); ?>][title]" value="<?php echo esc_attr( $item_note['title'] ); ?>" placeholder="<?php esc_attr_e( 'Additional Note:', 'rw-elephant-rental-inventory' ); ?>" />
											</label>
											<label>
												<strong><?php esc_html_e( 'Text', 'rw-elephant-rental-inventory' ); ?></strong>
												<textarea name="rw-elephant-rental-inventory[item-notes][<?php echo esc_attr( $x ); ?>][text]" placeholder="<?php echo esc_attr( 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.' ); ?>"><?php echo esc_attr( $item_note['text'] ); ?></textarea>
											</label>
										</li>

										<?php

										$x++;

									}

								} else {
									?>
									<li class="item item-note">
										<div class="actions">
											<span class="dashicons dashicons-menu"></span>
										</div>
										<label class="text-label">
											<strong><?php esc_html_e( 'Title', 'rw-elephant-rental-inventory' ); ?></strong>
											<input type="text" name="rw-elephant-rental-inventory[item-notes][0][title]" value="" />
										</label>
										<label>
											<strong><?php esc_html_e( 'Text', 'rw-elephant-rental-inventory' ); ?></strong>
											<textarea name="rw-elephant-rental-inventory[item-notes][0][text]"></textarea>
										</label>
									</li>
									<?php
								}
								?>
							</ul>
							<li class="item item-note cloneable hidden">
								<div class="actions">
									<span class="js-remove-item-note icon dashicons dashicons-no-alt"></span>
									<span class="dashicons dashicons-menu"></span>
								</div>
								<label class="text-label">
									<strong><?php esc_html_e( 'Title', 'rw-elephant-rental-inventory' ); ?></strong>
									<input type="text" name="" value="" />
								</label>
								<label>
									<strong><?php esc_html_e( 'Text', 'rw-elephant-rental-inventory' ); ?></strong>
									<textarea name=""></textarea>
								</label>
							</li>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[pinterest-button]"><?php esc_html_e( 'Display Pinterest Button', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[pinterest-button]" value="1" <?php isset( self::$options['pinterest-button'] ) ? checked( self::$options['pinterest-button'], '1' ) : ''; ?> />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[contact-button]"><?php esc_html_e( 'Display Contact Button', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[contact-button]" value="1" <?php isset( self::$options['contact-button'] ) ? checked( self::$options['contact-button'], '1' ) : ''; ?> />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[contact-title]"><?php esc_html_e( 'Contact Title', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="text" name="rw-elephant-rental-inventory[contact-title]" value="<?php echo esc_attr( self::$options['contact-title'] ); ?>" />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[contact-email]"><?php esc_html_e( 'Contact Email Address', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="email" name="rw-elephant-rental-inventory[contact-email]" value="<?php echo esc_attr( self::$options['contact-email'] ); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
							<hr />
							<!-- Item Page Layout -->
							<h2 class="title"><strong><?php esc_html_e( 'Item Page Layout', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<p class="description"><?php esc_html_e( 'Prefer your item details to appear on the left and item images on the right? Switch to reversed!', 'rw-elephant-rental-inventory' ); ?></p>
							<div class="item-page-layout">
								<label>
									<strong><?php esc_html_e( 'Default', 'rw-elephant-rental-inventory' ); ?></strong>
									<img src="<?php echo esc_url( RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/img/layout-icon.png' ); ?>" />
									<input type="radio" name="rw-elephant-rental-inventory[item-page-layout]" value="default" <?php isset( self::$options['item-page-layout'] ) ? checked( self::$options['item-page-layout'], 'default' ) : ''; ?>>
								</label>
								<label>
									<strong><?php esc_html_e( 'Reversed', 'rw-elephant-rental-inventory' ); ?></strong>
									<img src="<?php echo esc_url( RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/img/layout-icon-reversed.png' ); ?>" />
									<input type="radio" name="rw-elephant-rental-inventory[item-page-layout]" value="reversed" <?php isset( self::$options['item-page-layout'] ) ? checked( self::$options['item-page-layout'], 'reversed' ) : ''; ?>>
								</label>
							</div>
							<hr />
							<!-- Kit Options -->
							<h2 class="title"><strong><?php esc_html_e( 'Kit Options', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[display-kit-contents]"><?php esc_html_e( 'Display Kit Contents', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[display-kit-contents]" value="1" <?php isset( self::$options['display-kit-contents'] ) ? checked( self::$options['display-kit-contents'], '1' ) : ''; ?> />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[kit-contents-title]"><?php esc_html_e( 'Kit Contents Title', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="text" name="rw-elephant-rental-inventory[kit-contents-title]" value="<?php echo esc_attr( self::$options['kit-contents-title'] ); ?>" placeholder="<?php esc_attr_e( 'Kit Contains', 'rw-elephant-rental-inventory' ); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
							<hr />
							<!-- Other Options -->
							<h2 class="title"><strong><?php esc_html_e( 'Other Options', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[currency-symbol]"><?php esc_html_e( 'Currency Symbol', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="text" name="rw-elephant-rental-inventory[currency-symbol]" value="<?php echo esc_attr( self::$options['currency-symbol'] ); ?>" placeholder="<?php esc_attr_e( '$', 'rw-elephant-rental-inventory' ); ?>" />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[display-related-items]"><?php esc_html_e( 'Display Related Items', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[display-related-items]" value="1" <?php isset( self::$options['display-related-items'] ) ? checked( self::$options['display-related-items'], '1' ) : ''; ?> />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[related-item-title]"><?php esc_html_e( 'Related Items Title', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="text" name="rw-elephant-rental-inventory[related-item-title]" value="<?php echo esc_attr( self::$options['related-item-title'] ); ?>" placeholder="<?php esc_attr_e( 'You might also like', 'rw-elephant-rental-inventory' ); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
							<?php
							break;

						case __( 'wishlist', 'rw-elephant-rental-inventory' ):
							?>
							<!-- General -->
							<h2 class="title"><strong><?php esc_html_e( 'General', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[enable-wishlists]"><?php esc_html_e( 'Enable Wishlists', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[enable-wishlists]" value="1" <?php isset( self::$options['enable-wishlists'] ) ? checked( self::$options['enable-wishlists'], '1' ) : ''; ?> />
										</td>
									</tr>
								</tbody>
							</table>
							<hr />
							<!-- Wishlist Behavior -->
							<h2 class="title"><strong><?php esc_html_e( 'Wishlist Behavior', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[gallery-wishlist-add-number]"><?php esc_html_e( '"Add to Wishlist" adds', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<label>
											<strong><?php esc_html_e( 'One Item', 'rw-elephant-rental-inventory' ); ?></strong>
											<input type="radio" name="rw-elephant-rental-inventory[gallery-wishlist-add-number]" value="one" <?php isset( self::$options['gallery-wishlist-add-number'] ) ? checked( self::$options['gallery-wishlist-add-number'], 'one' ) : esc_attr_e( 'checked="checked"' ); ?>>
										</label>
										<label>
											<strong><?php esc_html_e( 'All Items', 'rw-elephant-rental-inventory' ); ?></strong>
											<input type="radio" name="rw-elephant-rental-inventory[gallery-wishlist-add-number]" value="all" <?php isset( self::$options['gallery-wishlist-add-number'] ) ? checked( self::$options['gallery-wishlist-add-number'], 'all' ) : ''; ?>>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[wishlist-limit]"><?php esc_html_e( 'Prevent users from adding more quantity than owned to the Wishlist', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[wishlist-limit]" value="1" <?php isset( self::$options['wishlist-limit'] ) ? checked( self::$options['wishlist-limit'], '1' ) : ''; ?> />
									</td>
								</tr>
								</tbody>
							</table>
							<hr />
							<!-- Gallery Wishlist Button -->
							<h2 class="title"><strong><?php esc_html_e( 'Gallery Wishlist Button', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[enable-wishlist-gallery-add]"><?php esc_html_e( 'Enable Adding to Wishlist from Gallery', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<input class="widefat" type="checkbox" name="rw-elephant-rental-inventory[enable-wishlist-gallery-add]" value="1" <?php isset( self::$options['enable-wishlist-gallery-add'] ) ? checked( self::$options['enable-wishlist-gallery-add'], '1' ) : ''; ?> />
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[gallery-wishlist-icon-position]"><?php esc_html_e( 'Wishlist Button Position', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<label>
											<strong><?php esc_html_e( 'Top Left', 'rw-elephant-rental-inventory' ); ?></strong>
											<input type="radio" name="rw-elephant-rental-inventory[gallery-wishlist-icon-position]" value="left" <?php isset( self::$options['gallery-wishlist-icon-position'] ) ? checked( self::$options['gallery-wishlist-icon-position'], 'left' ) : ''; ?>>
										</label>
										<label>
											<strong><?php esc_html_e( 'Top Right', 'rw-elephant-rental-inventory' ); ?></strong>
											<input type="radio" name="rw-elephant-rental-inventory[gallery-wishlist-icon-position]" value="right" <?php isset( self::$options['gallery-wishlist-icon-position'] ) ? checked( self::$options['gallery-wishlist-icon-position'], 'right' ) : ''; ?>>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[gallery-wishlist-icon-style]"><?php esc_html_e( 'Wishlist Button Icon Style', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<label>
											<strong><?php esc_html_e( 'Plus/Minus', 'rw-elephant-rental-inventory' ); ?></strong>
											<input type="radio" name="rw-elephant-rental-inventory[gallery-wishlist-icon-style]" value="plus" <?php isset( self::$options['gallery-wishlist-icon-style'] ) ? checked( self::$options['gallery-wishlist-icon-style'], 'plus' ) : ''; ?>>
										</label>
										<label>
											<strong><?php esc_html_e( 'Heart', 'rw-elephant-rental-inventory' ); ?></strong>
											<input type="radio" name="rw-elephant-rental-inventory[gallery-wishlist-icon-style]" value="heart" <?php isset( self::$options['gallery-wishlist-icon-style'] ) ? checked( self::$options['gallery-wishlist-icon-style'], 'heart' ) : ''; ?>>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[gallery-wishlist-icon-color]"><?php esc_html_e( 'Wishlist Button Icon Color', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<?php $color = self::$options['gallery-wishlist-icon-color'] ?? '#000000'; ?>
										<input type="text" name="rw-elephant-rental-inventory[gallery-wishlist-icon-color]" value="<?php echo esc_attr( $color ); ?>" class="rwe-color-picker" >
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[gallery-wishlist-icon-hover]"><?php esc_html_e( 'Wishlist Button Icon Hover Color', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<?php $hover = self::$options['gallery-wishlist-icon-hover'] ?? '#888888'; ?>
										<input type="text" name="rw-elephant-rental-inventory[gallery-wishlist-icon-hover]" value="<?php echo esc_attr( $hover ); ?>" class="rwe-color-picker" >
									</td>
								</tr>
								</tbody>
							</table>
							<hr />
							<!-- Wishlist Form Fields -->
							<h2 class="title"><strong><?php esc_html_e( 'Wishlist Form Fields', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<p class="description"><?php esc_html_e( 'The following wishlist form fields are required.', 'rw-elephant-rental-inventory' ); ?></p>
							<div class="js-sortable wishlist-form-fields">
								<ul>
									<?php
									if ( ! empty( self::$options['wishlist-form-fields'] ) ) {
										foreach ( self::$options['wishlist-form-fields'] as $field_key => $field ) {
											self::wishlist_form_field( $field_key, $field );
										}
									}
									?>
								</ul>
							</div>
							<div class="additional-form-fields">
								<h2 class="title"><?php esc_html_e( 'Additional Form Fields', 'rw-elephant-rental-inventory' ); ?></h2>
								<ul>
								<?php
								$types = (array) apply_filters(
									'rw_elephant_form_field_types',
									[
										'text',
										'date',
										'select',
										'radio',
										'checkbox',
										'email',
									]
								);
								if ( ! empty( $types ) ) {
									foreach ( $types as $type ) {
										printf(
											'<a href="#" class="js-add-field button button-secondary" data-type="%s">%s</a>',
											esc_attr( $type ),
											esc_html( ucwords( $type ) )
										);
									}
								}
								?>
								</ul>
							</div>
							<hr />
							<!-- Additional Information Section -->
							<h2 class="title"><strong><?php esc_html_e( 'Additional Information Section', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[wishlist-additional-info][heading]"><?php esc_html_e( 'Heading', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<input type="text" class="wishlist-additional-info-field" name="rw-elephant-rental-inventory[wishlist-additional-info][heading]" value="<?php echo esc_attr( self::$options['wishlist-additional-info']['heading'] ); ?>" placeholder="<?php esc_attr_e( 'Additional Information', 'rw-elephant-rental-inventory' ); ?>" />
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="rw-elephant-rental-inventory[wishlist-additional-info][text]"><?php esc_html_e( 'Text', 'rw-elephant-rental-inventory' ); ?></label></th>
										<td>
											<textarea class="wishlist-additional-info-field textarea" name="rw-elephant-rental-inventory[wishlist-additional-info][text]" placeholder="<?php esc_attr_e( 'Wishlist items.', 'rw-elephant-rental-inventory' ); ?>"><?php echo esc_html( self::$options['wishlist-additional-info']['text'] ); ?></textarea>
										</td>
									</tr>
								</tbody>
							</table>
							<hr />
							<!-- Wishlist Submission Confirmation -->
							<h2 class="title"><strong><?php esc_html_e( 'Wishlist Submission Confirmation', 'rw-elephant-rental-inventory' ); ?></strong></h2>
							<table class="form-table">
								<tbody>
								<tr>
									<th scope="row"><label for="rw-elephant-rental-inventory[wishlist-confirmation]"><?php esc_html_e( 'Wishlist Submission Confirmation Message', 'rw-elephant-rental-inventory' ); ?></label></th>
									<td>
										<textarea class="wishlist-submission-confirmation textarea" name="rw-elephant-rental-inventory[wishlist-confirmation]" placeholder="<?php esc_attr_e( 'Your wishlist has been submitted. Keep an eye out for an email from us.', 'rw-elephant-rental-inventory' ); ?>"><?php echo wp_kses_post( self::$options['wishlist-confirmation'] ); ?></textarea>
									</td>
								</tr>
								</tbody>
							</table>
							<?php
							break;

						default:
							do_action( 'rw_elephant_settings_section', $this->tab );
							break;

					}

					wp_nonce_field( 'rw_elephant_settings', 'rw_elephant_save_settings' );

					submit_button();

					?>

			</form>

			<div id="edit-field" class="hidden">
				<h2><?php esc_html_e( 'Edit Wishlist Form Field', 'rw-elephant-rental-inventory' ); ?></h2>
				<div class="js-edit-field-form hidden"></div>
				<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="preloader" />
			</div>

		</div>

		<?php

	}

	/**
	 * Redirect non-connected users back to the general tab.
	 *
	 * @since 2.0.0
	 *
	 * @return null when not connected to the API
	 */
	public function redirect_not_connected() {

		$page = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		if ( 'rw-elephant' !== $page ) {

			return;

		}

		if ( ! self::$options['is-connected'] && 'general' !== $this->tab ) {

			wp_safe_redirect( admin_url( 'options-general.php?page=rw-elephant' ) );

			exit;

		}

	}

	/**
	 * Render the sortable item details rows.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the sortable item rows.
	 */
	private function sortable_item_details() {

		if ( empty( self::$options['item-details'] ) ) {

			return;

		}

		foreach ( self::$options['item-details'] as $key => $data ) {

			printf(
				'<tr class="item item-detail">
					<td>%1$s</td>
					<td>
						<input type="checkbox" name="rw-elephant-rental-inventory[item-details][%1$s][show]" %2$s value="1" />
					</td>
					<td>
						<input type="text" name="rw-elephant-rental-inventory[item-details][%1$s][title]" value="%3$s" class="widefat" />
					</td>
					<td><span class="dashicons dashicons-menu sort-handle"></span></td>
				</tr>',
				esc_html( $key ),
				isset( self::$options['item-details'][ $key ]['show'] ) ? checked( self::$options['item-details'][ $key ]['show'], '1', false ) : '',
				esc_html( self::$options['item-details'][ $key ]['title'] )
			);

		}

	}

	/**
	 * Render the wishlist form field on the settings page.
	 *
	 * @param  string $key   The field type key.
	 * @param  array  $field Wishlist field data array.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed        Markup for the field type.
	 */
	public static function wishlist_form_field( $key = '', $field = [] ) {

		if ( empty( $key ) || empty( $field ) ) {

			return;

		}

		?>

		<li class="item wishlist-form-field">
			<span class="field-label"><?php echo esc_html( $field['label'] ); ?></span>
			<span class="dashicons dashicons-menu"></span>
			<span class="dashicons dashicons-edit js-edit-field" data-key="<?php echo esc_attr( $key ); ?>"></span>

			<?php

			if ( ! $field['default'] ) {

				?>

				<span class="dashicons dashicons-no-alt js-remove-field" data-key="<?php echo esc_attr( $key ); ?>"></span>

				<?php

			}

			?>

			<input type="hidden" class="js-field-label" name="rw-elephant-rental-inventory[wishlist-form-fields][]" value="<?php echo esc_attr( $key ); ?>" />

		</li>

		<?php

	}

	/**
	 * Get wishlist form field data to edit.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the edit field form.
	 */
	public function get_wishlist_form_field_data() {

		$key = ! empty( $_POST['key'] ) ? sanitize_text_field( $_POST['key'] ) : '';

		if ( ! $key ) {

			wp_send_json_error();

		}

		$fields = self::$options['wishlist-form-fields'];

		$field = array_key_exists( $key, $fields ) ? $fields[ $key ] : [
			'label' => '',
			'type'  => ! empty( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '',
		];

		$form_class = array_key_exists( $key, $fields ) ? 'js-update-wishlist-field' : 'js-add-wishlist-field';

		$label   = $field['label'];
		$type    = $field['type'];
		$default = $field['default'];

		ob_start();

		?>

		<form class="<?php echo esc_attr( $form_class ); ?>">
			<label>
				<span><?php esc_html_e( 'Type', 'rw-elephant-rental-inventory' ); ?></span>
				<div><?php echo esc_attr( ucwords( $type ) ); ?></div>
				<input type="hidden" class="widefat" name="type" value="<?php echo esc_attr( $type ); ?>" class="field-label" />
			</label>

			<label>
				<span><?php esc_html_e( 'Label', 'rw-elephant-rental-inventory' ); ?></span>
				<input type="text" class="widefat" name="label" required value="<?php echo esc_attr( $label ); ?>" class="field-label" />
			</label>

			<?php if ( $default ) : ?>

				<input type="hidden" name="required" value="1">
				<input type="hidden" name="default" value="1">

			<?php else : ?>

				<label>
					<span><?php esc_html_e( 'Required', 'rw-elephant-rental-inventory' ); ?></span>
					<input type="checkbox" class="widefat" name="required" value="1" class="field-required" <?php checked( $field['required'], 1 ); ?> />
				</label>

			<?php endif; ?>

			<?php

			switch ( $type ) {

				default:
					break;

				case 'radio':
				case 'select':
				case 'checkbox':
					?>
					<label>

						<span class="options-label"><?php esc_html_e( 'Options', 'rw-elephant-rental-inventory' ); ?> <a href="#" class="js-add-option"><span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Option', 'rw-elephant-rental-inventory' ); ?></a></span>

						<ul class="options">

						<?php

						if ( isset( $field['options'] ) ) {

							if ( ! empty( $field['options'] ) ) {

								foreach ( $field['options'] as $option_key => $label ) {

									printf(
										'<li>
											<div class="section">
												<strong>%s</strong>
												<input type="text" name="key[]" value="%s" class="widefat" />
											</div>
											<div class="section">
												<strong>%s</strong>
												<input type="text" name="value[]" value="%s" class="widefat" />
											</div>
										</li>',
										esc_html__( 'Key', 'rw-elephant-rental-inventory' ),
										esc_attr( $option_key ),
										esc_html__( 'Label', 'rw-elephant-rental-inventory' ),
										esc_attr( $label )
									);

								}

							} else {

								printf(
									'<li class="no-options-message">%s</li>',
									esc_html__( 'No options set.', 'rw-elephant-rental-inventory' )
								);

							}

						} else {

							printf(
								'<li class="no-options-message">%s</li>',
								esc_html__( 'No options set.', 'rw-elephant-rental-inventory' )
							);

						}

						$options = isset( $field['options'] ) ? $field['options'] : [];

						?>

						</ul>

						<li class="blank-options hidden">
							<div class="section">
								<strong><?php esc_html_e( 'Key', 'rw-elephant-rental-inventory' ); ?></strong>
								<input type="text" name="key[]" value="" class="widefat" />
							</div>
							<div class="section">
								<strong><?php esc_html_e( 'Label', 'rw-elephant-rental-inventory' ); ?></strong>
								<input type="text" name="value[]" value="" class="widefat" />
							</div>
						</li>

					</label>

					<?php

					break;

			}

			submit_button( __( 'Update Form Field', 'rw-elephant-rental-inventory' ) );

			?>

			<input type="hidden" class="js-field-key" value="<?php echo esc_attr( $key ); ?>" />

		</form>

		<?php

		$form = ob_get_contents();
		ob_get_clean();

		wp_send_json_success( $form );

	}

	/**
	 * Add a wishlist form field to the options array.
	 *
	 * @since 2.0.0
	 *
	 * @return false|mixed False on error, else markup for the option li element.
	 */
	public function add_wishlist_form_field() {

		$data = ! empty( $_POST['data'] ) ? sanitize_text_field( urldecode( $_POST['data'] ) ) : '';

		if ( ! $data ) {

			wp_send_json_error( [ 'message' => __( 'Field data is missing.', 'rw-elephant-rental-inventory' ) ] );

		}

		$data = wp_parse_args( $data );

		$data['required'] = isset( $data['required'] ) ? '1' : '0';
		$data['default']  = false;

		// Setup the options array
		if ( isset( $data['key'] ) && ! empty( $data['key'] ) ) {

			$data['options'] = array_filter( array_combine( $data['key'], $data['value'] ), 'strlen' );

			unset( $data['key'], $data['value'] );

		}

		$key = sanitize_title( $data['label'] );

		// Insert the form field into the options array
		self::$options['wishlist-form-fields'][ $key ] = $data;

		self::update( 'wishlist-form-fields', self::$options['wishlist-form-fields'] );

		ob_start();

		self::wishlist_form_field( $key, $data );

		$field = ob_get_contents();
		ob_get_clean();

		wp_send_json_success( $field );

	}

	/**
	 * Delete a wishlist form field to the options array.
	 *
	 * @since 2.0.0
	 *
	 * @return false|mixed False on error, else markup for the option li element.
	 */
	public function delete_wishlist_form_field() {

		$key = ! empty( $_POST['key'] ) ? sanitize_text_field( $_POST['key'] ) : '';

		if ( ! $key || ! array_key_exists( $key, self::$options['wishlist-form-fields'] ) ) {

			wp_send_json_error( [ 'message' => __( 'Option not found.', 'rw-elephant-rental-inventory' ) ] );

		}

		unset( self::$options['wishlist-form-fields'][ $key ] );

		self::update( 'wishlist-form-fields', self::$options['wishlist-form-fields'] );

		wp_send_json_success();

	}

	/**
	 * Update a wishlist form field in the options array.
	 *
	 * @since 2.0.0
	 *
	 * @return false|mixed False on error, else markup for the option li element.
	 */
	public function update_wishlist_form_field() {

		$key       = ! empty( $_POST['key'] ) ? sanitize_text_field( $_POST['key'] ) : '';
		$form_data = ! empty( $_POST['data'] ) ? wp_strip_all_tags( $_POST['data'] ) : '';

		if ( ! $key || ! array_key_exists( $key, self::$options['wishlist-form-fields'] ) ) {

			wp_send_json_error( [ 'message' => __( 'Option not found.', 'rw-elephant-rental-inventory' ) ] );

		}

		if ( ! $form_data ) {

			wp_send_json_error( [ 'message' => __( 'Field data missing.', 'rw-elephant-rental-inventory' ) ] );

		}

		$form_data = wp_parse_args( $form_data );

		$form_data['default']  = isset( $form_data['default'] ) ? '1' : '0';
		$form_data['required'] = isset( $form_data['required'] ) ? '1' : '0';

		// Setup the options array
		if ( isset( $form_data['key'] ) && ! empty( $form_data['key'] ) ) {

			$form_data['options'] = array_filter( array_combine( $form_data['key'], $form_data['value'] ), 'strlen' );

			unset( $form_data['key'], $form_data['value'] );

		}

		self::$options['wishlist-form-fields'][ $key ] = $form_data;

		self::update( 'wishlist-form-fields', self::$options['wishlist-form-fields'] );

		ob_start();

		self::wishlist_form_field( $key, self::$options['wishlist-form-fields'][ $key ] );

		$field = ob_get_contents();
		ob_get_clean();

		wp_send_json_success( $field );

	}

	/**
	 * Toggle Cache on/off
	 *
	 * @since 2.1.2
	 */
	public function toggle_cache() {

		$cache_enabled = ! empty( $_POST['cacheEnabled'] ) ? sanitize_text_field( $_POST['cacheEnabled'] ) : '';
		$cache_enabled = ( 'true' === $cache_enabled ) ? 1 : 0;

		$options = self::$options;

		$options['disable-cache'] = $cache_enabled;

		update_option( 'rw-elephant-rental-inventory', $options );

		wp_send_json_success( $cache_enabled );

	}

	/**
	 * Sanitize the settings and save them to the database.
	 *
	 * @since 2.0.0
	 */
	public function sanitize_settings() {

		$nonce    = ! empty( $_POST['rw_elephant_save_settings'] ) ? sanitize_text_field( $_POST['rw_elephant_save_settings'] ) : '';
		$settings = filter_input( INPUT_POST, 'rw-elephant-rental-inventory', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$tab      = ! empty( $_POST['tab'] ) ? sanitize_text_field( $_POST['tab'] ) : '';

		if ( ! $settings || empty( $settings ) || ! $nonce || ! wp_verify_nonce( $nonce, 'rw_elephant_settings' ) ) {

			return;

		}

		/**
		 * Make tweaks to the submitted options, such as adding a 'false' value to
		 * unchecked checkboxes.
		 */
		$new_options = $this->update_settings_pre_save( $settings, $tab );

		/**
		 * Filter the settings before they are saved into the database.
		 *
		 * @since 2.0.0
		 *
		 * @var array
		 */
		$new_options = (array) apply_filters( 'rwe_elephant_settings_pre_save', wp_parse_args( $new_options, self::$options ) );

		foreach ( $new_options as $option_name => $option_value ) {

			if ( in_array( $option_name, [ 'gallery-page', 'wishlist-page' ], true ) ) {

				$new_options[ $option_name ] = absint( $option_value );

				continue;

			}

			if ( is_array( $option_value ) ) {

				array_walk_recursive( $option_value, 'sanitize_text_field' );

				$new_options[ $option_name ] = $option_value;

				continue;

			}

			$new_options[ $option_name ] = sanitize_text_field( $option_value );

		}

		foreach ( $new_options['item-notes'] as $key => $note ) {

			if ( ! empty( $note['title'] ) || ! empty( $note['text'] ) ) {

				continue;

			}

			unset( $new_options['item-notes'][ $key ] );

		}

		// Strip any empty thumbnails that are empty.
		$new_options['thumbnails'] = array_filter( array_map( 'array_filter', $new_options['thumbnails'] ) );

		foreach( $new_options['category-descriptions'] as $category => $description ) {
			$new_options['category-descriptions'][$category] = sanitize_text_field( $description );
		}

		update_option( 'rw-elephant-rental-inventory', $new_options );

		// Flush cache and check the connection status if the tenant-id or the api-key changes
		// or if is-connected is false and tenant-id and api-key are not empty.
		if ( ( self::$options['tenant-id'] !== $new_options['tenant-id'] ) || self::$options['api-key'] !== $new_options['api-key'] || ( ! empty( self::$options['tenant-id'] ) && ! empty( $new_options['api-key'] ) && ! self::$options['is-connected'] ) ) {

			rwe_flush_cache();
			Walkthrough::test_api_connection();

		}

		$query_args = [ 'settings-updated' => true ];

		if ( $tab ) {

			$query_args['tab'] = $tab;

		}

		// Flush rewrite rules when the gallery page is changed
		if ( self::$options['gallery-page'] !== $new_options['gallery-page'] ) {

			delete_option( 'rewrite_rules' );

			rwe_custom_rewrite_rules();

		}

		wp_safe_redirect( add_query_arg( $query_args, admin_url( 'options-general.php?page=rw-elephant' ) ) );

		exit;

	}

	/**
	 * Make tweaks to the submitted settings before they are saved to the database.
	 *
	 * @param  array  $settings Options that were submitted for save.
	 * @param  string $tab      The current settings tab being saved.
	 *
	 * @since 2.0.0
	 *
	 * @return array            Filtered options array.
	 */
	public function update_settings_pre_save( $settings, $tab ) {

		switch ( $tab ) {

			default: // @codingStandardsIgnoreLine
				break; // @codingStandardsIgnoreLine

			case __( 'gallery', 'rw-elephant-rental-inventory' ):
				$settings['enable-hover-thumb'] = isset( $settings['enable-hover-thumb'] );
				break;

			case __( 'items', 'rw-elephant-rental-inventory' ):
				$settings['pinterest-button']      = isset( $settings['pinterest-button'] );
				$settings['contact-button']        = isset( $settings['contact-button'] );
				$settings['display-kit-contents']  = isset( $settings['display-kit-contents'] );
				$settings['display-related-items'] = isset( $settings['display-related-items'] );
				break;

			case __( 'wishlist', 'rw-elephant-rental-inventory' ):
				$settings['enable-wishlists']            = isset( $settings['enable-wishlists'] );
				$settings['wishlist-limit']              = isset( $settings['wishlist-limit'] );
				$settings['enable-wishlist-gallery-add'] = isset( $settings['enable-wishlist-gallery-add'] );

				// Set the proper form fields order
				$form_fields = [];

				foreach ( $settings['wishlist-form-fields'] as $field_key ) {

					if ( ! array_key_exists( $field_key, self::$options['wishlist-form-fields'] ) ) {

						continue;

					}

					$form_fields[ $field_key ] = self::$options['wishlist-form-fields'][ $field_key ];

				}

				$settings['wishlist-form-fields'] = $form_fields;

				break;

		}

		return $settings;

	}

	/**
	 * Render the page select dropdown
	 *
	 * @param string $option_name The option name to render a select for.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the select field.
	 */
	public function render_page_select( $option_name ) {

		$pages = get_pages();

		if ( ! $pages || empty( $pages ) ) {

			return printf(
				'<em>%s</em>',
				esc_html__( 'No pages found.', 'rw-elephant-rental-inventory' )
			);

		}

		printf(
			'<select name="rw-elephant-rental-inventory[%s]">',
			esc_attr( $option_name )
		);

		foreach ( $pages as $page ) {

			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $page->ID ),
				esc_attr( selected( $page->ID, self::$options[ $option_name ], false ) ),
				esc_html( $page->post_title )
			);

		}

		print( '</select>' );

	}

	/**
	 * Display a settings updated notice.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the admin notice.
	 */
	public function settings_notice() {

		global $pagenow;

		$page             = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$settings_updated = ! empty( $_GET['settings-updated'] ) ? sanitize_text_field( $_GET['settings-updated'] ) : '';

		if ( 'options-general.php' !== $pagenow || ! $page || 'rw-elephant' !== $page || ! $settings_updated ) {

			return;

		}

		printf(
			'<div class="notice notice-success is-dismissible">
				<p>%s</p>
			</div>',
			__( 'Settings successfully updated.', 'rw-elephant-rental-inventory' )
		);

	}

	/**
	 * Display a notice when the cache/transients is flushed.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the cache flush notice.
	 */
	public function cache_flush_notice() {

		global $pagenow;

		$page          = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$cache_flushed = ! empty( $_GET['cache-flushed'] ) ? sanitize_text_field( $_GET['cache-flushed'] ) : '';

		if ( 'options-general.php' !== $pagenow || ! $page || 'rw-elephant' !== $page || ! $cache_flushed ) {

			return;

		}

		printf(
			'<div class="notice notice-success is-dismissible">
				<p>%s</p>
			</div>',
			__( 'Cache successfully flushed.', 'rw-elephant-rental-inventory' )
		);

	}

	/**
	 * Render the main settings content.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the settings page.
	 */
	private function render_sidebar() {

		?>

		<!-- sidebar -->
		<div id="postbox-container-1" class="postbox-container">

			<div class="meta-box-sortables">

				<div class="postbox">

					<!-- Toggle -->

					<h2 class="hndle"><span><?php esc_attr_e( 'RW Elephant', 'rw-elephant-rental-inventory' ); ?></span></h2>

					<div class="inside">
						<img src="<?php echo esc_url( RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/img/rw-elephant-logo.svg' ); ?>" class="logo widefat" />
						<hr />
						<p><?php esc_attr_e( "Mighty Inventory Software that's friendly and easy to use. Welcome to the fresh new world of online inventory management for your rental business.", 'rw-elephant-rental-inventory' ); ?></p>
					</div>
					<!-- .inside -->

				</div>
				<!-- .postbox -->

			</div>
			<!-- .meta-box-sortables -->

			<div class="meta-box-sortables">

				<div class="postbox">

					<div class="handlediv" title="Click to toggle"><br></div>
					<!-- Toggle -->

					<h2 class="hndle"><span><?php esc_attr_e( 'Flush Cached Data', 'rw-elephant-rental-inventory' ); ?></span></h2>

					<div class="inside">
						<p><?php esc_attr_e( 'RW Elephant inventory data is cached locally to improve performance. If changes were made to your inventory but are not reflected on the site you can flush cache to retreive new data.', 'rw-elephant-rental-inventory' ); ?></p>
						<form name="rw-elephant-flush-cache" method="POST">
							<input type="hidden" name="tab" value="<?php echo esc_attr( $this->tab ); ?>" />
							<?php
							wp_nonce_field( 'rw_elephant_flush_cache', 'flush_cache' );
							$btn_atts = ( ! rwe_do_transients_exist() || WP_DEBUG ) ? [ 'disabled' => 'disabled' ] : [];
							submit_button( esc_html__( 'Flush Cache', 'rw-elephant-rental-inventory' ), 'secondary widefat', 'flush-cache', false, $btn_atts );
							if ( WP_DEBUG ) {
								printf(
									'<div class="rwe-notice" style="position: relative; padding: 0px 5px; border-left: 4px solid #ffb900; box-shadow: 0 1px 1px 0 rgba( 0, 0, 0, 0.1 ); margin-top: 1em;"><p style="margin: 0.25em 0; padding: 2px;">%s</p></div>',
									__( '<code>WP_DEBUG</code> is enabled. Caching is disabled.', 'rw-elephant-rental-inventory' )
								);
							}
							?>
						</form>

						<?php

						$cache_enabled_label = rwe_is_cache_disabled() ? __( 'Cache is disabled.', 'rw-elephant-rental-inventory' ) : __( 'Cache is enabled.', 'rw-elephant-rental-inventory' );
						$checked_attr        = rwe_is_cache_disabled() ? '' : 'checked';

						?>

						<!-- Toggle Cache -->
						<div class="toggle-cache">
							<h4><?php esc_html_e( 'Toggle Cache', 'rw-elephant-rental-inventory' ); ?></h4>
							<div class="display">
								<label class="label toggle">
									<input type="checkbox" class="toggle_input rwe-toggle-cache" <?php echo esc_attr( $checked_attr ); ?> <?php echo WP_DEBUG ? 'disabled="disabled"' : ''; ?> />
									<div class="toggle-control <?php echo esc_attr( get_user_option( 'admin_color' ) ); ?>"></div>
									<label class="toggle-label"><?php echo esc_html( $cache_enabled_label ); ?></label>
								</label>
							</div>
						</div>

					</div>
					<!-- .inside -->

				</div>
				<!-- .postbox -->

			</div>
			<!-- .meta-box-sortables -->

		</div>
		<!-- #postbox-container-1 .postbox-container -->

		<?php

	}

	/**
	 * Update a setting in the option array.
	 *
	 * @param string $option_name  The option name to update.
	 * @param string $option_value The value to update the option to.
	 * @param bool   $new_value    If a new value or not, if so it will be added to the options array
	 *
	 * @return null
	 */
	public static function update( $option_name = '', $option_value = '', $new_value = false ) {

		if ( empty( $option_name ) || ! isset( self::$options[ $option_name ] ) ) {

			if ( ! $new_value ) {

				return;

			}

		}

		self::$options[ $option_name ] = $option_value;

		update_option( 'rw-elephant-rental-inventory', self::$options );

	}

	/**
	 * Flush the API data on button click.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function flush_rwe_transients() {

		$nonce = ! empty( $_POST['flush_cache'] ) ? sanitize_text_field( $_POST['flush_cache'] ) : '';
		$tab   = ! empty( $_POST['tab'] ) ? sanitize_text_field( $_POST['tab'] ) : '';

		if ( ! $nonce || empty( $nonce ) || ! wp_verify_nonce( $nonce, 'rw_elephant_flush_cache' ) ) {

			return;

		}

		rwe_flush_cache();

		$query_args = [
			'cache-flushed' => '1',
		];

		if ( false !== $tab ) {

			$query_args['tab'] = $tab;

		}

		wp_safe_redirect( add_query_arg( $query_args, admin_url( 'options-general.php?page=rw-elephant' ) ) );

		exit;

	}

}
