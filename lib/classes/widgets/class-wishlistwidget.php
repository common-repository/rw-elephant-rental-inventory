<?php
/**
 * Custom RWE Categories List Widget.
 */
namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class WishlistWidget extends \WP_Widget {

	private $wishlist;

	// constructor
	public function __construct() {

		parent::__construct(
			'rwe_wishlist_widget',
			__( 'RW Elephant Wishlist', 'rw-elephant-rental-inventory' ),
			[
				'description' => __( 'Display the current wishlist items.', 'rw-elephant-rental-inventory' ),
			]
		);

		add_action( 'wp_enqueue_scripts', 'rwe_wishlist_scripts' );

		$current_wishlist = isset( $_COOKIE['rw-elephant-wishlist'] ) ? json_decode( $_COOKIE['rw-elephant-wishlist'], true ) : [];

		if ( empty( $current_wishlist ) ) {

			return;

		}

		$wishlist = class_exists( 'RWEG\Wishlist' ) ? Plugin::$wishlist : new Wishlist();

		$this->wishlist = $wishlist->rwe_get_items( $current_wishlist );

	}

	/**
	 * Frontend Markup
	 *
	 * @param  array  $args     Widget arguments array.
	 * @param  object $instance Widget instance.
	 *
	 * @return mixed Markup for the widget.
	 */
	public function widget( $args, $instance ) {

		if ( is_wp_error( $this->wishlist ) ) {

			return;

		}

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {

			echo $args['before_title'] . $title . $args['after_title'];

		}

		if ( ! empty( $this->wishlist ) ) {

			print( '<ul class="wishlist">' );

			foreach ( $this->wishlist as $wishlist_item ) {

				// Storage
				new Data( $wishlist_item );

				ob_start();
				rwe_wishlist_remove_button();
				$wishlist_button = ob_get_contents();
				ob_get_clean();

				printf(
					'<li class="item" data-itemid="%1$s">
						<a href="%2$s">%3$s</a>
						%4$s
					</li>',
					esc_attr( $wishlist_item['inventory_item_id'] ),
					esc_url( rwe_get_url() ),
					esc_html( $wishlist_item['name'] ),
					$wishlist_button
				);

			}

			print( '</ul>' );

		}

		if ( empty( $this->wishlist ) ) {

			printf(
				'<ul class="wishlist"><li class="empty-wishlist-text"><p>%s%s</p></li></ul>',
				esc_html__( "You haven't added anything to your wishlist yet.", 'rw-elephant-rental-inventory' ),
				sprintf(
					' <a href="%s">%s</a>',
					esc_url( get_the_permalink( Options::$options['gallery-page'] ) ),
					__( 'Get Started', 'rw-elephant-rental-inventory' )
				)
			);

		}

		echo $args['after_widget'];

	}

	/**
	 * Widget backend
	 *
	 * @param  object $instance Widget instance
	 *
	 * @return mixed Markup for the widget form.
	 */
	public function form( $instance ) {

		$title = isset( $instance['title'] ) ? $instance['title'] : '';

		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<?php
	}

	/**
	 * Update the widget data.
	 *
	 * @param  object $new_instance New widget instance object.
	 * @param  object $old_instance Old widget instance object.
	 *
	 * @return array Sanitize widget data.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = [];

		$instance['title'] = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;

	}

}
