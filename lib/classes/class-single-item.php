<?php
/**
 * RW Elephant Rental Inventory - Single Item
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Single_Item {

	private $inventory_id;

	public static $item_data;

	// Gallery page content
	private $gallery_content;

	public function __construct( $inventory_id ) {

		if ( ! $inventory_id || empty( $inventory_id ) ) {

			return new \WP_Error( 'error', __( 'Missing inventory ID.', 'rw-elephant-rental-inventory' ) );

		}

		$this->gallery_content = apply_filters( 'the_content', get_post_field( 'post_content', Options::$options['gallery-page'] ) );

		$this->setup_single_item();

		$this->inventory_id = $inventory_id;
		$api                = new API( 'item_info', [ 'inventory_item_id' => $this->inventory_id ] );

		$item_data = $api->request();

		if ( empty( $item_data ) ) {

			global $wp_query;

			$wp_query->set_404();

			status_header( 404 );

			get_template_part( 404 );

			exit();

		}

		// If there is an error, bail and redirect to the gallery page to display the error.
		if ( is_wp_error( $item_data ) ) {

			wp_safe_redirect(
				add_query_arg(
					'product_error',
					$item_data->get_error_message(),
					get_the_permalink( Options::$options['gallery-page'] )
				)
			);

			exit;

		}

		self::$item_data = isset( $item_data[0] ) ? $item_data[0] : false;

		if ( ! self::$item_data ) {

			return new \WP_Error( 'error', __( 'No product data found.', 'rw-elephant-rental-inventory' ) );

		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Single Item Filters
		add_filter(
			'document_title_parts',
			function( $title_parts ) {

				$title_parts['title'] = rwe_item_name( false );

				return $title_parts;

			}
		);

		add_filter( 'the_content', [ $this, 'single_content_loader' ], 10, 2 );

		/**
		 * Remove admin bar menu links.
		 * Edit & Comments
		 *
		 * @since 2.0.0
		 *
		 * @var array Admin bar node array
		 */
		add_action(
			'admin_bar_menu',
			function( $wp_admin_bar ) {

				$wp_admin_bar->remove_node( 'edit' );
				$wp_admin_bar->remove_node( 'comments' );

			},
			999
		);

	}

	/**
	 * Setup the single item page.
	 *
	 * @since 2.0.0
	 */
	public function setup_single_item() {

		// Add body class
		add_filter(
			'body_class',
			function( $classes ) {

				$classes[] = 'page';
				$classes[] = is_rwe_single() ? 'rwe-single-item' : '';

				return $classes;

			}
		);

		// Disable comments
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'ping_open', '__return_false', 20, 2 );
		add_filter( 'get_comments_number', '__return_zero', 10, 2 );
		add_filter( 'comments_template', '__return_null' );

		// Disable the edit post link output
		add_filter( 'edit_post_link', '__return_false' );

		// SEO actions and filters
		add_filter( 'get_canonical_url', [ $this, 'canonical_url' ], 1, 100 );
		add_filter( 'wpseo_canonical', [ $this, 'canonical_url' ] );
		add_filter( 'language_attributes', [ $this, 'add_open_graph_doctype' ] );
		add_action( 'wp_head', [ $this, 'open_graph_meta_tags' ], 5 );

	}

	/**
	 * Filter the canonical URL for single item pages.
	 *
	 * @since 2.2.19
	 *
	 * @return string URL of the current item.
	 */
	public function canonical_url( $canonical ) {
		if ( ! is_rwe_single() ) {
			return $canonical;
		}
		global $wp;
		return apply_filters( 'rw_elephant_single_item_canonical_url', home_url( $wp->request ) . '/' );
	}

	/**
	 * Enqueue Scripts and Styles for single items.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function enqueue_scripts() {

		$rtl    = ! is_rtl() ? '' : '-rtl';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'slick', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/slick{$suffix}.css", [], '1.8.1', 'all' );
		wp_enqueue_style( 'slick-lightbox', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/slick-lightbox{$suffix}.css", [ 'slick' ], '0.2.12', 'all' );
		wp_enqueue_style( 'rwe-single-item', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/rw-elephant{$rtl}{$suffix}.css", [ 'slick-lightbox' ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, 'all' );

		// Load styles from the options
		rwe_gallery_option_styles( 'rwe-single-item' );

		wp_enqueue_script( 'slick', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/slick{$suffix}.js", [ 'jquery' ], '1.8.1', true );
		wp_enqueue_script( 'slick-lightbox', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/slick-lightbox{$suffix}.js", [ 'slick' ], '0.2.12', true );
		wp_enqueue_script( 'rwe-elephant-gallery', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/rw-elephant{$suffix}.js", [ 'slick-lightbox' ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, true );

		wp_localize_script(
			'rwe-elephant-frontend',
			'rwe',
			[
				'i18n' => [
					'invalidDateFormat' => __( 'Invalid date format.', 'rw-elephant-rental-inventory' ),
				],
			]
		);

	}

	/**
	 * Single product page content.
	 *
	 * @since 2.0.0
	 *
	 * @param string  Original single content.
	 * @param integer Page ID.
	 *
	 * @return string Filtered single page content
	 */
	public function single_content_loader( $content, $id = null ) {

		// Single item
		if ( is_admin() || ! is_rwe_single() ) {

			return $content;

		}

		ob_start();

		Plugin::load_rw_template( 'single-item-content' );

		$single_template = ob_get_clean();

		return $this->gallery_content . $single_template;

	}

	/**
	 * Add Open Graph doctype to the header
	 *
	 * @since 2.2.21
	 *
	 * @param $output
	 *
	 * @return mixed|string
	 */
	public function add_open_graph_doctype( $output ) {
		if ( ! is_rwe_single() ) {
			return $output;
		}

		return $output . '
	    xmlns="https://www.w3.org/1999/xhtml"
	    xmlns:og="https://ogp.me/ns#" 
	    xmlns:fb="http://www.facebook.com/2008/fbml"';
	}

	/**
	 * Add Open Graph meta tags to the single item header.
	 *
	 * @since 2.2.21
	 *
	 * @return void
	 */
	public function open_graph_meta_tags() {

		if ( ! is_rwe_single() ) {
			return;
		}

		$properties = array(
			'og:type' => 'product',
		);

		$properties['og:title']             = self::$item_data['name'] ? esc_attr( self::$item_data['name'] ) : '';
		$properties['product:price:amount'] = self::$item_data['rental_price'] ?? '';
		$properties['og:description']       = self::$item_data['description'] ? esc_attr( self::$item_data['description'] ) : '';
		$properties['og:url']               = $this->canonical_url( get_the_permalink() );
		$properties['og:image']             = self::$item_data['image_links'][0]['photo_link'] ?? '';
		$properties['og:image:alt']         = self::$item_data['image_links'][1]['photo_link'] ?? '';

		$og_tags = '';

		foreach ( $properties as $property => $content ) {
			if ( '' !== $content ) {
				$og_tags .= "<meta property='{$property}' content='{$content}' />";
			}
		}

		echo $og_tags;

	}

}
