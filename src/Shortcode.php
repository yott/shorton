<?php namespace Yott\WP;

use \Timber\Timber;

class Shortcode {
	/**
	 * The machine name of this shortcode
	 * @var string
	 */
	protected $shortcode;
	/**
	 * The human name of this shortcode
	 * @var string
	 */
	protected $name;
	/**
	 * Whether to show the shortcode in an admin UI
	 * @var bool
	 */
	protected $show_ui;
	/**
	 * The twig template of this shortcode
	 * @var string
	 */
	protected $template;
	/**
	 * Data to be passed to twig for rendering
	 * This should specify any known defaults
	 * @var array
	 */
	protected $data = [];

	public function __construct( $shortcode ) {
		$props = (array) $shortcode;
		foreach ( $props as $prop => $val ) {
			if ( property_exists( $this, $prop ) ) {
				$this->$prop = $val;
			}
		}

		\add_shortcode( $this->shortcode, [$this, 'do_shortcode'] );
		if ( $this->show_ui ) {
			\add_action( 'admin_enqueue_scripts', function () {
				\wp_enqueue_script('shortcode-editor', \plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/shortcode.js', ['jquery']);
			});
			$this->addToEditor();
		}
	}

	/**
	 * Parse shortcode attributes and render.
	 *
	 * @param array $atts
	 *  Shortcode attributes entered by user
	 * @param string $content
	 *  Additional HTML content entered between the shortcode tags by the user
	 * @return string
	 *  Rendered HTML of the shortcode
	 */
	public function do_shortcode( $atts = [], $content = null ) {
		$before = \apply_filters( 'shortcode_before', '', $this->shortcode );
		$this->data = [];
		if ( !empty( $atts) ) {
			foreach ( $atts as $att => $val ) {
				if ( strpos( $att, '_' ) ) {
					$definition = explode( '_', $att );
					$key = array_shift($definition);
					$this->data[$key] = $this->hydrate($val, $definition, @$this->data[$key]);
				} else {
					$this->data[$att] = $val;
				}
			}
		}
		$this->prepare();
		$this->data['content'] = \do_shortcode( $content );
		$after = \apply_filters( 'shortcode_after', '', $this->shortcode );

		\do_action( "shorton\\shortcode", $this );
		\do_action( "shorton\\shortcode\\{$this->shortcode}", $this );

		return $before . $this->render() . $after;
	}

	/**
	 * Logic for rendering this shortcode
	 *
	 * Note that we return the output of this shortcode. This is a requirement
	 * by WordPress based on how do_shortcode works.
	 * @return string
	 *  Compiled shortcode
	 */
	public function render( $atts = array(), $content = null ) {
		$template = $this->shortcode . '.twig';
		if ( $this->template !== null ) {
			$template = $this->template;
		}
		$template = \apply_filters( 'shortcode_template', $template, $this->shortcode );
		if ( class_exists( '\\Timber\\Timber' ) ) {
			return Timber::compile( $template, $this->data );
		} else {
			include_once $template;
		}
	}

	/**
	 * Populates an object structure from a shortcode attribute
	 *
	 * Transforms shortcode attributes into object properties.
	 * For example, $atts['foo.bar.baz'] = 'Hello World' should be transformed to
	 * $atts['foo']->bar->baz = 'Hello World'
	 * @param mixed $val
	 *  Value to set at the lowest property
	 * @param array $definition
	 *  Hierarchy of object property names
	 * @param object $struct
	 *  (Optional) Existing object to add to
	 * @return object
	 *  Hydrated object structure
	 */
	protected function hydrate( $val, $definition, $struct = null ) {
		$name = array_shift( $definition );
		$struct = (object) $struct;
		if ( count( $definition ) === 0 ) {
			$struct->$name = $val;
			return $struct;
		}
		if ( $struct === null ) {
			$struct = $object = new \stdClass();
		} elseif ( property_exists( $struct, $name ) ) {
			$object = $struct->$name;
		} else {
			$object = $struct->$name = new \stdClass();
		}
		$struct->$name = self::hydrate( $val, $definition, $object );
		return $struct;
	}

	/**
	 * Prepare data passed to shortcode for twig rendering
	 *
	 * This function allows children to modify data before it is sent
	 * to Twig for rendering. A possible change would be making this a hook
	 * so a single controller could modify data as needed and we have less classes.
	 * @return Shortcode
	 *  Shortcode object for chaining
	 */
	protected function prepare() {
		return;
	}

	protected function addToEditor() {
		\add_action( 'media_buttons_context', [ $this, 'media_buttons_context' ] );
		\add_action( 'admin_footer', [ $this, 'admin_footer' ] );
	}

	public function media_buttons_context( $context ) {
		return $context . __('<button type="button" class="button"'.' id="'.$this->shortcode.'_button" title="Add '.$this->name.'">Add '.$this->name.'</button>');
	}

	public function admin_footer() {
		$shortcode = new \stdClass();
		$shortcode->shortcode = $this->shortcode;
		$shortcode->name = $this->name;
		if ( !empty( $this->data ) ) {
			$shortcode->data = $this->data;
		}
		$shortcode = json_encode( $shortcode );
		echo '<script type="text/javascript">var shortcode = '.$shortcode.'; Yott.Shortcode.Editor.addHandler(shortcode);</script>';
	}
}
