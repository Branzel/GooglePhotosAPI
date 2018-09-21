<?php
/**
 * Cache for GoogleAPI
 *
 * @package GoogleAPI
 * @subpackage Classes
 * @author Rico Paridaens
 * @since 1.0.0
 *
 * Based upon SimpleCache v1.4.1 (Gilbert Pellegrom, http://dev7studios.com )
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Cache class
 * @package GoogleAPI
 * @subpackage Classes
 * @author Rico Paridaens
 * @since 1.0.0
 */
class GoogleAPI_Cache  {
	// Path to cache folder (with trailing /)
	public static $cache_path = BRANZEL_GOOGLEAPI_ABSPATH . 'cache/';
	// Length of time to cache a file (in seconds)
	public static $cache_time = 3600;
	// Cache file extension
	public static $cache_extension = '.cache';
	
	/**
	 * Cache label
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $label;
	
	/**
	 * Cache data
	 *
	 * @since 1.0.0
	 * @var Object
	 */
	private $data;
	
	/**
	 * Cache data
	 *
	 * @since 1.0.0
	 * @var Object
	 */
	private $expiredData;
	
	/**
	 * Initialize the Cache
	 *
	 * @since 1.0.0
	 */
	public function __construct($label) {
		$this->label = $label;
		
		$this->data = $this->get_cache($label);
	}

	public function get_cache($label) {
		$filename = GoogleAPI_Cache::$cache_path . $this->safe_filename($label) . GoogleAPI_Cache::$cache_extension;
			
		if($this->is_cached($label)){
			return json_decode(file_get_contents($filename), true);
		} elseif(file_exists($filename)) {
			$this->expiredData = json_decode(file_get_contents($filename), true);
		}

		return array();
	}
	
	

	public function is_cached($label) {
		$filename = GoogleAPI_Cache::$cache_path . $this->safe_filename($label) . GoogleAPI_Cache::$cache_extension;

		if(file_exists($filename) && (filemtime($filename) + GoogleAPI_Cache::$cache_time >= time())) return true;

		return false;
	}

	//Helper function to validate filenames
	private function safe_filename($filename)
	{
		return preg_replace('/[^0-9a-z\.\_\-]/i','', strtolower($filename));
	}
	
	/**
	 * Save the cache to the local storage
	 *
	 * @since 1.0.0
	 */
	public function save() {
		file_put_contents(GoogleAPI_Cache::$cache_path . $this->safe_filename($this->label) . GoogleAPI_Cache::$cache_extension, json_encode($this->data));
	}
	
	/**
	 * Get the label of the cache
	 *
	 * @since 1.0.0
	 * @return string label
	 */
	public function getLabel() {
		return $this->label;
	}
	
	/**
	 * Get the data of the cache
	 *
	 * @since 1.0.0
	 * @return array data
	 */
	public function getExpiredData() {
		return $this->expiredData;
	}
	
	/**
	 * Get the data of the cache
	 *
	 * @since 1.0.0
	 * @return array data
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Set the data of the cache
	 *
	 * @since 1.0.0
	 * @param string $data
	 */
	public function setData($data) {
		$this->data = $data;
	}
} // class GoogleAPI_Cache