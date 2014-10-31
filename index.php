<?php
	// initiate global header
define('SITE_ROOT', realpath(dirname(__FILE__)));
date_default_timezone_set('Asia/Almaty');
session_start();

	// initiate autoload
require_once SITE_ROOT.'/system/lib/elly_functions.php';
require_once SITE_ROOT.'/system/lib/autoloader.lib.php';
require_once SITE_ROOT.'/system/mod/tracy/tracy.php';

if ( isset($_GET['m']) ) {
    $_GET = array('index@index'=>'', 'm'=>$_GET['m']);
}

	// initiate core functions
autoloader::init();
config::init();
cache::init();

	// if DEBUG=1, show php errors
if ( config::get('DEBUG', 0) || request::req('debug') ) {
	ini_set('display_errors', true);
	error_reporting(E_ALL);
	$_COOKIE['debug'] = 1;
	config::set('DEBUG', 1);
	Tracy\Debugger::enable();
} else {
	ini_set('display_errors', false);
}

if ( config::get('dbHOST')!='' ) {
	try {
		db::connect(
			config::get('dbHOST'),
			config::get('dbUSER'),
			config::get('dbPASS'),
			config::get('dbNAME')
		);
	} catch (Exception $e) {
		include SITE_ROOT.'/templates/fatal_error.html';
		exit();
	}
}

if ( config::get('DB_LOG') && config::get('dbHOST')!='' ) { db::debug_start(); }

$router = new router();
$router->delegate();

if ( config::get('DB_LOG') && config::get('dbHOST')!='' ) { db::debug_finish(); }