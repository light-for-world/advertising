<?php
/**
 * Elly Framework Database layer
 */

class db_mssql extends db_placeholder {

	public static function table_structure($table_name) {
		$ret = array(
			'fields'=>array(),
			'primary'=>''
		);

		$fields = self::rows("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_CATALOG='" . config::get('dbNAME') . "' AND TABLE_NAME='" . $table_name . "'");

		foreach ( $fields as $field ) {
			$ret['fields'][$field['COLUMN_NAME']] = $field['DATA_TYPE'];
		}

				// get auto_increment field or not
		$auto_increment = (bool)db::field("SELECT ident_seed(QUOTENAME(u.name) + '.' + QUOTENAME(tb.name)) AS iseed
			FROM sys.all_columns c
			INNER JOIN sys.all_objects tb ON tb.object_id = c.object_id
			INNER JOIN sys.schemas u ON u.schema_id = tb.schema_id
			LEFT OUTER JOIN sys.extended_properties p ON p.major_id = c.object_id AND p.minor_id = c.column_id AND p.class = 1 AND p.name = 'MS_Description'
			WHERE tb.name = N'".$table_name."' AND u.name = N'dbo' ORDER BY c.column_id");

		$primary = db::field("SELECT ISNULL(K.COLUMN_NAME, '') FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS C
					INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K
						ON C.CONSTRAINT_NAME = K.CONSTRAINT_NAME AND K.TABLE_CATALOG='".config::get('dbNAME')."' AND K.TABLE_NAME='".$table_name."'
					WHERE C.CONSTRAINT_TYPE IN ('PRIMARY KEY')");

		$ret['primary'] = array('name'=>$primary, 'auto_increment'=>$auto_increment);

		return $ret;
	}

	public static function connect($dbHost, $dbUser, $dbPass, $dbName) {
		$res = mssql_connect($dbHost, $dbUser, $dbPass, $dbName);

		if ( !$res ) {
			throw new Exception('MSSQL gone away.');
		}

		self::$_connections[] = $res;

		if ( !mssql_select_db($dbName, $res) ) {
			throw new Exception('No Database.');
		}
	}

	public static function query() {
		$args = func_get_args();
		foreach ($args as $key=>$val) {
			$args[$key] = iconv("utf-8", "cp1251", $val);
		}
		$query = self::placeholder($args);
		$start = self::microtime();
		$res = mssql_query($query);

		self::debug_log($query, self::microtime()-$start);
		dump_sql($query, self::microtime()-$start, db::sql_error());

		return $res;
	}

	public static function rows() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$rows = array();
		while( $row = mssql_fetch_assoc($res) ) {
			foreach ($row as $key=>$val) {
				$row[$key] = iconv("cp1251", "utf-8//IGNORE", $val);
			}
			$rows[] = $row;
		}
		return $rows;
	}

	public static function rows_indexed() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$rows = array();
		while( $row = mssql_fetch_assoc($res) ) {
			foreach ($row as $key=>$val) {
				$row[$key] = iconv("cp1251", "utf-8//IGNORE", $val);
			}
			$rows[array_shift($row)] = $row;
		}

		return $rows;
	}

	public static function column() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$rows = array();
		while( $row = mssql_fetch_assoc($res) ) {
			$rows[] = iconv("cp1251", "utf-8//IGNORE", array_shift($row));
		}

