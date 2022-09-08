<?php
/**
 * Simply Schedule Appointments Settings.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Settings.
 *
 * @since 0.0.3
 */
class SSA_Settings {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.3
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	protected $option_name = 'ssa_settings_json';

	protected $defaults = null;

	protected $schema;

	protected $settings;

	/**
	 * Constructor
	 *
	 * @since  0.0.3
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  0.0.3
	 * @return void
	 */
	public function hooks() {
	}

	public function get_schema() {
		if ( !empty( $this->schema ) ) {
			return $this->schema;
		}

		$schema = apply_filters( 'ssa_settings_schema', array() );

		$this->schema = $schema;
		return $this->schema;
	}

	public function get_computed_schema() {
		if ( !empty( $this->computed_schema ) ) {
			return $this->computed_schema;
		}

		$computed_schema = apply_filters( 'ssa_settings_computed_schema', array() );

		$this->computed_schema = $computed_schema;
		return $this->computed_schema;
	}

	public function get() {
		if ( !empty( $this->settings ) ) {
			$this->add_computed_values();
			return $this->settings;
		}

		$settings_json = get_option( $this->option_name, json_encode( array() ) );
		$settings = json_decode( $settings_json, true );

		$schema = $this->get_schema(); // load the schema so that we can load default values as necessary
		foreach ($schema as $section_key => $section_schema) {
			if ( !empty( $settings[$section_key]['schema_version'] )
			 	&& $settings[$section_key]['schema_version'] >= $schema[$section_key]['version'] ) {
				// In this case we already have saved data with this schema, so we do not need to load any default values
				continue;
			}

			if ( empty( $settings[$section_key]['schema_version'] ) ) {
				// this is the first time that we have encountered this schema
				$settings[$section_key] = array();
			}

			//  let's start merging in default values
			$default_values_from_schema = array_combine(
				wp_list_pluck( $schema[$section_key]['fields'], 'name' ),
				wp_list_pluck( $schema[$section_key]['fields'], 'default_value' )
			);

			if ( empty( $settings[$section_key] ) ) {
				$settings[$section_key] = $default_values_from_schema;
			} else {
				$settings[$section_key] = array_merge( $default_values_from_schema, $settings[$section_key] );
			}
			
			$settings[$section_key]['schema_version'] = $schema[$section_key]['version'];
		}

		if ( empty( $settings['global']['timezone_string'] ) ) {
			$settings['global']['timezone_string'] = 'UTC';
		}
		$this->settings = $settings;
		$this->add_computed_values();
		$this->add_enabled_activated_values();
		$this->decrypt();
		return $this->settings;
	}

	public function remove_unauthorized_settings_for_current_user( $settings ) {
		foreach ($settings as $module_slug => $module_settings) {
			if ( empty( $module_settings ) || ! is_array( $module_settings ) ) {
				continue;
			}
			
			foreach ($module_settings as $field_slug => $module_setting_value) {
				$module_settings_slug = $module_slug.'_settings';
				$module_schema = $this->plugin->$module_settings_slug->get_schema();

				if ( empty( $module_schema['fields'] ) ) {
					continue;
				}

					if ( ! empty( $module_schema['fields'][$field_slug]['required_capability'] ) ) {
						if ( ! current_user_can( $module_schema['fields'][$field_slug]['required_capability'] ) ) {
							unset( $settings[$module_slug][$field_slug] );
						}
					}
			}
		}

		return $settings;
	}

	public function add_computed_values() {
		$computed_schema = $this->get_computed_schema(); // load the computed_schema so that we can calculate necessary values
		foreach ($computed_schema as $section_key => $section_computed_schema) {
			if ( empty( $section_computed_schema['fields'] ) ) {
				continue;
			}

			foreach ($section_computed_schema['fields'] as $computed_field ) {
				if ( !empty( $computed_field['get_input'] ) ) {
					$input = $computed_field['get_input'];
				} elseif ( !empty( $computed_field['get_input_path'] ) && isset( $this->settings[$section_key][$computed_field['get_input_path']] ) ) {
					$input = $this->settings[$section_key][$computed_field['get_input_path']];
				} else {
					$input = null;
				}
				$computed_value = call_user_func( $computed_field['get_function'], $input );
				$this->settings[$section_key][$computed_field['name']] = $computed_value;
			}
		}
	}

	public function add_enabled_activated_values() {
		$schema = $this->get_schema(); // load the schema so that we can calculate necessary values
		foreach ($schema as $section_key => $section_schema) {
			if ( empty( $section_schema['fields'] ) ) {
				continue;
			}
			$this->settings[$section_key]['enabled'] = $this->plugin->settings_installed->is_enabled( $section_key );
		}
	}

	public function decrypt() {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return;
		}

