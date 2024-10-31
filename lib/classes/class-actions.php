<?php
/**
 * RW Elephant Actions Class
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 2.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Actions {

	public function __construct() {

		/**
		 * Gallery
		 */
		// Header
		add_action( 'rw_elephant_gallery_header', 'rwe_breadcrumbs', 10 );
		add_action( 'rw_elephant_gallery_header', 'rwe_actions', 15 );

		add_action( 'rw_elephant_gallery_header_actions', 'rwe_wishlist_action_links', 10 );

		add_action( 'rw_elephant_gallery_top', 'rwe_gallery_error', 10 );

		// Inventory Item
		add_action( 'rw_elephant_inventory_item_content', 'rwe_image', 10 );
		add_action( 'rw_elephant_inventory_item_content', 'rwe_title', 15 );

		/**
		 * Single Product
		 */
		// Header
		add_action( 'rw_elephant_product_top', 'rwe_gallery_header', 10 );

		// Items
		add_filter( 'rwe_content', 'wptexturize' );
		add_filter( 'rwe_content', 'convert_smilies' );
		add_filter( 'rwe_content', 'convert_chars' );
		add_filter( 'rwe_content', 'wpautop' );
		add_filter( 'rwe_content', 'shortcode_unautop' );
		add_filter( 'rwe_content', 'prepend_attachment' );

		// Content
		add_action( 'rw_elephant_product_content', 'rwe_image_slider', 10 );
		add_action( 'rw_elephant_product_content', 'rwe_product_content', 15 );
		add_action( 'rw_elephant_product_content', 'rwe_kit_contents', 20 );

		add_action( 'rw_elephant_product_data', 'rwe_product_title', 10 );
		add_action( 'rw_elephant_product_data', 'rwe_product_details', 15 );
		add_action( 'rw_elephant_product_data', 'rwe_product_wishlist_button', 20 );
		add_action( 'rw_elephant_product_data', 'rwe_contact_links', 25 );

		add_action( 'rw_elephant_product_bottom', 'rwe_related_items', 10 );

		/**
		 * Wishlist
		 */
		add_action( 'rw_elephant_wishlist_top', 'rwe_wishlist_error_check', 5 );
		add_action( 'rw_elephant_wishlist_top', 'rwe_gallery_header', 10 );

		add_action( 'rw_elephant_wishlist_scripts', 'rwe_wishlist_scripts', 10 );

		add_action( 'rw_elephant_wishlist_main', 'rwe_wishlist_empty_text', 10, 2 );

		add_action( 'rw_elephant_wishlist_product', 'rwe_wishlist_product', 10 );

		add_action( 'rw_elephant_inventory_item_bottom', 'rwe_wishlist_product_quantity', 10 );

		add_action( 'rw_elephant_wishlist_bottom', 'rwe_wishlist_action_links', 10 );

		// Wishlist buttons on the gallery page
		add_action( 'rw_elephant_inventory_item_top', 'rwe_gallery_wishlist_button', 10 );
		add_filter( 'rw_elephant_wishlist_add_button_text', 'rwe_gallery_wishlist_add_icon', 10 );
		add_filter( 'rw_elephant_wishlist_remove_button_text', 'rwe_gallery_wishlist_remove_icon', 10 );

		// Wishlist Item
		add_action( 'rw_elephant_inventory_item_top', 'rwe_wishlist_remove_button', 10 );

		add_filter( 'pre_get_document_title', 'rwe_page_title_tag', PHP_INT_MAX );

		// Yoast SEO meta description and twitter meta descriptions
		add_filter( 'wpseo_opengraph_title', 'rwe_page_title_tag', PHP_INT_MAX );
		add_filter( 'wpseo_opengraph_desc', 'rwe_page_description_meta', PHP_INT_MAX );
		add_filter( 'wpseo_twitter_description', 'rwe_page_description_meta', PHP_INT_MAX );
		add_filter( 'wpseo_metadesc', 'rwe_page_description_meta', PHP_INT_MAX );

	}

}
