<?php
class utils {

	/** Ýêñïîðò â ýêñåëü.
	* Ôóíêöèÿ ïðîñòî äîáàâëÿåò çàãîëîâêè, íåîáõîäèìûå äëÿ ñêà÷èâàíèÿ ôàéëà, ïå÷àòàåò $html è äåëàåò exit()
	* Ïðèìåð èñïîëüçîâàíèÿ:
		$users = user::find();
		$fields = array(
			'fio'=>'ÔÈÎ',
			'phone'=>'Íîìåð òåëåôîíà',
			'email'=>'Ýëåêòðîííûé àäðåñ',
		);
		export_excel($fields, $users)
	*/
	public static function export_excel($fields, $data, $filename='') {

		$table = '<table><tr>';

		foreach($fields as $field){
			$table .= '<td>'.iconv('utf-8','cp1251',$field).'</td>';
		}

		$table .= '</tr>';

		foreach($data as $item){
			$table .= '<tr>';

			if(is_array($item)){
				foreach($fields as $key => $field){
					$table .= '<td style="mso-number-format:\'\@\'">'.iconv('utf-8','cp1251',$item[$key]).'</td>';
				}
			} else {
				foreach($fields as $key => $field){
					$table .= '<td style="mso-number-format:\'\@\'">'.iconv('utf-8','cp1251',$item->$key).'</td>';
				}
			}

			$table .= '</tr>';
		}

		$table .= '</table>';

		$filename = ( $filename ) ? str_replace(' ', '-', $filename).'.xls' : date('d-m-YY') . '.xls';

		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.ms-excel; format=attachment;');
		header("Content-Disposition: attachment; filename=".$filename);
		header("Content-Transfer-Encoding: binary");

		print '<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=WINDOWS-1251">';
		print $table;

		exit();
	}

	/** Ýêñïîðò â âîðä.
	* Ôóíêöèÿ ïðîñòî äîáàâëÿåò çàãîëîâêè, íåîáõîäèìûå äëÿ ñêà÷èâàíèÿ ôàéëà, ïå÷àòàåò $html è äåëàåò exit()
	* Ïðèìåð èñïîëüçîâàíèÿ:
		export_word('<h1>Ïðèâåò Àòàé</h1>', $users);
	*/
	public static function export_word($text, $filename='') {

		$word_xmlns = "<xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
		$word_xml_settings = "<xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml>";
		$word_landscape_style = "@page {size:8.5in 11.0in; margin:0.5in 0.31in 0.42in 0.25in;} div.Section1{page:Section1;}";
		$word_landscape_div_start = "<div class='Section1'>";
		$word_landscape_div_end = "</div>";

		$filename = ( $filename ) ? str_replace(' ', '-', $filename).'.doc' : date('d-m-Y') . '.doc';

		$content = '
		<html '.$word_xmlns.'>
		<head>'.$word_xml_settings.'<style type="text/css">
		'.$word_landscape_style.' table,td {border:0px solid #FFFFFF;}</style>
		</head>
		<body>'.$word_landscape_div_start.$text.$word_landscape_div_end.'</body>
		</html>
		';

		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Type: application/msword; format=attachment;');
		header("Content-Disposition: attachment; filename=".$filename);
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . strlen($content));

		print '<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">';
		print $content;

		exit();
	}

	public static function pagination($total_pages,$page) {
		$html = '<ul class="elly_pagination pagination">';
		$display_first = '';
		$display_last = '';
		if($page == 1) $display_first .= 'display:none';
		if($page == $total_pages) $display_last .= 'display:none';

		$html .= '<li style="'.$display_first.'" ><a href="#" class="first">&laquo;</a></li>
		<li style="'.$display_first.'" ><a href="#" class="previous">&lsaquo;</a></li>
		<li><span style="padding:5px;"><input style="width:30px; padding: 1px 8px 1px 8px;" value="'.$page.'" /></span></li>
		<li style="'.$display_last.'" ><a href="#" class="next">&rsaquo;</a></li>
		<li style="'.$display_last.'" ><a href="#" class="last">&raquo;</a></li></ul>';

		return $html;
	}


	/** Функция для обрезки строки под определенное количество знаков.
	* $str String строка, которую нужно обрезать
	* $length Int количество символов, после которого обрезать строку
	* $str_ending String Символы, которые появятся в конце строки после обрезки. Если длина строки меньше, чем $length, то эти символы не добавляются
	* $preserve_word Bool Если true, то обрезает до ближайшего пробела (т.е. обрезает по слову), а не точно по длине $length
	*/
	public static function truncate($str, $length=64, $str_ending='...', $preserve_word=true) {

		$str_len = mb_strlen($str, 'UTF-8');
		if ( $str_len<=$length+3 ) return $str;

		$plus_pos = 1;
		$minus_pos = 0;

		if ( !$preserve_word ) {
			$str = mb_substr($str, 0, $length);

			return $str . $str_ending;
		}

		while (true) {
				// если дошли до конца строки, выходим из цикла, $str_ending не ставим
			if ( $str_len<=$length+$plus_pos || $minus_pos>=10 ) {
				$str = mb_substr($str, 0, $length, 'UTF-8');
				break;
			}

				// ищем ближайший пробел от базовой позиции $length
			$char = mb_substr($str, $length+$plus_pos, 1, 'UTF-8');
			if ( $char===' ' || $char==="\r\n" || $char==="\n" || $char==="." || $char==="," ) {
				$str = mb_substr($str, 0, $length+$plus_pos, 'UTF-8');
				break;
			}

			$char = mb_substr($str, $length-$minus_pos, 1, 'UTF-8');
			if ( $char===' ' || $char==="\r\n" || $char==="\n" || $char==="." || $char==="," ) {
				$str = mb_substr($str, 0, $length-$minus_pos, 'UTF-8');
				break;
			}

			$plus_pos++;
			$minus_pos++;
		}

		return $str . $str_ending;
	}

	/** Функция для обрезки строки под определенное количество знаков.
	* $str String строка, которую нужно обрезать
	* $length Int количество символов, после которого обрезать строку
	* $str_ending String Символы, которые появятся в конце строки после обрезки. Если длина строки меньше, чем $length, то эти символы не добавляются
	* $preserve_word Bool Если true, то обрезает до ближайшего пробела (т.е. обрезает по слову), а не точно по длине $length
	*/
	public static function date_format($date, $type=1) {

		$month_array = array(
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
			12=>'Декабря',
		);

		if ( is_object($date) ) {

			$str = $date->format('d') . ' ' . $month_array[$date->format('n')] . ', ' . $date->format('H') . ':' . $date->format('i');

		} else {

			$str = date('d', $date) . ' ' . $month_array[date('n', $date)] . ', ' . date('H', $date) . ':' . date('i', $date);

		}

		return $str;
	}


	public static function create_guid() {
		$uid = uniqid('', true);

		$data = $_SERVER['REQUEST_TIME'];
		$data .= $_SERVER['HTTP_USER_AGENT'];
		$data .= $_SERVER['REMOTE_ADDR'];
		$data .= $_SERVER['REMOTE_PORT'];

		$hash = strtoupper(hash('ripemd128', $uid . md5($data)));
		$guid = substr($hash,  0,  4) . '-' .
				substr($hash,  8,  4) . '-' .
				substr($hash, 12,  4) . '-' .
				substr($hash, 16,  4) ;

		return $guid;
	}


}