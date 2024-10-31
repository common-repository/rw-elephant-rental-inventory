<?php
/**
 * Plugin Setup Walkthrough
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Widgets {

	public function __construct() {

		add_action( 'widgets_init', [ $this, 'rwe_widgets' ] );

	}

	public function rwe_widgets() {

		register_widget( new WishlistWidget() );

	}

}
