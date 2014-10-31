<?php
/*
	FUNCTIONS:
	jsonResponse($result, $data=array(), $message='')	- делает json_encode
	getRuWeekday($day_number)							- Возвращает день недели на русском по номеру, 0 - понедельник
	getRuMonth($month_number, $mode=1)					- Возвращает месяц на русском по номеру, 1 - январь, mode - вернуть "Январь", или "Января"
	makeActiveLink($text)								- Заменяет в тексте www.domain.com на <a href="http://www.domain.com" target="_blank">www.domain.com</a> и example@mail.com на <a href="mailto:example@mail.com">example@mail.com</a>
	print_arr($var, $die = false, $title = '')			- Распечатать переменную. Если $die = 1, то сделает exit() после распечатки. $title - текст перед распечатанной переменной, можно использовать если распечатывается много разных переменных, что бы не запутаться.
    send_email($email,$msg)                             - Отправляет электронным писмом содержимое переменной $msg на электронный адрес $email
	modelArrayToJson($model_list)						- Возращает json массив моделей. $model_list обычно результат функции find() у модели
*/






/**
 * JSON ответ
 * @result	Результат (1 в случае успеха, 0 при ошибке)
 * @data    Переменные которые нужно передать в JSON объекте
 * @return	void
 */
function jsonResponse($result, $data=array(), $message='') {
	if ( !empty($message) ) {
		$json = json_encode(array('result'=>$result, 'data'=>$data, 'message'=>$message));
	} else {
		$json = json_encode(array('result'=>$result, 'data'=>$data));
	}
	return $json;
}

function getRuWeekday($day_number) {
	$ru_weekdays = array(
		0=>'Понедельник',
		1=>'Вторник',
		2=>'Среда',
		3=>'Четверг',
		4=>'Пятница',
		5=>'Суббота',
		6=>'Воскресенье',
	);
	return $ru_weekdays[$day_number];
}

function getRuMonth($month_number, $mode=1) {
	$ru_months = array(
		1=>array(
			1=>'Январь',
			2=>'Февраль',
			3=>'Март',
			4=>'Апрель',
			5=>'Май',
			6=>'Июнь',
			7=>'Июль',
			8=>'Август',
			9=>'Сентябрь',
			10=>'Октябрь',
			11=>'Ноябрь',
			12=>'Декабрь'
		),
		2=>array(
			1=>'Января',
			2=>'Февраля',
			3=>'Марта',
			4=>'Апреля',
			5=>'Мая',
			6=>'Июня',
			7=>'Июля',
			8=>'Августа',
			9=>'Сентября',
			10=>'Октября',
			11=>'Ноября',
			12=>'Декабря'
		),
	);
	return $ru_months[$mode][$month_number];
}

function makeActiveLink($text)
{
	$text= preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" >$3</a>", $text);
	$text= preg_replace("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" >$3</a>", $text);
	$text= preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\">$2@$3</a>", $text);
	return($text);
}

