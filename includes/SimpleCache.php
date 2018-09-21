<?php

namespace Gilbitron\Util;

/*
 * SimpleCache v1.4.1
 *
 * By Gilbert Pellegrom
 * http://dev7studios.com
 *
 * Free to use and abuse under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 */
class SimpleCache {
	// Path to cache folder (with trailing /)
	public $cache_path = 'cache/';
	// Length of time to cache a file (in seconds)
	public $cache_time = 3600;
	// Cache file extension
	public $cache_extension = '.cache';

	public function set_cache($label, $data) {
		file_put_contents($this->cache_path . $this->safe_filename($label) . $this->cache_extension, $data);
	}

	public function get_cache($label) {
		if($this->is_cached($label)){
			$filename = $this->cache_path . $this->safe_filename($label) . $this->cache_extension;
			return file_get_contents($filename);
		}

		return false;
	}

	public function is_cached($label) {
		$filename = $this->cache_path . $this->safe_filename($label) . $this->cache_extension;

		if(file_exists($filename) && (filemtime($filename) + $this->cache_time >= time())) return true;

		return false;
	}

	//Helper function to validate filenames
	private function safe_filename($filename)
	{
		return preg_replace('/[^0-9a-z\.\_\-]/i','', strtolower($filename));
	}
}
