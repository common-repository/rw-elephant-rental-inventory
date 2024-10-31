<?php
/**
 * Pluggable Helper Methods
 *
 * These can be overridden by re-defining them inside of a plugin or theme.
 */

if ( ! function_exists( 'rwe_image' ) ) {
	/**
	 * Render the product thumbnail
	 * Note: Should instantiate a new RWEG\Data class before using.
	 *
	 * @return mixed Markup for the thumbnail if it exists, else empty.
	 */
	function rwe_image() {

		$tenant_id   = RWEG\Options::$options['tenant-id'];
		$data        = RWEG\Data::$data;
		$image_links = ! empty( $data['image_links'] ) ? $data['image_links'][0] : $data;

		/**
		 * Note: v2.0.4 introduced thumbnail size (padded or cropped), some users may not have the option.
		 */
		$format = 'cm-pad_resize,';
		if ( isset( RWEG\Options::$options['thumbnail-size'] ) ) {
			$format = 'plugin_320px_padded_link' === RWEG\Options::$options['thumbnail-size'] ? 'cm-pad_resize,' : '';
		}

		/**
		 * Note: v2.2.19 introduced thumbnail dimensions.
		 */
		if ( isset( RWEG\Options::$options['thumbnail-dimensions'] ) && '' !== RWEG\Options::$options['thumbnail-dimensions'] ) {
			$dimensions = RWEG\Options::$options['thumbnail-dimensions'];
		} else {
			$dimensions = '320';
		}

		if ( ! $image_links || ! isset( $image_links['photo_hash'] ) ) {
			$image_url = RWEG\Plugin::$placeholder_url;
		} else {
			$image_url = "https://ik.imagekit.io/rwelephant/tr:h-{$dimensions},w-{$dimensions},{$format}bg-FFFFFF00/{$tenant_id}_photo_{$image_links['photo_hash']}";
		}
		$alt_text = isset( $image_links['alternate_text'] ) ? 'alt="' . htmlentities( $image_links['alternate_text'] ) . '"' : '';

		$hover_image = '';
		$hover_class = '';

		if ( is_rwe_gallery() ) {

			$thumbnails   = RWEG\Options::$options['thumbnails'];
			$inventory_id = isset( $data['inventory_type_id'] ) ? sanitize_text_field( $data['inventory_type_id'] ) : false;

			/**
			 * Filter the gallery image size (eg: use other sizes beyond the 320px cropped)
			 *
			 * @var int Image dimension, such as 500
			 */
			$gallery_image_size = (string) apply_filters( 'rw_elephant_gallery_image_size', $dimensions );
			if ( ! $image_links || ! isset( $image_links['photo_hash'] ) ) {
				$image_url = RWEG\Plugin::$placeholder_url;
			} else {
				$image_url = "https://ik.imagekit.io/rwelephant/tr:h-{$gallery_image_size},w-{$gallery_image_size},{$format}bg-FFFFFF00/{$tenant_id}_photo_{$image_links['photo_hash']}";
			}

			if ( false !== $inventory_id ) {

				if ( array_key_exists( intval( $inventory_id ), $thumbnails ) && isset( $thumbnails[ $inventory_id ]['id'] ) && '' !== $thumbnails[ $inventory_id ]['id'] ) {
					$image_src = wp_get_attachment_image_src( $thumbnails[ $inventory_id ]['id'], 'rwe-category-thumbnail' );
					$image_url = ! $image_src ? $thumbnails[ $inventory_id ]['url'] : $image_src[0];

				}
			}

			$hover_image_links = ! empty( $data['image_links'][1] ) ? $data['image_links'][1] : false;
			if ( isset( RWEG\Options::$options['enable-hover-thumb'] ) && '1' === RWEG\Options::$options['enable-hover-thumb'] && $hover_image_links ) {
				$hover_image_url = "https://ik.imagekit.io/rwelephant/tr:h-{$gallery_image_size},w-{$gallery_image_size},{$format}bg-FFFFFF00/{$tenant_id}_photo_{$hover_image_links['photo_hash']}";
				$alt_text        = isset( $hover_image_links['alternate_text'] ) ? 'alt="' . htmlentities( $hover_image_links['alternate_text'] ) . '"' : '';
				$hover_image     = sprintf( '<img src="%1$s" %2$s class="rwe-thumbnail-hover" />', esc_url( $hover_image_url ), esc_attr( $alt_text ) );
				$hover_class     = 'rwe-item__image--has-hover-image';
			}
		}

		return printf( '<div class="rwe-item__image %1$s"><img src="%2$s" %3$s class="rwe-thumbnail" />%4$s</div>', esc_attr( $hover_class ), esc_url( $image_url ), $alt_text, $hover_image );

	}
}

/**
 * Render the product thumbnail
 * Note: Should instantiate a new RWEG\Data class before using.
 *
 * @param string  $size The thumbnail size to retreive.
 *
 * @return mixed Markup for the thumbnail if it exists, else empty.
 */
function rwe_get_image_url( $size = 'thumbnail' ) {

	$image_links = isset( RWEG\Single_Item::$item_data['image_links'][0] ) ? RWEG\Single_Item::$item_data['image_links'][0] : ( isset( RWEG\Data::$data['image_links'][0] ) ? RWEG\Data::$data['image_links'][0] : [] );

	$sizes = [
		'thumbnail'       => 'thumbnail_link',
		'large-thumbnail' => 'large_thumbnail_link',
		'small'           => '300_link',
		'medium'          => '600_link',
		'large'           => '1200_link',
		'plugin'          => 'plugin_250px_thumbnail_link',
		'original'        => '',
	];

	// If specified size is not available, return the medium image.
	$size = isset( $sizes[ $size ] ) ? $sizes[ $size ] : 'medium';

	if ( ! $image_links || ! $size || ! isset( $image_links[ $size ] ) ) {

		return esc_url( RWEG\Plugin::$placeholder_url );

	}

	return esc_url( $image_links[ $size ] );

}

/**
 * Retreive the last enqueued style.
 * Note: Used to ensure RW Elephant styles are enqueued last.
 *
 * @return string The handle of the last enqueued style on the page.
 */
function rwe_get_last_style() {

	global $wp_styles;

	return ( isset( $wp_styles->queue ) && ! empty( $wp_styles->queue ) ) ? end( $wp_styles->queue ) : '';

}

/**
 * Get the gallery type class.
 * eg: item-list, category-list etc.
 *
 * @return string Gallery type
 */
function rwe_get_gallery_type() {

	$type = 'category';

	global $wp_query;

	// Gallery View
	if ( is_rwe_gallery() ) {

		$type = 'category-list';

	}

	// Category view
	if ( isset( $wp_query->query_vars['rw_category'] ) || isset( $_GET['category'] ) ) {

		$type = 'item-list';

	}

	// Search Results
	if ( is_rwe_search() ) {

		$type = 'search-results';

	}

	return $type;

}

/**
 * Generate custom rewrite rules.
 */
function rwe_custom_rewrite_rules() {

	$wishlist_page_obj = get_post( RWEG\Options::$options['wishlist-page'] );

	// the slug of the page to handle these rules
	$wishlistpage = is_object( $wishlist_page_obj ) ? $wishlist_page_obj->post_name : 'wishlist';

	if ( empty( RWEG\Options::$options['gallery-page'] ) ) {

		return;

	}

	$item_slug = RWEG\Plugin::$item_slug;
	$page_obj  = get_post( RWEG\Options::$options['gallery-page'] );

	// the slug of the page to handle these rules
	$gallery = is_object( $page_obj ) ? $page_obj->post_name : 'gallery';

	// gallery/$category (excludes /item/ - or whatever the custom item slug is set to)
	add_rewrite_rule( $gallery . '/(?!' . $item_slug . ')([^/]+)', 'index.php?pagename=' . $gallery . '&rw_category=$matches[1]', 'top' );
	add_rewrite_rule( $gallery . '/' . $item_slug . '/([^/]+)', 'index.php?pagename=' . $gallery . '/' . $item_slug . '&rw_item=$matches[1]', 'top' );

}

/**
 * Render the category thumbnail
 * Note: Should instantiate a new RWEG\Data class before using.
 *
 * @param string  $size The thumbnail size to retreive.
 *
 * @return mixed Markup for the thumbnail if it exists, else empty.
 */
function rwe_get_category_image_url( $size = 'thumbnail', $include_override = true ) {

	$data = RWEG\Data::$data;

	$image_link = isset( $data[ $size ] ) ? $data[ $size ] : '';

	// Return the original thumbnail URL
	if ( ! $include_override || empty( $data['inventory_type_id'] ) ) {

		return $image_link;

	}

	$thumbnail_overrides = RWEG\Options::$options['thumbnails'];

	// Check for this category in the override options.
	if ( ! isset( $thumbnail_overrides[ $data['inventory_type_id'] ] ) || empty( $thumbnail_overrides[ $data['inventory_type_id'] ]['id'] ) ) {

		return $image_link;

	}

	$image = wp_get_attachment_image_src( $thumbnail_overrides[ $data['inventory_type_id'] ]['id'], 'full' );

	if ( ! $image || empty( $image[0] ) ) {

		return $image_link;

	}

	return esc_url( $image[0] );

}

if ( ! function_exists( 'rwe_title' ) ) {
	/**
	 * Render the product title
	 * Note: Should instantiate a new RWEG\Data class before using.
	 *
	 * @return string Title if it exists, else empty.
	 */
	function rwe_title( $before = '<h3 class="rwe-item__title">', $after = '</h3>' ) {

		$data = RWEG\Data::$data;

		if ( is_rwe_category_item() ) {

			return rwe_category_title();

		}

		if ( empty( $before ) ) {
			$before = '<h3 class="rwe-item__title">';
		}

		if ( empty( $after ) ) {
			$after = '</h3>';
		}

		echo isset( $data['name'] ) ? sprintf( '%s%s%s', $before, esc_html( $data['name'] ), $after ) : '';

	}
}

/**
 * Render the product title attribute
 *
 * Note: Can also be used for category title attributes.
 * Note: Should instantiate a new RWEG\Data class before using.
 *
 * @return string Title if it exists, else empty.
 */
