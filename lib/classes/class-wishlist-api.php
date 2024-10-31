<?php
/**
 * API Class
 *
 * @author R.W. Elephant <info@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Wishlist_API {

	private $api_base;

	private $options;

	public $session_id;

	public $wishlist_id;

	private $error;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->options    = Options::$options;
		$this->api_base   = sprintf( 'https://%s.rwelephant.com/perl/wishlist', strtolower( $this->options['tenant-id'] ) );
		$this->session_id = $this->get_session_id();

	}

	/**
	 * Retreive a session ID from the rw elephant API.
	 *
	 * @since 2.0.0
	 *
	 * @return string Session ID on success, else empty.
	 */
	public function get_session_id() {

		$request = wp_remote_get(
			add_query_arg(
				[
					'api_key' => $this->options['api-key'],
					'action'  => 'obtain_session_id',
				],
				$this->api_base
			),
			[ 'timeout' => '30' ]
		);

		$response_body = wp_remote_retrieve_body( $request );
		$response_code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $response_code ) {

			return new \WP_Error( 'error', __( 'There was an error creating the session ID.', 'rw-elephant-rental-inventory' ) );

		}

		$data = json_decode( $response_body, true );

		return isset( $data['sid'] ) ? $data['sid'] : '';

	}

	/**
	 * Set the error text.
	 *
	 * @param string $error Error text.
	 */
	public function add_error( $error = '' ) {

		$this->error = $error;

	}

	/**
	 * Create the wishlist on the RW elephant servers.
	 *
	 * @since 2.0.0
	 *
	 * @return boolean True on creation, else false
	 */
	public function create_wishlist( $event_date = '' ) {

		if ( empty( $event_date ) ) {

			return new \WP_Error( 'error', __( 'Wishlist not created. No event date specified.', 'rw-elephant-rental-inventory' ) );

		}

		$request = wp_remote_get(
			add_query_arg(
				[
					'action'     => 'create_new_wishlist',
					'event_date' => $event_date,
					'sid'        => $this->session_id,
				],
				$this->api_base
			),
			[ 'timeout' => '30' ]
		);

		$response_body = wp_remote_retrieve_body( $request );
		$response_code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $response_code ) {

			return new \WP_Error( 'error', __( 'There was an error creating the wishlist.', 'rw-elephant-rental-inventory' ) );

		}

		$data = json_decode( $response_body, true );

		$this->wishlist_id = isset( $data['wishlist_id'] ) ? $data['wishlist_id'] : '';

	}

	/**
	 * Add an item to the wishlist on RW Elephant servers.
	 *
	 * @param array $product Product data array.
	 *
	 * @since 2.0.0
	 *
	 * @return null
	 */
	public function add_item( $product = [] ) {

		if ( empty( $this->wishlist_id ) || ! isset( $product['inventory_item_id'] ) || empty( $product['inventory_item_id'] ) ) {

			return;

		}

		wp_remote_get(
			add_query_arg(
				[
					'action'            => 'add_item_to_wishlist',
					'wishlist_id'       => $this->wishlist_id,
					'inventory_item_id' => $product['inventory_item_id'],
					'quantity'          => rwe_get_product_quantity_from_cookie( $product['inventory_item_id'] ),
					'sid'               => $this->session_id,
				],
				$this->api_base
			),
			[ 'timeout' => '30' ]
		);

	}

	/**
	 * Finalize and submit the wishlist.
	 *
	 * @since 2.0.0
	 *
	 * @return true|WP_Error True on success else WP_Error object.
	 */
	public function submit_wishlist() {

		// Format the custom fields if they exist.
		$email_address = ! empty( $_POST['email_address'] ) ? sanitize_text_field( $_POST['email_address'] ) : '';
		$first_name    = ! empty( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$last_name     = ! empty( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		$phone_number  = ! empty( $_POST['phone_number'] ) ? sanitize_text_field( $_POST['phone_number'] ) : '';

		/**
		 * Filter the wishlist submission query arguments array
		 *
		 * @var array
		 *
		 * @since 2.2.1
		 */
		$query_args = apply_filters(
			'rw_elephant_wishlist_query_args',
			[
				'action'        => 'finalize_wishlist',
				'wishlist_id'   => $this->wishlist_id,
				'email_address' => $email_address,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
				'phone_number'  => $phone_number,
				'sid'           => $this->session_id,
			]
		);

		$custom_fields = $this->format_custom_fields( $_POST );

		if ( false !== $custom_fields ) {

			$query_args['wishlist_custom_fields'] = $custom_fields;

		}

		/**
		 * Wishlist submission action
		 *
		 * @var array $query_args Query arguments array
		 * @var array $_POST      Form submission fields
		 *
		 * @since 2.2.1
		 */
		do_action( 'rw_elephant_submit_wishlist', $this, $query_args, $_POST );

		/**
		 * Error present, do not continue.
		 */
		if ( ! empty( $this->error ) ) {

			return new \WP_Error( 'error', $this->error );

		}

		$submit_wishlist = wp_remote_get(
			add_query_arg( $query_args, $this->api_base ),
			[ 'timeout' => '30' ]
		);

		if ( 200 !== wp_remote_retrieve_response_code( $submit_wishlist ) ) {

			return new \WP_Error( 'error', __( 'There was an error finalizing and submitting the wishlist.', 'rw-elephant-rental-inventory' ) );

		}

		return true;

	}

	/**
	 * Format the custom fields array if it's set
	 *
	 * @param  array  $post_data Global $_POST data array.
	 *
	 * @since 2.0.0
	 *
	 * @return string Custom fields string to send to wishlist API.
	 */
	public function format_custom_fields( $post_data = [] ) {

		if ( empty( $post_data ) ) {

			return false;

		}

		$custom_fields = rwe_get_wishlist_custom_fields();

		if ( empty( $custom_fields ) ) {

			return false;

		}

		$fields_string = '';

		foreach ( $custom_fields as $custom_field_key => $custom_field_data ) {

			if ( ! isset( $post_data[ $custom_field_key ] ) ) {

				continue;

			}

			$custom_field_value = $post_data[ $custom_field_key ];

			if ( is_array( $custom_field_value ) ) {

				$custom_field_value = implode( ' ', $custom_field_value );

			}

			$fields_string .= "{$custom_field_data['label']}: {$custom_field_value}\n";

		}

		return urlencode( $fields_string );

	}

}
