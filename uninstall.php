<?php
/**
* Uninstall File
*
* Executed at the time of plugin uninstall
*/

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

	exit();

}

delete_option( 'rw-elephant-rental-inventory' );
