<?php namespace Yott\WP;

class ShortcodeController {
	protected static $data = [];

	protected static $json = [];
	protected static $shortcodes = [];

	/**
	 * Shortcodes only transform data, so we don't need an instance
	 */
	private function __construct() {}

	/**
	 * Any needed logic for bootstrapping shortcode should go here
	 */
	public static function init() {
		\add_action( 'init', [__CLASS__, 'register_shortcodes'] );
	}

	/**
	 * Parses and registers any shortcodes
	 * @throws Exception
	 */
	public static function register_shortcodes() {
		self::$json[] = \get_stylesheet_directory() . '/shortcodes/';
		self::$json = \apply_filters( 'yott\wp\shortcode\json', self::$json );
		if ( !is_array( self::$json ) ) {
			throw new Exception( __CLASS__ . ' expects an array of JSON files or directories.' );
		}
		self::register_json( self::$json );
	}

	/**
	 * @param $json
	 * @throws \Exception
	 *  Class parameter of JSON object must exist
	 */
	public static function register_json( $json ) {
		if ( is_dir( $json ) ) {
			$json = glob( $json . '/*.json' );
			self::register_json( $json );
		} else {
			$json = json_decode( $json );
			if ( !is_array( $json ) ) {
				$json = [$json];
			}
			foreach ( $json as $shortcode ) {
				$class = $shortcode->class ? $shortcode->class : __NAMESPACE__ . '\\Shortcode';
				if ( !class_exists( $class ) ) {
					throw new \Exception( 'Invalid class type for Shortcode: ' . $class );
				}
				self::register_shortcode( $class( $shortcode ) );
			}
		}
	}

	/**
	 * @param Shortcode
	 *  Shortcode to register
	 */
	public static function register_shortcode( Shortcode $shortcode ) {
		self::$shortcodes[] = $shortcode;
	}
}