function print_arr( $var, $die = false, $title = '', $ignore_cookie = false, $return = false ) {

	// if ( $_COOKIE['debug'] ) {

		debug_declare_styles();

		$type = gettype( $var );

		$out = print_r( $var, true );
		$out = htmlspecialchars( $out );
		$out = str_replace('  ', '&nbsp; ', $out );
		$global_count = ++$GLOBALS['debug_global_count'];

		$backtrace = debug_backtrace();
		$count = count($backtrace)-1;
		$backtrace_info = '<input id="debug_window_'.$global_count.'_input" list="debug_window_'.$global_count.'" onclick="this.value=\'\';" oninput="this.select();" class="debug_backtrace_input">
<datalist id="debug_window_'.$global_count.'">';

		foreach ($backtrace as $key=>$line) {
			if ( $key==$count ) break;
			if ( !isset($line['file']) || !isset($line['line']) ) continue;
			$path = explode(DIRECTORY_SEPARATOR, $line['file']);
			$file = array_pop($path);
			$dir = array_pop($path);
			$line = $line['line'];
			$backtrace_info .= '
	<option value="' . $dir . '/' . $file . ':' . $line . '">';
		}
		$backtrace_info .= '
</datalist>
';

		if( $type == 'boolean' ) {
			$content = $var ? 'true' : 'false';

			$out = '
			<div class="debug_main_frame">
				<span class="debug_var_type">('.$type.')</span> '.$backtrace_info.$content.'
				<span class="debug_var_title">'.$title.'</span>
			</div>';
		} else {

			$object_definition = '';

			if ($type=='object') {
				$class_name = get_class($var);
				if ( get_parent_class($var) ) { $object_parent_class='&nbsp;extend&nbsp;'.get_parent_class($var); } else { $object_parent_class=''; }
				$object_definition = '
				<div>
					<a href="javascript://" id="debug_object_definition_'.$global_count.'_lnk" onclick="debug_functions_expand(\'debug_object_definition_'.$global_count.'\');" class="lnkExpand">+</a>&nbsp;&nbsp;&nbsp;&nbsp;<span class="debug_var_type">Functions</span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="debug_var_title"><a href="javascript://" onClick="debug_expand(\'debug_variable_definition_'.$global_count.'\');" id="debug_variable_definition_'.$global_count.'_lnk">[-]</a>&nbsp;&nbsp;'.$title.'</span>
					<div class="debug_object_definition" id="debug_object_definition_'.$global_count.'">
						<ul id="debug_object_definition_vars">';
				foreach ( get_class_vars($class_name) as $class_var) {
					$object_definition .= '<li>' . $class_var . '</li>';
				}
				$object_definition .= '</ul>';

				$object_definition .= '<ul id="debug_object_definition_functions">';
				foreach ( get_class_methods($var) as $method) {
					$object_definition .= '<li>' . $method . '()</li>';
				}
				$object_definition .= '
						</ul>
					</div>
					<hr>
				</div>';

				$content = nl2br( $out );

				$out = '
				<div class="debug_main_frame" id="debug_variable_definition_'.$global_count.'">'.$object_definition.'
					<span class="debug_var_type">('.$type.$object_parent_class.')</span> '.$backtrace_info.$content.'
				</div>';

			} else {
				$content = nl2br( $out );
				$out = '
				<div class="debug_main_frame" id="debug_variable_definition_'.$global_count.'">
					<span class="debug_var_title"><a href="javascript://" onClick="debug_expand(\'debug_variable_definition_'.$global_count.'\');" id="debug_variable_definition_'.$global_count.'_lnk">[-]</a>&nbsp;&nbsp;'.$title.'</span>
					<span class="debug_var_type">('.$type.')</span> '.$backtrace_info.$content.'
				</div>';
			}

		}


		if( !$return ) { echo $out; } else { return $out; }
		if( $die && $die<=$GLOBALS['debug_global_count'] ) { die('...stopped...'); }
	// } else {
		// return;
	// }
}



/*
	Распечатывает массив в виде таблицы.
	Примерный формат переменной $array:
	array(
		0=>array(
			'codeid'=>1,
			'code_user'=>1,
			'nameid'=>'Название',
		),
		1=>array(
			'codeid'=>1,
			'code_user'=>1,
			'nameid'=>'Название',
		),
	)
	Подходит результат от функции db::rows(), т.е. обычное использование функции:

	$query = 'SELECT * FROM accounts WHERE deleted=0';
	print_table(db::rows($query), 1)
*/
function print_table( $array = array(), $die = false, $title = '' ) {
	debug_declare_styles();

		// Если дебаг не включен в конфиге или куках, то выходим
	if ( !empty($_COOKIE['debug']) && !$_COOKIE['debug'] && !config::get('DEBUG', 1) ) return;

	$out = '
	<table class="ellyDebugTable">
		<thead>
			<tr>';
			foreach ( $array[0] as $field=>$value ) {
				$out .= '<th>'.$field.'</th>';
			}
			$out .= '
			</tr>
		</thead>
		<tbody>
		';
	foreach ( $array as $key=>$row ) {
		$out .= '<tr>';
		foreach ( $row as $field=>$value ) {
			$out .= '
			<td>' . $value . '</td>
			';
		}
		$out .= '</tr>';
	}
	$out .= '
		</tbody>
	</table>';

	print $out;

	if ( $die ) { die('...stopped...'); }
}

function print_js( $var )
{
	print '<script type="text/javascript">console.log('.json_encode($var).')</script>';
}

