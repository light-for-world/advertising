<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

use Tracy\Debugger;

/**
 * Tracy\Debugger::dump() shortcut.
 * @tracySkipLocation
 */
function dump($var)
{
	foreach (func_get_args() as $arg) {
		Debugger::dump($arg);
	}
	return $var;
}

function print_arr2($var, $die = false)
{
	if ( Debugger::$currentContext=='ajax' || Debugger::$currentContext=='html' ) {
		Debugger::dump($var);
	} else {
		if ( $die ) {
			Debugger::dump($var);
		} else {
			Debugger::$ajaxDumpVars[] = $var;
		}
	}

	if ( $die ) { print '<br><br>Выполнение скрипта остановлено отладчиком'; exit(); }
	return $var;
}

/**
 * Tracy\Debugger::sql() shortcut.
 * @tracySkipLocation
 */
function dump_sql($query, $duration = 0, $error = '')
{
	$string = '';

	if (
		strpos($query, 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_CATALOG')!==false ||
		strpos($query, 'SELECT ident_seed(QUOTENAME(u.name)')!==false ||
		strpos($query, 'SELECT ISNULL(K.COLUMN_NAME, \'\') FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS')!==false ||
		strpos($query, 'SELECT COLUMN_NAME, COLUMN_KEY, DATA_TYPE, EXTRA FROM `information_schema`.`COLUMNS`')!==false
	) {
		return;
	}

	if ( round($duration, 3) > 0 ) {
		if ( $duration >= .6 ) {
			$color = '#f00';
		} else if ( $duration >= .4 ) {
			$color = '#ff7266';
		} else if ( $duration >= .2 ) {
			$color = '#f80';
		} else if ( $duration >= .1 ) {
			$color = '#fc0';
		} else if ( $duration >= .05 ) {
			$color = '#cfc700';
		} else {
			$color = '#7d7';
		}
		$string .= '<span style="color:'. $color .';">[ ' . str_pad(round($duration, 3), 5, '0') . ' ]</span> ';
	}

	$string .= $query;

	if ( !empty($error) && strpos($error, 'Changed database context to')===false ) {
		$string = $string . '<div style="color:#f00"> Ошибка: ' . $error . '</div> ';
	}

	Debugger::sql($string);
}


/**
 * Tracy\Debugger::barDump() shortcut.
 * @tracySkipLocation
 */
function dump_bar($var)
{
	foreach (func_get_args() as $arg) {
		Debugger::barDump($arg);
	}
	return $var;
}


/**
 * Tracy\Debugger::console() shortcut.
 * @tracySkipLocation
 */
function console($var)
{

}


/**
 * Tracy\Debugger::log() shortcut.
 */
function dlog($var = NULL)
{
	if (func_num_args() === 0) {
		Debugger::log(new Exception, 'dlog');
	}
	foreach (func_get_args() as $arg) {
		Debugger::log($arg, 'dlog');
	}
	return $var;
}
