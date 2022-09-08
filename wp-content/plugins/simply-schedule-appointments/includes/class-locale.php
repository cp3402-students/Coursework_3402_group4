<?php
/**
 * Simply Schedule Appointments Locales.
 *
 * @since   3.1.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Locales.
 *
 * @since 3.1.0
 */
class SSA_Locale {
	public $english_name;
	public $native_name;
	public $text_direction = 'ltr';
	public $lang_code_iso_639_1 = null;
	public $lang_code_iso_639_2 = null;
	public $lang_code_iso_639_3 = null;
	public $country_code;
	public $wp_locale;
	public $slug;
	public $nplurals = 2;
	public $plural_expression = 'n != 1';
	public $google_code = null;
	public $preferred_sans_serif_font_family = null;
	public $facebook_locale = null;
	// TODO: days, months, decimals, quotes

	private $_index_for_number;

	public function __construct( $args = array() ) {
		foreach( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	public static function __set_state( $state ) {
		return new SSA_Locale( $state );
	}

	/**
	 * Make deprecated properties checkable for backwards compatibility.
	 *
	 * @param string $name Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		if ( 'rtl' == $name ) {
			return isset( $this->text_direction );
		}
	}

	/**
	 * Make deprecated properties readable for backwards compatibility.
	 *
	 * @param string $name Property to get.
	 * @return mixed Property.
	 */
	public function __get( $name ) {
		if ( 'rtl' == $name ) {
			return ( 'rtl' === $this->text_direction );
		}
	}

	public function combined_name() {
		/* translators: combined name for locales: 1: name in English, 2: native name */
		return sprintf( _x( '%1$s/%2$s', 'locales' ), $this->english_name, $this->native_name );
	}

	public function numbers_for_index( $index, $how_many = 3, $test_up_to = 1000 ) {
		$numbers = array();

		for( $number = 0; $number < $test_up_to; ++$number ) {
			if ( $this->index_for_number( $number ) == $index ) {
				$numbers[] = $number;

				if ( count( $numbers ) >= $how_many ) {
					break;
				}
			}
		}

		return $numbers;
	}

	public function index_for_number( $number ) {
		if ( ! isset( $this->_index_for_number ) ) {
			$gettext = new Gettext_Translations;
			$expression = $gettext->parenthesize_plural_exression( $this->plural_expression );
			$this->_index_for_number = $gettext->make_plural_form_function( $this->nplurals, $expression );
		}

		$f = $this->_index_for_number;

		return $f( $number );
	}

}