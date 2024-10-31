<?php
/**
 * Plugin Setup Walkthrough
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Walkthrough {

	public function __construct() {

		if ( ! is_admin() ) {

			return;

		}

		add_action( 'wp_ajax_complete_plugin_setup', [ $this, 'complete_plugin_setup' ] );

		add_action( 'wp_ajax_next_step', [ $this, 'next_step' ] );

		add_action( 'wp_ajax_test_api_connection', [ $this, 'test_api_connection' ] );

		$fresh_install = Options::$options['fresh-install'];

		if ( ! $fresh_install ) {

			return;

		}

		$this->init();

	}

	/**
	 * Initialize the walkthrough
	 *
	 * @since 2.0.0
	 */
	public function init() {

		// Disables the walkthrough popup after the first load
		Options::update( 'fresh-install', false );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_action( 'admin_footer', [ $this, 'walkthrough_markup' ] );

	}

	/**
	 * Enqueue walkthrough scripts and styles.
	 *
	 * @since 2.0.0
	 *
	 * @action admin_enqueue_scripts - 10
	 */
	public function enqueue_scripts() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$rtl    = is_rtl() ? '-rtl' : '';

		wp_enqueue_style( 'featherlight', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/featherlight{$suffix}.css", [], '1.7.9', 'all' );
		wp_enqueue_style( 'rwe-admin', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/rw-elephant-admin{$rtl}{$suffix}.css", [], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, 'all' );

		wp_enqueue_script( 'featherlight', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/featherlight{$suffix}.js", [], '1.7.9', false );
		wp_enqueue_script( 'rwe-walkthrough', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/rw-elephant-walkthrough{$suffix}.js", [ 'featherlight' ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, false );

		wp_localize_script(
			'rwe-walkthrough',
			'rweWalkthroughData',
			[
				'complete' => __( 'Success', 'rw-elephant-rental-inventory' ),
			]
		);

	}

	/**
	 * Generate the walkthrough markup.
	 *
	 * @action admin_footer - 10
	 *
	 * @return mixed Markup for the setup plugin walkthrough
	 */
	public function walkthrough_markup() {

		?>

		<div class="rwe-walkthrough">

			<!-- Step 1 -->
			<div class="step step-1">

				<!-- Title/Step -->
				<h2 class="text-center"><?php esc_html_e( 'RW Elephant Plugin Setup', 'rw-elephant-rental-inventory' ); ?></h2>
				<p class="current-step text-center"><?php esc_html_e( 'Step 1 of 4', 'rw-elephant-rental-inventory' ); ?></p>

				<!-- Sections -->
				<div class="wrap">

					<div class="section section--logo">
						<div class="walkthrough-logo">
							<img src="https://code.rwelephant.com/wp-plugin-assets/logo.png" />
						</div>
					</div>

					<div class="section section--text">
						<p><?php esc_html_e( "The RW Elephant WordPress Plugin enables rental businesses to seamlessly display their collection on their own websites. Easily show your customers your Rental Items with the images, descriptions, tags, and all of the other important details you've included in your RW Elephant account.", 'rw-elephant-rental-inventory' ); ?></p>
						<p><?php esc_html_e( "Using the plugin requires a monthly subscription to RW Elephant. If you don't already have an RW Elephant account, you can get started for free with a free trial. Upgrade or downgrade at anytime. No contracts. No sweat. Start your trail today and change your rental business for the better!", 'rw-elephant-rental-inventory' ); ?></p>
						<p><a href="//rwelephant.com/plans" target="_blank"><?php esc_html_e( 'Start your free trial', 'rw-elephant-rental-inventory' ); ?></a></p>
					</div>

				</div>

				<!-- Actions -->
				<div class="actions">
					<a href="#" class="js-go-to-step button button-primary" data-type="next" data-step="2"><?php esc_html_e( "I have an existing account, let's go!", 'rw-elephant-rental-inventory' ); ?></a>
				</div>

			</div>
			<!-- /.step-1 -->

			<!-- Step 2 -->
			<div class="step step-2 hidden">

				<!-- Title/Step -->
				<h2 class="text-center"><?php esc_html_e( 'RW Elephant Plugin Setup', 'rw-elephant-rental-inventory' ); ?></h2>
				<p class="current-step text-center"><?php esc_html_e( 'Step 2 of 4', 'rw-elephant-rental-inventory' ); ?></p>

				<div class="connection-error rwe-alert danger hidden"></div>

				<!-- Sections -->
				<div class="wrap">

					<div class="section section--demo">
						<div class="walkthrough-demo">
							<img src="https://code.rwelephant.com/wp-plugin-assets/apikey.gif" />
						</div>
					</div>

					<div class="section section--text">
						<p><?php esc_html_e( 'Start by entering your RW Elephant Tenant ID and API Key from the RW Elephant Application. You\'ll find those under the Account Icon (upper right corner of the application), then click on "Online Gallery Settings" in the list on the left.', 'rw-elephant-rental-inventory' ); ?></p>

						<div class="form-field">
							<label for="tenant-id"><strong><?php esc_html_e( 'RWE Tenant ID', 'rw-elephant-rental-inventory' ); ?></strong></label>
							<input type="text" class="widefat" id="tenant-id" name="tenant-id" required />
						</div>

						<div class="form-field">
							<label for="api-key"><strong><?php esc_html_e( 'RWE API Key', 'rw-elephant-rental-inventory' ); ?></strong></label>
							<input type="text" class="widefat" id="api-key" name="api-key" required />
						</div>
					</div>

				</div>

				<!-- Actions -->
				<div class="actions">
					<a href="#" class="js-go-to-step button button-primary" data-type="next" data-step="3"><?php esc_html_e( 'Continue', 'rw-elephant-rental-inventory' ); ?></a>
				</div>

			</div>
			<!-- /.step-2 -->

			<!-- Step 3 -->
			<div class="step step-3 hidden">

				<!-- Title/Step -->
				<h2 class="text-center"><?php esc_html_e( 'RW Elephant Plugin Setup', 'rw-elephant-rental-inventory' ); ?></h2>
				<p class="current-step text-center"><?php esc_html_e( 'Step 3 of 4', 'rw-elephant-rental-inventory' ); ?></p>

				<!-- Sections -->
				<div class="wrap">

					<div class="section section--pages">
						<div class="walkthrough-pages">
							<img src="https://code.rwelephant.com/wp-plugin-assets/pages.png" />
						</div>
					</div>

					<div class="section section--text">
						<p><?php esc_html_e( 'The plugin will need two initial pages in order to function on your WordPress website. One page will display the "Inventory Gallery" and the other will display "Wishlist" functionality.', 'rw-elephant-rental-inventory' ); ?></p>
						<p><?php esc_html_e( "If you haven't created these pages on your site, the plugin will automatically generate them now. If you have, you can select both pages below:", 'rw-elephant-rental-inventory' ); ?></p>

						<div class="form-field">
							<label for="gallery-page"><strong><?php esc_html_e( 'Gallery page', 'rw-elephant-rental-inventory' ); ?></strong></label>
							<?php
							$gallery_page = new WP_Query( array(
								'post_type' => 'page',
								'title' => __( 'Gallery', 'rw-elephant-rental-inventory' ),
								'post_status' => 'all',
								'posts_per_page' => 1,
								'no_found_rows' => true,
								'ignore_sticky_posts' => true,
								'update_post_term_cache' => false,
								'update_post_meta_cache' => false,
								'orderby' => 'post_date ID',
								'order' => 'ASC',
							) );
							$exclude_pages = get_option( 'page_on_front', 0 ) . ',' . get_option( 'page_for_posts', 0 );
							wp_dropdown_pages(
								[
									'name'             => 'gallery-page',
									'exclude'          => $exclude_pages,
									'id'               => 'gallery-page',
									'show_option_none' => __( 'Choose page', 'rw-elephant-rental-inventory' ),
									'class'            => 'widefat',
									'selected'         => ! empty( $gallery_page->post ) ? $gallery_page->post->ID : '',
								]
							);
							?>
						</div>

						<div class="form-field">
							<label for="wishlist-page"><strong><?php esc_html_e( 'Wishlist page', 'rw-elephant-rental-inventory' ); ?></strong></label>
							<?php
							$wishlist_page = new WP_Query( array(
								'post_type' => 'page',
								'title' => __( 'Wishlist', 'rw-elephant-rental-inventory' ),
								'post_status' => 'all',
								'posts_per_page' => 1,
								'no_found_rows' => true,
								'ignore_sticky_posts' => true,
								'update_post_term_cache' => false,
								'update_post_meta_cache' => false,
								'orderby' => 'post_date ID',
								'order' => 'ASC',
							) );
							wp_dropdown_pages(
								[
									'name'             => 'wishlist-page',
									'exclude'          => $exclude_pages,
									'id'               => 'wishlist-page',
									'show_option_none' => __( 'Choose page', 'rw-elephant-rental-inventory' ),
									'class'            => 'widefat',
									'selected'         => ! empty( $wishlist_page->post ) ? $wishlist_page->post->ID : '',
								]
							);
							?>
						</div>
					</div>

				</div>

				<!-- Actions -->
				<div class="actions">
					<a href="#" class="js-go-to-step button button-secondary" data-type="prev" data-step="2"><?php esc_html_e( 'Back', 'rw-elephant-rental-inventory' ); ?></a>
					<a href="#" class="js-go-to-step button button-primary" data-type="next" data-step="4"><?php esc_html_e( 'Continue', 'rw-elephant-rental-inventory' ); ?></a>
				</div>

			</div>
			<!-- /.step-3 -->

			<!-- Step 4 -->
			<div class="step step-4 hidden">

				<!-- Title/Step -->
				<h2 class="text-center"><?php esc_html_e( 'RW Elephant Plugin Setup', 'rw-elephant-rental-inventory' ); ?></h2>
				<p class="current-step text-center"><?php esc_html_e( 'Step 4 of 4', 'rw-elephant-rental-inventory' ); ?></p>

				<!-- Sections -->
				<div class="wrap">

					<div class="section section--final">
						<img src="https://code.rwelephant.com/wp-plugin-assets/settings.png" />
					</div>

					<div class="section section--text">
						<p><?php esc_html_e( "Ok! That should cover all the basic information that we'll need to get you set up! In the following step, the plugin will apply your choosen settings and generate any required pages.", 'rw-elephant-rental-inventory' ); ?></p>
						<p><?php esc_html_e( "If you want to make any last minute changes, feel free navigate back and do so now. You'll also be able to update all plugin settings at a later time within the RW Elephant plugin settings page.", 'rw-elephant-rental-inventory' ); ?></p>
					</div>

				</div>

				<!-- Actions -->
				<div class="actions">
					<a href="#" class="js-go-to-step button button-secondary" data-type="prev" data-step="3"><?php esc_html_e( 'Back', 'rw-elephant-rental-inventory' ); ?></a>
					<a href="#" class="js-go-to-step button button-primary" data-type="finish"><?php esc_html_e( 'Finish!', 'rw-elephant-rental-inventory' ); ?></a>
				</div>

			</div>
			<!-- /.step-4 -->

			<!-- Step 4 -->
			<div class="step final-step hidden">

				<!-- Title/Step -->
				<h2 class="text-center"><?php esc_html_e( 'RW Elephant Plugin Setup', 'rw-elephant-rental-inventory' ); ?></h2>
				<p class="current-step text-center"><?php esc_html_e( 'Setting up plugin...', 'rw-elephant-rental-inventory' ); ?></p>

				<img class="rwe-preloader-final" src="<?php echo esc_url( RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/img/loading.gif' ); ?>" />

				<!-- AJAX results -->
				<div class="walkthrough-results rwe-alert"></div>

				<!-- Sections -->
				<div class="wrap">

				</div>

			</div>
			<!-- /.step-4 -->

			<!-- Preloader -->
			<img class="rwe-preloader hidden" src="<?php echo esc_url( RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/img/loading.gif' ); ?>" />
		</div>

		<?php

	}

	/**
	 * AJAX Handler to update the options and create the pages
	 *
	 * @action wp_ajax_complete_plugin_setup - 10
	 *
	 * @return object JSON object with success/error details
	 */
	public function complete_plugin_setup() {

		$setup_data = filter_input( INPUT_POST, 'rw_plugin_data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! $setup_data || ( empty( $setup_data['tenantID'] ) || empty( $setup_data['apiKey'] ) ) ) {

			wp_send_json_error(
				sprintf(
					/* translators: Link to the settings page. */
					__( 'We encountered an error setting up the plugin. Head to the %s to manually setup the plugin.', 'rw-elephant-rental-inventory' ),
					sprintf(
						'<a href="%1$s" %2$s>%2$s</a>',
						esc_url( admin_url( 'options-general.php?page=rw-elephant' ) ),
						esc_html__( 'settings page', 'rw-elephant-rental-inventory' )
					)
				)
			);

		}

		$gallery_page  = ! empty( $setup_data['galleryPage'] ) ? (int) $setup_data['galleryPage'] : false;
		$wishlist_page = ! empty( $setup_data['wishlistPage'] ) ? (int) $setup_data['wishlistPage'] : false;

		// Create the gallery page
		if ( ! $gallery_page ) {

			$gallery_page = wp_insert_post(
				[
					'post_title'  => __( 'Gallery', 'rw-elephant-rental-inventory' ),
					'post_type'   => 'page',
					'post_status' => 'publish',
				]
			);

			// If there was an error, set back to null
			if ( is_wp_error( $gallery_page ) ) {

				$gallery_page = '';

			}

		}

		// Create the wishlist page
		if ( ! $wishlist_page ) {

			$wishlist_page = wp_insert_post(
				[
					'post_title'  => __( 'Wishlist', 'rw-elephant-rental-inventory' ),
					'post_type'   => 'page',
					'post_status' => 'publish',
				]
			);

			// If there was an error, set back to null
			if ( is_wp_error( $wishlist_page ) ) {

				$wishlist_page = '';

			}

		}

		// Update tenant-id and api-key
		$options = wp_parse_args(
			[
				'api-key'       => sanitize_text_field( $setup_data['apiKey'] ),
				'tenant-id'     => sanitize_text_field( $setup_data['tenantID'] ),
				'gallery-page'  => $gallery_page,
				'wishlist-page' => $wishlist_page,
			],
			Options::$options
		);

		update_option( 'rw-elephant-rental-inventory', $options );

		self::test_api_connection();

		delete_option( 'rewrite_rules' );

		rwe_custom_rewrite_rules();

		// Log the connected state
		wp_send_json_success(
			sprintf(
				/* translators: Link to the settings page. */
				__( 'RW Elephant Rental Inventory is successfully setup. Head to the %s to adjust the appearance of your product inventory.', 'rw-elephant-rental-inventory' ),
				sprintf(
					'<a href="%1$s" title="%2$s">%2$s</a>',
					esc_url( admin_url( 'options-general.php?page=rw-elephant' ) ),
					esc_html__( 'settings page', 'rw-elephant-rental-inventory' )
				)
			)
		);

	}

	/**
	 * Go to the next step in the walkthrough.
	 *
	 * @return boolean True to continue, false to go back
	 */
	public function next_step() {

		$step = ! empty( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';

		if ( '3' === $step ) {

			$tenant_id = ! empty( $_POST['tenantID'] ) ? sanitize_text_field( $_POST['tenantID'] ) : '';
			$api_key   = ! empty( $_POST['apiKey'] ) ? sanitize_text_field( $_POST['apiKey'] ) : '';

			if ( ! $tenant_id || ! $api_key ) {

				wp_send_json_error();

			}

			Options::update( 'tenant-id', $tenant_id );
			Options::update( 'api-key', $api_key );

			self::test_api_connection();

			$options = new Options();

			if ( isset( $options::$options['is-connected'] ) && ! $options::$options['is-connected'] ) {

				wp_send_json_error(
					[
						'error' => $options::$options['connection-response'],
					]
				);

			}

		}

		wp_send_json_success();

	}

	/**
	 * Test the API connection with the stored credentials, and update the
	 * 'is-connected' option with the connection state.
	 *
	 * @return null
	 */
	public static function test_api_connection() {

		// Instantiate a new options class, to update the static $options property
		$options  = new Options();
		$api      = new API( 'list_inventory_types' );
		$is_error = false;
		$message  = '';

		$test = $api->request();

		if ( is_wp_error( $test ) ) {

			$is_error = true;
			$message  = $test->get_error_message();

		}

		if ( is_array( $test ) && isset( $test['response_status'] ) && 'Error' === $test['response_status'] ) {

			$is_error = true;
			$message  = __( 'An error occurred. Double check your API key and try again.', 'rw-elephant-rental-inventory' );

		}

		update_option(
			'rw-elephant-rental-inventory',
			wp_parse_args(
				[
					'is-connected'        => $is_error ? false : true,
					'connection-response' => $message,
				],
				Options::$options
			)
		);

	}

}
