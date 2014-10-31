<?php
require_once(SITE_ROOT.'/system/mod/DklabCache/ZendCache.php');
require_once(SITE_ROOT.'/system/mod/DklabCache/DklabCache.php');

class cache {

	private static $_cacheEnabled = 1;
	private static $_cacheKeyPrefix = '';
	private static $_cacheDefaultLifeTime = 600;
	private static $_cacheInstance = '';
	private static $_cacheInstanceTag = '';
	private static $_cacheStatic = array();
	protected static $cacheStats = array(
		'time'=>0,
		'count'=>0,
		'count_get'=>0,
		'count_set'=>0,
	);

	/**
	 * Init cache, enable or disable caching depending on options and installed php APC extention
	 */
	public static function init() {
		self::$_cacheEnabled = config::get('cacheEnabled', 0) && extension_loaded('apc') && ini_get('apc.enabled');

		if ( !self::$_cacheEnabled ) return false;

		self::$_cacheKeyPrefix = config::get('cacheKeyPrefix');
		if ( empty(self::$_cacheKeyPrefix) ) {
			throw new Exeption('Для включения кешинга необходимо установить уникальный префикс для кеша в config.php');
		}
		self::$_cacheDefaultLifeTime = config::get('cacheDefaultLifeTime');
		self::$_cacheInstance = new Zend_Cache_Backend_APC();
		self::$_cacheInstanceTag = new Dklab_Cache_Backend_TagEmuWrapper(
				new Dklab_Cache_Backend_Profiler(self::$_cacheInstance, array('cache', 'calcStats')
			));
	}

	/**
	 * Get value from cache
	 *
	 * @param  string | array $prefix   Prefix of cache key (e.g. model name)
	 * @param  string | array $identity Cache key, or array of keys
	 * @return mixed | bool
	 */
	public static function get($prefix, $identity) {
		if ( !self::$_cacheEnabled ) return false;

			// if identity is array, call multiGet function
		if ( is_array($identity) ) {
			return self::multiGet($identity);
		} else {
			$identity = self::$_cacheKeyPrefix . '_' . $prefix . '_' . md5($identity);
			$data = self::$_cacheInstanceTag->load($identity);
			return $data;
		}
	}

	/**
	 * Get multi values from cache
	 *
	 * @param  string $prefix   Prefix of cache key (e.g. model name)
	 * @param  array  $identity Array of cache keys
	 * @return array | bool
	 */
	public static function multiGet($prefix, $identity) {
		if ( !self::$_cacheEnabled ) return false;

		if (count($identity)==0) {
			return false;
		}

		$data=array();
		foreach ( $identity as $key=>$name ) {
			if ( (false !== ($info=self::get($prefix, $name))) ) {
				$data[$name] = $info;
			}
		}

		if (count($data)>0) {
			return $data;
		}
		return false;
	}

	/**
	 * Store value to cache
	 *
	 * @param  string $prefix    Prefix of cache key (e.g. model name)
	 * @param  string $identity  Cache key name
	 * @param  mixed  $data      Data to store in cache
	 * @param  array  $tags      Tags list
	 * @param  int    $iTimeLife Cache time to life, seconds
	 * @return bool
	 */
	public static function set($prefix, $identity, $data, $tags=array(), $lifetime=false) {
		if ( !self::$_cacheEnabled ) return false;

		$identity = self::$_cacheKeyPrefix . '_' . $prefix . '_' . md5($identity);
		if ( false === $lifetime ) { $lifetime = self::$_cacheDefaultLifeTime; }
		return self::$_cacheInstanceTag->save($data, $identity, $tags, $lifetime);
	}

	/**
	 * Remove from cache
	 *
	 * @param  string $prefix   Prefix of cache key (e.g. model name)
	 * @param  string $identity Cache key
	 * @return void
	*/
	public static function delete($prefix, $identity) {
		if ( !self::$_cacheEnabled ) return false;

		$identity = self::$_cacheKeyPrefix . '_' . $prefix . '_' . md5($identity);
		return self::$_cacheInstanceTag->remove($identity);
	}

	/**
	 * Get value from local static variable
	 *
	 * @param  string $model    Prefix of cache key (e.g. model name)
	 * @param  string $identity Model identity
	 * @return mixed | bool
	*/
	public static function getStatic($model, $identity) {
		if ( isset(self::$_cacheStatic[$model][$identity]) ) {
			return self::$_cacheStatic[$model][$identity];
		} else {
			return false;
		}
	}

	/**
	 * For debug
	 *
	 * @return array
	*/
	public static function getAllStatic() {
		return self::$_cacheStatic;
	}

	/**
	 * Store value to local static variable
	 *
	 * @param  string $model    Prefix of cache key (e.g. model name)
	 * @param  string $identity Model identity
	 * @param  mixed  $value    Stored value
	 * @return void
	*/
	public static function setStatic($model, $identity, $value) {
		self::$_cacheStatic[$model][$identity] = $value;
	}

	/**
	 * Clean up cache
	 *
	 * @param int   $mode Clean cache mode
	 * @param array $tags Clean by tags ( used for $mode = Zend_Cache::CLEANING_MODE_MATCHING_TAG )
	 * @return bool
	 */
	public static function clean($mode=Zend_Cache::CLEANING_MODE_ALL, $tags=array()) {
		if ( !self::$_cacheEnabled ) return false;

		return self::$_cacheInstanceTag->clean($mode, $tags);
	}

	/**
	 * Count stats for using cache
	 *
	 * @param int    $time   How long method executes
	 * @param string $method Name of method
	 */
	public static function calcStats($time, $method) {
		self::$cacheStats['time'] += $time;
		self::$cacheStats['count']++;

		if ($method=='Dklab_Cache_Backend_Profiler::load') {
			self::$cacheStats['count_get']++;
		}
		if ($method=='Dklab_Cache_Backend_Profiler::save') {
			self::$cacheStats['count_set']++;
		}
	}

	/**
	 * Get stats for using cache
	 *
	 * @return array
	 */
	public static function getStats() {
		return self::$cacheStats;
	}

}