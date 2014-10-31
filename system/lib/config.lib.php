<?php
class config {

	static $config = array();

	public static function init() {
		self::$config = ( is_readable(SITE_ROOT.'/config.php') ) ? parse_ini_file(SITE_ROOT.'/config.php') : array();
	}

	public static function get($var, $default=NULL) {
		return ( isset(self::$config[$var]) ) ? self::$config[$var] : $default;
	}

	public static function set($var, $value) {
		self::$config[$var] = $value;
	}

}