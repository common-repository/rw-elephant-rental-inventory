<?php
/**
#_________________________________________________ PLUGIN
Plugin Name: RW Elephant Rental Inventory
Plugin URI: https://www.rwelephant.com/
Description: Gallery displays R.W. Elephant rental inventory on your website.
Version: 2.3.8
Author: R.W. Elephant
Author URI: https://www.rwelephant.com/
Text Domain: rw-elephant-rental-inventory
Domain Path: languages
License: GPL2

#_________________________________________________ LICENSE
Copyright 2012-18 R.W. Elephant (email : info@rwelephant.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

#_________________________________________________ CONSTANTS
*/

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( ! class_exists( 'RWEGallery' ) ) {

	final class RWEGallery {

		/**
		 * Minimum PHP version
		 *
		 * @var string
		 */
		private $php_min_version = '5.6.0';

		/**
		 * Plugin assets URL
		 *
		 * @var string
		 */
		public static $assets_url;

		/**
		 * Plugin Data
		 *
		 * @var array
		 */
		public static $plugin_data;

		public function __construct( $cur_php_version = PHP_VERSION ) {

			self::$assets_url = plugin_dir_url( __FILE__ ) . 'lib/assets/';

			self::$plugin_data = $this->get_plugin_data();

			require_once __DIR__ . '/constants.php';
			require_once __DIR__ . '/lib/helpers.php';
			require_once __DIR__ . '/lib/classes/autoload.php';

			if ( defined( 'WP_CLI' ) && WP_CLI ) {

				\WP_CLI::add_command( 'rwe', '\RWEG\CLI' );

				return;

			}

			add_action( 'plugins_loaded', [ $this, 'i18n' ] );

			if ( version_compare( $cur_php_version, $this->php_min_version, '<' ) ) {

				new RWEG\Notice(
					'error',
					sprintf(
						/* translators: 1. The minimum support PHP version for this plugin. */
						__( 'RW Elephant Rental Inventory requires PHP version %s or higher. Please contact your system administrator.', 'rw-elephant-rental-inventory' ),
						esc_html( $this->php_min_version )
					),
					true
				);

			}

		}

		/**
		 * Load languages
		 *
		 * @action plugins_loaded
		 */
		public function i18n() {

			load_plugin_textdomain( 'rw-elephant-rental-inventory', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		}

		/**
		 * Get the plugin data from the plugin header in this file
		 *
		 * @return array Data from the plugin header.
		 *
		 * @since 1.0.0
		 */
		public function get_plugin_data() {

			if ( ! function_exists( 'get_plugins' ) ) {

				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			}

			$plugin_data = get_plugin_data( __FILE__ );

			$plugin_data['base'] = plugin_basename( __FILE__ );

			return $plugin_data;

		}

	}

	new RWEGallery();

}