function rwe_title_attr() {

	$data = RWEG\Data::$data;

	if ( is_rwe_category_item() ) {

		echo isset( $data['inventory_type_name'] ) ? esc_html( $data['inventory_type_name'] ) : '';

		return;

	}

	echo isset( $data['name'] ) ? esc_html( $data['name'] ) : '';

}

if ( ! function_exists( 'rwe_category_title' ) ) {
	/**
	 * Render the category title
	 * Note: Should instantiate a new RWEG\Data class before using.
	 *
	 * @return string Title if it exists, else empty.
	 */
	function rwe_category_title( $before = '<h3 class="rwe-item__title">', $after = '</h3>' ) {

		$data = RWEG\Data::$data;

		echo isset( $data['inventory_type_name'] ) ? sprintf( '%s%s%s', $before, esc_html( $data['inventory_type_name'] ), $after ) : '';

	}
}

if ( ! function_exists( 'rwe_url' ) ) {
	/**
	 * Render the product URL
	 * Note: Should instantiate a new RWEG\Data class before using or pass in a product ID.
	 *
	 * @return string URL for the product.
	 */
	function rwe_url( $id = false ) {

		$data = RWEG\Data::$data;

		if ( is_rwe_category_item() ) {

			return trailingslashit( rwe_category_url() );

		}

		$id = ! $id ? ( isset( $data['inventory_item_id'] ) ? $data['inventory_item_id'] : '' ) : $id;
		$id = sanitize_title( $data['name'] ) . '-' . $id;

		if ( empty( get_option( 'permalink_structure', '' ) ) ) {

			echo trailingslashit( add_query_arg( 'product', $id, site_url() ) );

			return;

		}

		$page_obj = get_post( RWEG\Options::$options['gallery-page'] );
		$gallery  = is_object( $page_obj ) ? $page_obj->post_name : 'gallery';

		printf( trailingslashit( site_url() . '/%1$s/%2$s/%3$s' ), $gallery, RWEG\Plugin::$item_slug, $id );

	}
}

if ( ! function_exists( 'rwe_get_url' ) ) {
	/**
	 * Return the product URL
	 * Note: Should instantiate a new RWEG\Data class before using or pass in a product ID.
	 *
	 * @return string URL for the product.
	 */
	function rwe_get_url( $id = false ) {

		$data = RWEG\Data::$data;
		$id   = ! $id ? ( isset( $data['inventory_item_id'] ) ? $data['inventory_item_id'] : '' ) : $id;

		if ( empty( get_option( 'permalink_structure', '' ) ) ) {

			return add_query_arg( 'product', $id, site_url() );

		}

		$page_obj = get_post( RWEG\Options::$options['gallery-page'] );
		$gallery  = is_object( $page_obj ) ? $page_obj->post_name : 'gallery';

		return sprintf( site_url() . '/%1$s/%2$s/%3$s', $gallery, RWEG\Plugin::$item_slug, $id );

	}
}

/**
 * Return the product URL base.
 * Note: Used in the wishlist scripts.
 *
 * @return string URL for the product.
 */
function rwe_get_url_base( $id = false ) {

	if ( empty( get_option( 'permalink_structure', '' ) ) ) {

		return add_query_arg( 'product', 'productID', site_url() );

	}

	$page_obj = get_post( RWEG\Options::$options['gallery-page'] );
	$gallery  = is_object( $page_obj ) ? $page_obj->post_name : 'gallery';

	return sprintf( site_url() . '/%1$s/%2$s/productID', $gallery, RWEG\Plugin::$item_slug );

}

if ( ! function_exists( 'rwe_category_url' ) ) {
	/**
	 * Echo the category URL
	 * Note: Should instantiate a new RWEG\Data class before using.
	 *
	 * @return string URL for the category.
	 */
	function rwe_category_url() {

		$data = RWEG\Data::$data;
		$id   = isset( $data['inventory_type_name'] ) ? $data['inventory_type_name'] : '';

		if ( empty( get_option( 'permalink_structure', '' ) ) ) {

			echo add_query_arg( 'category', sanitize_title( $data['inventory_type_name'] ), get_the_permalink( RWEG\Options::$options['gallery-page'] ) );

			return;

		}

		echo sprintf( trailingslashit( get_the_permalink( RWEG\Options::$options['gallery-page'] ) ) . '%1$s/', sanitize_title( $data['inventory_type_name'] ) );

	}
}

/**
 * Retreive the category from either the rw_category query var, or
 * from the category query param ?category=xyz.
 *
 * @since 2.0.0
 *
 * @return string The category.
 */
