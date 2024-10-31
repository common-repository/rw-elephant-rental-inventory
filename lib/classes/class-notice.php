<?php
/**
 * Admin Notice Class
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Notice {

	private $type;

	private $text;

	private $shutdown;

	public function __construct( $type = 'success', $text = '', $shutdown = false ) {

		$this->type     = $type;
		$this->text     = $text;
		$this->shutdown = $shutdown;

		$this->init();

	}

	public function init() {

		add_action( 'admin_notices', [ $this, 'generate_admin_notice' ] );

	}

	/**
	 * Generate the HTML markup for the admin notice
	 *
	 * @return mixed HTML markup for the admin notice.
	 */
	public function generate_admin_notice() {

		printf(
			'<div class="%1$s"><p>%2$s</p></div>',
			esc_html( 'notice notice-' . $this->type ),
			wp_kses_post( $this->text )
		);

		if ( $this->shutdown ) {

			deactivate_plugins( \RWEgallery::$plugin_data['base'] );

		}

	}

}
