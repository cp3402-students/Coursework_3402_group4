<?php
/**
 * Simply Schedule Appointments Developer Settings.
 *
 * @since   1.0.1
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Developer Settings.
 *
 * @since 1.0.1
 */
class SSA_Developer_Settings extends SSA_Settings_Schema {
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
		// add_filter( 'update_'.$this->slug.'_settings', array( $this, 'auto_validate_api_key' ), 10, 2 );
		add_action( 'ssa/settings/developer/updated', array( $this, 'update_debug_mode' ), 10, 2 );
		add_action( 'ssa/settings/developer/updated', array( $this, 'maybe_invalidate_cache' ), 10, 2 );
		add_action( 'ssa/settings/developer/updated', array( $this, 'maybe_invalidate_cache_for_separate_availability' ), 10, 2 );
	}

	protected $slug = 'developer';

	public function get_schema() {
		if ( !empty( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array(
			'version' => '2022-01-12',
			'fields' => array(
				'enabled' => array(
					'name' => 'enabled',
					'default_value' => true,
				),

				'enqueue_everywhere' => array(
					'name' => 'enqueue_everywhere',
					'default_value' => false,
				),

				'separate_appointment_type_availability' => array(
					'name' => 'separate_appointment_type_availability',
					'default_value' => apply_filters( 'ssa/get_booked_periods/should_separate_availability_for_appointment_types', false ),
				),

				'beta_updates' => array(
					'name' => 'beta_updates',
					'default_value' => false
				),
				// Beta Features

				// Calendar Events Customization
				'beta_calendar_events' => array(
					'name' => 'beta_calendar_events',
					'default_value' => false
				),

				'capacity_availability' => array(
					'name' => 'capacity_availability',
					'default_value' => false
				),

				// TODO: remove cache_availability
				'cache_availability' => array(
					'name' => 'cache_availability',
					'default_value' => false
				),
				
				'disable_availability_caching' => array(
					'name' => 'disable_availability_caching',
					'default_value' => false
				),
				
				'object_cache' => array(
					'name' => 'object_cache',
					'default_value' => false
				),
				
				'debug_mode' => array(
					'name' => 'debug_mode',
					'default_value' => false
				),
				'ssa_debug_mode' => array(
					'name' => 'ssa_debug_mode',
					'default_value' => false
				),

				// hidden settings
				'display_capacity_available' => array(
					'name' => 'display_capacity_available',
					'default_value' => false
				),

			),
		);

		return $this->schema;
	}

	public function update_debug_mode( $settings, $old_settings ) {
		if( ! isset( $settings['debug_mode'] ) ) {
			return;
		}

		$config_path = ABSPATH . 'wp-config.php';

		if ( ! class_exists( '\WPConfigTransformer' ) ) {
			require $this->plugin->dir( 'includes/lib/wp-cli/wp-config-transformer/src/WPConfigTransformer.php' );
		}
		
		try{
			$config_transformer = new \WPConfigTransformer( $config_path );

			if( $settings['debug_mode'] ) {
				$config_transformer->update( 'constant', 'WP_DEBUG', 'true', array( 'raw' => true ) );	
				$config_transformer->update( 'constant', 'WP_DEBUG_LOG', 'true', array( 'raw' => true ) );	
				$config_transformer->update( 'constant', 'WP_DEBUG_DISPLAY', 'false', array( 'raw' => true ) );	
			} else {
				$config_transformer->remove( 'constant', 'WP_DEBUG' );	
				$config_transformer->remove( 'constant', 'WP_DEBUG_LOG' );	
				$config_transformer->remove( 'constant', 'WP_DEBUG_DISPLAY' );	
			}
		} catch( \Exception $e ) {
			ssa_debug_log( '$config_transformer', $e->getMessage() );
		}

	}

	public function maybe_invalidate_cache( $settings, $old_settings ) {
		if( ! isset( $settings['disable_availability_caching'] ) ) {
			return;
		}

		if ( isset( $old_settings['disable_availability_caching'] ) && $old_settings['disable_availability_caching'] == $settings['disable_availability_caching'] ) {
			return;
		}

		if ( ! empty( $settings['disable_availability_caching'] ) ) {
			$this->plugin->availability_model->drop();
			$this->plugin->availability_model->create_table();
			$this->plugin->availability_cache_invalidation->increment_cache_version();
			$this->plugin->google_calendar->increment_google_cache_version();
		} else {
			$this->plugin->availability_cache_invalidation->invalidate_everything();
			$this->plugin->google_calendar->increment_google_cache_version();
		}

	}

	public function maybe_invalidate_cache_for_separate_availability( $settings, $old_settings ) {
		if( ! isset( $settings['separate_appointment_type_availability'] ) ) {
			return;
		}

		if ( isset( $old_settings['separate_appointment_type_availability'] ) && $old_settings['separate_appointment_type_availability'] == $settings['separate_appointment_type_availability'] ) {
			return; // nothing changed
		}

		$this->plugin->availability_cache_invalidation->invalidate_everything();
	}

}