		return $rows;
	}

	public static function row() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$row = mssql_fetch_assoc($res);
		foreach ($row as $key=>$val) {
			$row[$key] = iconv("cp1251", "utf-8//IGNORE", $val);
		}

		return $row;
	}

	public static function field() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$row = mssql_fetch_array($res);
		return $row ? $row[0] : null;
	}

	public static function insert($name, $values, $schema = array()) {
		$fields_array = array();
		$values_array = array();

		foreach ( $values as $key=>$value ) {
			$fields_array[] = '[' . $key . ']';
			if ( isset($schema[$key]) && ($schema[$key]=='date' || $schema[$key]=='datetime' || $schema[$key]=='time') ) {
				$value = ( empty($value) ) ? 'NULL' : "'" . self::real_escape($value) . "'";
			} else {
				$value = "'" . self::real_escape($value) . "'";
			}
			$values_array[] = $value;
		}
		$fields = implode(', ', $fields_array);
		$values = implode(', ', $values_array);
		$query = iconv("utf-8", "cp1251", "INSERT INTO [{$name}] ({$fields}) VALUES ({$values})");

		$start = self::microtime();
		$res = mssql_query($query);
		self::debug_log($query, self::microtime()-$start);
		dump_sql($query, self::microtime()-$start, db::sql_error());

		return $res;
	}

	public static function update($name, $values, $where, $schema = array()) {
		$fields_array = array();

		foreach ( $values as $key=>$value ) {
			if ( isset($schema[$key]) && ($schema[$key]=='date' || $schema[$key]=='datetime' || $schema[$key]=='time') ) {
				$value = ( empty($value) ) ? 'NULL' : "'" . self::real_escape($value) . "'";
			} else {
				$value = "'" . self::real_escape($value) . "'";
			}

			$fields_array[] = "[{$key}] = ".$value;
		}
		$fields = implode(', ', $fields_array);
		$query = iconv("utf-8", "cp1251", "UPDATE [{$name}] SET {$fields} WHERE " . $where);

		$start = self::microtime();
		$res = mssql_query($query);
		self::debug_log($query, self::microtime()-$start);
		dump_sql($query, self::microtime()-$start, db::sql_error());

		return $res;
	}

	public static function procedure($storedproc, $params){
		$start = self::microtime();
		$varlist = "";
		$setlist = "";
		$paramlist = "";
		$outs = "";
		foreach ($params as $key => $value) {
			$quote = strpos($value['type'],'char')!==false;
			$varlist .= "@$key ".$value['type'].",\n";
			if (isset($value['value'])) {
				$setlist .= "set @".$key."=".($quote?"'":'').$value['value'].($quote?"'\n":"\n");
			}
			$paramlist .= " @".$key.(isset($value['out'])?' output,':',');
			if (isset($value['out'])) {
				$outs .= "@$key as $key,";
			}
		}
		if (strlen($paramlist)) {
			$paramlist = substr($paramlist,0,strlen($paramlist)-1);
		}

		$stmt = "begin try\n";
		$stmt .= "declare\n";
		$stmt .= "@ret int";
		if (strlen($varlist)) {
			$stmt .= ",\n";
			$stmt .= $varlist;
			$stmt = substr($stmt,0,strlen($stmt)-2);
		}
		else {
			$stmt .= "\n";
		}
		$outs = "@ret as ret,".$outs;
		$outs = substr($outs,0,strlen($outs)-1);

		$stmt .= "\n".$setlist;
		$stmt .= "exec @ret = ".$storedproc.$paramlist."\n";
		$stmt .= "select ".$outs."\n";
		$stmt .= "end try\n";
		$stmt .= "begin catch\n";
		$stmt .= "select error_number() as ret,error_message() as errorMsg\n";
		$stmt .= "end catch\n";

		return self::row($stmt);
	}

	public static function real_escape($unescaped_string) {
		if ($unescaped_string instanceof file) {
			return str_replace(array("'"),array('"'),json_encode($unescaped_string->toArray()));
		} else if ( $unescaped_string instanceof DateTime ) {
			return self::format_date($unescaped_string);
		} else if ( is_array($unescaped_string) ) {
			return str_replace(array("'"),array('"'),json_encode($unescaped_string));
		} else {
			return str_replace(array("'"),array('"'),$unescaped_string);
		}
	}

	public static function insert_id() {
		return self::field('SELECT SCOPE_IDENTITY() AS ins_id');
	}

	public static function affected_rows() {
		return self::field('SELECT @@ROWCOUNT AS rows');
	}

	public static function sql_error() {
		return mssql_get_last_message();
	}
}


class db_mysql extends db_placeholder {

	public static function table_structure($table_name) {
		$ret = array(
			'fields'=>array(),
			'primary'=>''
		);

		$fields = self::rows("SELECT COLUMN_NAME, COLUMN_KEY, DATA_TYPE, EXTRA FROM `information_schema`.`COLUMNS` WHERE TABLE_SCHEMA='".config::get('dbNAME')."' AND TABLE_NAME='".$table_name."'");

		foreach ($fields as $field) {
			$ret['fields'][$field['COLUMN_NAME']] = $field['DATA_TYPE'];
			if ( $field['COLUMN_KEY']=='PRI' ) {
				$ret['primary'] = array('name'=>$field['COLUMN_NAME'], 'auto_increment'=>( $field['EXTRA']=='auto_increment' ));
			}
		}

		return $ret;
	}

