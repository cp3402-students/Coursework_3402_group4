<?php
/**
 * Simply Schedule Appointments Appointment Meta Model.
 *
 * @since   3.3.0
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Appointment Meta Model.
 *
 * @since 3.3.0
 */
class SSA_Appointment_Meta_Model extends SSA_Db_Model {
	protected $slug = 'appointment_meta';
	protected $version = '1.0.0';

	/**
	 * Parent plugin class.
	 *
	 * @since 0.0.2
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	public function get_pluralized_slug() {
		return 'appointment_meta';
	}

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

	public function rest_api_init() {
		// No API Endpoints for this model
		// All manipulations should be done through an Appointment
		// $this->register_routes();
	}

	public function belongs_to() {
		return array(
			// 'Author' => array(
			// 	'model' => 'WP_User_Model',
			// 	'foreign_key' => 'author_id',
			// ),
			'Appointment' => array(
				'model' => $this->plugin->appointment_model,
				'foreign_key' => 'appointment_id',
			),
		);
	}

	protected $schema = array(
		'appointment_id' => array(
			'field' => 'appointment_id',
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
		'meta_key' => array(
			'field' => 'meta_key',
			'label' => 'Meta Key',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'VARCHAR',
			'mysql_length' => '120',
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
		),
		'meta_value' => array(
			'field' => 'meta_value',
			'label' => 'Meta Value',
			'default_value' => false,
			'format' => '%s',
			'mysql_type' => 'TEXT',
			'mysql_length' => false,
			'mysql_unsigned' => false,
			'mysql_allow_null' => false,
			'mysql_extra' => '',
			'cache_key' => false,
			'encoder' => 'json_serialize',
		),
	);

	public $indexes = array(
		'appointment_id' => [ 'appointment_id' ],
		'meta_key' => [ 'meta_key' ],
	);

	public function filter_where_conditions( $where, $args ) {
		if ( !empty( $args['appointment_id'] ) ) {
			$where .= ' AND appointment_id="'.sanitize_text_field( $args['appointment_id'] ).'"';
		}
		if ( !empty( $args['meta_key'] ) ) {
			$where .= ' AND meta_key="'.sanitize_text_field( $args['meta_key'] ).'"';
		}
		
		return $where;
	}

	public function create_item_permissions_check( $request ) {
		return $this->nonce_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		if ( true === $this->get_item_permissions_check( $request ) ) {
			return true;
		}
		
		return false;
	}

}
