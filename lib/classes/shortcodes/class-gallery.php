<?php
// [rw-elephant-gallery]

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Gallery {

	public static $categories;

	public static $tags;

	public static $category_data;

	public static $product;

	private $category;

	public $shortcode_atts;

	public function __construct() {

		add_shortcode( 'rw-elephant-gallery', [ $this, 'render_gallery' ] );

		add_action( 'init', [ $this, 'search_inventory' ] );

		add_action( 'init', [ $this, 'filter_inventory_api' ] );

		// Define the custom category image size 320x320 hard cropped from the image center
		add_image_size( 'rwe-category-thumbnail', 320, 320, [ 'center', 'center' ] );

		// Gallery Header
		add_action( 'rw_elephant_gallery_top', [ $this, 'render_gallery_header' ], 10 );

		// Gallery Products
		add_action( 'rw_elephant_gallery_products', [ $this, 'render_category_gallery' ], 10 );
		add_action( 'rw_elephant_gallery_products', [ $this, 'render_all_products_gallery' ], 10 );

		add_filter( 'get_canonical_url', [ $this, 'canonical_url' ] );
		add_filter( 'wpseo_canonical', [ $this, 'canonical_url' ] );
		add_filter( 'language_attributes', [ $this, 'add_open_graph_doctype' ] );
		add_action( 'wp_head', [ $this, 'open_graph_meta_tags' ], 5 );

	}

	/**
	 * Render the RW Elephant Rental Inventory
	 *
	 * @param array $atts Shortcode attribute array.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the gallery.
	 */
	public function render_gallery( $atts ) {

		$atts = shortcode_atts(
			(array) apply_filters(
				'rw_elephant_gallery_shortcode_atts',
				[
					'view'       => 'category', // category | all
					'theme'      => Options::$options['gallery-theme'], // defeault, overlay, polaroid
					'categories' => true,
				]
			),
			$atts,
			'rw-elephant-gallery'
		);

		if ( ! Options::$options['is-connected'] ) {

			$message = __( 'Gallery unavailable. Please try back later.', 'rw-elephant-rental-inventory' );

			if ( current_user_can( 'manage_options' ) ) {

				$message = sprintf(
					/* translators: Link to the settings page. */
					__( 'You are not connected to the API. Please check the %s.', 'rw-elephant-rental-inventory' ),
					sprintf(
						'<a href="%1$s" title="%2$s">%2$s</a>',
						esc_url( admin_url( 'options-general.php?page=rw-elephant' ) ),
						esc_html__( 'settings page', 'rw-elephant-rental-inventory' )
					)
				);

			}

			return sprintf( '<p>%s</p>', $message );

		}

		global $wp_query;

		$this->shortcode_atts = $atts;
		$this->category       = rwe_get_category();

		// Setup the gallery data
		$this->setup_gallery_data();

		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$rtl    = is_rtl() ? '-rtl' : '';

		wp_enqueue_style( 'rwe-gallery', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/rw-elephant{$rtl}{$suffix}.css", [ rwe_get_last_style() ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, 'all' );

		// Load styles from the options
		rwe_gallery_option_styles( 'rwe-gallery' );

		ob_start();

		/**
		 * @todo Break this out into a gallery.php template
		 */
		?>

		<div class="rwe-inventory rwe-inventory--<?php echo esc_attr( rwe_get_gallery_type() ); ?>">

			<?php

			/**
			 * RW Elephant Gallery Top
			 *
			 * @hooked render_gallery_header - 10
			 */
			do_action( 'rw_elephant_gallery_top' );

			?>

			<div class="rwe-inventory__main rwe-inventory--<?php echo esc_attr( $atts['theme'] ); ?>">

				<div class="<?php echo ( ( isset( $wp_query->query_vars['rw_category'] ) || isset( $_GET['category'] ) ) || is_rwe_search() ) ? 'rwe-item-list' : 'rwe-category-list'; ?>">

					<div class="rwe-grid rwe-grid--items">

						<?php

						/**
						 * RW Elephant Gallery Products
						 *
						 * @hooked render_category_gallery - 10
						 * @hooked render_all_products_gallery - 10
						 */
						do_action( 'rw_elephant_gallery_products' );

						?>

					</div>

				</div>

			</div>

			<?php

			/**
			 * RW Elephant Gallery Bottom
			 */
			do_action( 'rw_elephant_gallery_bottom' );

			?>

		</div>

		<?php

		$gallery = ob_get_contents();
		ob_get_clean();

		return $gallery;

	}

	/**
	 * Filter the API request when a search was made and redirect the user.
	 *
	 * @return null
	 */
	public function search_inventory() {

		$nonce = ! empty( $_GET['search_inventory'] ) ? sanitize_text_field( $_GET['search_inventory'] ) : '';
		$term  = ! empty( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rw_elephant' ) ) {

			return;

		}

		// No term specified
		if ( ! $term || empty( $term ) ) {

			wp_safe_redirect( get_the_permalink( Options::$options['gallery-page'] ) );

			exit;

		}

		wp_safe_redirect( add_query_arg( 'search', urlencode( $term ), get_the_permalink( Options::$options['gallery-page'] ) ) );

		exit;

	}

	/**
	 * Filter the API request when a search is made.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function filter_inventory_api() {

		$term = ! empty( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

		if ( ! $term || empty( $term ) ) {

			return;

		}

		/**
		 * Filter the API request endpoint.
		 *
		 * @since 2.0.0
		 */
		add_filter(
			'rw_elephant_gallery_api_endpoint',
			function( $endpoint ) {

				return 'list_items_for_search';

			}
		);

		/**
		 * Filter the API args.
		 *
		 * @since 2.0.0
		 */
		add_filter(
			'rw_elephant_gallery_api_args',
			function( $args ) use ( $term ) {

				$args['search_term'] = $term;

				return $args;

			}
		);

		/**
		 * Filter the shortcode to view => 'all'
		 */
		add_filter(
			'rw_elephant_gallery_shortcode_atts',
			function( $shortcode_atts ) {

				$shortcode_atts['view']       = 'all';
				$shortcode_atts['categories'] = false;

				return $shortcode_atts;

			}
		);

	}

	/**
	 * Render the gallery header
	 * Includes breadcrumbs, search bar, view wishlist button etc.
	 *
	 * @uses rw_elephant_display_gallery_header
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the gallery header.
	 */
	public static function render_gallery_header() {

		if ( ! (bool) apply_filters( 'rw_elephant_display_gallery_header', true ) ) {

			return;

		}

		Plugin::load_rw_template( 'gallery-header' );

	}

	/**
	 * Render the gallery in 'categories' mode
	 * List out the cateogires in the gallery in the initial load, not products
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the category gallery.
	 */
	public function render_category_gallery() {

		if ( 'category' !== $this->shortcode_atts['view'] ) {

			return;

		}

		$api_endpoint = ! $this->category ? 'list_inventory_types' : 'list_items_for_type';
		$api_args     = [ 'inclusion_mask' => 'all' ];

		// Filter by category
		if ( $this->category ) {

			if ( ! is_array( self::$category_data ) || empty( self::$category_data ) || ! array_key_exists( $this->category, self::$category_data ) ) {

				echo '<p>' . esc_html__( 'Error processing your request, please refresh and try again.', 'rw-elephant-rental-inventory' ) . '</p>';

				return;

			}

			$api_args['inventory_type_id'] = self::$category_data[ $this->category ];

		}

		// Filter by tag
		$tag = ! empty( $_GET['tag'] ) ? sanitize_text_field( $_GET['tag'] ) : '';

		if ( $tag ) {
			$api_endpoint                      = 'list_items_for_tag';
			$api_args['inventory_tag_type_id'] = $tag;
		}

		/**
		 * Filter the API endpoint.
		 * @var string
		 */
		$api_endpoint = (string) apply_filters( 'rw_elephant_gallery_api_endpoint', $api_endpoint );

		/**
		 * Filter the API args.
		 * @var string
		 */
		$api_args = (array) apply_filters( 'rw_elephant_gallery_api_args', $api_args );

		// Start API request
		$products = new API( $api_endpoint, $api_args );

		$products = $products->request();

		if ( is_wp_error( $products ) ) {

			echo $products->get_error_message();

			return;

		}

		if ( empty( $products ) ) {

			Plugin::load_rw_template( 'no-products' );

			return;

		}

		$is_category_item = ( ! $this->category && ! $tag );

		foreach ( $products as $product ) {

			$product['is_category_item'] = $is_category_item;

			// Instantiate a new data container
			new Data( $product );

			Plugin::load_rw_template( 'inventory-item' );

		}

	}

	/**
	 * Filter the canonical URL for gallery category pages.
	 *
	 * @since 2.2.19
	 *
	 * @return string URL of the current category.
	 */
	public function canonical_url( $url ) {
		if ( ! rwe_get_category() ) {
			return $url;
		}

		global $wp;
		return apply_filters( 'rw_elephant_category_canonical_url', home_url( $wp->request ) . '/' );
	}

	/**
	 * Render the gallery in 'all' mode
	 * List all products, with category links above.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the all products gallery.
	 */
	public function render_all_products_gallery() {

		if ( 'all' !== $this->shortcode_atts['view'] ) {

			return;

		}

		$api_endpoint = ! $this->category ? 'list_all_items' : 'list_items_for_type';
		$api_args     = [ 'inclusion_mask' => 'all' ];

		// Filter by category
		if ( $this->category ) {

			if ( ! is_array( self::$category_data ) || empty( self::$category_data ) || ! array_key_exists( $this->category, self::$category_data ) ) {

				echo '<p>' . esc_html__( 'Error processing your request, please refresh and try again.', 'rw-elephant-rental-inventory' ) . '</p>';

				return;

			}

			$api_args['inventory_type_id'] = self::$category_data[ $this->category ];

		}

		// Filter by tag
		$tag = ! empty( $_GET['tag'] ) ? sanitize_text_field( $_GET['tag'] ) : '';

		if ( $tag ) {
			$api_endpoint                      = 'list_items_for_tag';
			$api_args['inventory_tag_type_id'] = $tag;
		}

		/**
		 * Filter the API endpoint.
		 * @var string
		 */
		$api_endpoint = (string) apply_filters( 'rw_elephant_gallery_api_endpoint', $api_endpoint );

		/**
		 * Filter the API args.
		 * @var string
		 */
		$api_args = (array) apply_filters( 'rw_elephant_gallery_api_args', $api_args );

		$products = new API( $api_endpoint, $api_args );

		$products = $products->request();

		if ( is_wp_error( $products ) ) {

			echo $products->get_error_message();

			return;

		}

		if ( empty( $products ) ) {

			Plugin::load_rw_template( 'no-products' );

			return;

		}

		foreach ( $products as $product ) {

			// Instantiate a new data container
			new Data( $product );

			Plugin::load_rw_template( 'inventory-item' );

		}

	}

	/**
	 * Setup the API data.
	 *
	 * @since 2.0.0
	 */
	public function setup_gallery_data() {

		// Setup the Categories
		$api = new API( 'list_inventory_types' );

		self::$categories = $api->request();

		if ( ! is_wp_error( self::$categories ) ) {

			$this->setup_category_data();

			/**
			 * "All Rentals" text prepended onto the categories.
			 *
			 * @var string
			 */
			$all_rentals_text = (string) apply_filters( 'rw_elephant_all_rentals_text', __( 'All Rentals', 'rw-elephant-rental-inventory' ) );

			array_unshift(
				self::$categories,
				[
					'inventory_type_name' => $all_rentals_text,
				]
			);

			/**
			 * Filter the categories list.
			 *
			 * @var array
			 */
			self::$categories = (array) apply_filters( 'rw_elephant_categories', self::$categories );

		}

		// Setup the Tags
		$api = new API( 'list_tags' );

		$tags = $api->request();

		if ( is_wp_error( $tags ) || empty( $tags ) ) {

			self::$tags = [];

		}

		$names = wp_list_pluck( $tags, 'inventory_tag_name' );
		$ids   = wp_list_pluck( $tags, 'inventory_tag_type_id' );

		self::$tags = array_combine( $ids, $names );

	}

	/**
	 * Setup the category data for later reference.
	 *
	 * @since 2.0.0
	 *
	 * @return array Category data array.
	 */
	private function setup_category_data() {

		self::$category_data = [];

		if ( empty( self::$categories ) ) {

			return self::$category_data;

		}

		foreach ( self::$categories as $category ) {

			if ( empty( $category['inventory_type_name'] ) || empty( $category['inventory_type_id'] ) ) {

				continue;

			}

			self::$category_data[ sanitize_title( $category['inventory_type_name'] ) ] = $category['inventory_type_id'];

		}

	}

	/**
	 * Render the gallery contents depending on the current query_vars.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed Markup for the gallery.
	 */
	private function render_gallery_contents() {

		$api_endpoint = ! $this->category ? 'list_all_items' : 'list_items_for_type';
		$api_args     = [ 'inclusion_mask' => 'all' ];

		// Filter by category
		if ( false !== $this->category ) {

			if ( ! is_array( self::$category_data ) || empty( self::$category_data ) || ! array_key_exists( $this->category, self::$category_data ) ) {

				echo '<p>' . esc_html__( 'Error processing your request, please refresh and try again.', 'rw-elephant-rental-inventory' ) . '</p>';

				return;

			}

			$api_args['inventory_type_id'] = self::$category_data[ $this->category ];

		}

		// Filter by tag
		$tag = ! empty( $_GET['tag'] ) ? sanitize_text_field( $_GET['tag'] ) : '';

		if ( $tag ) {
			$api_args['inventory_tag_type_id'] = $tag;
			$api_endpoint                      = 'list_items_for_tag';
		}

		$products = new API( $api_endpoint, $api_args );

		$products = $products->request();

		if ( is_wp_error( $products ) ) {

			echo $products->get_error_message();

			return;

		}

		if ( empty( $products ) ) {

			Plugin::load_rw_template( 'no-products' );

			return;

		}

		foreach ( $products as $product ) {

			// Instantiate a new data container
			new Data( $product );

			Plugin::load_rw_template( 'inventory-item' );

		}

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
		$this->category = rwe_get_category();

		if ( ! $this->category ) {
			$this->category = rwe_get_category();
		}

		if ( ! $this->category ) {
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
		$this->category = rwe_get_category();

		if ( ! $this->category ) {
			$this->category = rwe_get_category();
		}

		if ( ! $this->category ) {
			return;
		}

		$properties = array(
			'og:type' => 'product.group',
		);

		$this->setup_gallery_data();
		$category_id = self::$category_data[ $this->category ];
		if ( isset( Options::$options['category-descriptions'][ $category_id ] ) && '' !== Options::$options['category-descriptions'][ $category_id ] ) {
			$properties['og:description'] = Options::$options['category-descriptions'][ $category_id ];
		}

		$categories = rwe_get_categories();
		$category = array_filter( $categories, function( $cat ) use ( $category_id ) {
			return $cat['inventory_type_id'] === $category_id;
		} );

		$properties['og:title']     = $category[0]['inventory_type_name'] ?? '';
		$properties['og:url']       = $this->canonical_url( get_the_permalink() );
		$properties['og:image']     = $category[0]['plugin_250px_thumbnail_link'] ?? '';
		$properties['og:image:alt'] = $category[0]['plugin_320px_thumbnail_link'] ?? '';

		$og_tags = '';

		foreach ( $properties as $property => $content ) {
			if ( '' !== $content ) {
				$og_tags .= "<meta property='{$property}' content='{$content}' />";
			}
		}

		echo $og_tags;

	}

}
