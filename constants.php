<?php
/**
 * Constants for the RW Inventory Gallery
 *
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {

	die;

}

/**
 * Define the version number
 *
 * @since 1.0.0
 */
if ( ! defined( 'RW_ELEPHANT_RENTAL_INVENTORY_VERSION' ) ) {

	define( 'RW_ELEPHANT_RENTAL_INVENTORY_VERSION', '2.3.8' );

}

/**
 * Define the path to the plugin
 *
 * @since 1.0.0
 */
if ( ! defined( 'RW_ELEPHANT_RENTAL_INVENTORY_PATH' ) ) {

	define( 'RW_ELEPHANT_RENTAL_INVENTORY_PATH', plugin_dir_path( __FILE__ ) );

}

/**
 * Define the url to the plugin
 *
 * @since 1.0.0
 */
if ( ! defined( 'RW_ELEPHANT_RENTAL_INVENTORY_URL' ) ) {

	define( 'RW_ELEPHANT_RENTAL_INVENTORY_URL', plugin_dir_url( __FILE__ ) );

}
