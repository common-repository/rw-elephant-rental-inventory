<?php
/**
 * RW Elephant Rental Inventory - "No Products Found" Template
 *
 * @author RW Elephant <support@rwelephant.com>
 */
?>

<div class="rwe-grid__noitem">
	<div class="rwe-no-products">

		<?php

		/**
		 * Filter the 'No Rentals Found' message.
		 *
		 * @var string
		 */
		$no_rentals_text = (string) apply_filters( 'rw_elephant_no_products_text', esc_html__( 'No Rentals Found.', 'rw-elephant-rental-inventory' ) );

		printf(
			'<h4>%s</h4>',
			esc_html( $no_rentals_text )
		);

		?>

	</div>
</div>
