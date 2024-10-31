<?php
/**
 * Data storage class
 *
 * @author R.W. Elephant <info@rwelephant.com>
 *
 * @since 1.0.0
 */

namespace RWEG;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Data {

	public static $data;

	public function __construct( $data ) {

		self::$data = $data;

	}

}
