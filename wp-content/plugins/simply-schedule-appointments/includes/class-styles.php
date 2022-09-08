<?php
/**
 * Simply Schedule Appointments Styles.
 *
 * @since   1.5.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Styles.
 *
 * @since 1.5.2
 */
class SSA_Styles {
	/**
	 * Parent plugin class.
	 *
	 * @since 1.5.2
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  1.5.2
	 *
	 * @param Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.5.2
	 */
	public function hooks() {

	}

	public function get_contrast_ratio( $rgb_string ) {
		sscanf( $rgb_string, 'rgba(%d,%d,%d,%f)', $r, $g, $b, $a );
		$L1 = 0.2126 * pow( $r / 255, 2.2 ) +
			0.7152 * pow( $g / 255, 2.2 ) +
			0.0722 * pow( $b / 255, 2.2 );
		$L2 = 0.2126 * pow( 0 / 255, 2.2 ) +
			0.7152 * pow( 0 / 255, 2.2 ) +
			0.0722 * pow( 0 / 255, 2.2 );
		// Adjust value for opacity
		$L1 = $L1 / $a;
		$L2 = $L2 / $a;
		$contrast_ratio = 0;
		if ( $L1 > $L2 ) {
			$contrast_ratio = (int)( ( $L1 + 0.05 ) / ( $L2 + 0.05 ) );
		} else {
			$contrast_ratio = (int)( ( $L2 + 0.05 ) / ( $L1 + 0.05 ) );
		}

	}

	/**
	 * checks if a string is a valid hex color or rgba.
	 * 
	 * @since 3.7.6
	 *
	 * @param string $color
	 * @return string|boolean
	 */
	public function is_hex_or_rgba( $color ) {
		if( strpos( $color, 'rgba' ) !== false ) {
			return 'rgba';
		}
		if( preg_match('/^#?(([a-f0-9]{3}){1,2})$/i', $color ) ) {
			return 'hex';
		}

		return null;
	}

	/**
	 * Transforms an hex color into rgba, necessary to calculate contrast.
	 * 
	 * @since 3.7.6
	 *
	 * @param string $hex_string
	 * @return string
	 */
	public function hex_to_rgba( $hex_string ) {
		// remove hash sign if it's passed on the hex string
		$hex_string = ltrim($hex_string, '#');
		$color_type = $this->is_hex_or_rgba( $hex_string );

		// if not a valid hex or rgba color, return
		if( ! $color_type ) {
			return null;
		}

		if( $color_type === 'rgba' ) {
			return $hex_string;
		}

		sscanf($hex_string, "%02x%02x%02x", $r, $g, $b);

		return "rgba($r,$g,$b,1)";
	}

	public function get_style_atts_from_string( $string ) {
		$values = preg_split('/(?<=[0-9])(?=[^0-9]+)/i', $string );
		$valid_units = array( 'cm', 'mm', 'in', 'px', 'pc', 'pt', 'em', 'ex', 'ch', 'rem', 'vw', 'vh', 'vmin', 'vmax', '%' );
		$unit = ( ! empty( $values[1] ) && in_array( $values[1], $valid_units ) ) ? $values[1] : 'px';
		$value = $values[0];

		return array(
			'unit' => $unit,
			'value' => $value,
		);
	}
}