	public static function connect($dbHost, $dbUser, $dbPass) {
		$res = mysql_connect($dbHost, $dbUser, $dbPass);

		if ( !$res ) {
			throw new Exception('MySQL gone away.');
		}

		self::$_connections[] = $res;

		if( !mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'", self::$_connections[0]) ) {
			throw new Exception('MySQL UTF8 error.');
		}

		if( !mysql_query('SET NAMES utf8', self::$_connections[0]) ) {
			throw new Exception('MySQL SET NAMES error.');
		}

		if( !mysql_select_db(config::get('dbNAME'), self::$_connections[0]) ) {
			throw new Exception('No Database');
		}
	}

	public static function query() {
		$query = self::placeholder(func_get_args());
		$start = self::microtime();
		$res = mysql_query($query);
		self::debug_log($query, self::microtime()-$start);
		dump_sql($query, self::microtime()-$start, db::sql_error());
		return $res;
	}

	public static function rows() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$rows = array();
		while( $row = mysql_fetch_assoc($res) ) {
			$rows[] = $row;
		}
		return $rows;
	}

	public static function rows_indexed() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$rows = array();
		while( $row = mysql_fetch_assoc($res) ) {
			$rows[array_shift($row)] = $row;
		}
		return $rows;
	}

	public static function column() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		if ( !is_resource($res) ) return false;

		$rows = array();
		while( $row = mysql_fetch_assoc($res) ) {
			$rows[] = array_shift($row);
		}

		return $rows;
	}

	public static function row() {
		$query = self::placeholder(func_get_args());
		$res = self::query($query);
		return ( !is_resource($res) ) ? false : mysql_fetch_assoc($res);
	}

	public static function field() {
		$query = self::placeholder(func_get_args());
		$row = mysql_fetch_array(self::query($query), MYSQL_NUM);
		return $row ? $row[0] : null;
	}

	public static function insert($name, $values, $schema = array()) {
		$fields_array = array();

		foreach ( $values as $key=>$value ) {
			if ( ($schema[$key]=='bit' || $schema[$key]=='int' || $schema[$key]=='tinyint' || $schema[$key]=='float' || $schema[$key]=='money' || $schema[$key]=='bigint') && $value=='' ) {
				$value = 0;
			}
			$fields_array[] = '`' . $key . '`';
			$values_array[] = ( isset($schema[$key]) && ( $schema[$key]=='bit' || $schema[$key]=='int' || $schema[$key]=='tinyint' || $schema[$key]=='float' || $schema[$key]=='money' || $schema[$key]=='bigint' ) )
								? self::real_escape($value)
								: '"' . self::real_escape($value) . '"';
		}
		$fields = implode(', ', $fields_array);
		$values = implode(', ', $values_array);
		$query = "INSERT INTO `{$name}` ({$fields}) VALUES ({$values})";

		$start = self::microtime();
		$res = mysql_query($query);
		self::debug_log($query, self::microtime()-$start);
		dump_sql($query, self::microtime()-$start, db::sql_error());

		return $res;
	}

	public static function update($name, $values, $where, $schema = array()) {
		$fields_array = array();

		foreach ( $values as $key=>$value ) {
			$fields_array[] = ( isset($schema[$key]) && ( $schema[$key]=='bit' || $schema[$key]=='int' || $schema[$key]=='tinyint' || $schema[$key]=='float' || $schema[$key]=='money' || $schema[$key]=='bigint' ) )
								? "`{$key}` = ".self::real_escape($value)
								: "`{$key}` = '".self::real_escape($value)."'";
		}
		$fields = implode(', ', $fields_array);
		$query = "UPDATE `{$name}` SET {$fields} WHERE " . $where;

		$start = self::microtime();
		$res = mysql_query($query);
		self::debug_log($query, self::microtime()-$start);
		dump_sql($query, self::microtime()-$start, db::sql_error());

		return $res;
	}

	public static function real_escape($unescaped_string) {
		if ( $unescaped_string instanceof file ) {
			return mysql_real_escape_string(json_encode($unescaped_string->toArray()));
		} else if ( $unescaped_string instanceof DateTime ) {
			return self::format_date($unescaped_string);
		} else if ( is_array($unescaped_string) ) {
			return mysql_real_escape_string(json_encode($unescaped_string));
		} else {
			return mysql_real_escape_string($unescaped_string);
		}
	}

	public static function insert_id() {
		return mysql_insert_id();
	}

	public static function affected_rows() {
		return mysql_affected_rows();
	}

	public static function sql_error() {
		return mysql_error();
	}
}




abstract class db_placeholder {

	static protected $_connections = array();
	static protected $debug = false;
	static protected $debug_mode = '';
	static protected $total_time = 0;
	static protected $script_start = 0;
	static protected $script_end = 0;
	static protected $fh;					// file handler for profiler.log file

