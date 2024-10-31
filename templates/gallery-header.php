<?php
/**
 * RW Elephant Rental Inventory - Gallery Header Template
 *
 * @author RW Elephant <support@rwelephant.com>
 *
 * @since 2.0.0
 */

/**
 * Before Gallery Header
 */
do_action( 'rw_elephant_gallery_header_before' );

?>

<header class="rwe-inventory__header">

	<?php

	/**
	 * Gallery Header
	 *
	 * @hooked rwe_breadcrumbs - 10
	 * @hooked rwe_actions - 15
	 */
	do_action( 'rw_elephant_gallery_header' );

	?>

</header>

<?php

/**
 * After Gallery Header
 */
do_action( 'rw_elephant_gallery_header_after' );

?>