		$schema = $this->get_schema(); // load the schema so that we can calculate necessary values
		foreach ($schema as $section_key => $section_schema) {
			if ( empty( $section_schema['fields'] ) ) {
				continue;
			}
			
			foreach ($section_schema['fields'] as $field ) {
				if ( empty( $field['encrypt'] ) ) {
					continue;
				}
				if ( empty( $this->settings[$section_key][$field['name']] ) ) {
					continue;
				}

				if ( false === strpos( $this->settings[$section_key][$field['name']], SSA_Encryption::SALT ) ) {
					continue; // confirm that this indeed encrypted data
				}

				$this->settings[$section_key][$field['name']] = str_replace( SSA_Encryption::SALT, '', $this->settings[$section_key][$field['name']] );

				// $decrypted_value = call_user_func( $field['get_function'], $input );
				$this->settings[$section_key][$field['name']] = SSA_Encryption::decrypt( $this->settings[$section_key][$field['name']] );
			}
		}
	}


	public function get_encrypted_settings( $new_settings ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return $new_settings;
		}

		$schema = $this->get_schema(); // load the schema so that we can calculate necessary values
		foreach ($schema as $section_key => $section_schema) {
			if ( empty( $section_schema['fields'] ) ) {
				continue;
			}
			
			foreach ($section_schema['fields'] as $field ) {
				if ( empty( $field['encrypt'] ) ) {
					continue;
				}

				if ( empty( $new_settings[$section_key][$field['name']] ) ) {
					continue;
				}

				// $decrypted_value = call_user_func( $field['get_function'], $input );
				$new_settings[$section_key][$field['name']] = SSA_Encryption::encrypt( $new_settings[$section_key][$field['name']] ) . SSA_Encryption::SALT;

			}
		}

		return $new_settings;
	}

	public function update( $new_settings ) {
		$existing_settings = $this->get();
		$merged_settings = shortcode_atts( $existing_settings, $new_settings );
		$merged_settings['last_updated'] = gmdate( 'Y-m-d H:i:s' );
		return $this->set( $merged_settings );
	}

	public function update_section( $section_key, $new_settings ) {
		$settings = $this->get();
		$old_settings = $settings;
		if ( empty( $settings[$section_key] ) ) {
			return false;
		}

		$settings[$section_key] = shortcode_atts( $settings[$section_key], $new_settings );

		$computed_schema = $this->get_computed_schema();
		if ( !empty( $computed_schema[$section_key]['fields'] ) ) {
			foreach ($computed_schema[$section_key]['fields'] as $computed_field ) {
				if ( !isset( $settings[$section_key][$computed_field['name']])) {
					continue;
				}
				if ( !empty( $settings[$section_key][$computed_field['name']] ) ) {
					if ( !empty( $computed_field['set_result_path'] ) ) {
						if ( isset( $settings[$section_key][$computed_field['set_result_path']] ) ) {					

							$result_value = call_user_func( $computed_field['set_function'], $settings[$section_key][$computed_field['name']] );
							$settings[$section_key][$computed_field['set_result_path']] = $result_value;
						}
					}
				}

				unset( $settings[$section_key][$computed_field['name']] );
			}
		}

		/* before_save */
		$schema = $this->get_schema();
		if ( !empty( $schema[$section_key]['fields'] ) ) {
			foreach ($schema[$section_key]['fields'] as $field) {
				if ( empty( $field['before_save_function'] ) ) {
					continue;
				}
				if ( !isset( $settings[$section_key][$field['name']])) {
					continue;
				}

				if ( !empty( $settings[$section_key][$field['name']] ) ) {
					if ( !empty( $field['before_save_function'] ) ) {
						$result_value = call_user_func( $field['before_save_function'], $settings[$section_key][$field['name']] );
						$settings[$section_key][$field['name']] = $result_value;
					}
				}
			}
		}

		$settings[$section_key]['last_updated'] = gmdate( 'Y-m-d H:i:s' );
		$this->set( $settings );

		$settings = $this->get();

		do_action( 'ssa/settings/'.$section_key.'/updated', $settings[$section_key], $old_settings[$section_key] );

		return $settings[$section_key];
	}

	private function set( $new_settings ) {
		$this->settings = $new_settings;
		$encrypted_settings = $this->get_encrypted_settings( $new_settings );
		update_option( $this->option_name, json_encode( $encrypted_settings ) );

		return $this->settings;
	}


}

abstract class SSA_Settings_Schema {

	protected $schema = array();
	protected $computed_schema = array();
	protected $slug;
	protected $parent_slug;

	abstract function get_schema();
	public function get_computed_schema() {
		return array();
	}

	public function __construct() {
		if ( empty( $this->slug ) ) {
			die( 'no slug defined for: '.get_class( $this ).' (this slug will be used as the key to save in to the settings array)' );
		}

		$this->parent_hooks();
	}

	public function parent_hooks() {
		add_filter( 'ssa_settings_schema', array( $this, 'filter_settings_schema' ) );
		add_filter( 'ssa_settings_computed_schema', array( $this, 'filter_settings_computed_schema' ) );
	}

	public function filter_settings_schema( $schema ) {
		$schema[$this->slug] = $this->get_schema();

		return $schema;
	}

	public function filter_settings_computed_schema( $schema ) {
		$schema[$this->slug] = $this->get_computed_schema();

		return $schema;
	}

	public function get_field_defaults() {
		if ( !empty( $this->defaults ) ) {
			return $this->defaults;
		}
		
		$defaults = array();
		$schema = $this->get_schema();
		if ( empty( $schema['fields'] ) ) {
			return $defaults;
		}

		$defaults = array_combine(
			wp_list_pluck( $schema['fields'], 'name' ),
			wp_list_pluck( $schema['fields'], 'default_value' )
		);

		$this->defaults = $defaults;
		return $this->defaults;
	}
 
	public function get() {
		if ( $this->slug !== 'installed' ) {		
			if ( ! $this->plugin->settings_installed->is_enabled( $this->slug ) ) {
				return null;
			}

			if ( ! empty( $this->parent_slug ) ) {
				if ( ! $this->plugin->settings_installed->is_enabled( $this->parent_slug ) ) {
					return null;
				}
			}
		}

		return $this->plugin->settings->get()[$this->slug];
	}

	public function reset_to_defaults( $re_enable_feature = true ) {
		$defaults = $this->get_field_defaults();
		if ( !empty( $re_enable_feature ) ) {
			$defaults['enabled'] = $re_enable_feature;
		}
		return $this->update( $defaults );
	}

	public function update( $new_settings ) {
		// $old_settings = $this->get();
		// $new_settings = apply_filters( 'update_'.$this->slug.'_settings', $new_settings, $old_settings );
		return $this->plugin->settings->update_section( $this->slug, $new_settings );
	}

}
