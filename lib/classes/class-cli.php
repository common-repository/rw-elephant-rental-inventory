<?php
/**
 * CLI Class
 *
 * @author R.W. Elephant <info@rwelephant.com>
 *
 * @since 2.0.0
 */

namespace RWEG;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class CLI extends \WP_CLI_Command {

	/**
	 * Reset the WordPress site as if this plugin were never installed.
	 *
	 * This command is monst commonly used for testing purposes, to undo everything
	 * that this plugin does during the on-boarding process (settings, etc.)
	 *
	 * Note: Requires WP_DEBUG to be set to TRUE
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Answer yes to confirmation message
	 *
	 * ## EXAMPLES
	 *
	 *    wp rwe reset [--yes]
	 */
	public function reset( $args, $assoc_args ) {

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {

			WP_CLI::error( 'WP_DEBUG must be enabled to reset RW Elephant Rental Inventory!' );

		}

		WP_CLI::confirm( 'Are you sure you want to reset your WordPress site and remove all RW Elephant Rental Inventory data?', $assoc_args );

		/**
		 * Delete the plugin options
		 */
		WP_CLI::line( 'Deleting options...' );

		delete_option( 'rw-elephant-rental-inventory' );

		WP_CLI::line( 'Options successfully deleted.' );

		/**
		 * Delete the cached API data and plugin transients
		 */
		WP_CLI::line( 'Deleting transients/cached data...' );

		global $wpdb;

		$transients = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * from `{$wpdb->prefix}options` WHERE option_name LIKE %s;",
				'%rw-%'
			)
		);

		if ( ! $transients || empty( $transients ) ) {

			WP_CLI::line( 'No cached data found.' );

		}

		if ( $transients && ! empty( $transients ) ) {

			foreach ( $transients as $transient ) {

				delete_transient( str_replace( '_transient_', '', $transient->option_name ) );

			}

			WP_CLI::line( 'Cached data cleared.' );

		}

	}

}
