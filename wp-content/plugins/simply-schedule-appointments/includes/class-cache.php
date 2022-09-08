<?php
/**
 * Simply Schedule Appointments Cache.
 *
 * @since   4.7.4
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Cache.
 *
 * @since 4.7.4
 */
class SSA_Cache {
	/**
	 * Parent plugin class.
	 *
	 * @since 4.7.4
	 *
	 * @var   Simply_Schedule_Appointments
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  4.0.1
	 *
	 * @param  Simply_Schedule_Appointments $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

  /**
   * Get SSA object cache.
   * 
   * @since 4.7.4
   *
   * @param string $key the cache identifier
   * @param string $group the cache group
   * @param boolean $force
   * @param boolean $found
   * @return mixed
   */
	public static function object_cache_get( $key, $group = '', $force = false, &$found = null ) {
		$group .= SSA_Availability_Cache_Invalidation::get_cache_group();
		$key = 'ssa/' . $group . '/' . $key;

		return get_transient( $key );
	}

  /**
   * Set SSA object cache.
   * 
   * @since 4.7.4
   *
   * @param string $key the cache identifier
   * @param mixed $data the data to be cached
   * @param string $group the cache group
   * @param integer $expire cache expiration time
   * @return boolean
   */
	public static function object_cache_set( $key, $data, $group = '', $expire = 0 ) {
		$group .= SSA_Availability_Cache_Invalidation::get_cache_group();
		$key = 'ssa/' . $group . '/' . $key;
		if ( empty( $expire ) ) {
			$expire = WEEK_IN_SECONDS;
		}

		set_transient( $key, $data, $expire );
		return true;
	}  

  /**
   * Deletes SSA object cache.
   * 
   * @since 4.7.4
   *
   * @param string $key the cache identifier
   * @param string $group the cache group
   * @return boolean
   */
  public static function object_cache_delete( $key, $group = '' ) {
		$group .= SSA_Availability_Cache_Invalidation::get_cache_group();
		$key = 'ssa/' . $group . '/' . $key;

    delete_transient( $key );
		return true;
	}  
}