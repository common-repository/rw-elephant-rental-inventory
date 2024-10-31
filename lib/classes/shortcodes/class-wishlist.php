<?php
// [rw-elephant-wishlist]

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Wishlist {

	private $wishlist;

	private $product_data;

	private static $action_link_iteration;

	public function __construct() {

		self::$action_link_iteration = 1;

		add_action( 'template_redirect', [ $this, 'no_wishlist_submission_when_empty_wishlist' ] );

		add_shortcode( 'rw-elephant-wishlist', [ $this, 'render_wishlist' ] );

		add_filter( 'the_title', [ $this, 'submit_wishlist_page_title' ], 10, 2 );

		add_action( 'init', [ $this, 'process_submit_wishlist' ] );

		add_action( 'init', [ $this, 'clear_wishlist_cookie' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );

		add_filter(
			'body_class',
			function( $classes ) {

				if ( is_rwe_wishlist() ) {

					$classes[] = 'rwe-inventory--wishlist';

				}

				if ( is_rwe_wishlist_submission_page() ) {

					$classes[] = 'rwe-wishlist-submission';

				}

				return $classes;

			}
		);

	}

	/**
	 * Prevent access to the wishlist submission page when the wishlist is empty.
	 *
	 * @return null
	 */
	public function no_wishlist_submission_when_empty_wishlist() {

		if ( ! Options::$options['enable-wishlists'] && ( is_rwe_wishlist() || is_rwe_wishlist_submission_page() ) ) {

			wp_safe_redirect( get_the_permalink( Options::$options['gallery-page'] ), 307 );

			exit;

		}

		$wishlist = rwe_get_wishlist_cookie();

		if ( ! empty( $wishlist ) ) {

			return;

		}

		if ( empty( $wishlist ) && is_rwe_wishlist_submission_page() ) {

			wp_safe_redirect( get_the_permalink( Options::$options['wishlist-page'] ), 307 );

			exit;

		}

	}

	/**
	 * Setup all product data.
	 *
	 * @since 2.0.0
	 *
	 * @return array API response of product data.
	 */
	public function setup_product_data() {

		$products = new API(
			'list_all_items',
			[
				'inclusion_mask' => 'all',
			]
		);

		$products = $products->request();

		return $products;

	}

	/**
	 * Render the wishlist page.
	 *
	 * @return mixed Markup for the wishlist page.
	 */
	public function render_wishlist() {

		$this->wishlist = array_filter( wp_list_pluck( rwe_get_wishlist_cookie(), 'itemID' ) );

		$this->product_data = $this->setup_product_data();

		/**
		 * RW Elephant before wishlist
		 *
		 * @hooked rwe_wishlist_error_check - 10
		 */
		do_action( 'rw_elephant_wishlist_before', $this->product_data );

		/**
		 * RW Elephant wishlist scripts
		 *
		 * @hooked rwe_wishlist_scripts - 10
		 */
		do_action( 'rw_elephant_wishlist_scripts' );

		ob_start();

		/**
		 * @todo Break this out into a wishlist.php template
		 */
		?>

		<div class="rwe-inventory rwe-inventory--wishlist<?php if ( is_rwe_wishlist_submission_page() ) { echo '-submit'; } // @codingStandardsIgnoreLine ?>">

			<?php

			/**
			 * RW Elephant wishlist header
			 *
			 * @hooked RWEG\Gallery::render_gallery_header - 10
			 */
			do_action( 'rw_elephant_wishlist_top', $this->product_data );

			// error catch
			if ( is_wp_error( $this->product_data ) ) {

				print( '</div>' );
				return ob_get_clean();

			}

			?>

			<div class="rwe-inventory__main">

				<?php

				$products = $this->rwe_get_items( $this->wishlist );

				/**
				 * RW Elephant wishlist content
				 *
				 * @param array $this->wishlist Wishlist data array
				 * @param array $products       Products data array
				 *
				 * @hooked rwe_wishlist_empty_text - 10
				 */
				do_action( 'rw_elephant_wishlist_main', $this->wishlist, $products );

				$action = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

				if ( 'submit-wishlist' === $action ) {

					$this->render_wishlist_secion();

				} else {

					if ( ! empty( $products ) ) {

						?>

						<div class="rwe-item-list">

							<div class="rwe-grid rwe-grid--items">

								<?php

								foreach ( $products as $product ) {

									// Instantiate a new data container
									new Data( $product );

									/**
									 * RW Elephant wishlist product template
									 *
									 * @hooked rwe_wishlist_product - 10
									 */
									do_action( 'rw_elephant_wishlist_product', $product );

								}

								?>

							</div>

						</div>

						<?php

					} // @codingStandardsIgnoreLine

				} // @codingStandardsIgnoreLine

				?>

			</div>

			<?php

			/**
			 * RW Elephant wishlist footer
			 *
			 * @hooked RWEG\Wishlist::action_links - 10
			 */
			do_action( 'rw_elephant_wishlist_bottom' );

			?>

		</div>

		<?php

		$content = ob_get_contents();
		ob_get_clean();

		return $content;

	}

	/**
	 * Extract an item(s) out of the total product list array
	 *
	 * @param  integer|array $item_id Single inventory_item_id or array of IDs
	 *
	 * @since 2.0.0
	 *
	 * @return array         Array of product data.
	 */
	public function rwe_get_items( $item_ids = '' ) {

		if ( empty( $this->product_data ) ) {

			$this->product_data = $this->setup_product_data();

		}

		if ( ! is_array( $item_ids ) ) {

			$item_ids = [ $item_ids ];

		}

		$wishlist_items = [];

		$x = 0;

		foreach ( $this->product_data as $product ) {

			if ( ! in_array( (int) $product['inventory_item_id'], $item_ids, true ) ) {

				$x++;

				continue;

			}

			$wishlist_items[] = $this->product_data[ $x ];

			$x++;

		}

		return $wishlist_items;

	}

	/**
	 * Render the action links on the wishlist page.
	 *
	 * @return mixed Markup for the action links.
	 */
	public static function action_links() {

		if ( is_rwe_wishlist_submission_page() || ! Options::$options['enable-wishlists'] ) {

			return;

		}

		/**
		 * Filter whether the 'Add More Items' link should display
		 * Filter the 'Add More Items' link text.
		 *
		 * @var boolean
		 */
		$add_more_btn_txt = is_rwe_wishlist() ? __( 'Add More Items', 'rw-elephant-rental-inventory' ) : __( 'View Wishlist', 'rw-elephant-rental-inventory' );
		$display_add_more = (bool) apply_filters( 'rw_elephant_show_wishlist_add_more_link', true );
		$add_more_text    = (string) apply_filters( 'rw_elephant_wishlist_add_more_text', $add_more_btn_txt );
		$add_more_class   = (array) apply_filters( 'rw_elephant_wishlist_add_more_class', [ 'rwe-button--primary' ] );
		$add_more_url     = (string) is_rwe_wishlist() ? get_the_permalink( Options::$options['gallery-page'] ) : get_the_permalink( Options::$options['wishlist-page'] );

		/**
		 * Filter whether the 'Submit Wishlist' link should display
		 *
		 * @var boolean
		 */
		$wishlist_cookie         = rwe_get_wishlist_cookie();
		$display_submit_wishlist = (bool) apply_filters( 'rw_elephant_show_wishlist_submit_link', ! empty( $wishlist_cookie ) );
		$submit_wishlist_text    = (string) apply_filters( 'rw_elephant_wishlist_submit_text', __( 'Submit Wishlist', 'rw-elephant-rental-inventory' ) );
		$submit_wishlist_class   = (array) apply_filters( 'rw_elephant_wishlist_submit_class', [ 'rwe-button--secondary', 'rwe-submit-wishlist-btn' ] );

		/**
		 * Filter the class added to the wishlist action links.
		 *
		 * @var array
		 */
		$action_classes = (array) apply_filters( 'rw_elephant_wishlist_action_class', [ 'rwe-button' ] );

		if ( ! $display_add_more && $display_submit_wishlist ) {

			return;

		}

		if ( 2 === self::$action_link_iteration ) {

			print( '<footer class="rwe-inventory__footer"><div class="rwe-actions">' );

		}

		print( '<nav class="rwe-nav" role="navigation">' );

		if ( $display_add_more ) {

			printf(
				'<a href="%1$s" class="%2$s" title="%3$s">%3$s</a>',
				esc_url( $add_more_url ),
				implode( ' ', array_merge( $action_classes, $add_more_class ) ),
				esc_html( $add_more_text )
			);

		}

		if ( $display_submit_wishlist && is_rwe_wishlist() ) {

			printf(
				'<a href="%1$s" class="%2$s" title="%3$s">%3$s</a>',
				esc_url( add_query_arg( 'action', 'submit-wishlist', get_the_permalink( Options::$options['wishlist-page'] ) ) ),
				implode( ' ', array_merge( $action_classes, $submit_wishlist_class ) ),
				esc_html( $submit_wishlist_text )
			);

		}

		print( '</nav>' );

		if ( 2 === self::$action_link_iteration ) {

			print( '</footer>' );

		}

		self::$action_link_iteration++;

	}

	/**
	 * Render the wishlist section.
	 *
	 * @param  string $action The action, pulled from ?action= query string param
	 *
	 * @return mixed          Markup for the wishlist section.
	 */
	private function render_wishlist_secion() {

		if ( filter_input( INPUT_GET, 'success', FILTER_SANITIZE_NUMBER_INT ) ) {

			$confirmation = Options::$options['wishlist-confirmation'] ? Options::$options['wishlist-confirmation'] : __( 'Your wishlist has been submitted. Keep an eye out for an email from us.', 'rw-elephant-rental-inventory' );

			printf(
				'<p>%s</p>',
				wp_kses_post( $confirmation )
			);

			return;

		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		// Date picker scripts
		wp_enqueue_script( 'moment', RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/js/moment.min.js', [ 'jquery' ], '2.21.0', true );
		wp_enqueue_script( 'pikaday', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/pikaday{$suffix}.js", [ 'moment' ], '1.6.1', true );
		wp_enqueue_script( 'rwe-elephant-gallery', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/rw-elephant{$suffix}.js", [ 'pikaday' ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, true );

		wp_localize_script(
			'rwe-elephant-frontend',
			'rwe',
			[
				'i18n' => [
					'invalidDateFormat' => __( 'Invalid date format.', 'rw-elephant-rental-inventory' ),
				],
			]
		);

		wp_enqueue_style( 'pikaday', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/pikaday{$suffix}.css", [ 'rwe-gallery' ], '2.0.0', 'all' );

		Plugin::load_rw_template( 'submit-wishlist' );

	}

	/**
	 * Filter the page title when submitting the wishlist
	 *
	 * @param  string  $title Original page title.
	 * @param  integer $id    Current post ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string         Filtered page title.
	 */
	public function submit_wishlist_page_title( $title, $id = null ) {

		$action = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		if ( is_admin() || (int) Options::$options['wishlist-page'] !== $id || ! $action || 'submit-wishlist' !== $action || ! in_the_loop() ) {

			return $title;

		}

		return sprintf(
			'%1$s: %2$s',
			$title,
			__( 'Submit', 'rw-elephant-rental-inventory' )
		);

	}

	/**
	 * Process the wishlist submission.
	 *
	 * @since 2.0.0
	 *
	 * @return bool True on successful, else false.
	 */
	public function process_submit_wishlist() {

		$nonce = ! empty( $_POST['submit_wishlist'] ) ? sanitize_text_field( $_POST['submit_wishlist'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rw_elephant_submit_wishlist' ) ) {

			return;

		}

		$wishlist = rwe_get_wishlist_cookie();

		if ( ! $wishlist || empty( $wishlist ) ) {

			$this->redirect_form_submission( false, 'empty-wishlist' );

		}

		// Strip nonces
		unset( $_POST['submit_wishlist'], $_POST['_wp_http_referer'] );

		// Create a session ID
		$wishlist = new Wishlist_API();

		if ( is_wp_error( $wishlist->session_id ) || empty( $wishlist->session_id ) ) {

			$this->redirect_form_submission( false, 'session-id' );

		}

		// Create the wishlist
		$wishlist->create_wishlist( $_POST['event_date'] );

		if ( is_wp_error( $wishlist->wishlist_id ) || empty( $wishlist->wishlist_id ) ) {

			$this->redirect_form_submission( false, 'wishlist-id' );

		}

		$product_ids = wp_list_pluck( rwe_get_wishlist_cookie(), 'itemID' );

		// Loop over the wishlist, and add each item via the API
		foreach ( $this->rwe_get_items( $product_ids ) as $product ) {

			$wishlist->add_item( $product );

		}

		$submit_wishlist = $wishlist->submit_wishlist( $product );

		// Finalize and submit the wishlist
		if ( is_wp_error( $submit_wishlist ) || ! $submit_wishlist ) {

			$error_text = is_wp_error( $submit_wishlist ) ? $submit_wishlist->get_error_message() : __( 'There was an error.', 'rw-elephant-inventory-gallery' );

			$this->redirect_form_submission( false, urlencode( $error_text ) );

		}

		$this->redirect_form_submission( true );

	}

	/**
	 * Delete the wishlist cookie after a successful form submission.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function clear_wishlist_cookie() {

		$action = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		$success = filter_input( INPUT_GET, 'success', FILTER_SANITIZE_NUMBER_INT );

		if ( ( ! $action || 'submit-wishlist' !== $action ) || ! $success ) {

			return;

		}

		// Delete the wishlist cookie once a successful submission has occurred
		setcookie( 'rw-elephant-wishlist', '', 1, '/' );

	}

	/**
	 * Redirect a form submission.
	 *
	 * @param  bool   $success True on success or false on error.
	 * @param  string $message The error message from the response.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	private function redirect_form_submission( $success, $message = '' ) {

		$query_args = [
			'action'  => 'submit-wishlist',
			'success' => true,
		];

		if ( ! $success ) {

			$query_args['error'] = $message;

			unset( $query_args['success'] );

		}

		wp_redirect( add_query_arg( $query_args, get_the_permalink( Options::$options['wishlist-page'] ) ) );

		exit;

	}

	/**
	 * Register and localize wishlist scripts.
	 *
	 * @since 2.3.4
	 *
	 * @return null
	 */
	public function register_scripts() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'rwe-elephant-wishlist', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/rw-elephant-wishlist{$suffix}.js", [ 'pikaday' ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, true );

		wp_localize_script(
			'rwe-elephant-wishlist',
			'rweWishlistData',
			[
				'urlBase'           => rwe_get_url_base(),
				'emptyWishlistText' => __( "You haven't added anything to your wishlist yet.", 'rw-elephant-rental-inventory' ) . ' ' . sprintf(
						'<a href="%s">%s</a>',
						esc_url( get_the_permalink( Options::$options['gallery-page'] ) ),
						__( 'Get Started', 'rw-elephant-rental-inventory' )
					),
			]
		);
	}

}
