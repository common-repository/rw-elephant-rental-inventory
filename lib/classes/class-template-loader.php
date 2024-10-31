<?php
/**
 * RW Elephant Rental Inventory - Frontend Template Loader
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Template_Loader {

	public function __construct() {

		add_filter( 'request', [ $this, 'filter_gallery_request' ] );

		add_filter( 'the_content', [ $this, 'content_loader' ] );

		add_action( 'init', 'rwe_custom_rewrite_rules' );

		add_filter( 'query_vars', [ $this, 'custom_query_vars' ] );

		add_action( 'template_redirect', [ $this, 'gallery_page' ] );
		add_action( 'template_redirect', [ $this, 'single_item' ] );

		add_action( 'the_posts', [ $this, 'mock_product_id' ], -10 );
	}

	/**
	 * Fitler the request so the gallery page content displays the correct info.
	 *
	 * @filter request - 10
	 *
	 * @since 2.0.0
	 *
	 * @param  array $query_vars Query variables array.
	 *
	 * @return array             Filered query variables array.
	 */
	public function filter_gallery_request( $query_vars ) {

		if ( is_admin() ) {

			return $query_vars;

		}

		$page_obj = get_post( Options::$options['gallery-page'] );

		$page_id   = is_object( $page_obj ) ? $page_obj->ID : false;
		$page_slug = is_object( $page_obj ) ? $page_obj->post_name : false;

		$id   = ( isset( $query_vars['page_id'] ) && (int) $query_vars['page_id'] === $page_id );
		$name = ( isset( $query_vars['pagename'] ) && $query_vars['pagename'] === $page_slug );

		if ( ( $id || $name || isset( $query_vars['rw_category'] ) ) && ! isset( $query_vars['error'] ) ) {

			$query_vars['pagename'] = $page_slug;
			$query_vars['page']     = '';

			return $query_vars;

		}

		return $query_vars;

	}

	/**
	 * Append the appropriate shortcode onto the selected page.
	 *
	 * @filter the_content - 10
	 *
	 * @param string $content Original content array.
	 *
	 * @since 2.0.0
	 *
	 * @return string Filtered content, with shortcode appended.
	 */
	public function content_loader( $content ) {

		global $post;

		if ( ! isset( $post->ID ) || is_admin() ) {

			return $content;

		}

		// Gallery page
		if ( (int) Options::$options['gallery-page'] === $post->ID || rwe_get_category() ) {

			// Pass in key => value pairs to append onto the shortcode
			$gallery_shortcode_atts = (array) apply_filters( 'rw_elephant_gallery_shortcode_atts', [] );

			$shortcode_atts = empty( $gallery_shortcode_atts ) ? '' : str_replace( '=', '="', http_build_query( $gallery_shortcode_atts, null, '" ', PHP_QUERY_RFC3986 ) ) . '"';

			return $content . do_shortcode( '[rw-elephant-gallery ' . $shortcode_atts . ']' );

		}

		// Wishlist page
		if ( (int) Options::$options['wishlist-page'] === $post->ID ) {

			return $content . do_shortcode( '[rw-elephant-wishlist]' );

		}

		return $content;

	}

	/**
	 * Add custom query vars to identify inventory categories and products.
	 *
	 * @filter query_vars - 10
	 *
	 * @param array $vars Default query vars array.
	 *
	 * @since 2.0.0
	 *
	 * @return array Altered array of query vars.
	 */
	public function custom_query_vars( $vars ) {

		$vars[] = 'rw_category';
		$vars[] = 'rw_item';

		return $vars;

	}

	/**
	 * Load page.php when on the gallery page.
	 *
	 * @action template_redirect - 10
	 *
	 * @return null
	 */
	public function gallery_page() {

		if ( ! is_rwe_gallery() || empty( get_page_template() ) ) {

			return;

		}

		add_filter(
			'template_include',
			function() {

				$custom_gallery_template = get_stylesheet_directory() . '/rw-elephant/gallery-page.php';

				/**
				 * If the custom gallery-page.php template exists inside of
				 * /wp-content/theme/rw-elepehant/gallery-page.php use that instead.
				 */
				if ( file_exists( $custom_gallery_template ) ) {

					return $custom_gallery_template;

				}

				return get_page_template();

			}
		);

	}

	/**
	 * Load page.php when viewing a single item.
	 *
	 * @action template_redirect - 10
	 *
	 * @return null
	 */
	public function single_item() {

		if ( ! is_rwe_single() ) {

			return;

		}

		$item_id = rwe_get_product_id();

		$this->setup_single_item();

		$post = $this->setup_single_post( $item_id );

		$this->setup_single_query( $post );

		if ( empty( get_page_template() ) ) {

			return;

		}

		add_filter(
			'template_include',
			function() {

				$custom_single_template = get_stylesheet_directory() . '/rw-elephant/single-item.php';

				/**
				 * If the custom single-item.php template exists inside of
				 * /wp-content/theme/rw-elepehant/single-item.php use that instead.
				 */
				if ( file_exists( $custom_single_template ) ) {

					return $custom_single_template;

				}

				return get_page_template();

			}
		);

	}


	/**
	 * Mock the product ID so that WordPress does not throw a 404 in the network request
	 *
	 * @param  array $posts Post array.
	 *
	 * @return array        Fitlered post array.
	 */
	public function mock_product_id( $posts ) {

		if ( ! is_rwe_single() ) {

			return $posts;

		}

		// This filter causes fatal errors in WooCommerce.
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				return $posts;

		}

		$post     = new \stdClass();
		$post->ID = -999;
		$posts[]  = $post;

		return $posts;

	}

	/**
	 * Mock a single post object.
	 *
	 * @param  $post_id RW Elephant inventory id.
	 *
	 * @return object   Post object.
	 */
	private function setup_single_post( $post_id ) {

		global $wp_query;

		if ( is_string( $post_id ) ) {

			$post_id = 0;

		}

		$post                 = new \stdClass();
		$post->ID             = Options::$options['gallery-page'];
		$post->post_author    = 1;
		$post->post_date      = current_time( 'mysql' );
		$post->post_date_gmt  = current_time( 'mysql', 1 );
		$post->post_content   = get_post_field( 'post_content', Options::$options['gallery-page'] );
		$post->post_title     = get_the_title( Options::$options['gallery-page'] );
		$post->post_status    = 'publish';
		$post->comment_status = 'closed';
		$post->ping_status    = 'closed';
		$post->post_name      = sanitize_title( rwe_item_name( false ) . '-' . $post_id );
		$post->post_parent    = Options::$options['gallery-page'];
		$post->post_type      = 'page';
		$post->filter         = 'raw';

		/**
		 * Allow users to filter the post object
		 *
		 * @var object
		 */
		$post = (object) apply_filters( 'rw_elephant_single_item_post_object', $post, $post_id );

		$wp_post = new \WP_Post( $post );

		return $wp_post;

	}

	/**
	 * Setup the single page query.
	 *
	 * @param  object $post Mock post object.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	private function setup_single_query( $post ) {

		global $wp, $wp_query;

		// Update the main query
		$wp_query->post                 = $post;
		$wp_query->posts                = [ $post ];
		$wp_query->queried_object       = $post;
		$wp_query->queried_object_id    = $post->ID;
		$wp_query->found_posts          = 1;
		$wp_query->post_count           = 1;
		$wp_query->max_num_pages        = 1;
		$wp_query->is_page              = true;
		$wp_query->post_parent          = Options::$options['gallery-page'];
		$wp_query->is_singular          = true;
		$wp_query->is_single            = false;
		$wp_query->is_attachment        = false;
		$wp_query->is_archive           = false;
		$wp_query->is_category          = false;
		$wp_query->is_tag               = false;
		$wp_query->is_tax               = false;
		$wp_query->is_author            = false;
		$wp_query->is_date              = false;
		$wp_query->is_year              = false;
		$wp_query->is_month             = false;
		$wp_query->is_day               = false;
		$wp_query->is_time              = false;
		$wp_query->is_search            = false;
		$wp_query->is_feed              = false;
		$wp_query->is_comment_feed      = false;
		$wp_query->is_trackback         = false;
		$wp_query->is_home              = false;
		$wp_query->is_embed             = false;
		$wp_query->is_404               = false;
		$wp_query->is_paged             = false;
		$wp_query->is_admin             = false;
		$wp_query->is_preview           = false;
		$wp_query->is_robots            = false;
		$wp_query->is_posts_page        = false;
		$wp_query->is_post_type_archive = false;

		$GLOBALS['wp_query'] = (object) apply_filters( 'rw_elephant_single_query_object', $wp_query );

		$wp->register_globals();

	}

	/**
	 * Setup the single item instance.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function setup_single_item() {

		$inventory_item = rwe_get_product_id();

		if ( ! $inventory_item ) {

			// Error
			return;

		}

		$single = new Single_Item( $inventory_item );

		if ( is_wp_error( $single ) ) {

			echo esc_html( $single->get_error_message() );

		}

	}

}
