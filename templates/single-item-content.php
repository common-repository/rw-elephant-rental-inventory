<?php
/**
 * RW Elephant Rental Inventory - Single Item Content Template
 *
 * @author RW Elephant <support@rwelephant.com>
 */
?>

<div class="rwe-inventory rwe-inventory--item-single">

	<?php

	/**
	 * RW Elephant Single Product Top
	 *
	 * @hooked RWEG\Gallery::render_gallery_header - 10
	 */
	do_action( 'rw_elephant_product_top' );

	?>

	<div class="rwe-inventory__main">

		<div class="rwe-single rwe-single--<?php rwe_layout_class(); ?>">

			<?php

			/**
			 * RW Elephant Single Product Content
			 *
			 * @hooked rwe_image_slider - 10
			 * @hooked rwe_product_content - 15
			 * @hooked rwe_kit_contents - 20
			 */
			do_action( 'rw_elephant_product_content' );

			?>

		</div>

		<?php

		/**
		 * RW Elephant Single Product Bottom
		 *
		 * @hooked rwe_related_items - 10
		 */
		do_action( 'rw_elephant_product_bottom' );

		?>

	</div>

</div>
