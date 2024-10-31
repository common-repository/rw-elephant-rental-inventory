<?php
/**
* RW Elephant Types Sitemap Class
*
* @author R.W. Elephant <support@rwelephant.com>
*
* @since 2.3.0
*/

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Types_Sitemap_Provider extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name = 'types';
	}

	public function get_url_list( $page_num, $post_type = '' ) {
		$gallery_slug = Sitemaps::get_gallery_slug();
		if ( ! $gallery_slug ) {
			return;
		}

		$api        = new API( 'list_inventory_types' );
		$categories = $api->request();

		$links = array();
		foreach ( $categories as $category ) {
			$links[] = array(
				'loc' => home_url() . '/' . $gallery_slug . '/' . sanitize_title( $category['inventory_type_name'] ) . '/',
			);
		}
		return $links;
	}

	public function get_max_num_pages( $subtype = '' ) {
		return 1;
	}


}
