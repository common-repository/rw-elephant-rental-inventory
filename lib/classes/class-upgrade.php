<?php
/**
 * Upgrades
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Upgrade {

	/**
	 * Upgrade methods array.
	 *
	 * @var array
	 */
	private $upgrades = [];

	/**
	 * Security check
	 *
	 * @var string
	 */
	private $nonce_failed = false;

	public function __construct() {

		if ( ! is_admin() ) {

			return;

		}

		if ( ! $this->is_upgrade_available() || empty( $this->upgrades ) ) {

			return;

		}

		add_action( 'admin_notices', [ $this, 'upgrade_admin_notice' ] );

		add_action(
			'admin_menu',
			function() {
				add_submenu_page(
					null,
					esc_html__( 'RW Elephant Rental Inventory - Upgrade', 'rw-elephant-rental-inventory' ),
					esc_html__( 'RW Elephant Rental Inventory - Upgrade', 'rw-elephant-rental-inventory' ),
					'manage_options',
					'rw-elephant-inventory-gallery-upgrade',
					[ $this, 'upgrade_page' ]
				);
			}
		);

		add_action( 'admin_init', [ $this, 'hide_admin_notices_on_upgrade_page' ] );

		// AJAX handlers
		// eg: $upgrade = two_two_zero
		if ( ! empty( $this->upgrades ) ) {

			foreach ( $this->upgrades as $upgrade ) {

				add_action( "wp_ajax_{$upgrade}", [ $this, "{$upgrade}" ] );

			}
		}

		// Security check
		$this->nonce        = ! empty( $_GET['rwe-upgrade'] ) ? sanitize_text_field( $_GET['rwe-upgrade'] ) : '';
		$this->nonce_failed = ( ! $this->nonce || ! wp_verify_nonce( $this->nonce, 'start' ) );

		if ( ! $this->nonce || ! wp_verify_nonce( $this->nonce, 'start' ) ) {

			return;

		}

		add_action( 'admin_enqueue_scripts', [ $this, 'upgrade_scripts' ] );

	}

	/**
	 * Enqueue the upgrader script.
	 *
	 * @return null
	 */
	public function upgrade_scripts() {

		$page = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		if ( ! $page || 'rw-elephant-inventory-gallery-upgrade' !== $page ) {

			return;

		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'rw-elephant-upgrade', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/rw-upgrade{$suffix}.js", [], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, true );

		wp_localize_script(
			'rw-elephant-upgrade',
			'rwUpgrades',
			[
				'upgrades'      => $this->upgrades,
				'errorResponse' => esc_html__( 'Error encountered during the upgrade routine. Please try again. If the error persists, please contact RW Elephant support.', 'rw-elephant-rental-inventory' ),
				'settingsURL'   => esc_url( admin_url( 'options-general.php?page=rw-elephant' ) ),
			]
		);

	}

	/**
	 * Remove all admin notices on the upgrade page.
	 *
	 * @return null
	 */
	public function hide_admin_notices_on_upgrade_page() {

		$page = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		if ( ! $page || 'rw-elephant-inventory-gallery-upgrade' !== $page ) {

			return;

		}

		remove_all_actions( 'admin_notices' );

	}

	/**
	 * Render the page where the upgrader will run.
	 *
	 * @return mixed Markup for the upgrade page
	 */
	public function upgrade_page() {

		?>

		<div class="wrap">

			<h1><?php esc_html_e( 'Upgrade', 'rw-elephant-rental-inventory' ); ?></h1>

			<?php

			if ( $this->nonce_failed ) {

				return printf(
					'<p>%s</p>',
					esc_html__( 'An error occurred. Please go back and try again.', 'rw-elephant-rental-inventory' )
				);

			}

			?>

			<style type="text/css">
			.rwe-upgrade-notice-wrap {
				position: relative
			}

			.rwe-upgrade-notice-wrap .rwe-progress {
				background: #ddd;
				position: absolute;
				bottom: 15px;
				width: 95%;
				height: 15px
			}

			.rwe-upgrade-notice-wrap .rwe-progress div {
				background: #ccc;
				height: 100%;
				width: 0
			}

			.rwe-upgrade-notice-wrap .spinner {
				background-image: url(images/spinner-2x.gif);
				margin: 4px 10px 8px;
				float: right
			}

			.rwe-upgrade-notice-wrap {
				background-color: #f4f4f4;
				border-style: solid;
				border-width: 1px 0;
				border-color: #eae9e9;
				padding: 12px 12px 4px;
				overflow: auto;
				margin: 20px -12px -23px;
				position: relative;
				width: 100%
			}

			.rwe-upgrade-notice-wrap .spinner {
				margin: 4px 10px 8px;
				float: right
			}

			.admin-color-fresh .rwe-upgrade-notice-wrap .rwe-progress div {
				background: #0073aa
			}

			.admin-color-light .rwe-upgrade-notice-wrap .rwe-progress div {
				background: #888
			}

			.admin-color-blue .rwe-upgrade-notice-wrap .rwe-progress div {
				background: #096484
			}

			.admin-color-coffee .rwe-upgrade-notice-wrap .rwe-progress div {
				background: #c7a589
			}

			.admin-color-ectoplasm .rwe-upgrade-notice-wrap .rwe-progress div {
				background: #a3b745
			}

			.admin-color-midnight .rwe-upgrade-notice-wrap .rwe-progress div {
				background: #e14d43
			}

			.admin-color-sunrise .rwe-upgrade-notice-wrap .rwe-progress div {
				background: #dd823b
			}
			</style>

			<div class="metabox-holder">
				<div class="postbox">
					<h3><span><?php esc_html_e( 'Sit Tight!', 'rw-elephant-rental-inventory' ); ?></span></h3>
					<div class="inside">
						<p><?php esc_html_e( "We're working on getting the RW Elephant Gallery plugin up to speed. This should only take a few moments. You will be redirected when the process completes.", 'rw-elephant-rental-inventory' ); ?></p>
						<div class="rwe-upgrade-notice-wrap">
							<span class="spinner is-active"></span>
							<div class="rwe-progress">
								<div class="progress-bar"></div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>

		<?php

	}

	/**
	 * Is an upgrade available to the user?
	 *
	 * @return boolean True when an upgrade is available, else false.
	 */
	public function is_upgrade_available() {

		$upgrade_available = false;

		// Upgrade from 2.0.3 => 2.0.4
		// Adds missing thumbnail-size option (plugin_320px_padded)
		if ( ! isset( Options::$options['thumbnail-size'] ) ) {

			$this->upgrades[] = 'two_zero_four';

			$upgrade_available = true;

		}

		// Upgrade to  2.2.0
		// Adds missing disable-cache option (false)
		if ( ! isset( Options::$options['disable-cache'] ) ) {

			$this->upgrades[] = 'two_two_zero';

			$upgrade_available = true;

		}

		// Upgrade to  2.2.3
		// Update the thumbnail size setting
		if ( ! isset( Options::$options['thumbnail-size'] ) || ( in_array( Options::$options['thumbnail-size'], [ 'plugin_320px_padded', 'plugin_320px_thumbnail' ], true ) ) ) {

			$this->upgrades[] = 'two_two_three';

			$upgrade_available = true;

		}

		return $upgrade_available;

	}

	/**
	 * Admin notice informing of an availible upgrade
	 *
	 * @return mixed Markup for the admin notice.
	 */
	public function upgrade_admin_notice() {

		?>

		<div class="notice notice-info">

			<p><?php esc_html_e( 'It looks like an upgrade is available for the RW Elephant settings. To ensure the RW Elephant Rental Inventory plugin continues to work without issue, please proceed with the upgrade now.', 'rw-elephant-rental-inventory' ); ?></p>

			<p>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'page', 'rw-elephant-inventory-gallery-upgrade', admin_url() ), 'start', 'rwe-upgrade' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Upgrade Now', 'easy-digital-downloads' ); ?></a>
			</p>

		</div>

		<?php

	}

	/**
	 * Initialize the upgrade to 2.0.4.
	 *
	 * @return null
	 */
	public function two_zero_four() {

		Options::update( 'thumbnail-size', 'plugin_320px_padded', true );

		$options        = get_option( 'rw-elephant-rental-inventory', [] );
		$option_updated = ( isset( $options['thumbnail-size'] ) && 'plugin_320px_padded' === $options['thumbnail-size'] );

		if ( $option_updated ) {

			wp_send_json_success();

		} else {

			wp_send_json_error();

		}

	}

	/**
	 * Initialize the upgrade to 2.2.0.
	 *
	 * @return null
	 */
	public function two_two_zero() {

		Options::update( 'disable-cache', false, true );

		$options        = get_option( 'rw-elephant-rental-inventory', [] );
		$option_updated = ( isset( $options['disable-cache'] ) && false === $options['disable-cache'] );

		if ( $option_updated ) {

			wp_send_json_success();

		} else {

			wp_send_json_error();

		}

	}

	/**
	 * Initialize the upgrade to 2.2.3.
	 *
	 * @return null
	 */
	public function two_two_three() {

		Options::update( 'thumbnail-size', Options::$options['thumbnail-size'] . '_link', true );

		$options        = get_option( 'rw-elephant-rental-inventory', [] );
		$option_updated = ( isset( $options['thumbnail-size'] ) && in_array( $options['thumbnail-size'], [ 'plugin_320px_padded_link', 'plugin_320px_thumbnail_link' ], true ) );

		if ( $option_updated ) {

			wp_send_json_success();

		} else {

			wp_send_json_error();

		}

	}

}
