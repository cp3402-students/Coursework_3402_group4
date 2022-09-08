<?php
/**
 * Simply Schedule Appointments Styles Settings.
 *
 * @since   1.5.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Styles Settings.
 *
 * @since 1.5.2
 */
class SSA_Styles_Settings extends SSA_Settings_Schema {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.3
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		parent::__construct();
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.3
	 */
	public function hooks() {
	}

	protected $slug = 'styles';

	public function get_schema() {
		if ( !empty( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array(
			'version' => '2018-10-23',
			'fields' => array(
				'enabled' => array(
					'name' => 'enabled',
					'default_value' => true,
				),

				'font' => array(
					'name' => 'font',
					'default_value' =>'Roboto',
				),

				'accent_color' => array(
					'name' => 'accent_color',
					'default_value' =>'rgba(139, 195, 74, 1)',
				),

				'background' => array(
					'name' => 'background',
					'default_value' =>'rgba(255, 255, 255, 1)',
				),

				'padding' => array(
					'name' => 'padding',
					'default_value' =>'1rem',
				),

				'contrast' => array(
					'name' => 'contrast',
					'default_value' => false,
				),

				'css' => array(
					'name' => 'css',
					'default_value' => '',
				),

			),
		);

		return $this->schema;
	}

	public function update( $new_settings ) {
		$this->plugin->settings->update_section( $this->slug, $new_settings );
	}

}
