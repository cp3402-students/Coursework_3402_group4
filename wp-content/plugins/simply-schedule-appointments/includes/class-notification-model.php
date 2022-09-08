<?php
/**
 * Simply Schedule Appointments Notification Model.
 *
 * @since   3.9.4
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notification Model.
 *
 * @since 3.9.4
 */
class SSA_Notification_Model extends SSA_Db_Model {
	protected $slug = 'notification';
	protected $version = '1.0.0';

	public function get_table_name() {
		return false;
	}

	protected $schema = array(
		'appointment_type_id' => array(
			'field' => 'appointment_type_id',
			'label' => 'Appointment Type ID',
			'default_value' => 0,
			'format' => '%d',
			'mysql_type' => 'BIGINT',
			'mysql_length' => 20,
			'mysql_unsigned' => true,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
	);

	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.2
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.2
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		// $this->version = $this->version.'.'.time(); // dev mode
		parent::__construct( $plugin );

		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.2
	 */
	public function hooks() {
		
	}

	public function update_item( $request ) {
		$notification_id = $request['id'];
		$params = $request->get_params();
		
		$notifications_settings = $this->plugin->notifications_settings->get();
		$updated_notification = false;
		foreach ($notifications_settings['notifications'] as $key => $notification) {
			if ( $notification['id'] != $notification_id ) {
				continue;
			}

			$notifications_settings['notifications'][$key] = array_merge( $notification, $params );
			$updated_notification = $notifications_settings['notifications'][$key];
		}

		if ( empty( $updated_notification ) ) {
			$notifications_settings['notifications'][] = $params;
			$updated_notification = $params;
		}

		$this->plugin->notifications_settings->update( $notifications_settings );

		return $updated_notification;
	}

	public function delete( $notification_id = 0, $force_delete = false ) {
		$notifications_settings = $this->plugin->notifications_settings->get();
		$deleted = false;
		$new_notifications = array();
		foreach ($notifications_settings['notifications'] as $key => $notification) {
			if ( $notification['id'] == $notification_id ) {
				$deleted = true;
				continue;
			}


			$new_notifications[] = $notification;
		}

		$notifications_settings['notifications'] = $new_notifications;
		$this->plugin->notifications_settings->update( $notifications_settings );

		return $deleted;
	}

}
