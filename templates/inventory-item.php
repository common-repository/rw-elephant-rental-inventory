<?php
/**
 * RW Elephant Rental Inventory - Inventory Item Template
 *
 * @author RW Elephant <support@rwelephant.com>
 *
 * @since 2.0.0
 */
?>

<div class="rwe-grid__item">

	<div class="rwe-item">

		<?php

		/**
		 * Inventory Item Top
		 *
		 * @hooked rwe_wishlist_remove_button - 10
		 */
		do_action( 'rw_elephant_inventory_item_top' );

		?>

		<a class="rwe-item__link" href="<?php rwe_url(); ?>" title="<?php rwe_title_attr(); ?>">

			<?php

			/**
			 * Inventory Item Content
			 *
			 * @hooked rwe_image - 10
			 * @hooked rwe_title - 15
			 */
			do_action( 'rw_elephant_inventory_item_content' );

			?>

		</a>

		<?php

		/**
		 * Inventory Item Bottom
		 *
		 * @hooked rwe_wishlist_product_quantity - 10
		 */
		do_action( 'rw_elephant_inventory_item_bottom' );

		?>

	</div>

</div>
