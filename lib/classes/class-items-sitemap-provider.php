<?php
/**
 * RW Elephant Items Sitemap Class
 *
 * @author R.W. Elephant <support@rwelephant.com>
 *
 * @since 2.3.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Items_Sitemap_Provider extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name = 'items';
	}

	public function get_url_list( $page_num, $post_type = '' ) {
		$gallery_slug = Sitemaps::get_gallery_slug();
		if ( ! $gallery_slug ) {
			return;
		}

		$api   = new API( 'list_all_items' );
		$items = $api->request();

		$links = array();
		foreach ( $items as $item ) {
			$links[] = array(
				'loc' => rwe_get_url( sanitize_title( $item['name'] ) . '-' . $item['inventory_item_id'], true ) . '/',
			);
		}

		return $links;
	}

	public function get_max_num_pages( $subtype = '' ) {
		return 1;
	}

}
