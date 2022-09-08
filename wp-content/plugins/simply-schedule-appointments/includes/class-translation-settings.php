<?php
/**
 * Simply Schedule Appointments Translation Settings.
 *
 * @since   3.2.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Translation Settings.
 *
 * @since 3.2.3
 */
class SSA_Translation_Settings extends SSA_Settings_Schema {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.6.7
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.6.7
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
	 * @since  2.6.7
	 */
	public function hooks() {
		
	}

	protected $slug = 'translation';

	public function get_schema() {
		if ( !empty( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array(
			'version' => '2019-10-15',
			'fields' => array(
				'enabled' => array(
					'name' => 'enabled',
					'default_value' => true,
				),

				'locales' => array(
					'name' => 'locales',
					'default_value' => array(),
				),
			),


		);

		return $this->schema;
	}
}