function debug_declare_styles() {
	if (isset($GLOBALS['debug_styles_declared'])) return;
	$GLOBALS['debug_styles_declared'] = true;
	$GLOBALS['debug_global_count']=0;
?>
<html>
<head>
<style type="text/css">
	.debug_main_frame {
		position:relative;
		border:2px inset #666;
		background:#000;
		font-family:Verdana;
		font-size:11px;
		color:#6F6;
		text-align:left;
		margin:20px;
		padding:16px;
		overflow:hidden;
	}
	.debug_main_frame hr { margin:8px 0; border:none; border-top:1px solid #930; height:1px; line-height:1px; }
	.debug_main_frame a:link { color:#900; text-decoration:none; }
	.debug_main_frame a:hover { color:#900; text-decoration:underline; }
	.debug_var_type { color:#f66; }
	.debug_var_title { position:absolute; left:6px; top:2px; color:#999; font-size:10px; }
	.debug_var_title a:link { color:#999; text-decoration:none; }
	.debug_object_definition { display:none; }
	.debug_object_definition ul { padding-left:24px; }

	.debug_backtrace_input {
		background: rgba(255, 0, 122, 0.8);
		border: none;
		color: #fff;
		position: absolute;
		right: 2px;
		top: 0;
		cursor: pointer;
		padding: 2px 4px;
		width: 300px;
	}

	.ellyDebugTable { width:100%; border-collapse:collapse; }
	.ellyDebugTable td,
	.ellyDebugTable th { padding:4px 6px 3px; border:1px solid #aaa; font-family:monospace; text-align:left; }
	.ellyDebugTable th { background:#333; color:white; font-weight:bold; }
	.ellyDebugTable tr:nth-of-type(odd) { background:#eee; }
</style>

<script type="text/javascript">
	function debug_expand(id) {
		var $element = document.getElementById(id);
		var $expand_link = document.getElementById(id + '_lnk');
		if ($expand_link.innerHTML=='[-]') {
			$element.style.height='2px';
			$element.style.padding='16px 0 0';
			$expand_link.innerHTML='[+]';
		} else {
			$element.style.height='auto';
			$element.style.padding='16px';
			$expand_link.innerHTML='[-]';
		}
	}

	function debug_functions_expand(id) {
		var $element = document.getElementById(id);
		var $expand_link = document.getElementById(id + '_lnk');
		if ($element.style.display=='block') {
			$element.style.display='none';
			$expand_link.innerHTML='+';
		} else {
			$element.style.display='block';
			$expand_link.innerHTML='-';
		}
	}
</script>
</head>
<body>
<?php
}

function log_file($str) {
	$file = fopen("./public/temporary/log/debug.txt","a");
	fputs($file, $str . "\r\n");
	fclose($file);
}

if ( isset($_GET['debug']) && $_GET['debug']!='0' ) {
	setcookie('debug', 1);
	$_COOKIE['debug'] = 1;
}

if ( isset($_GET['debug']) && $_GET['debug']=='0' ) {
	setcookie('debug', 0);
	$_COOKIE['debug'] = 0;
}

function encrypt($string)
{
	$key='1q2w3e4r5t';
	$result = '';
	for($i=0; $i<strlen($string); $i++)
	{
		$char = substr($string, $i, 1);
		$keychar = substr($key, ($i % strlen($key))-1, 1);
		$code_symbol=ord($char)^ord($keychar);
		$char = chr($code_symbol);
		$result.=$char;
	}
	$result=rawurlencode($result);
	$tmp=base64_encode($result);
	return $tmp;

}

function decrypt($string)
{
	$key='1q2w3e4r5t';
	$result = '';
	$string = base64_decode($string);
	$string=rawurldecode($string);
	for($i=0; $i<strlen($string); $i++)
	{
		$char = substr($string, $i, 1);
		$keychar = substr($key, ($i % strlen($key))-1, 1);
		$char = chr(ord($char)^ord($keychar));
		$result.=$char;
	}
	  $mas_source=explode("&",$result);
	  for($x=0;$x<count($mas_source);$x++)
	  {
		$mas_temp=explode("=",$mas_source[$x]);
		//$map[mysql_real_escape_string(HTMLSPECIALCHARS($mas_temp[0]))]=mysql_real_escape_string(HTMLSPECIALCHARS($mas_temp[1]));
		  $map[addslashes(HTMLSPECIALCHARS($mas_temp[0]))]=addslashes(HTMLSPECIALCHARS($mas_temp[1]));
	  }

	return $map;

}

function fatal_error($text) {
	print_arr($text, 1);
}
