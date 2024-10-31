<?php

// Namespace: Anagrom of plugin name
namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

spl_autoload_register(
	function( $resource ) {

		if ( 0 !== strpos( $resource, __NAMESPACE__ ) ) {

			return;

		}

			$resource = strtolower(
				str_replace(
					[ __NAMESPACE__ . '\\', '_' ],
					[ '', '-' ],
					$resource
				)
			);

			$parts = explode( '\\', $resource );
			$name  = array_pop( $parts );
			$files = str_replace( '//', '/', glob( sprintf( '%s/%s/*-%s.php', __DIR__, implode( '/', $parts ), $name ) ) );

		if ( isset( $files[0] ) && is_readable( $files[0] ) ) {

			require_once $files[0];

			return;

		}

			// @todo: Run loop for sub directory code here.

			$shortcodes = str_replace( '//', '/', glob( sprintf( '%s/%s/%s/*-%s.php', __DIR__, implode( '/', $parts ), 'shortcodes/', $name ) ) );

		if ( isset( $shortcodes[0] ) && is_readable( $shortcodes[0] ) ) {

			require_once $shortcodes[0];

		}

			$widgets = str_replace( '//', '/', glob( sprintf( '%s/%s/%s/*-%s.php', __DIR__, implode( '/', $parts ), 'widgets/', $name ) ) );

		if ( isset( $widgets[0] ) && is_readable( $widgets[0] ) ) {

			require_once $widgets[0];

		}

			$compat = str_replace( '//', '/', glob( sprintf( '%s/%s/%s/*-%s.php', __DIR__, implode( '/', $parts ), 'compat/', $name ) ) );

		if ( isset( $compat[0] ) && is_readable( $compat[0] ) ) {

			require_once $compat[0];

		}

	}
);

add_action( 'plugins_loaded', [ __NAMESPACE__ . '\Plugin', 'init' ] );
