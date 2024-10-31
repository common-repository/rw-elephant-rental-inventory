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

class API {

	const API_BASE = 'https://galleryapi-v2.rwelephant.com/api/public_api';

	private $options;

	private $action;

	private $url;

	private $additional_api_args;

	private $transient_name;

	private $transient_expiration;

	/**
	 * Constructor
	 *
	 * @param string $action   The 'action' in the endpoint.
	 * @param array  $api_args Additional API query arguments.
	 */
	public function __construct( $action = '', $api_args = [] ) {

		if ( empty( $action ) ) {

			return new \WP_Error( 'error', __( "No 'action' was specified.", 'rw-elephant-rental-inventory' ) );

		}

		$this->action              = $action;
		$this->additional_api_args = $api_args;
		$this->options             = Options::$options;

		if ( ( empty( $this->options['tenant-id'] ) || empty( $this->options['api-key'] ) ) && ! $this->options['is-connected'] ) {

			return new \WP_Error( 'error', __( 'You are not connected to the RW elephant API.', 'rw-elephant-rental-inventory' ) );

		}

		$this->url                  = $this->build_url();
		$this->transient_name       = $this->setup_transient_name();
		$this->transient_expiration = apply_filters( 'rw_elephant_cache_expiration', 12 * HOUR_IN_SECONDS );

		add_filter( 'rw_elephant_cache_api_data', [ $this, 'toggle_cache_enabled' ] );

	}

	/**
	 * Build the transient name so data is cached and referenced properly
	 * Note: Individual items, categories and tags have IDs appended to their transient names.
	 *
	 * @since 2.0.0
	 *
	 * @return string The appropriate transient name for the API request
	 */
	public function setup_transient_name() {

		$transient_base = "rw-elephant-{$this->action}";

		// Append the inventory ID onto the transient name for 'item_info' and 'list_tags_for_item' endpoints
		// So item data is cached correctly
		if ( in_array( $this->action, [ 'item_info', 'list_tags_for_item' ], true ) ) {

			$transient_base .= '-' . $this->additional_api_args['inventory_item_id'];

		}

		// Append the category ID onto the transient when a rw_category query_var is present
		// So item data is cached correctly
		$rw_category = rwe_get_category();

		if ( $rw_category ) {

			$transient_base .= '-' . $rw_category;

		}

		// Append the tag ID onto the transient when ?tag= query variable is present
		// So item data is cached correctly
		$tag = ! empty( $_GET['tag'] ) ? sanitize_text_field( $_GET['tag'] ) : '';

		if ( $tag ) {

			$transient_base .= '-' . $tag;

		}

		return $transient_base;

	}

	/**
	 * Get the API endpoints that should NOT be cached.
	 *
	 * @since 2.1.2
	 *
	 * @return array RW Elephant endpoints that should not be cached.
	 */
	public function get_cache_exclusions() {

		return (array) apply_filters(
			'rw_elephant_endpoint_cache_exclusions',
			[
				'list_items_for_search',
			]
		);

	}

	/**
	 * Build the API endpoint URL.
	 *
	 * @since 2.0.0
	 *
	 * @return string API endpoint URL.
	 */
	public function build_url() {

		/**
		 * Filter the API arguments.
		 *
		 * @since 2.0.0
		 *
		 * @var array
		 */
		$query_args = (array) apply_filters(
			'rw_elephant_api_args',
			[
				'tenant'   => strtolower( $this->options['tenant-id'] ),
				'api_key'  => $this->options['api-key'],
				'action'   => $this->action,
				'callback' => 'data',
			]
		);

		if ( ! empty( $this->additional_api_args ) ) {

			$query_args = wp_parse_args( $query_args, $this->additional_api_args );

		}

		return add_query_arg( $query_args, self::API_BASE );

	}

	/**
	 * Execute the API request.
	 *
	 * @since 2.0.0
	 *
	 * @return false|json False on error, else json array of results.
	 */
	public function request() {

		$api_data_transient = get_transient( $this->transient_name );

		// If the endpoint is an excluded endpoint, always set transient data to
		// false so no cache is returned
		if ( in_array( $this->action, $this->get_cache_exclusions(), true ) ) {

			$api_data_transient = false;

		}

		/**
		 * Filter to toggle caching of API data.
		 *
		 * @since 2.0.0
		 *
		 * @var boolean
		 */
		$cache_api_data = (bool) apply_filters( 'rw_elephant_cache_api_data', true );

		// Note: Cache is bypassed when WP_DEBUG is set to TRUE
		if ( ! WP_DEBUG && $cache_api_data && false !== $api_data_transient ) {

			return $api_data_transient;

		}

		/**
		 * Filter the API request arguments.
		 *
		 * @since 2.0.0
		 *
		 * @var array
		 */
		$request_args = (array) apply_filters(
			'rw_elephant_api_request_args',
			[
				'timeout' => 30,
			]
		);

		$request = wp_remote_get( $this->url, $request_args );

		if ( is_wp_error( $request ) ) {

			return new \WP_Error( 'error', $request->get_error_message() );

		}

		if ( ! $request || empty( $request ) ) {

			return new \WP_Error( 'error', __( 'There was an error during the API request. Please try again. If things continue to fail, please try increasing the API request timeout.', 'rw-elephant-rental-inventory' ) );

		}

		$response_body = wp_remote_retrieve_body( $request );
		$response_code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $response_code ) {

			return new \WP_Error( 'error', $this->extract_error( $response_body ) );

		}

		$data = rwe_jsonp_decode( $response_body );

		set_transient( $this->transient_name, $data, $this->transient_expiration );

		return $data;

	}

	/**
	 * Extract the error text from the response body.
	 *
	 * @since 2.0.0
	 *
	 * @param array $response_body API request response body.
	 *
	 * @return string Error response text.
	 */
	public function extract_error( $response_body ) {

		$split_response = explode( '<pre>', $response_body );
		$error_extract  = isset( $split_response[1] ) ? explode( '. at', $split_response[1] ) : '';

		return isset( $error_extract[0] ) ? $error_extract[0] . '.' : '';

	}

	/**
	 * Filter cache enable/disable based on the 'disable-cache' option
	 *
	 * @since 2.1.2
	 *
	 * @return bool True when the 'disable-cache' option is enabled, else false.
	 */
	public function toggle_cache_enabled() {

		return ! rwe_is_cache_disabled();

	}

}
