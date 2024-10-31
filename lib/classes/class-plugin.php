<?php

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Plugin {

	public static $item_slug;

	public static $placeholder_url;

	public static $wishlist;

	/**
	 * Class constructor
	 */
	public static function init() {

		/**
		 * Filter the item slug
		 *
		 * @var string
		 */
		self::$item_slug = (string) apply_filters( 'rw_elephant_item_base', 'item' );

		/**
		 * Placeholder image, when none is set.
		 *
		 * @var string
		 */
		self::$placeholder_url = RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/img/no-image-found.png';

		$options         = new Options();
		$walkthrough     = new Walkthrough();
		$gallery         = new Gallery();
		self::$wishlist  = new Wishlist();
		$template_loader = new Template_Loader();
		$widgets         = new Widgets();
		$actions         = new Actions();
		$upgrade         = new Upgrade();
		$sitemaps        = new Sitemaps();

		add_filter( 'plugin_action_links_rw-elephant-inventory-gallery/rw-elephant-inventory-gallery.php', [ 'RWEG\Plugin', 'plugin_action_links' ] );

	}

	/**
	 * Load a given RW Elephant template.
	 *
	 * @param string $template_name The template name to load.
	 * @param array  $data          Data that will be stored in the data container for later retreival.
	 *
	 * @since 2.0.0
	 *
	 * @return string Path to template file.
	 */
	public static function load_rw_template( $template_name = '' ) {

		if ( empty( $template_name ) ) {

			return;

		}

		$plugin_template = sprintf( RW_ELEPHANT_RENTAL_INVENTORY_PATH . 'templates/%s.php', $template_name );
		$local_template  = sprintf( get_stylesheet_directory() . '/rw-elephant/%s.php', $template_name );

		$template = file_exists( $local_template ) ? $local_template : $plugin_template;

		ob_start();
		include( $template );
		$contents = ob_get_contents();
		ob_get_clean();

		echo $contents; // xss ok

	}

	/**
	 * Custom plugin action links.
	 *
	 * @param  array $links Original array of plugin action links.
	 *
	 * @since 2.0.0
	 *
	 * @return array        Filtered array of links.
	 */
	public static function plugin_action_links( $links ) {

		$links[] = sprintf(
			'<a href="%1$s" title="%2$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=rw-elephant' ) ),
			esc_attr__( 'Settings', 'rw-elephant-rental-inventory' ),
			esc_html__( 'Settings', 'rw-elephant-rental-inventory' )
		);

		$links[] = sprintf(
			'<a href="%1$s" title="%2$s" target="_blank">%2$s</a>',
			esc_url( 'https://login.rwelephant.com/' ),
			esc_attr__( 'Account', 'rw-elephant-rental-inventory' ),
			esc_html__( 'Account', 'rw-elephant-rental-inventory' )
		);

		return $links;

	}

}