function rwe_get_category() {

	$category = get_query_var( 'rw_category', false );

	if ( ! $category ) {

		$category = ! empty( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';

	}

	return $category;

}

/**
 * Retreive the product ID from the rw_item query var, or
 * from the product query param ?product=123.
 *
 * @since 2.1.2
 *
 * @return integer The product ID.
 */
function rwe_get_product_id() {

	$product_id = get_query_var( 'rw_item', false );

	if ( ! $product_id ) {

		$product_id = ! empty( $_GET['product'] ) ? sanitize_text_field( $_GET['product'] ) : '';

	}

	$split = explode( '-', $product_id );

	return end( $split );

}

/**
 * Retreive all categories setup for products.
 *
 * @since 2.0.0
 *
 * @return array The categories.
 */
function rwe_get_categories() {

	$api        = new RWEG\API( 'list_inventory_types' );
	$categories = $api->request();

	return $categories;

}

/**
 * Detect if this is this a gallery view.
 * Note: When on the page with ID matching gallery-page option or
 *       the query var rw_category is present, it is assumed user is viewing the gallery.
 *
 * @return boolean True if on the gallery page, else false
 */
function is_rwe_gallery() {

	global $post;

	if ( isset( $post->ID ) && (int) RWEG\Options::$options['gallery-page'] === $post->ID ) {

		return true;

	}

	$rw_category = get_query_var( 'rw_category', false );

	if ( ! $rw_category ) {

		$rw_category = ! empty( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';

	}

	return ! $rw_category ? false : true;

}

/**
 * Check if the current item in a loop is a category item.
 *
 * @return boolean True when is_category_item is set in the $data array, else false.
 */
function is_rwe_category_item() {

	$data = RWEG\Data::$data;

	return isset( $data['is_category_item'] ) ? $data['is_category_item'] : false;

}

/**
 * Determine if this is the wishlist submission page.
 *
 * @since 2.0.0
 *
 * @return boolean True when on the wishlist submission page, else false.
 */
function is_rwe_wishlist_submission_page() {

	$action = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

	if ( get_the_ID() !== (int) RWEG\Options::$options['wishlist-page'] || ! $action ) {

		return false;

	}

	return ( 'submit-wishlist' === $action );

}

/**
 * Detect if this is this a single item.
 * Note: When the query var rw_item is present, it is assumed a single item page.
 *
 * @return boolean True if viewing a single item, else false
 */
function is_rwe_single() {

	global $wp_query;
	if( ! isset( $wp_query ) || ! method_exists( $wp_query, 'get' ) ) return false;

	$rw_item = get_query_var( 'rw_item', false );

	if ( ! $rw_item ) {

		$product = ! empty( $_GET['product'] ) ? sanitize_text_field( $_GET['product'] ) : '';
		$rw_item = ! $product ? false : true;

	}

	return ! $rw_item ? false : true;

}

/**
 * Detect if this is this the wishlist page.
 *
 * @return boolean True if viewing a wishlist page, else false
 */
function is_rwe_wishlist() {

	global $post;

	if ( ! $post || ! isset( $post->ID ) ) {

		return false;

	}

	return ( RWEG\Options::$options['wishlist-page'] === $post->ID );

}

/**
 * Check if this is the search results page.
 *
 * @since 2.0.0
 *
 * @return boolean True when on the search results, else false.
 */
function is_rwe_search() {

	$term = ! empty( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

	global $post;

	if ( ! $post || ! isset( $post->ID ) || ! $term ) {

		return false;

	}

	return ( RWEG\Options::$options['gallery-page'] === $post->ID );

}

/**
 * Check if this is a RW Elephant kit.
 *
 * @since 2.0.0
 *
 * @return boolean True when on the item being viewed is a kit, else false.
 */
function is_rwe_kit() {

	$kit_contents = isset( RWEG\Single_Item::$item_data['inventory_kit_line_items'] ) ? RWEG\Single_Item::$item_data['inventory_kit_line_items'] : ( isset( RWEG\Data::$data['inventory_kit_line_items'] ) ? RWEG\Data::$data['inventory_kit_line_items'] : [] );

	return ! empty( $kit_contents );

}

/**
 * Generate a 'x' button to remove the item from the wishlist page.
 *
 * @since 2.0.0
 *
 * @return null
 */
function rwe_wishlist_remove_button() {

	if ( ! is_rwe_wishlist() ) {

		return;

	}

	rwe_enqueue_wishlist_scripts();

	printf(
		'<div class="js-remove-from-wishlist rwe-item__remove" data-itemID="%s" aria-label="Remove">
			<span class="rwe-icon rwe-icon--remove"><svg viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><path d="M5 3.586L2.173.759A.999.999 0 1 0 .76 2.173L3.586 5 .759 7.827A.999.999 0 1 0 2.173 9.24L5 6.414l2.827 2.827A.999.999 0 1 0 9.24 7.827L6.414 5l2.827-2.827A.999.999 0 1 0 7.827.76L5 3.586z" fill-rule="evenodd"></path></svg></span>
		</div>',
		esc_attr( RWEG\Data::$data['inventory_item_id'] )
	);

}

/**
 * Set the <title> tag on specific pages.
 *
 * @param  string $title The <title> tag text.
 *
 * @return string        The filtered <title> tag text.
 */
function rwe_page_title_tag( $title ) {

	// Core document title separator
	$sep      = apply_filters( 'document_title_separator', '-' );
	$category = get_query_var( 'rw_category', false );

	// Category page, eg: Stools
	if ( false !== $category ) {

		$title = ucwords( str_replace( '-', ' ', $category ) ) . " $sep " . get_bloginfo( 'name', 'display' );

	}

	// Single page, eg: Powell Blue Stool
	if ( is_rwe_single() ) {

		$title = rwe_item_name( false ) . " $sep " . get_bloginfo( 'name', 'display' );

	}

	return $title;

}

/**
 * Set the <meta> tag on specific pages.
 *
 * @param  string $meta_description The <meta> tag text.
 *
 * @return string        The filtered <meta> tag text.
 */
function rwe_page_description_meta( $meta_description ) {

	// Prevents the og:description from rendering on the gallery page.
	if ( is_rwe_gallery() ) {

		$meta_description = '';

	}

	// Single item page
	if ( is_rwe_single() ) {

		$meta_description = RWEG\Single_Item::$item_data['description'];

	}

	return $meta_description;

}

/**
 * Single Template Helpers
 */

/**
 * Retreive the item name
 *
 * @param  boolean $echo Should the title be echoed?
 *
 * @return string The item name.
 */
function rwe_item_name( $echo = true ) {

	$item_name = isset( RWEG\Single_Item::$item_data['name'] ) ? RWEG\Single_Item::$item_data['name'] : ( isset( RWEG\Data::$data['name'] ) ? RWEG\Data::$data['name'] : '' );

	if ( ! $echo ) {

		return $item_name;

	}

	echo $item_name;

}

/**
 * Retreive the item description.
 *
 * @param  boolean $echo Should the description be echoed?
 *
 * @return string The item description.
 */
function rwe_item_description( $echo = true ) {

	$item_description = isset( RWEG\Single_Item::$item_data['description'] ) ? RWEG\Single_Item::$item_data['description'] : ( isset( RWEG\Data::$data['description'] ) ? RWEG\Data::$data['description'] : '' );

	/**
	 * Filter the product description.
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	$item_description = apply_filters( 'rw_elephant_product_description', $item_description, RWEG\Single_Item::$item_data );

	if ( ! $echo ) {

		return $item_description;

	}

	echo $item_description;

}

/**
 * Retrieve the item price.
 *
 * @param  boolean $echo Should the price be echoed?
 *
 * @return string The item price.
 */
function rwe_item_rental_price( $echo = true ) {

	$item_rental_price = isset( RWEG\Single_Item::$item_data['rental_price'] ) ? RWEG\Single_Item::$item_data['rental_price'] : ( isset( RWEG\Data::$data['rental_price'] ) ? RWEG\Data::$data['rental_price'] : '' );

	if ( '' === $item_rental_price || '0.00' === $item_rental_price ) {
		return;
	}

	$currency_symbol = RWEG\Options::$options['currency-symbol'] ? RWEG\Options::$options['currency-symbol'] : '$';

	/**
	 * Filter the product rental price.
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	$item_rental_price = apply_filters( 'rw_elephant_product_rental_price', $currency_symbol . $item_rental_price, RWEG\Single_Item::$item_data );

	if ( ! $echo ) {

		return $item_rental_price;

	}

	echo $item_rental_price;

}

/**
 * Retreive the item quantity.
 *
 * @param  boolean $echo Should the quantity be echoed?
 *
 * @return string The item quantity.
 */
function rwe_item_quantity( $echo = true ) {

	$item_quantity = isset( RWEG\Single_Item::$item_data['quantity'] ) ? RWEG\Single_Item::$item_data['quantity'] : ( isset( RWEG\Data::$data['quantity'] ) ? RWEG\Data::$data['quantity'] : '' );

	/**
	 * Filter the product quantity.
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	$item_quantity = apply_filters( 'rw_elephant_product_quantity', $item_quantity, RWEG\Single_Item::$item_data );

	if ( ! $echo ) {

		return $item_quantity;

	}

	echo $item_quantity;

}

/**
 * Retreive the item dimensions.
 *
 * @param  boolean $echo Should the dimensions be echoed?
 *
 * @return string The item dimensions.
 */
function rwe_item_dimensions( $echo = true ) {

	$dimensions = array_filter(
		[
			isset( RWEG\Single_Item::$item_data['frac_length'] ) ? RWEG\Single_Item::$item_data['frac_length'] : ( isset( RWEG\Data::$data['frac_length'] ) ? RWEG\Data::$data['frac_length'] : false ),
			isset( RWEG\Single_Item::$item_data['frac_width'] ) ? RWEG\Single_Item::$item_data['frac_width'] : ( isset( RWEG\Data::$data['frac_width'] ) ? RWEG\Data::$data['frac_width'] : false ),
			isset( RWEG\Single_Item::$item_data['frac_height'] ) ? RWEG\Single_Item::$item_data['frac_height'] : ( isset( RWEG\Data::$data['frac_height'] ) ? RWEG\Data::$data['frac_height'] : false ),
		]
	);

	if ( empty( $dimensions ) ) {

		return;

	}

	/**
	 * Filter the product dimensions.
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	$item_dimensions = apply_filters( 'rw_elephant_product_dimensions', implode( ' x ', $dimensions ), RWEG\Single_Item::$item_data );

	if ( ! $echo ) {

		return $item_dimensions;

	}

	echo $item_dimensions;

}

/**
 * Retreive the item tags.
 *
 * @param  boolean $echo Should the tags be echoed?
 *
 * @return string The item tags.
 */
function rwe_item_tags( $echo = true ) {

	$api = new RWEG\API(
		'list_tags_for_item',
		[
			'inventory_item_id' => RWEG\Single_Item::$item_data['inventory_item_id'],
		]
	);

	$tags = $api->request();

	if ( is_wp_error( $tags ) || empty( $tags ) ) {

		return '';

	}

	$ids   = wp_list_pluck( $tags, 'inventory_tag_type_id' );
	$names = wp_list_pluck( $tags, 'inventory_tag_name' );

	$final_tags_array = [];

	foreach ( array_combine( $ids, $names ) as $tag_id => $tag_name ) {

		$final_tags_array[] = sprintf(
			'<a href="%1$s" title="%2$s">%2$s</a>',
			add_query_arg( 'tag', esc_html( $tag_id ), get_the_permalink( RWEG\Options::$options['gallery-page'] ) ),
			esc_attr( $tag_name ),
			esc_html( $tag_name )
		);

	}

	/**
	 * Filter the product tags.
	 *
	 * @since 2.2.1
	 *
	 * @var array
	 */
	$final_tags_array = apply_filters( 'rw_elephant_product_tags', $final_tags_array, RWEG\Single_Item::$item_data );

	if ( ! $echo ) {

		return implode( ', ', $final_tags_array );

	}

	echo implode( ', ', $final_tags_array );

}

/**
 * Return the Custom ID value, when set.
 *
 * @return mixed The custom ID value.
 */
function rwe_custom_id() {

	$custom_id = ( isset( RWEG\Single_Item::$item_data['custom_id'] ) && ! empty( RWEG\Single_Item::$item_data['custom_id'] ) ) ? RWEG\Single_Item::$item_data['custom_id'] : false;

	/**
	 * Filter the custom ID
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	$custom_id = apply_filters( 'rw_elephant_product_custom_id', $custom_id, RWEG\Single_Item::$item_data );

	return $custom_id;

}

/**
 * Return the Custom Field value, when set.
 *
 * @param  array Custom field number (eg: 1, 2 etc.)
 *
 * @return mixed Custom field value
 */
function rwe_custom_field( $number = false ) {

	$custom_field = ( false !== $number && isset( RWEG\Single_Item::$item_data[ "custom_field_{$number}" ] ) && ! empty( RWEG\Single_Item::$item_data[ "custom_field_{$number}" ] ) ) ? RWEG\Single_Item::$item_data[ "custom_field_{$number}" ] : false;

	/**
	 * Filter the custom field
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	$custom_field = apply_filters( "rw_elephant_product_custom_field_{$number}", $custom_field, RWEG\Single_Item::$item_data );

	return $custom_field;

}

/**
 * Get all tags for the account.
 *
 * @return array Key value pair of all available tags.
 */
function rwe_get_item_tags() {

	$api  = new RWEG\API( 'list_tags' );
	$tags = $api->request();

	if ( is_wp_error( $tags ) ) {

		return esc_html_e( 'We were unable to retreive tags.', 'rw-elephant-rental-inventory' );

	}

	if ( empty( $tags ) ) {

		return [];

	}

	$ids   = wp_list_pluck( $tags, 'inventory_tag_type_id' );
	$names = wp_list_pluck( $tags, 'inventory_tag_name' );

	return array_combine( $ids, $names );

}

/**
 * Retreive the kit contents for the single item.
 *
 * @return string The item tags.
 */
function rwe_kit_contents() {

	$kit_contents = isset( RWEG\Single_Item::$item_data['inventory_kit_line_items'] ) ? RWEG\Single_Item::$item_data['inventory_kit_line_items'] : ( isset( RWEG\Data::$data['inventory_kit_line_items'] ) ? RWEG\Data::$data['inventory_kit_line_items'] : [] );

	if ( ! RWEG\Options::$options['display-kit-contents'] || empty( $kit_contents ) ) {

		return;

	}

	?>

	<div class="rwe-single__kit-contains">

		<div class="rwe-contains">

			<?php

			$kit_contents_title = ! empty( RWEG\Options::$options['kit-contents-title'] ) ? RWEG\Options::$options['kit-contents-title'] : __( 'Kit Contains', 'rw-elephant-rental-inventory' );

			printf( '<h3 class="rwe-contains__heading">%s</h3>', esc_html( $kit_contents_title ) );

			?>

			<div class="rwe-contains__list">

				<div class="rwe-grid rwe-grid--items">

					<?php

					foreach ( $kit_contents as $kit_content ) {

						new RWEG\Data( $kit_content );

						RWEG\Plugin::load_rw_template( 'inventory-item' );

					}

					?>

				</div>

			</div>

		</div>

	</div>

	<?php

}

/**
 * Retreive the item tags.
 *
 * @return string The item tags.
 */
function rwe_related_items() {

	$related_items = isset( RWEG\Single_Item::$item_data['related_items'] ) ? RWEG\Single_Item::$item_data['related_items'] : ( isset( RWEG\Data::$data['related_items'] ) ? RWEG\Data::$data['related_items'] : false );

	if ( ! RWEG\Options::$options['display-related-items'] || empty( $related_items ) ) {

		return;

	}

	?>

	<div class="rwe-related">

		<?php

		$related_title = ! empty( RWEG\Options::$options['related-item-title'] ) ? RWEG\Options::$options['related-item-title'] : __( 'You might also like', 'rw-elephant-rental-inventory' );

		printf( '<h3 class="rwe-related__heading">%s</h3>', esc_html( $related_title ) );

		?>

		<div class="rwe-related__list">

			<div class="rwe-grid rwe-grid--items">

				<?php

				foreach ( $related_items as $related_item ) {

					new RWEG\Data( $related_item );

					RWEG\Plugin::load_rw_template( 'inventory-item' );

				}

				?>

			</div>

		</div>

	</div>

	<?php

}

/**
 * Check for errors in the product data, and display errors or notices back to the user
 *
 * @param  array $product_data Product data array.
 *
 * @return mixed               Markup for the error when there is an error in the product data
 *                             or the enable-wishlists option is disabled.
 */
function rwe_wishlist_error_check( $product_data ) {

	if ( ! is_wp_error( $product_data ) && RWEG\Options::$options['enable-wishlists'] ) {

		return;

	}

	if ( is_wp_error( $product_data ) ) {

		remove_action( 'rw_elephant_wishlist_top', 'rwe_gallery_header', 10 );

		return printf(
			'<div class="error"><p>%s</p></div>',
			$product_data->get_error_message()
		);

	}

	if ( ! RWEG\Options::$options['enable-wishlists'] ) {

		return printf(
			'<p>%s</p>',
			esc_html__( 'Wishlists are not enabled on the site right now. Please check back at a later time.', 'rw-elephant-rental-inventory' )
		);

	}

}

/**
 * Enqueue the wishlist scripts.
 *
 * @return null
 */
function rwe_wishlist_scripts() {

	$suffix = SCRIPT_DEBUG ? '' : '.min';
	$rtl    = is_rtl() ? '-rtl' : '';

	wp_enqueue_style( 'rwe-gallery', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/rw-elephant{$rtl}{$suffix}.css", [ rwe_get_last_style() ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, 'all' );

	// Load styles from the options
	rwe_gallery_option_styles( 'rwe-gallery' );

	wp_enqueue_script( 'rwe-elephant-gallery', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/rw-elephant{$suffix}.js", [ 'jquery' ], RW_ELEPHANT_RENTAL_INVENTORY_VERSION, true );

}

/**
 * Output the empty wishlist text.
 *
 * @param array $wishlist Wishlist data array.
 * @param array $products Product data array.
 *
 * @return mixed Markup for the wishlist text.
 */
function rwe_wishlist_empty_text( $wishlist, $products ) {

	printf(
		'<div %s class="rwe-wishlist-empty"><p class="rwe-wishlist-empty__message">%s %s</p></div>',
		! empty( $wishlist ) ? 'style="display: none;"' : '',
		__( "You haven't added anything to your wishlist yet.", 'rw-elephant-rental-inventory' ),
		sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_the_permalink( RWEG\Options::$options['gallery-page'] ) ),
			__( 'Get Started', 'rw-elephant-rental-inventory' )
		)
	);

}

/**
 * Load RW Elephant Wishlist product template.
 *
 * @param  array  $prodct Product data array.
 *
 * @return mixed          Wishlist product template.
 */
function rwe_wishlist_product( $product ) {

	RWEG\Plugin::load_rw_template( 'inventory-item' );

}

/**
 * Render the RW Elephant product quantity field.
 *
 * @param  array $product Product data array.
 *
 * @return mixed Markup for the product quantity field.
 */
function rwe_wishlist_product_quantity() {

	if ( ! is_rwe_wishlist() || ! (bool) apply_filters( 'rw_elephant_product_quantities', true ) ) {

		return;

	}

	$max        = '';
	$max_helper = '';
	$value      = rwe_product_quantity_from_cookie( RWEG\Data::$data['inventory_item_id'], false );

	if ( 0 === $value ) {
		$value = 1;
	}

	if ( RWEG\Options::$options['wishlist-limit'] ) {
		$product_quantity = rwe_get_product_quantity( RWEG\Data::$data['inventory_item_id'] );
		if ( 0 === $product_quantity ) {
			$product_quantity = 1;
			$value            = 1;
		}
		$max              = $product_quantity;
		$max_helper       = sprintf( esc_html__( ' (Max: %s)', 'rw-elephant-rental-inventory' ), $product_quantity );
	}
	?>

	<div class="rwe-item__quantity-input">
		<div class="form-field">
			<label class="form-label"><?php printf( esc_html__( 'Quantity%s:', 'rw-elephant-rental-inventory' ), $max_helper ); ?></label>
			<input class="text-input" type="number" min="1" max="<?php echo esc_attr( $max ); ?>" value="<?php esc_attr_e( $value ); ?>" data-inventory-id="<?php echo esc_attr( RWEG\Data::$data['inventory_item_id'] ); ?>">
		</div>
	</div>

	<?php

}

/**
 * Retreive the wishlist cookie.
 *
 * @return array Wishlist cookie array.
 */
function rwe_get_wishlist_cookie() {

	return isset( $_COOKIE['rw-elephant-wishlist'] ) ? json_decode( stripslashes( $_COOKIE['rw-elephant-wishlist'] ), true ) : [];

}

/**
 * Retreive the custom wishlist submission fields created by the user.
 *
 * @since 2.0.0
 *
 * @return array Wishlist submission fields keys. eg: 'date-custom', 'text-custom' etc.
 */
function rwe_get_wishlist_custom_fields() {

	$fields = RWEG\Options::$options['wishlist-form-fields'];

	if ( empty( $fields ) ) {

		return [];

	}

	$default_fields = [
		'email_address',
		'first_name',
		'last_name',
		'phone_number',
		'event_date',
	];

	// Unset the default fields, so the custom ones are returned.
	foreach ( $default_fields as $field_name ) {

		if ( ! isset( $fields[ $field_name ] ) ) {

			continue;

		}

		unset( $fields[ $field_name ] );

	}

	if ( empty( $fields ) ) {

		return $fields;

	}

	return $fields;

}

/**
 * Retrieve the product quantity.
 *
 * @param array Product data array, or empty to retreive current product in loop.
 *
 * @return integer Product quantity.
 */
function rwe_get_product_quantity( $product_id = '' ) {

	$quantity = false;

	if ( isset( RWEG\Data::$data['quantity'] ) && ! empty( RWEG\Data::$data['quantity'] ) ) {

		return (int) RWEG\Data::$data['quantity'];

	}

	$product_id = ! empty( $product_id ) ? $product_id : false;

	if ( false !== $product_id ) {

		// API request to retreive product data.
		$api = new RWEG\API( 'item_info', [ 'inventory_item_id' => $product_id ] );

		$item_data = $api->request();

		if ( ! is_wp_error( $item_data ) ) {

			$quantity = ( isset( $item_data[0]['quantity'] ) && ! empty( $item_data[0]['quantity'] ) ) ? $item_data[0]['quantity'] : 0;

		}

	}

	// If quantity still not set, set to 1.
	if ( ! $quantity ) {

		$quantity = 1;

	}

	return $quantity;

}

/**
 * Echo the product quantity.
 *
 * @param  string $product_id Product ID to retreive the quantity for.
 *
 * @return integer            The product quantity.
 */
function rwe_product_quantity( $product_id = '' ) {

	echo rwe_get_product_quantity( $product_id );

}

/**
 * Retrieve the set quantity from the cookie.
 *
 * @param  string|integer $product_id Product ID
 *
 * @return integer        The set product quantity.
 */
function rwe_product_quantity_from_cookie( $product_id = '', $echo = true ) {

	$cookie   = rwe_get_wishlist_cookie();

	if ( empty( $cookie ) ) {

		if ( $echo ) {
			echo 0;
			return;
		} else {
			return 0;
		}

	}

	$cookie_key = array_search( (int) $product_id, wp_list_pluck( $cookie, 'itemID' ), true );

	if ( false === $cookie_key ) {

		if( $echo ) {
			echo 0;
			return;
		} else {
			return 0;
		}

	}

	if ( $echo ) {
		echo $cookie[ $cookie_key ]['quantity'];
		return;
	} else {
		return $cookie[ $cookie_key ]['quantity'];

	}

}

/**
 * Return the product quantity from the set cookie.
 * Note: This assumes a quantity of 1 if no cookie is set or product is not found.
 *
 * @param  string|integer $product_id Product ID
 *
 * @return integer        Product quantity.
 */
function rwe_get_product_quantity_from_cookie( $product_id = '' ) {

	if ( empty( $product_id ) ) {

		return 1;

	}

	ob_start();

	rwe_product_quantity_from_cookie( $product_id );

	$quantity = ob_get_clean();

	return $quantity;

}

/**
 * Enqueue the wishlist scripts and styles.
 *
 * @return null
 */
function rwe_enqueue_wishlist_scripts() {

	$suffix = SCRIPT_DEBUG ? '' : '.min';

	// Date picker scripts
	wp_enqueue_script( 'moment', RW_ELEPHANT_RENTAL_INVENTORY_URL . 'lib/assets/js/moment.min.js', [ 'jquery' ], '2.21.0', true );
	wp_enqueue_script( 'pikaday', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/js/pikaday{$suffix}.js", [ 'moment' ], '1.6.1', true );
	wp_enqueue_script( 'rwe-elephant-wishlist' );

	wp_enqueue_style( 'pikaday', RW_ELEPHANT_RENTAL_INVENTORY_URL . "lib/assets/css/pikaday{$suffix}.css", [ 'rwe-gallery' ], '2.0.0', 'all' );

}

/**
 * Render the Wishlist Action Links
 */
function rwe_wishlist_action_links() {

	return RWEG\Wishlist::action_links();

}

/**
 * Display the error message at the top of the gallery when one occurs
 *
 * @since 2.2.4
 *
 * @return mixed Markup for the error
 */
function rwe_gallery_error() {

	$error_message = ! empty( $_GET['product_error'] ) ? sanitize_text_field( $_GET['product_error'] ) : '';

	if ( ! $error_message ) {

		return;

	}

	?>

	<div class="error">
		<p><?php echo esc_html( $error_message ); ?></p>
	</div>

	<?php

}

/**
 * Render the Gallery Header
 */
function rwe_gallery_header() {

	return RWEG\Gallery::render_gallery_header();

}

/**
 * Render the Wishlist Button
 *
 * @return empty when wishlist is disabled, else wishlist button.
 */
function rwe_wishlist_button() {

	if ( isset( RWEG\Data::$data['inventory_item_id'] ) ) {
		$item_id = RWEG\Data::$data['inventory_item_id'];
	} elseif ( isset( RWEG\Single_Item::$item_data['inventory_item_id'] ) ) {
		$item_id = RWEG\Single_Item::$item_data['inventory_item_id'];
	} else {
		$item_id = false;
	}

	/**
	 * Allow users to disable the output of the 'Add to Wishlist' button.
	 *
	 * @var boolean
	 */
	$show_wishlist_button = (bool) apply_filters( 'rw_elephant_show_wishlist_button', true, $item_id );

	if ( ! RWEG\Options::$options['enable-wishlists'] || ! $show_wishlist_button || ! $item_id ) {

		return;

	}

	rwe_enqueue_wishlist_scripts();

	/**
	 * Allow users to filter the 'Add to Wishlist' button text.
	 *
	 * @var string
	 */
	$wishlist_add_button_text = (string) apply_filters( 'rw_elephant_wishlist_add_button_text', __( 'Add to Wishlist', 'rw-elephant-rental-inventory' ) );

	/**
	 * Allow users to filter the 'Remove from Wishlist' button text.
	 *
	 * @var string
	 */
	$wishlist_remove_button_text = (string) apply_filters( 'rw_elephant_wishlist_remove_button_text', __( 'Remove from Wishlist', 'rw-elephant-rental-inventory' ) );

	/**
	 * Allow users to filter the classes added to the 'Add to Wishlist' button.
	 *
	 * @var array
	 */
	$wishlist_button_class = (array) apply_filters( 'rw_elephant_wishlist_button_class', [ 'rwe-button', 'rwe-button--primary' ] );

	$item_in_wishlist = rwe_is_item_in_wishlist( (int) $item_id );

	$add_or_remove_class  = $item_in_wishlist ? 'remove-from-wishlist' : 'add-to-wishlist';
	$wishlist_button_text = $item_in_wishlist ? $wishlist_remove_button_text : $wishlist_add_button_text;

	$preloader_style = rwe_get_preloader_style();

	if ( ! isset( RWEG\Options::$options['gallery-wishlist-add-number'] ) || 'one' === RWEG\Options::$options['gallery-wishlist-add-number'] ) {
		$quantity = 1;
	} else {
		$quantity = rwe_get_product_quantity( $item_id );
	}

	$preloader = '<div class="rwe-circle-preloader" ' . $preloader_style . '>
		<div class="rwe-circle-preloader1 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader2 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader3 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader4 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader5 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader6 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader7 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader8 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader9 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader10 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader11 rwe-circle-preloader-child"></div>
		<div class="rwe-circle-preloader12 rwe-circle-preloader-child"></div>
	</div>';

	printf(
		'<a href="#" class="js-add-to-wishlist %s %s" data-itemID="%s" data-wishlistQuantity="%s" data-wishlistAddText="%s" data-wishlistRemoveText="%s">%s<span class="text">%s</span></a>',
		esc_attr( $add_or_remove_class ),
		! empty( $wishlist_button_class ) ? implode( ' ', $wishlist_button_class ) : '',
		esc_attr( $item_id ),
		esc_attr( $quantity ),
		esc_html( $wishlist_add_button_text ),
		esc_html( $wishlist_remove_button_text ),
		wp_kses_post( $preloader ),
		wp_kses( $wishlist_button_text, rwe_kses_with_svg() )
	);

}

/**
 * Render the Wishlist Button on the gallery page
 *
 * @return string|empty  The HTML for the wishlist button
 */
function rwe_gallery_wishlist_button() {
	if ( ! is_rwe_gallery() ) {
		return;
	}

	if ( isset( RWEG\Options::$options['enable-wishlist-gallery-add'] ) && '1' === RWEG\Options::$options['enable-wishlist-gallery-add'] ) {
		$position       = RWEG\Options::$options['gallery-wishlist-icon-position'] ?? 'right';
		$position_class = esc_attr( 'rwe-item__actions--icon-' . $position );
		$icon_style     = RWEG\Options::$options['gallery-wishlist-icon-style'] ?? 'plus';
		$icon_class     = esc_attr( ' rwe-item__actions--style-' . $icon_style );

		echo '<div class="rwe-item__actions ' . $position_class . $icon_class . '">';
		rwe_wishlist_button();
		echo '</div>';
	}
}

/**
 * Display the wishlist add icon on the gallery page.
 *
 * @param string $text Button text to filter.
 *
 * @return string $text Wishlist icon
 */
function rwe_gallery_wishlist_add_icon( $text ) {
	if ( ! is_rwe_gallery() || is_rwe_category_item() || '1' !== RWEG\Options::$options['enable-wishlist-gallery-add'] ) {
		return $text;
	}

	$icon_style = RWEG\Options::$options['gallery-wishlist-icon-style'];
	if ( 'plus' === $icon_style ) {
		$svg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zM0 12C0 5.373 5.373 0 12 0s12 5.373 12 12-5.373 12-12 12S0 18.627 0 12z"/><path d="M12 6a1 1 0 0 0-1 1v4H7a1 1 0 1 0 0 2h4v4a1 1 0 1 0 2 0v-4h4a1 1 0 1 0 0-2h-4V7a1 1 0 0 0-1-1z"/></svg>';
	} else {
		$svg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.989 3.766c1.824-2.651 5.256-3.599 8.113-1.955 1.777 1.022 3.437 2.961 3.82 5.762.317 2.32-.407 4.181-.848 5.109-.89 1.871-2.318 3.398-3.194 4.276a37.728 37.728 0 01-2.866 2.56 44.405 44.405 0 01-1.41 1.097l-.003.001-2.991 2.19a1 1 0 01-1.182 0l-2.991-2.19-.002-.001a44.652 44.652 0 01-1.409-1.094 38.26 38.26 0 01-2.865-2.552c-.88-.878-2.298-2.388-3.194-4.229-.44-.905-1.179-2.732-.91-5.03.33-2.802 1.952-4.8 3.764-5.868 2.86-1.684 6.328-.75 8.168 1.924zm7.115-.222c-2.069-1.19-4.687-.372-5.839 1.993-.13.267-.235.542-.316.821a1 1 0 01-1.92 0 5.138 5.138 0 00-.337-.862C9.52 3.145 6.896 2.353 4.836 3.566c-1.331.784-2.543 2.261-2.792 4.378-.206 1.752.356 3.172.721 3.92.743 1.526 1.958 2.84 2.808 3.688a36.292 36.292 0 002.712 2.414 42.92 42.92 0 001.335 1.037v.001l2.399 1.756 2.398-1.756h.001a42.139 42.139 0 001.336-1.039 35.782 35.782 0 002.71-2.42c.847-.848 2.068-2.175 2.803-3.722.365-.766.915-2.21.674-3.979-.29-2.113-1.528-3.547-2.837-4.3z"/></svg>';
	}

	$icon = '<span class="icon">' . $svg . '</span><span class="screen-reader-text">' . __( 'Add to Wishlist', 'rw-elephant-rental-inventory' ) . '</span>';
	return $icon;
}

/**
 * Display the wishlist remove icon on the gallery page.
 *
 * @param string $text Button text to filter.
 *
 * @return string $text Wishlist icon
 */
function rwe_gallery_wishlist_remove_icon( $text ) {
	if ( ! is_rwe_gallery() || is_rwe_category_item() || '1' !== RWEG\Options::$options['enable-wishlist-gallery-add'] ) {
		return $text;
	}

	$icon_style = RWEG\Options::$options['gallery-wishlist-icon-style'];
	if ( 'plus' === $icon_style ) {
		$svg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zM0 12C0 5.373 5.373 0 12 0s12 5.373 12 12-5.373 12-12 12S0 18.627 0 12z"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6 12a1 1 0 011-1h10a1 1 0 110 2H7a1 1 0 01-1-1z"/></svg>';
	} else {
		$svg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.989 3.867c1.807-2.75 5.265-3.693 8.098-2.074 1.759 1.005 3.444 2.935 3.834 5.768.323 2.346-.416 4.224-.86 5.152-.898 1.875-2.335 3.4-3.215 4.275a37.852 37.852 0 01-2.88 2.556 44.21 44.21 0 01-1.418 1.094l-.002.002-3.013 2.19a.865.865 0 01-1.028 0l-3.013-2.19-.002-.002a45.056 45.056 0 01-1.416-1.092A38.426 38.426 0 014.195 17c-.883-.875-2.31-2.384-3.214-4.227-.444-.905-1.196-2.75-.921-5.072.335-2.836 1.983-4.827 3.778-5.876 2.836-1.659 6.33-.73 8.152 2.043z"/></svg>';
	}

	$icon = '<span class="icon">' . $svg . '</span><span class="screen-reader-text">' . __( 'Add to Wishlist', 'rw-elephant-rental-inventory' ) . '</span>';
	return $icon;
}

/**
 * If the wishlist button is an icon, adjust the loader styles
 *
 * @return string|void
 */
function rwe_get_preloader_style() {
	if ( ! is_rwe_gallery() || is_rwe_category_item() || '1' !== RWEG\Options::$options['enable-wishlist-gallery-add'] ) {
		return;
	}

	$color = RWEG\Options::$options['gallery-wishlist-icon-color'];

	return 'style=background:' . $color . ';';
}

/**
 * Check if the specified item is in the wishlist.
 *
 * @param integer  $item_id The item ID to check for in the wishlist.
 *
 * @since 2.0.0
 *
 * @return boolean          True when the item is in the wishlist, else false.
 */
function rwe_is_item_in_wishlist( $item_id = '' ) {

	$rwe_cookie = rwe_get_wishlist_cookie();

	if ( empty( $item_id ) || empty( $rwe_cookie ) ) {

		return false;

	}

	return in_array( (int) $item_id, wp_list_pluck( $rwe_cookie, 'itemID' ), true );

}

/**
 * Determine if cache is disabled.
 *
 * @since 2.1.2
 *
 * @return boolean True when disabled, else false.
 */
function rwe_is_cache_disabled() {

	$options = RWEG\Options::$options;

	if ( WP_DEBUG ) {

		return true;

	}

	return (bool) isset( $options['disable-cache'] ) ? ( 0 === $options['disable-cache'] ) : false;

}

/**
 * Echo the layout class.
 *
 * @return string Layout class. default|reversed
 */
function rwe_layout_class() {

	echo RWEG\Options::$options['item-page-layout'];

}

/**
 * Render the breadcrumbs on the single item template.
 *
 * @return mixed Markup for the breadcrumbs.
 */
function rwe_breadcrumbs() {

	/**
	 * Filter to enable/disable the breadcrumbs.
	 *
	 * @var boolean
	 */
	if ( ! (bool) apply_filters( 'rw_elephant_inventory_breadcrumbs', true ) ) {

		?>

		<nav class="rwe-breadcrumb" role="navigation" aria-label="Breadcrumb"></nav>

		<?php

		return;

	}

	// Get the post object to retrieve the title, so it's not filtered
	$post = get_post( RWEG\Options::$options['gallery-page'] );

	// Setup the item tags
	$item_tags = rwe_get_item_tags();

	// Setup the parent breadcrumb item
	$breadcrumbs = [
		(string) apply_filters( 'rw_elephant_breadcrumb_parent_title', $post->post_title ) => get_the_permalink( RWEG\Options::$options['gallery-page'] ),
	];

	$cats = rwe_get_categories();

	// Gallery breadcrumbs
	if ( is_rwe_gallery() ) {

		global $wp_query;

		if ( isset( $wp_query->query_vars['rw_category'] ) || isset( $_GET['category'] ) ) {

			$category = isset( $wp_query->query_vars['rw_category'] ) ? $wp_query->query_vars['rw_category'] : sanitize_text_field( $_GET['category'] );

			if ( ! is_wp_error( $cats ) ) {

				$cat_titles = wp_list_pluck( $cats, 'inventory_type_name' );
				$cat_slugs  = array_map( 'sanitize_title', $cat_titles );

				if ( in_array( $category, $cat_slugs, true ) ) {

					$key = array_search( $category, $cat_slugs, true );

					if ( false !== $key ) {

						$category = esc_html( $cat_titles[ $key ] );

					}

				} // @codingStandardsIgnoreLine

			}

			$category_url = empty( get_option( 'permalink_structure', '' ) ) ? add_query_arg( 'category', sanitize_title( $category ), get_the_permalink( RWEG\Options::$options['gallery-page'] ) ) : get_the_permalink( RWEG\Options::$options['gallery-page'] ) . sanitize_title( $category );

			$breadcrumbs[ $category ] = strtolower( $category_url );

		}

		$tag = ! empty( $_GET['tag'] ) ? sanitize_text_field( $_GET['tag'] ) : '';

		if ( $tag ) {

			$referer = ( is_ssl() ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

			if ( empty( get_option( 'permalink_structure', '' ) ) ) {

				$args = wp_parse_args( $referer );

				$category = array_key_exists( $tag, $item_tags ) ? sprintf( /* translators: 1. The tag name. eg: Brown */ __( 'Tag: %s', 'rw-elephant-rental-inventory' ), sanitize_text_field( $item_tags[ $tag ] ) ) : false;

				$category_url = add_query_arg( 'tag', $tag, get_the_permalink( RWEG\Options::$options['gallery-page'] ) );

			} else {

				$parse_url = parse_url( $referer );

				if ( isset( $parse_url['query'] ) ) {

					parse_str( $parse_url['query'], $query );

					$tag_id = isset( $query['tag'] ) ? $query['tag'] : false;

					if ( ! empty( $tag_id ) ) {

						if ( array_key_exists( $tag_id, $item_tags ) ) {

							$category     = sprintf( /* translators: 1. The tag name. eg: Brown */ __( 'Tag: %s', 'rw-elephant-rental-inventory' ), esc_html( $item_tags[ $tag_id ] ) );
							$category_url = add_query_arg( 'tag', $tag_id, get_the_permalink( RWEG\Options::$options['gallery-page'] ) );

						}

					}

				} else {

					$split_url = explode( '/', untrailingslashit( $referer ) );
					$category  = end( $split_url );

					if ( ! empty( $category ) ) {

						$category_url = get_the_permalink( RWEG\Options::$options['gallery-page'] ) . $category;

					}

				}

			}

			if ( isset( $category ) && isset( $category_url ) ) {

				$breadcrumbs[ ucwords( $category ) ] = strtolower( $category_url );

			}

		}
	}

	// Single breadcrumbs
	if ( is_rwe_single() ) {

		$item_data = RWEG\Single_Item::$item_data;

		$item_type = $item_data['inventory_type_name'];
		if ( $item_type ) {
			// get the category URL
			foreach ( $cats as $category_data ) {
				if ( $category_data['inventory_type_name'] === $item_type ) {
					$category_url = empty( get_option( 'permalink_structure', '' ) ) ? add_query_arg( 'category', sanitize_title( $category_data['inventory_type_name'] ), get_the_permalink( RWEG\Options::$options['gallery-page'] ) ) : get_the_permalink( RWEG\Options::$options['gallery-page'] ) . sanitize_title( $category_data['inventory_type_name'] );

					$breadcrumbs[ $item_type ] = $category_url;
				}
			}
		}

		// Single item breadcrumb
		$breadcrumbs[ rwe_item_name( false ) ] = '';

	}

	// Wishlist breadcrumbs
	if ( is_rwe_wishlist() ) {

		$breadcrumbs[ get_the_title( RWEG\Options::$options['wishlist-page'] ) ] = '';

	}

	// Search results page
	if ( is_rwe_search() ) {

		$term = ! empty( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		$breadcrumbs[ sprintf( /* translators: 1. The searched term. eg: Chair */ __( 'Search: %s', 'rw-elephant-rental-inventory' ), $term ) ] = '';

	}

	// Wishlist submission page
	if ( is_rwe_wishlist_submission_page() ) {

		$breadcrumbs = [];

		$breadcrumbs[ __( 'Inventory', 'rw-elephant-rental-inventory' ) ]       = get_the_permalink( RWEG\Options::$options['gallery-page'] );
		$breadcrumbs[ __( 'Your Wishlist', 'rw-elephant-rental-inventory' ) ]   = get_the_permalink( RWEG\Options::$options['wishlist-page'] );
		$breadcrumbs[ __( 'Submit Wishlist', 'rw-elephant-rental-inventory' ) ] = add_query_arg( 'action', 'submit-wishlist', get_the_permalink( RWEG\Options::$options['wishlist-page'] ) );

	}

	/**
	 * Filter the breadcrumbs before they are rendered.
	 *
	 * @var array
	 */
	$breadcrumbs = (array) apply_filters( 'rw_elephant_breadcrumbs', $breadcrumbs );

	if ( empty( $breadcrumbs ) ) {

		?>

		<nav class="rwe-breadcrumb" role="navigation" aria-label="<?php esc_attr_e( 'Breadcrumb', 'rw-elephant-rental-inventory' ); ?>"></nav>

		<?php

		return;

	}

	?>
	<nav class="rwe-breadcrumb" role="navigation" aria-label="<?php esc_attr_e( 'Breadcrumb', 'rw-elephant-rental-inventory' ); ?>">
		<ol class="rwe-breadcrumb__list">
			<?php
			$x = 1;
			foreach ( $breadcrumbs as $text => $url ) {
				printf(
					'<li class="rwe-breadcrumb__item">
						<a href="%s" %s>%s</a>
					</li>',
					esc_url( $url ),
					( count( $breadcrumbs ) === $x ) ? 'aria-current="page"' : '',
					esc_html( ucwords( $text ) )
				);
				$x++;
			}
			?>
		</ol>
	</nav>
	<?php

}

/**
 * Generate the Action links.
 * Note: Does not render on wishlist submission page.
 *
 * @return mixed Markup for the action links.
 */
function rwe_actions() {

	if ( is_rwe_wishlist_submission_page() ) {

		return;

	}

	$term = ! empty( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

	?>

	<div class="rwe-actions">

		<?php

		// Search Input
		if ( ! is_rwe_wishlist_submission_page() ) {

			?>

			<form class="rwe-search" role="search" method="" action="" _lpchecked="1">
				<div class="rwe-search__content">
					<label class="rwe-search__label" for="search"><?php esc_html_e( 'Search', 'rw-elephant-rental-inventory' ); ?></label>
					<input class="text-input rwe-search__input" id="search" name="search" type="text" aria-label="search text" placeholder="<?php esc_attr_e( 'Search Inventory…', 'rw-elephant-rental-inventory' ); ?>" value="<?php echo esc_attr( $term ); ?>">
					<?php wp_nonce_field( 'rw_elephant', 'search_inventory' ); ?>
					<input class="rwe-search__button" type="submit" value="Search">
				</div>
			</form>

			<?php

		}

		/**
		 * Header Actions (eg: View Wishlist link)
		 *
		 * @hooked RWEG\Wishlist::action_links - 10
		 */
		do_action( 'rw_elephant_gallery_header_actions' );

		?>

	</div>

	<?php

}

/**
 * Get item notes from the settings page.
 *
 * @return mixed Markup for the item notes.
 */
function rwe_item_notes() {

	/**
	 * Filter the item notes array.
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	$item_notes = apply_filters( 'rw_elephant_item_notes', RWEG\Options::$options['item-notes'], RWEG\Single_Item::$item_data );

	do_action( 'rwe_before_item_notes', RWEG\Single_Item::$item_data );

	foreach ( $item_notes as $note ) {

		if ( empty( $note['title'] ) && empty( $note['text'] ) ) {

			continue;

		}

		?>

		<p class="rwe-data__add-note">
			<span class="rwe-data__label"><?php echo esc_html( $note['title'] ); ?>:</span> <?php echo wp_kses_post( $note['text'] ); ?>
		</p>

		<?php

	}

	do_action( 'rwe_after_item_notes', RWEG\Single_Item::$item_data );

}

/**
 * Display the wishlist error.
 *
 * @return mixed Markup for the wishlist page error.
 */
function wishlist_errors() {

	$error = ! empty( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';

	if ( ! $error ) {

		return;

	}

	?>

	<div class="rwe-wishlist-error">
		<?php echo urldecode( $error ); ?>
	</div>

	<?php

}

/**
 * If the wishlist does not contain additional notes, add a full width class
 *
 * @since 2.0.0
 *
 * @return string Empty if additional notes, else 'full-width'
 */
function wishlist_additional_info_class() {

	$additional_notes = RWEG\Options::$options['wishlist-additional-info'];

	echo ( ! empty( $additional_notes['heading'] ) || ! empty( $additional_notes['text'] ) ) ? '' : 'full-width';

}

/**
 * Get the item details.
 *
 * @since 2.0.0
 *
 * @return mixed Markup for the item details.
 */
function rwe_product_details() {

	$item_details = RWEG\Options::$options['item-details'];

	$helpers = [
		[
			'label'  => __( 'Description', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_item_description',
			'params' => [ false ],
		],
		[
			'label'  => __( 'Price', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_item_rental_price',
			'params' => [ false ],
		],
		[
			'label'  => __( 'Quantity', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_item_quantity',
			'params' => [ false ],
		],
		[
			'label'  => __( 'Dimensions', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_item_dimensions',
			'params' => [ false ],
		],
		[
			'label'  => __( 'Tags', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_item_tags',
			'params' => [ false ],
		],
		[
			'label'  => __( 'Custom ID', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_custom_id',
			'params' => [],
		],
		[
			'label'  => __( 'Custom Field 1', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_custom_field',
			'params' => [
				'number' => 1,
			],
		],
		[
			'label'  => __( 'Custom Field 2', 'rw-elephant-rental-inventory' ),
			'name'   => 'rwe_custom_field',
			'params' => [
				'number' => 2,
			],
		],
	];

	$labels = wp_list_pluck( $helpers, 'label' );

	foreach ( $item_details as $label => $details ) {

		$index  = array_search( $label, $labels, true );
		$method = is_array( $helpers[ $index ] ) ? $helpers[ $index ]['name'] : $helpers[ $index ];

		if ( ! isset( $details['show'] ) || ! $details['show'] || ! is_callable( $method ) ) {

			continue;

		}

		$parameters = isset( $helpers[ $index ]['params'] ) ? $helpers[ $index ]['params'] : [];

		$field_data = call_user_func_array( $method, $parameters );

		if ( empty( $field_data ) ) {

			continue;

		}

		$action = sanitize_title( $label );

		do_action( "rw_elephant_before_item_detail_{$action}" );

		// Dictates the element wrap for each item detail.
		$wrap = ( 'rwe_item_description' === $method ) ? 'div' : 'p';

		?>

		<<?php echo esc_attr( $wrap ); ?> class="rwe-data__<?php echo sanitize_title( $label ); ?>">
		<?php do_action( "rw_elephant_item_detail_{$action}_top" ); ?>
		<span class="rwe-data__label">
				<?php echo ! empty( $details['title'] ) ? esc_html( $details['title'] ) : esc_html( $label ); ?>:
			</span>
		<?php echo $field_data; // xss ok. ?>
		<?php do_action( "rw_elephant_item_detail_{$action}_bottom" ); ?>
		</<?php echo esc_attr( $wrap ); ?>>

		<?php

		do_action( "rw_elephant_after_item_detail_{$action}" );

	}

	rwe_item_notes();

}

/**
 * Flush cached API data.
 * Pass in transient name to flush, or leave empty to fush all data.
 *
 * @since 2.0.0
 *
 * @param string Cache name to bust.
 *
 * @return null
 */
function rwe_flush_cache( $transient_name = '' ) {

	$query_transient = empty( $transient_name ) ? 'rw-elephant' : "rw-elephant-{$transient_name}";

	global $wpdb;

	$query_transient_results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * from `{$wpdb->prefix}options` WHERE option_name LIKE %s;",
			'%' . $wpdb->esc_like( $query_transient ) . '%'
		)
	);

	if ( ! $query_transient_results && ! empty( $query_transient_results ) ) {

		return;

	}

	foreach ( $query_transient_results as $transient ) {

		delete_transient( str_replace( '_transient_', '', $transient->option_name ) );

	}

}

/**
 * Check if transients exist at all.
 *
 * @since 2.0.0
 *
 * @return boolean True when cached data exists, else false.
 */
function rwe_do_transients_exist() {

	global $wpdb;

	$query_transient_results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * from `{$wpdb->prefix}options` WHERE option_name LIKE %s AND option_name != %s;",
			'%' . $wpdb->esc_like( 'rw-elephant' ) . '%',
			'rw-elephant-rental-inventory'
		)
	);

	return count( $query_transient_results ) > 0;

}

/**
 * Generate markup for the image preview and slider.
 * Note: Uses the 1200_link image sizes for the slider
 *       and thumbnail_link image sizes for the thumbnail previews
 *
 * @since 2.0.0
 *
 * @return mixed Markup for the image slider.
 */
function rwe_image_slider() {

	$images = isset( RWEG\Single_Item::$item_data['image_links'] ) ? RWEG\Single_Item::$item_data['image_links'] : ( isset( RWEG\Data::$data['image_links'] ) ? RWEG\Data::$data['image_links'] : [] );

	if ( empty( $images ) ) {

		return;

	}

	$x = 0;

	foreach ( $images as $image ) {

		if ( isset( $image['photo_link'] ) && isset( $image['plugin_250px_thumbnail_link'] ) ) {

			$x++;

			continue;

		}

		unset( $images[ $x ] );

		$x++;

	}

	$large_images       = wp_list_pluck( $images, 'photo_link' );
	$six_hundred_images = wp_list_pluck( $images, '600_link' );
	$thumbnails         = wp_list_pluck( $images, 'plugin_320px_thumbnail_link' );
	$alt_texts          = wp_list_pluck( $images, 'alternate_text' );
	$is_video           = wp_list_pluck( $images, 'is_video' );
	$photo_hashes       = wp_list_pluck( $images, 'photo_hash' );
	$video_exts         = wp_list_pluck( $images, 'extension' );
	?>
	<div class="rwe-single__gallery">

		<!-- Preloader -->
		<div class="rwe-circle-preloader">
			<div class="rwe-circle-preloader1 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader2 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader3 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader4 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader5 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader6 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader7 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader8 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader9 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader10 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader11 rwe-circle-preloader-child"></div>
			<div class="rwe-circle-preloader12 rwe-circle-preloader-child"></div>
		</div>

		<div class="rwe-gallery" style="opacity: 0; height: 0;">
			<div class="rwe-gallery__for slider">
				<?php
				if ( ! empty( $six_hundred_images ) ) {
					$x = 0;
					foreach ( $six_hundred_images as $image_url ) {
						$lightbox_image = isset( $large_images[ $x ] ) ? $large_images[ $x ] : $image_url;
						$alternate_text = isset( $alt_texts[ $x ] ) ? 'alt="' . htmlentities( $alt_texts[ $x ] ) . '"' : '';
						$photo_hash     = isset( $photo_hashes[ $x ] ) ? $photo_hashes[ $x ] : false;
						$video_ext      = isset( $video_exts[ $x ] ) ? $video_exts[ $x ] : false;

						if ( '1' === $is_video[ $x ] && $photo_hash ) {
							?>
							<div class="rwe-gallery__item">
								<video preload="metadata" muted>
									<?php
									printf(
										'<source src="https://ik.imagekit.io/rwelephant/video/%s_video_%s.%s?tr=f-mp4#t=0.001" type="video/mp4" />',
										esc_attr( strtolower( RWEG\Options::$options['tenant-id'] ) ),
										esc_attr( $photo_hash ),
										esc_attr( $video_ext )
									);
									?>
								</video>
								<div class="controls">
									<div class="play_pause">
										<span class="play"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12zM9.474 8.15a1 1 0 01.973-.044l6 3a1 1 0 010 1.788l-6 3A1 1 0 019 15V9a1 1 0 01.474-.85z"></path></svg></span>
										<span class="pause" style="display: none;"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12zM9 8a1 1 0 011 1v6a1 1 0 11-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v6a1 1 0 11-2 0V9a1 1 0 011-1z"></path></svg></span>
									</div>
								</div>
							</div>',

							);
							<?php
						} else {
							printf(
								'<div class="rwe-gallery__item">
									<a href="%1$s" target="_blank">
										<img src="%2$s" %3$s />
									</a>
								</div>',
								esc_url( $lightbox_image ),
								esc_url( $image_url ),
								$alternate_text
							);
						}
						$x++;
					}
				}
				?>
			</div>

			<nav class="rwe-gallery__nav slider">
				<?php
				if ( ! empty( $thumbnails ) ) {
					$y = 0;
					foreach ( $thumbnails as $thumb_url ) {
						printf(
							'<div class="rwe-gallery__thumb"><img src="%s" /></div>',
							esc_url( $thumb_url )
						);
						$y++;
					}
				}
				?>
			</nav>
		</div>

	</div>

	<?php

}

/**
 * Render the single product content.
 *
 * @return mixed Markup for the single product content.
 */
function rwe_product_content() {

	?>

	<div class="rwe-single__data">
		<div class="rwe-data">

			<?php
			/**
			 * RW Elephant Single Product Data
			 *
			 * @hooked rwe_product_title - 10
			 * @hooked rwe_product_details - 15
			 * @hooked rwe_product_wishlist_button - 20
			 * @hooked rwe_contact_links - 25
			 */
			do_action( 'rw_elephant_product_data' );
			?>

		</div>
	</div>

	<?php

}

/**
 * Single product title - including markup.
 *
 * @return mixed Markup for the single product title, wrapped in <h2> tags.
 */
function rwe_product_title() {

	printf(
		'<h2 class="rwe-data__title">%s</h2>',
		esc_html( rwe_item_name( false ) )
	);

}

/**
 * Single product wishlist button - including markup.
 *
 * @return mixed Markup for the single product wishlist button.
 */
function rwe_product_wishlist_button() {

	ob_start();
	rwe_wishlist_button();
	$button = ob_get_contents();
	ob_get_clean();

	printf(
		'<div class="rwe-data__actions">
			%s
		</div>',
		$button
	);

}

/**
 * Generate the actions links on the single item page.
 * ie: Pinterest and Contact links.
 *
 * @return mixed Markup for the action links.
 */
function rwe_contact_links() {

	$id = isset( RWEG\Single_Item::$item_data['inventory_item_id'] ) ? sanitize_title( RWEG\Single_Item::$item_data['inventory_item_id'] ) : ( isset( RWEG\Data::$data['inventory_item_id'] ) ? RWEG\Data::$data['inventory_item_id'] : '' );

	$page_obj = get_post( RWEG\Options::$options['gallery-page'] );
	$gallery  = is_object( $page_obj ) ? $page_obj->post_name : 'gallery';

	$action_links = (array) apply_filters(
		'rw_elephant_action_links',
		[
			'pinterest' => [
				'icon'  => '<span class="rwe-icon rwe-icon--pinterest"><svg viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"><path d="M11 0C4.927 0 0 4.925 0 11c0 4.504 2.708 8.373 6.585 10.076-.032-.769-.006-1.692.19-2.528.212-.89 1.416-5.992 1.416-5.992s-.352-.702-.352-1.742c0-1.63.947-2.847 2.122-2.847 1.002 0 1.483.751 1.483 1.652 0 1.006-.64 2.51-.97 3.904-.276 1.166.585 2.118 1.736 2.118 2.084 0 3.487-2.677 3.487-5.849 0-2.41-1.623-4.215-4.575-4.215-3.338 0-5.417 2.488-5.417 5.267 0 .96.284 1.635.726 2.158.202.242.23.338.157.615-.053.201-.173.69-.224.882-.071.278-.3.378-.55.275-1.537-.627-2.252-2.31-2.252-4.203 0-3.125 2.634-6.872 7.86-6.872 4.2 0 6.965 3.04 6.965 6.301 0 4.316-2.4 7.541-5.937 7.541-1.186 0-2.303-.644-2.686-1.373 0 0-.64 2.535-.774 3.026-.232.848-.69 1.695-1.107 2.356.99.291 2.035.45 3.118.45C17.076 22 22 17.075 22 11S17.076 0 11 0" fill-rule="evenodd"/></svg></span>',
				'title' => __( 'Pin Item', 'rw-elephant-rental-inventory' ),
				'text'  => __( 'Pin Item', 'rw-elephant-rental-inventory' ),
				'link'  => add_query_arg(
					[
						'url'         => sprintf( site_url() . '/%1$s/%2$s/%3$s', $gallery, RWEG\Plugin::$item_slug, $id ),
						'media'       => rwe_get_image_url( 'medium' ),
						'description' => rwe_item_description( false ),
					],
					'https://www.pinterest.com/pin/create/button/'
				),
			],
			'contact'   => [
				'icon'  => '<span class="rwe-icon rwe-icon--contact"><svg viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"><path d="M17.046 14.257l-3.86-3.861 3.86-3.063v6.924zm-11.65.627l3.891-3.89 1.537 1.219.03.033.006-.005.003.002.012-.016 1.634-1.295 3.953 3.952H5.396zm-.493-7.37l3.71 2.943-3.71 3.71V7.514zm11.22-.556l-5.263 4.175L5.597 6.96h10.526zM11 0C4.925 0 0 4.925 0 11c0 6.076 4.926 11 11 11 6.076 0 11-4.924 11-11 0-6.075-4.924-11-11-11z" fill-rule="evenodd"/></svg></span>',
				'title' => __( 'Have questions? Get in contact with us.', 'rw-elephant-rental-inventory' ),
				'text'  => RWEG\Options::$options['contact-title'],
				'link'  => sprintf( 'mailto:%s', RWEG\Options::$options['contact-email'] ),
			],
		]
	);

	// Remove pinterest button if disabled on settings page
	if ( ! RWEG\Options::$options['pinterest-button'] ) {

		unset( $action_links['pinterest'] );

	}

	// Remove contact button if disabled on settings page
	if ( ! RWEG\Options::$options['contact-button'] || empty( RWEG\Options::$options['contact-email'] ) ) {

		unset( $action_links['contact'] );

	}

	if ( empty( $action_links ) ) {

		return;

	}

	print( '<ul class="rwe-data__links">' );

	$x = 0;

	foreach ( $action_links as $action_data ) {

		printf(
			'<li class="rwe-data__link-item">
				<a href="%1$s" title="%2$s" class="rwe-data__link" %3$s>%4$s %5$s</a>
			</li>',
			esc_url( $action_data['link'] ),
			esc_attr( $action_data['title'] ),
			( 0 === $x ) ? 'target="_blank"' : '',
			$action_data['icon'], // xss ok.
			esc_html( $action_data['text'] )
		);

		$x++;

	}

	print( '</ul>' );

}

/**
 * Print out the inline gallery styles on the frontend.
 *
 * @param string $dependency The stylesheet to load these styles after.
 * @since 2.0.0
 *
 * @return mixed
 */
function rwe_gallery_option_styles( $dependency = '' ) {

	$styles                                = RWEG\Options::$options['gallery-styles'];
	$styles['gallery-wishlist-icon-color'] = isset( RWEG\Options::$options['gallery-wishlist-icon-color'] ) ? RWEG\Options::$options['gallery-wishlist-icon-color'] : '#000000';
	$styles['gallery-wishlist-icon-hover'] = isset( RWEG\Options::$options['gallery-wishlist-icon-hover'] ) ? RWEG\Options::$options['gallery-wishlist-icon-hover'] : '#888888';

	$final_styles = [];

	foreach ( $styles as $element => $color ) {
		switch ( $element ) {

			case 'primary-button':
				$elem  = '.rwe-button--primary, a.rwe-button--primary, input[type="submit"].rwe-button--primary, input[type="submit"].rwe-button--primary:disabled:hover';
				$style = 'background-color';
				break;

			case 'primary-button-hover':
				$elem  = '.rwe-button--primary:hover, a.rwe-button--primary:hover, input[type="submit"].rwe-button--primary:hover, .rwe-button--primary:focus, a.rwe-button--primary:focus, input[type="submit"].rwe-button--primary:focus,  .rwe-button--primary:active, a.rwe-button--primary:active, input[type="submit"].rwe-button--primary:active';
				$style = 'background-color';
				break;

			case 'primary-button-text':
				$elem  = '.rwe-button--primary, a.rwe-button--primary, input[type="submit"].rwe-button--primary,
				.rwe-button--primary:hover, a.rwe-button--primary:hover, input[type="submit"].rwe-button--primary:hover,
				.rwe-button--primary:focus, a.rwe-button--primary:focus, input[type="submit"].rwe-button--primary:focus';
				$style = 'color';
				break;

			case 'secondary-button':
				$elem  = '.rwe-button--secondary, a.rwe-button--secondary, input[type="submit"].rwe-button--secondary, input[type="submit"].rwe-button--secondary:disabled:hover';
				$style = 'background-color';
				break;

			case 'secondary-button-hover':
				$elem  = '.rwe-button--secondary:hover, a.rwe-button--secondary:hover, input[type="submit"].rwe-button--secondary:hover, .rwe-button--secondary:focus, a.rwe-button--secondary:focus, input[type="submit"].rwe-button--secondary:focus, .rwe-button--secondary:active, a.rwe-button--secondary:active, input[type="submit"].rwe-button--secondary:active';
				$style = 'background-color';
				break;

			case 'secondary-button-text':
				$elem  = '.rwe-button--secondary, a.rwe-button--secondary, input[type="submit"].rwe-button--secondary,
				.rwe-button--secondary:hover, a.rwe-button--secondary:hover, input[type="submit"].rwe-button--secondary:hover,
				.rwe-button--secondary:focus, a.rwe-button--secondary:focus, input[type="submit"].rwe-button--secondary:focus';
				$style = 'color';
				break;

			case 'border-color':
				$elem  = '.rwe-inventory__header, .rwe-search__input';
				$style = 'border-color';
				break;

			case 'gallery-wishlist-icon-color':
				$elem  = '.rwe-item__actions .icon svg';
				$style = 'fill';
				break;

			case 'gallery-wishlist-icon-hover':
				$elem  = '.rwe-item__actions .icon:hover svg';
				$style = 'fill';
				break;

			default:
				break;

		}

		$final_styles[ $elem ][] = $style . ': ' . $color . '!important' . ';';

	}

	$styles_string = '';

	foreach ( $final_styles as $element => $styles ) {

		$styles = ! is_array( $styles ) ? $styles : implode( ' ', $styles );

		$styles_string .= $element . '{ ' . $styles . ' }';

	}

	global $wp_scripts;

	wp_add_inline_style( $dependency, $styles_string );

}

/**
 * Convert a JSONP response to JSON, decode it and return an array
 * Note: The JSONP callback should be 'data'
 *
 * @param  string $jsonp API response JSONP string.
 *
 * @since 2.0.0
 *
 * @return array API response converted to array.
 */
function rwe_jsonp_decode( $jsonp ) {

	$jsonp = rtrim( str_replace( 'data(', '', $jsonp ), ')' );

	return json_decode( $jsonp, true );

}

/**
 * Add SVG tags to the allowed HTML tags in for wp_kses
 *
 * @return array|bool[][]
 */
function rwe_kses_with_svg() {
	$kses_defaults = wp_kses_allowed_html( 'post' );

	$svg_args = array(
		'svg'   => array(
			'class'           => true,
			'aria-hidden'     => true,
			'aria-labelledby' => true,
			'role'            => true,
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
			'viewbox'         => true,
		),
		'g'     => array( 'fill' => true ),
		'title' => array( 'title' => true ),
		'path'  => array(
			'd'    => true,
			'fill' => true,
		),
		'span'  => array( 'class' => true ),
	);
	return array_merge( $kses_defaults, $svg_args );
}
