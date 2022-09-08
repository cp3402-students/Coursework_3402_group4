<?php
/**
 * Simply Schedule Appointments Db Model.
 *
 * @since   0.0.3
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Db Model.
 *
 * @since 0.0.3
 */
abstract class SSA_Db_Model extends TD_DB_Model {
	protected $hook_namespace = 'ssa';
	protected $db_namespace = 'ssa';
	protected $api_namespace = 'ssa';
	protected $api_version = '1';

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
		parent::__construct( $plugin );
		$this->ssa_model_hooks();

		add_filter( 'rest_authentication_errors', array( $this, 'whitelist_ssa_rest_api' ), 1000 );
	}

	public function whitelist_ssa_rest_api( $result ) {
		$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );
		if ( 0 === strpos( $route, '/ssa/' ) ) {
			return true;
		}

		return $result;
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.3
	 */
	public function ssa_model_hooks() {
		add_filter( 'query_'.$this->slug.'_db_where_conditions', array( $this, 'ssa_filter_where_conditions' ), 10, 2 );
	}

	public function ssa_filter_where_conditions( $where, $args ) {
		global $wpdb;

		if( ! empty( $args['id'] ) ) {

			if( is_array( $args['id'] ) ) {
				$ids = implode( ',', array_map('intval', $args['id'] ) );
				$where .= " AND `".$this->primary_key."` IN( {$ids} ) ";
			} else {
				$ids = intval( $args['id'] );
				$where .= " AND `".$this->primary_key."` = {$ids} ";
			}


		}

		if ( !empty( $this->schema['user_id'] ) ) {		
			// rows for specific user actions	
			if( ! empty( $args['user_id'] ) ) {

				if( is_array( $args['user_id'] ) ) {
					$user_ids = implode( ',', array_map('intval', $args['user_id'] ) );
					$where .= " AND `user_id` IN( {$user_ids} ) ";
				} else {
					$user_ids = intval( $args['user_id'] );
					$where .= " AND `user_id` = {$user_ids} ";
				}


			}
		}

		if ( !empty( $this->post_id_field ) && !empty( $this->schema[$this->post_id_field] ) ) {		
			if ( is_user_logged_in()
			 && !current_user_can( 'ssa_manage_appointments' ) ) {
				$args['author_id'] = get_current_user_id();
			}

			// rows for specific user accounts
			if( ! empty( $args['author_id'] ) ) {
				if( is_array( $args['author_id'] ) ) {
					$author_ids = implode( ',', array_map('intval', $args['author_id'] ) );
					$where .= " AND `".$this->post_id_field."` IN( SELECT ID FROM $wpdb->posts WHERE post_author IN ( {$author_ids} ) ) ";
				} else {
					$author_ids = intval( $args['author_id'] );
					$where .= " AND `".$this->post_id_field."` IN( SELECT ID FROM $wpdb->posts WHERE post_author = {$author_ids} ) ";
				}
				
			}

			// specific rows by name
			if( ! empty( $args[$this->post_id_field] ) ) {
				if ( is_array( $args[$this->post_id_field] ) ) {
					$post_ids = implode( ',', array_map('intval', $args[$this->post_id_field] ) );
					$where .= " AND `".$this->post_id_field."` IN( {$post_ids} ) ";
				} else {
					$where .= $wpdb->prepare( " AND `".$this->post_id_field."` = '" . '%d' . "' ", $args[$this->post_id_field] );
				}
			}
		}

		// specific rows by name
		if ( !empty( $this->schema['type'] ) ) {		
			if( ! empty( $args['type'] ) ) {
				$where .= $wpdb->prepare( " AND `type` = '" . '%s' . "' ", $args['type'] );
			}
		}


		// specific rows by name
		if ( !empty( $this->schema['name'] ) ) {		
			if( ! empty( $args['name'] ) ) {
				$where .= $wpdb->prepare( " AND `name` = '" . '%s' . "' ", $args['name'] );
			}
		}

		if ( !empty( $this->schema['start_date'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['start_date'] ) ) {
				$where .= $wpdb->prepare( " AND `start_date` = '" . '%s' . "' ", $args['start_date'] );
			} else {

				if( ! empty( $args['start_date_min'] ) ) {
					$where .= " AND `start_date` >= '{$args["start_date_min"]}'";
				}

				if( ! empty( $args['start_date_max'] ) ) {
					$where .= " AND `start_date` <= '{$args["start_date_max"]}'";
				}

			}
		}

		if ( !empty( $this->schema['end_date'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['end_date'] ) ) {
				$where .= $wpdb->prepare( " AND `end_date` = '" . '%s' . "' ", $args['end_date'] );
			} else {

				if( ! empty( $args['end_date_min'] ) ) {
					$where .= " AND `end_date` >= '{$args["end_date_min"]}'";
				}

				if( ! empty( $args['end_date_max'] ) ) {
					$where .= " AND `end_date` <= '{$args["end_date_max"]}'";
				}

			}
		}

		if ( !empty( $this->schema['date_created'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['date_created'] ) ) {
				$where .= $wpdb->prepare( " AND `date_created` = '" . '%s' . "' ", $args['date_created'] );
			} else {

				if( ! empty( $args['date_created_min'] ) ) {
					$where .= " AND `date_created` >= '{$args["date_created_min"]}'";
				}

				if( ! empty( $args['date_created_max'] ) ) {
					$where .= " AND `date_created` <= '{$args["date_created_max"]}'";
				}

			}
		}

		if ( !empty( $this->schema['date_modified'] ) ) {		
			// Customers created for a specific date or in a date range
			if( ! empty( $args['date_modified'] ) ) {

				if( !is_array( $args['date_modified'] ) ) {

					$year  = date( 'Y', strtotime( $args['date_modified'] ) );
					$month = date( 'm', strtotime( $args['date_modified'] ) );
					$day   = date( 'd', strtotime( $args['date_modified'] ) );

					$where .= " AND $year = YEAR ( date_modified ) AND $month = MONTH ( date_modified ) AND $day = DAY ( date_modified )";
				}

			} else {

				if( ! empty( $args['date_modified_min'] ) ) {
					$where .= " AND `date_modified` >= '{$args["date_modified_min"]}'";
				}

				if( ! empty( $args['date_modified_max'] ) ) {
					$where .= " AND `date_modified` <= '{$args["date_modified_max"]}'";
				}

			}
		}

		return $where;
	}

	public function get_id_token( $request ) {
		if ( empty( $request['id'] ) ) {
			return false;
		}

		return SSA_Utils::hash( sanitize_text_field( $request['id'] ) );
	}

	public function id_token_permissions_check( $request ) {
		$correct_token = $this->get_id_token( $request );

		if ( empty( $correct_token ) ) {
			return false;
		}

		$params = $request->get_params();
		if ( empty( $params['token'] ) ) {
			return false;
		}

		if ( $correct_token == sanitize_text_field( $params['token'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns global SSA Token.
	 *
	 * @since 5.7.2
	 *
	 * @return string SSA Token.
	 */
	public function get_token() {
		return apply_filters( 'ssa/api/token', SSA_Utils::site_unique_hash( 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9' ) );
	}

	/**
	 * Validates global SSA Token.
	 *
	 * @since 5.7.2
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the token is valid, false if not.
	 */
	public function token_permissions_check( $request ) {
		$correct_token = $this->get_token();

		if ( empty( $correct_token ) ) {
			return false;
		}

		$params = $request->get_params();
		if ( empty( $params['token'] ) ) {
			return false;
		}

		if ( sanitize_text_field( $params['token'] ) === $correct_token ) {
			return true;
		}

		return false;
	}


}
