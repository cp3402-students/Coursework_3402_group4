<?php
/**
 * Simply Schedule Appointments Support.
 *
 * @since   2.1.6
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Support.
 *
 * @since 2.1.6
 */
class SSA_Support {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.1.6
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.1.6
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.1.6
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'fix_appointment_durations' ) );
		add_action( 'admin_init', array( $this, 'fix_appointment_group_ids' ) );
		add_action( 'admin_init', array( $this, 'fix_db_datetime_schema' ) );
		add_action( 'admin_init', array( $this, 'fix_db_availability_schema' ) );
		add_action( 'admin_init', array( $this, 'fix_appointment_types' ) );
		add_action( 'admin_init', array( $this, 'purge_abandoned_appointments' ) );
		add_action( 'admin_init', array( $this, 'reset_settings' ) );
		add_action( 'admin_init', array( $this, 'ssa_factory_reset' ) );
		add_action( 'admin_init', array( $this, 'rebuild_db' ) );
		add_action( 'admin_init', array( $this, 'clear_google_cache' ) );
		add_action( 'admin_init', array( $this, 'set_google_query_limit' ) );
		add_action( 'admin_init', array( $this, 'clear_all_cache' ) );
		add_action( 'admin_init', array( $this, 'populate_cache' ) );
		add_action( 'admin_init', array( $this, 'restore_plugin_backup' ) );
		add_action( 'admin_init', array( $this, 'bulk_send_notifications' ) );

		add_action( 'admin_init', array( $this, 'set_display_capacity_available' ) );
		add_action( 'admin_init', array( $this, 'ssa_set_license' ) );
		add_action( 'admin_init', 	  array( $this, 'set_beta_booking_app_setting' ) );
	}

	public function ssa_set_license() {
		if ( empty( $_GET['ssa-set-license'] )) {
			return;
		}

		$license_key = sanitize_text_field( $_GET['ssa-set-license'] );
		$this->plugin->license->activate( $license_key );

		wp_redirect( remove_query_arg( 'ssa-set-license' ) );
		exit;
	}

	public function populate_cache() {
		if ( empty( $_GET['ssa-populate-cache'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$this->plugin->availability_cache->populate_cache();

		wp_redirect( remove_query_arg( 'ssa-populate-cache' ) );
		exit;
	}

	public function clear_google_cache() {
		if ( empty( $_GET['ssa-clear-google-cache'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$this->plugin->availability_external_model->bulk_delete( array(
			'type' => 'appointment_type',
			'service' => 'google',
		) );
		$this->plugin->google_calendar->increment_google_cache_version();

		wp_redirect( remove_query_arg( 'ssa-clear-google-cache' ) );
		exit;
	}

	public function set_google_query_limit() {
		if ( empty( $_GET['ssa-set-google-query-limit'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$set_google_query_limit = (int)esc_attr( $_GET['ssa-set-google-query-limit'] );
		if ( empty( $set_google_query_limit ) ) {
			return;
		}

		$this->plugin->google_calendar_settings->update( array(
			'query_limit' => $set_google_query_limit,
		) );

		$this->plugin->google_calendar->increment_google_cache_version();

		wp_redirect( remove_query_arg( 'ssa-set-google-query-limit' ) );
		exit;
	}

	public function set_display_capacity_available() {
		if ( ! isset ( $_GET['ssa-set-display-capacity-available'] ) ) {
			return;
		}

		// if ( ! is_user_logged_in() ) {
		// 	return;
		// }
		$new_value = (bool) $_GET['ssa-set-display-capacity-available'];
		$developer_settings = $this->plugin->developer_settings->get();
		$developer_settings['display_capacity_available'] = $new_value;
		$this->plugin->developer_settings->update( $developer_settings );
		$this->plugin->availability_cache_invalidation->increment_cache_version();

		wp_redirect( remove_query_arg( 'ssa-set-display-capacity-available' ) );
		exit;
	}

	public function clear_all_cache() {
		if ( empty( $_GET['ssa-clear-all-cache'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$this->plugin->availability_external_model->bulk_delete( array(
			'type' => 'appointment_type',
			'service' => 'google',
		) );
		$this->plugin->google_calendar->increment_google_cache_version();
		$this->plugin->availability_cache_invalidation->increment_cache_version();

		wp_redirect( remove_query_arg( 'ssa-clear-all-cache' ) );
		exit;
	}

	public function fix_appointment_durations() {
		if ( empty( $_GET['ssa-fix-appointment-durations'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$appointments = $this->plugin->appointment_model->query( array(
			'number' => -1,
		) );
		$now = new DateTimeImmutable();

		foreach ($appointments as $key => $appointment) {
			if ( empty( $appointment['appointment_type_id'] ) ) {
				continue; // likely an abandoned appointment from a form integration (where an appointment type was never selected)
			}
			$appointment_type = new SSA_Appointment_Type_Object( $appointment['appointment_type_id'] );
			$duration = $appointment_type->duration;
			$start_date = new DateTimeImmutable( $appointment['start_date'] );

			$end_date = $start_date->add( new DateInterval( 'PT' .$duration. 'M' ) );
			if ( $end_date->format( 'Y-m-d H:i:s' ) != $appointment['end_date'] ) {
				echo '<pre>'.print_r($appointment, true).'</pre>';
				$appointment['end_date'] = $end_date->format( 'Y-m-d H:i:s' );

				$this->plugin->appointment_model->update( $appointment['id'], $appointment );
			}
		}

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}

	public function purge_abandoned_appointments() {
		if ( empty( $_GET['ssa-purge-abandoned-appointments'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$this->plugin->appointment_model->delete_abandoned();

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}

	public function fix_db_availability_schema() {
		if ( empty( $_GET['ssa-fix-db-availability-schema'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}


		$this->plugin->availability_model->drop();
		$this->plugin->availability_model->create_table();

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}

	public function fix_appointment_types() {
		if ( empty( $_GET['ssa-fix-appointment-types'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$appointment_types = $this->plugin->appointment_type_model->query( array(
			'number' => -1,
		) );
		foreach ($appointment_types as $appointment_type) {
			if ( empty( $appointment_type['custom_customer_information'] ) ) {
				$appointment_type['custom_customer_information'] = array(
					array(
						'field' => 'Name',
						'display' => true,
						'required' => true,
						'type' => 'single-text',
						'icon' => 'face',
						'values' => '',
					),
					array(
						'field' => 'Email',
						'display' => true,
						'required' => true,
						'type' => 'single-text',
						'icon' => 'email',
						'values' => '',
					),
				);
			}

			$appointment_type['custom_customer_information'] = array_values( $appointment_type['custom_customer_information'] );
			$appointment_type['customer_information'] = array_values( $appointment_type['customer_information'] );

			$appointment_types = $this->plugin->appointment_type_model->update(
				$appointment_type['id'],
				$appointment_type
			);
		}

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}
	public function fix_db_datetime_schema() {
		if ( empty( $_GET['ssa-fix-db-datetime-schema'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		global $wpdb;

		$now = gmdate( 'Y-m-d H:i:s' );

		$before_queries = array(
			/* Appointment Types */
			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `booking_start_date`='".SSA_Constants::EPOCH_START_DATE."' WHERE `booking_start_date`=0",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `booking_end_date`='".SSA_Constants::EPOCH_END_DATE."' WHERE `booking_end_date`=0",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `availability_start_date`='".SSA_Constants::EPOCH_START_DATE."' WHERE `availability_start_date`=0",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `availability_end_date`='".SSA_Constants::EPOCH_END_DATE."' WHERE `availability_end_date`=0",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `date_created`='1970-01-01' where `date_created`=0",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `date_modified`='1970-01-01' where `date_modified`=0",

			/* Appointments */
			"UPDATE {$this->plugin->appointment_model->get_table_name()} SET `start_date`='1970-01-01' where `start_date`=0",

			"UPDATE {$this->plugin->appointment_model->get_table_name()} SET `end_date`='1970-01-01' where `end_date`=0",

			"UPDATE {$this->plugin->appointment_model->get_table_name()} SET `date_created`='1970-01-01' where `date_created`=0",

			"UPDATE {$this->plugin->appointment_model->get_table_name()} SET `date_modified`='1970-01-01' where `date_modified`=0",

		);

		$after_queries = array(
			/* Appointment Types */
			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `booking_start_date`=NULL where `booking_start_date`='".SSA_Constants::EPOCH_START_DATE."'",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `booking_end_date`=NULL where `booking_end_date`='".SSA_Constants::EPOCH_END_DATE."'",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `availability_start_date`=NULL where `availability_start_date`='".SSA_Constants::EPOCH_START_DATE."'",

			"UPDATE {$this->plugin->appointment_type_model->get_table_name()} SET `availability_end_date`=NULL where `availability_end_date`='".SSA_Constants::EPOCH_END_DATE."'",
		);

		$has_failed = false;
		foreach ($before_queries as $query) {
			$result = $wpdb->query( $query );
			if ( false === $result ) {
				$has_failed = true;
			}
		}

		$this->plugin->appointment_type_model->create_table();
		$this->plugin->appointment_model->create_table();

		foreach ($after_queries as $query) {
			$result = $wpdb->query( $query );
			if ( false === $result ) {
				$has_failed = true;
			}
		}

		$this->fix_appointment_group_ids( true );

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}

	public function fix_appointment_group_ids( $force = false ) {
		if ( empty( $force ) && empty( $_GET['ssa-fix-appointment-group-ids'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$appointments = $this->plugin->appointment_model->query( array(
			'number' => -1,
		) );
		$now = new DateTimeImmutable();

		foreach ($appointments as $key => $appointment) {
			if ( ! empty( $appointment['group_id'] ) ) {
				continue;
			}

			$appointment_type = new SSA_Appointment_Type_Object( $appointment['appointment_type_id'] );
			$capacity_type = $appointment_type->capacity_type;
			if ( empty( $capacity_type ) || $capacity_type !== 'group' ) {
				continue;
			}

			$start_date = new DateTimeImmutable( $appointment['start_date'] );

			$args = array(
				'number' => -1,
				'orderby' => 'id',
				'order' => 'ASC',
				'appointment_type_id' => $appointment['appointment_type_id'],
				'start_date' => $appointment['start_date'],
				'exclude_ids' => $appointment['id'],
			);

			$new_group_id = 0;
			$appointment_arrays = $this->plugin->appointment_model->query( $args );
			foreach ($appointment_arrays as $appointment_array) {
				if ( ! empty( $appointment_array['group_id'] ) ) {
					$new_group_id = $appointment_array['group_id'];
				}
			}

			if ( empty( $new_group_id ) && empty( $appointment_arrays[0]['id'] ) ) {
				continue;
			}

			$new_group_id = $appointment_arrays[0]['id'];

			$this->plugin->appointment_model->update( $appointment['id'], array(
				'group_id' => $new_group_id
			) );

			foreach ($appointment_arrays as $appointment_array) {
				$this->plugin->appointment_model->update( $appointment_array['id'], array(
					'group_id' => $new_group_id
				) );
			}
		}

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}

	public function reset_settings() {
		if ( empty( $_GET['ssa-reset-settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$options_to_delete = array(
			'wp_ssa_appointments_db_version',
			'wp_ssa_appointment_meta_db_version',
			'wp_ssa_appointment_types_db_version',
			'wp_ssa_availability_db_version',
			'wp_ssa_async_actions_db_version',
			'wp_ssa_payments_db_version',
			'ssa_settings_json',
			'ssa_versions',
		);

		foreach ($options_to_delete as $option_name) {
			delete_option( $option_name );
		}

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}


	/**
	 * Deletes all ssa related options from wp_options table and truncate all ssa database tables.
	 *
	 * @since 5.4.3
	 *
	 * @return void
	 */
	public function ssa_factory_reset() {
		if ( empty( $_GET['ssa-factory-reset'] ) ) { // phpcs:ignore
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		// Finds and delete all rows from the wp_options table that contains "ssa_" in the option_name column.
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ssa\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// Truncate all ssa database tables.
		$this->plugin->appointment_model->truncate();
		$this->plugin->appointment_meta_model->truncate();
		$this->plugin->appointment_type_model->truncate();
		$this->plugin->availability_model->truncate();
		$this->plugin->availability_external_model->truncate();
		$this->plugin->async_action_model->truncate();
		$this->plugin->payment_model->truncate();
		$this->plugin->staff_model->truncate();
		$this->plugin->staff_appointment_model->truncate();
		$this->plugin->staff_appointment_type_model->truncate();

		wp_safe_redirect( $this->plugin->wp_admin->url(), $status = 302 );
		exit;
	}

	public function rebuild_db() {
		if ( empty( $_GET['ssa-rebuild-db'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		$this->plugin->appointment_model->create_table();
		$this->plugin->appointment_meta_model->create_table();
		$this->plugin->appointment_type_model->create_table();
		$this->plugin->availability_model->create_table();
		$this->plugin->availability_external_model->create_table();
		$this->plugin->async_action_model->create_table();
		$this->plugin->payment_model->create_table();


		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}

	public function restore_plugin_backup() {
		if ( empty( $_GET['ssa-restore-backup'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		// restore previous backup file
		$restore = $this->plugin->support_status->restore_settings_backup();

		// if something happens, print the errors
		if( is_wp_error( $restore ) ) {
			$string = implode("\n", $restore->get_error_messages());
			wp_die($string);
		}

		wp_redirect( $this->plugin->wp_admin->url(), $status = 302);
		exit;
	}

	/**
	 * Given a GET parameter on the url, get a list of future booked appointments and schedule notifications for the ones that are valid.
	 *
	 * @since 4.8.8
	 *
	 * @return void
	 */
	public function bulk_send_notifications() {
		if ( empty( $_GET['ssa-resend-booked-notifications'] ) ) {
			return;
		}

		if ( ! current_user_can( 'ssa_manage_site_settings' ) ) {
			return;
		}

		// Get list of booked appointments.
		$appointments = $this->plugin->appointment_model->query(
			array(
				'status'         => SSA_Appointment_Model::get_booked_statuses(),
				'start_date_min' => gmdate( 'Y-m-d H:i:s' ),
				'number'         => -1,
			)
		);

		$notifications = $this->plugin->notifications_settings->get_notifications();

		if ( empty( $notifications ) ) {
			wp_safe_redirect( $this->plugin->wp_admin->url(), $status = 302 );
			exit;
		}

		// filter list of notifications to return only the one to be sent to the customer.
		$customer_booked_notifications = array_values(
			array_filter(
				$notifications,
				function( $notification ) {
					return (
						! empty( $notification['active'] ) &&
						'appointment_booked' === $notification['trigger'] &&
						strpos( implode( ';', $notification['sent_to'] ), 'customer_email' ) !== false
					);
				}
			)
		);

		// If notification is found, then send list of appointments to validate the notification and possibly send them.
		if ( ! empty( $customer_booked_notifications ) ) {
			foreach ( $customer_booked_notifications as $customer_booked_notification ) {
				$this->plugin->action_scheduler->bulk_schedule_notifications( $customer_booked_notification, $appointments );
			}
		}

		wp_safe_redirect( $this->plugin->wp_admin->url(), $status = 302 );
		exit();
	}



	/**
	 * Defines the SSA_DEBUG_LOG constant to identify if we need to log information specific to the plugin on the
	 * ssa_debug.log file.
	 *
	 * @return void
	 */
	public function set_beta_booking_app_setting() {
		if (!isset($_GET['ssa-beta-booking-app'])) {
			return;
		}

		if (!current_user_can('ssa_manage_site_settings')) {
			return;
		}

		$developer_settings = $this->plugin->developer_settings->get();
		$developer_settings['beta_booking_app'] = (int)$_GET['ssa-beta-booking-app'];
		$this->plugin->developer_settings->update( $developer_settings );
		
		wp_safe_redirect($this->plugin->wp_admin->url(), $status = 302);
		exit();
	}
}