	public static function format_date($timestamp) {
		if ( is_object($timestamp) ) {
			return $timestamp->format('Y-m-d\TH:i:s');
		} else {
			return date('Y-m-d\TH:i:s', $timestamp);
		}
	}

	public static function debug() {
		db::$debug = true;
	}

	public static function debug_log($sql, $query_time) {
		if ( db::$debug_mode=='file' ) {
			$strString = str_pad(round($query_time,7),9) . "\t" . $sql;
			db::$total_time += $query_time;
			fwrite(db::$fh, $strString."\r\n");
		}

		$show_error = false;

		if ( $_COOKIE['debug']==1 ) {
			$last_error = error_get_last();
			if ( isset($last_error['message']) && (strpos($last_error['message'], 'mssql_query')!==false || strpos($last_error['message'], 'mysql_query')!==false) ) {
				$show_error = true;
			}
		}

		if ( db::$debug || $show_error ) {
			print_arr2($sql, 0, db::sql_error());
		}
	}

	public static function debug_start($mode='file') {
		db::$script_start = db::microtime();
		db::$debug_mode = $mode;
		db::$fh = fopen(SITE_ROOT.'/public/temporary/log/db_profiler.log', 'a');
		fwrite(db::$fh, str_pad('  '.date('d.m.Y H:i:s').'  ', 100, '-', STR_PAD_BOTH)."\r\n");
		fwrite(db::$fh, str_pad('  '.$_SERVER['REQUEST_URI'].'  ', 100, '-', STR_PAD_BOTH)."\r\n");
	}

	public static function debug_finish() {
		db::$script_end = db::microtime();
		$total_php_time = db::$script_end - db::$script_start - db::$total_time;
		fwrite(db::$fh, str_pad('  Total DB time: '.db::$total_time.'; Total PHP time: '.$total_php_time.'  ', 100, '-', STR_PAD_BOTH)."\r\n");
		fwrite(db::$fh, str_pad('  Memory: '.(memory_get_peak_usage()/1024).'kb / Real memory: '.(memory_get_peak_usage(true)/1024).'kb  ', 100, '-', STR_PAD_BOTH)."\r\n\r\n");
	}

	public static function microtime() {
		list($usec, $sec) = explode(' ',microtime());
		return ((float)$usec + (float)$sec);
	}

	public static function compile_placeholder($query_tpl) {
		$compiled = array();
		$i = 0;			// placeholders counter
		$p = 0;			// current position
		$prev_p = 0;	// previous position

		while ( false !== ($p = strpos($query_tpl, '?', $p)) ) {
			$compiled[] = substr($query_tpl, $prev_p, $p-$prev_p);

			$type_char = $char = $query_tpl{$p-1};

			switch ( $type_char ) {
				case '"': case "'": case '`':
					$type = $type_char;		// string
					break;
				default:
					$type = '';				// integer
					break;
			}

			$next_char = isset($query_tpl{$p+1}) ? $query_tpl{$p+1} : null;
			if ( $next_char === '@' ) {		// array list
				$compiled[] = array($i++, $type, '@');
				$prev_p = ($p=$p+2);
			}
			else {
				$compiled[] = array($i++, $type);
				$prev_p = ++$p;
			}
		}

		$tail_length = (strlen($query_tpl) - $prev_p);
		if ( $tail_length ) {
			$compiled[] = substr($query_tpl, - $tail_length);
		}

		return $compiled;
	}

	public static function placeholder($arguments) {
		$c_query = array_shift($arguments);

		if ( !is_array($c_query) ) {
			$c_query = self::compile_placeholder($c_query);
		}

		$query = '';

		foreach ( $c_query as $piece )
		{
			if ( !is_array($piece) ) {
				$query .= $piece;
				continue;
			}

			list( $index, $type ) = $piece;

			if ( isset($piece[2]) ) // array value
			{
				$array = $arguments[$index];

				switch ( $type ) {
					case '"': case "'": case '`':
						$query .= implode("$type,$type", array_map(array(db, 'real_escape'), $array));
						break;
					default:
						$query .= implode(",", array_map('intval', $array));
						break;
				}
			}
			else // scalar value
			{
				$var = $arguments[$index];

				switch ( $type ) {
					case '"': case "'": case '`':
						$query .= db::real_escape($var);
						break;
					default:
						$query .= (int)$var;
						break;
				}
			}
		}
		return $query;
	}
}

	// class db impliments functions from db_mysql or db_mssql class
if ( config::get('dbTYPE')=='mssql' ) {

	class db extends db_mssql{};

} else {

	class db extends db_mysql{};

}