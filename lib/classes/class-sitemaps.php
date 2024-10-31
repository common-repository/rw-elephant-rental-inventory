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

class Sitemaps {

	private $types_sitemap;
	private $items_sitemap;

	public function __construct() {

		$this->types_sitemap = new Types_Sitemap_Provider();
		$this->items_sitemap = new Items_Sitemap_Provider();

		// Register WordPress sitemaps
		add_action(
			'init',
			function() {
				wp_register_sitemap_provider( 'types', $this->types_sitemap );
			}
		);

		add_action(
			'init',
			function() {
				wp_register_sitemap_provider( 'items', $this->items_sitemap );
			}
		);

		// Register Yoast sitemaps
		if ( class_exists( 'WPSEO_Options' ) ) {
			add_filter( 'wpseo_sitemap_index', array( $this, 'rwe_types_sitemap_index' ) );
			add_filter( 'wpseo_sitemap_index', array( $this, 'rwe_items_sitemap_index' ) );
			add_action( 'init', array( $this, 'rwe_yoast_sitemap_register' ) );
		}

		// Register All in One SEO sitemaps
		if ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
			add_action( 'init', array( $this, 'generate_custom_sitemaps' ) );
			add_filter( 'aioseo_sitemap_indexes', array( $this, 'rwe_aioseo_add_sitemap_index' ) );
		}

	}


	/**
	 * Add the Types sitemap to the Yoast sitemap index page.
	 *
	 * @param $sitemap_index
	 *
	 * @return string
	 */
	public function rwe_types_sitemap_index( $sitemap_index ) {
		return $this->rwe_generate_yoast_sitemap_index( 'types', $sitemap_index );
	}

	/**
	 * Add the Items sitemap to the Yoast sitemap index page.
	 *
	 * @param $sitemap_index
	 *
	 * @return string
	 */
	public function rwe_items_sitemap_index( $sitemap_index ) {
		return $this->rwe_generate_yoast_sitemap_index( 'items', $sitemap_index );
	}

	/**
	 * Generate a Yoast sitemap index entry
	 *
	 * @param $slug
	 * @param $sitemap_index
	 *
	 * @return string
	 */
	public function rwe_generate_yoast_sitemap_index( $slug, $sitemap_index ) {
		$sitemap_url  = home_url( "rwe_{$slug}-sitemap.xml" );
		$sitemap_date = gmdate( DATE_W3C );  # Current date and time in sitemap format.

		$custom_sitemap = <<<SITEMAP_INDEX_ENTRY
<sitemap>
    <loc>%s</loc>
    <lastmod>%s</lastmod>
</sitemap>
SITEMAP_INDEX_ENTRY;
		$sitemap_index .= sprintf( $custom_sitemap, $sitemap_url, $sitemap_date );

		return $sitemap_index;
	}

	/**
	 * Register sitemaps with Yoast
	 */
	public function rwe_yoast_sitemap_register() {
		global $wpseo_sitemaps;
		if ( isset( $wpseo_sitemaps ) && ! empty( $wpseo_sitemaps ) ) {
			$wpseo_sitemaps->register_sitemap( 'rwe_types', array( $this, 'rwe_types_sitemap_generate' ) );
			$wpseo_sitemaps->register_sitemap( 'rwe_items', array( $this, 'rwe_items_sitemap_generate' ) );
		}
	}


	/**
	 * Generate the types sitemap for Yoast
	 */
	public function rwe_types_sitemap_generate() {
		$data = $this->types_sitemap->get_url_list( 1 );
		$this->rwe_yoast_sitemap_generate( $data );
	}

	/**
	 * Generate the items sitemap for Yoast
	 */
	public function rwe_items_sitemap_generate() {
		$data = $this->items_sitemap->get_url_list( 1 );
		$this->rwe_yoast_sitemap_generate( $data );
	}

	/**
	 * Create the XML for a Yoast sitemap
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function rwe_yoast_sitemap_generate( $data ) {
		global $wpseo_sitemaps;

		$urls = array();
		foreach ( $data as $item ) {
			$urls[] = $wpseo_sitemaps->renderer->sitemap_url(
				array(
					'loc' => $item['loc'],
				)
			);
		}
		$sitemap_body = <<<SITEMAP_BODY
<urlset
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd"
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
%s
</urlset>
SITEMAP_BODY;
		$sitemap      = sprintf( $sitemap_body, implode( "\n", $urls ) );
		$wpseo_sitemaps->set_sitemap( $sitemap );
	}

	/**
	 * Add sitemaps to AIOSEO's list of sitemaps
	 *
	 * @param $indexes
	 *
	 * @return array
	 */
	public function rwe_aioseo_add_sitemap_index( $indexes ) {
		$indexes[] = array(
			'loc'     => home_url() . '/types-sitemap.xml',
			'lastmod' => aioseo()->helpers->dateTimeToIso8601( gmdate( 'Y-m-d G:i' ) ),
		);
		$indexes[] = array(
			'loc'     => home_url() . '/items-sitemap.xml',
			'lastmod' => aioseo()->helpers->dateTimeToIso8601( gmdate( 'Y-m-d G:i' ) ),
		);
		return $indexes;
	}

	/**
	 * For each content type, generate a sitemap
	 */
	public function generate_custom_sitemaps() {
		$this->generate_sitemap( $this->types_sitemap->get_url_list( 1 ), 'types-sitemap.xml' );
		$this->generate_sitemap( $this->items_sitemap->get_url_list( 1 ), 'items-sitemap.xml' );
	}

	/**
	 * Generate sitemaps to add to AIOSEO's sitemap page
	 *
	 * @param array $data Array of URLs to add to the sitemap
	 * @param string $filename Name of the file to generate
	 *
	 * @return void
	 */
	public function generate_sitemap( $data, $filename ) {
		$sitemap  = '<?xml version="1.0" encoding="UTF-8"?>';
		$sitemap .= "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $data as $url ) {
			$sitemap .= "\t" . '<url>' . "\n" .
						"\t\t" . '<loc>' . $url['loc'] . '</loc>' .
						"\n\t" . '</url>' . "\n";
		}
		$sitemap .= '</urlset>';

		$fp = fopen( ABSPATH . $filename, 'w' );
		fwrite( $fp, $sitemap );
		fclose( $fp );
	}

	/**
	 * Get the slug for the gallery page
	 *
	 * @return string
	 */
	public static function get_gallery_slug() {
		$gallery_page = get_post( Options::$options['gallery-page'] );
		return $gallery_page->post_name;
	}
}
