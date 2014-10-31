<?php
class autoloader {

	public static $loader;
	public static $lib_map = array(
		'autoloader'=>'lib/autoloader.lib.php',
		'cache'=>'lib/cache.lib.php',
		'config'=>'lib/config.lib.php',
		'controller'=>'lib/controller.lib.php',
		'db'=>'lib/db.lib.php',
		'file'=>'lib/file.lib.php',
		'mail'=>'lib/mail.lib.php',
		'model'=>'lib/model.lib.php',
		'request'=>'lib/request.lib.php',
		'router'=>'lib/router.lib.php',
		'template'=>'lib/template.lib.php',
		'upload'=>'lib/upload.lib.php',
		'utils'=>'lib/utils.lib.php',
		'widget'=>'lib/widget.lib.php',
	);

	public static function init() {
		if (self::$loader == NULL)
			self::$loader = new self();

		return self::$loader;
	}

	public function __construct() {
		set_include_path(SITE_ROOT);
		// spl_autoload_register(array($this, 'widgets'));
		spl_autoload_register(array($this, 'controllers'));
		spl_autoload_register(array($this, 'library'));
		spl_autoload_register(array($this, 'model'));
	}

	public function model($class) {
		if ( substr($class, -6) === 'Widget' || substr($class, -10) === 'Controller' ) {
			return;
		}

		$filename = '/models/' . $class . '.php';
		if ( !is_readable(SITE_ROOT . $filename) ) {
			fatal_error('Не найден файл ' . $filename);
		}
		require SITE_ROOT . $filename;

			// вызываем функцию инициализации у модели что бы узнать поля и типы полей в таблице. Если класс model то вызывать функцию не надо
		if ( $class!=='model' && is_callable(array($class, '__init')) ) {
			call_user_func(array($class, '__init'));
		}
	}

	public function library($class) {
		if ( substr($class, -6) === 'Widget' || substr($class, -10) === 'Controller' || !isset(self::$lib_map[$class]) ) {
			return;
		}

		require SITE_ROOT.'/system/'.self::$lib_map[$class];
	}

	public function controllers($class) {
		if ( substr($class, -10) !== 'Controller' ) {
			return;
		}

		$class = preg_replace('/Controller$/ui','',$class);

		$filename = '/controllers/' . $class . '.php';
		if ( !is_readable(SITE_ROOT . $filename) ) {
			fatal_error('Не найден файл ' . $filename);
		}
		require SITE_ROOT . $filename;
	}

	// public function widgets($class) {
	// 	if ( substr($class, -6) !== 'Widget' ) {
	// 		return;
	// 	}

	// 	$class = preg_replace('/Widget$/ui','',$class);
	// 	require SITE_ROOT.'/widgets/'.$class.'/'.$class.'.widget.php';
	// }

}