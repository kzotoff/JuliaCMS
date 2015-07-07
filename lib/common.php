<?php

// шаблон для проверки названия алиаса
define('REGEXP_ALIAS', '~^[a-zA-Z0-9\_\-]+$~ui');

// input filtering options
define('FILTER_GET_BY_LIST', 1);  // use _GET, return only fields that are in filter array
define('FILTER_POST_BY_LIST', 2); // use _POST, return only fields that are in filter array
define('FILTER_GET_FULL', 3);     // use _GET, return filtered values wich keys are in filter array, the rest values come "as is"
define('FILTER_POST_FULL', 4);    // use _POST, return filtered values wich keys are in filter array, the rest values come "as is"

// каталоги пользовательских файлов
Registry::Set('USERFILES_DIRS', array(
	'css' => array(
		'caption'         => 'CSS',                                                  // заголовок для списка в админке
		'dir'             => 'userfiles/css/',                                       // папка для хранения
		'regexp_full'     => '~^userfiles/css/[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.css$~',  // регэксп для проверки полного пути (при редактировании)
		'regexp_filename' =>               '~^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.css$~'   // для проверки при загрузке
	),
	'js' => array(
		'caption'         => 'scripts',
		'dir'             => 'userfiles/js/',
		'regexp_full'     => '~^userfiles/js/[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.js$~',
		'regexp_filename' =>              '~^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.js$~',
		),
	'images' => array(
		'caption'         => 'images',
		'dir'             => 'userfiles/images/',
		'regexp_full'     => '~^userfiles/images/[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.(png|gif|jpg|bmp)+$~i',
		'regexp_filename' =>                  '~^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.(png|gif|jpg|bmp)+$~i',
		),
	'pages' => array(
		'caption'         => 'pages',
		'dir'             => 'userfiles/pages/',
		'regexp_full'     => '~^userfiles/pages/[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.(html|htm|php|xml)+$~i',
		'regexp_filename' =>                  '~^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.(html|htm|php|xml)+$~i',
		),
	'xsl' => array(
		'caption'         => 'XSL stylesheets',
		'dir'             => 'userfiles/xsl/',
		'regexp_full'     => '~^userfiles/xsl/[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.(xsl|xml)$~',
		'regexp_filename' =>               '~^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.(xsl|xml)$~',
		),
	'trash' => array(
		'caption'         => 'trash',
		'dir'             => 'userfiles/trash/',
		'regexp_full'     => '~^userfiles/trash/[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.[a-zA-Z0-9]+$~',
		'regexp_filename' =>                 '~^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.[a-zA-Z0-9]+$~',
		),
	'special' => array( // для файлов в корневой папке
		'caption'         => '*** special',
		'dir'             => './',
		'regexp_full'     => '~^\./(?!jquery)[a-zA-Z0-9\-_]+\.(html|css|js)$~',
		'regexp_filename' => '~^(?!jquery)[a-zA-Z0-9\-_]+\.(html|css|js)$~',
		),
	'files' => array( // должен быть последним, из-за особенностей работы алгоритма module_filemanager_get_userfolder_params
		'caption'         => 'files',
		'dir'             => 'userfiles/files/',
		'regexp_full'     => '~^userfiles/files/[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.[a-zA-Z0-9]+$~',
		'regexp_filename' =>                 '~^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*\.[a-zA-Z0-9]+$~',
		)
));

/**
 * Searches string for a substring, inserts some string before matched one
 *
 * @param string $search string to look up
 * @param string $insert what to insert before $search
 * @param string $text text to find atan
 * @return string modified text
 */
function str_insert_before($search, $insert, $text) {
	$pos = strpos($text, $search);
	$text = substr_replace($text, $insert, $pos, 0);
	return $text;
}


/**
 * Searches a string for a substring, inserts some string after matched one
 *
 * @param string $search string to look up
 * @param string $insert what to insert after $search
 * @param string $text text to find atan
 * @return string modified text
 */
function str_insert_after($search, $insert, $text) {
	$pos = strpos($text, $search);
	$text = substr_replace($text, $search.$insert, $pos, strlen($search));
	return $text;
}


/**
 * Adds a CSS stylesheet link to the template
 *
 * @param string $template HTML page
 * @param string $link CSS href
 * @return modified HTML
 */
function add_CSS($template, $link) {
	if (trim($link) > '') {
		$add = '<link rel="stylesheet" href="%s" type="text/css" />';
		$template = str_insert_before('</head>', sprintf($add, $link).PHP_EOL, $template);
	}
	return $template;
}


/**
 * Adds a meta to the template
 *
 * @param string $template HTML page
 * @param string $link meta description
 * @return modified HTML
 */
function add_meta($template, $attr, $value, $content) {
	if (trim($value) > '') {
		$add = '<meta '.($attr ?: 'name').'="'.$value.'" '.($content>''? 'content="'.$content.'" ' : '').'/>';
		$template = str_insert_before('</head>', $add.PHP_EOL, $template);
	}
	return $template;
}


/**
 * Adds a javascript link to the template
 *
 * @param string $template HTML page
 * @param string $link script href
 * @return modified HTML
 */
function add_JS($template, $link) {
	if (trim($link) > '') {
		$add = '<script src="%s" type="text/javascript"></script>';
		$template = str_insert_before('</head>', sprintf($add, $link).PHP_EOL, $template);
	}
	return $template;
}

/**
 * Replaces entire HTML body with given content
 *
 * @param string $template HTML page
 * @param string $content HTML to replace with
 * @return modified HTML
 */
function content_replace_body($template, $content) {
	return preg_replace('~<body(.*?)>.*</body>~smui', '<body$1>'.$content.'</body>', $template, 1);
}

/**
 * Replaces HTML header title with given content
 *
 * @param string $template HTML page
 * @param string $content title to replace with
 * @return modified HTML
 */
function content_replace_title($template, $content) {
	return preg_replace('~<title(.*?)>.*</title>~smui', '<title$1>'.$content.'</title>', $template, 1);
}


/**
 * parser string like <template module="menu" id="1" type="standard" /> into separate params
 *
 * @param string $str string to parse
 * @return array parsed data
 */
function parse_plugin_template($str) {
	$result = array();

	// разберем на атомы, если где-то есть знак "равно", это для нас
	preg_match_all('~\s([a-zA-Z\-_]+)="([^"]+)"~', $str, $params);
	$result = array_combine($params[1], $params[2]);

	return $result;
}


/**
 * logger wrapper - log data
 *
 * @param string $message message to log
 * @param int|array $level minimal event level to collect or parameter array. refer logger.php.
 */ 
function logthis($message, $level = false) {
	if ($level === false) {
		$level = ZLogger::getDefaultLogLevel();
	}
	ZLogger::log(@$message, @$level);
}


/**
 * logger wrapper - output
 * 
 * @param int $level minimal event level display, see logger.php for possible values
 * @param array $options output level to override initial
 */
function logger_out($level = ZLOG_LEVEL_MESSAGE, $options = array()) {
	return ZLogger::singleton()->flushAll($level, $options);
}

/**
 * XSL tranform wrapper
 * 
 * @param string $xml source data to transform or filename to load from
 * @param string $xslt transformation XML or filename to load from
 * @param bool $xml_is_string true if $xml is XML data (default), false if filename
 * @param bool $xsl_is_string true if $xslt is XML data, false (default) if filename
 */
function XSLTransform($xml_source, $xsl_source, $xml_is_string = true, $xsl_is_string = false) {
	
	// load source stylesheet
	$xml_doc = new DOMDocument('1.0', 'utf-8');

	$loaded_ok = $xml_is_string ? $xml_doc->loadXML($xml_source) : $xml_doc->load($xml_source);
	if (!$loaded_ok) {
		trigger_error('Loading XML ' . (!$isString ? 'from file "' . $xmlSource . '" ' : '') . 'Failed!', E_USER_ERROR);
	}

	// load transformer stylesheet
	$xsl_doc = new DOMDocument('1.0', 'utf-8');
	
	$loaded_ok = $xsl_is_string ? $xsl_doc->loadXML($xsl_source) : $xsl_doc->load($xsl_source);
	if (!$loaded_ok) {
		trigger_error('Loading XSL ' . (!$isString ? 'from file "' . $xmlSource . '" ' : '') . 'Failed!', E_USER_ERROR);
	}

	// transformer instance
	$processor = new XSLTProcessor();
	if (!($processor->importStylesheet($xsl_doc))) {
		trigger_error('Importing stylesheet has failed Failed!', E_USER_ERROR);
	}

	// transform and return
	return $processor->transformToXml($xml_doc);
}


/**
 * terminate script and get out
 *
 * @param string $data message to display
 * @param string $http HTTP responce to send. will automatically split into code and message,
 *	so just use something like "403 get out!". Will be ignored without code
 */
function terminate($data = '', $http = '') {
	
	// header must be the first
	if (($http > '') && is_numeric($code = substr($http, 0, 3))) {
		header(substr($http, 3), true, $code);
	}

	// user-readable data
	if ($data > '') {
		echo $data;
	} elseif ($http > '') {
		echo '<h1>'.$http.'</h1>';
	}

	logthis('terminating: '.$data);
	//logger_out();
	
	Registry::Get('db')->close();
	exit;
	
}


/**
 * GET / POST filtering
 *
 * uses filter_input_array to filter GET and POST data
 * POST data with the same key will override GET one
 *
 * @param array $filter filter data to pass to filter_input_array
 * @param array $sources what to filter. Array containing INPUT_GET or INPUT_POST or both.
 *
 * @return array filtered data
 */
function get_filtered_input($filter, $options = array(FILTER_GET_BY_LIST, FILTER_POST_BY_LIST)) {
	
	$r = array();
	
	// check if both _GET and _POST have the same keys
	if (count($test_intersect = array_intersect_key($_POST, $_GET)) > 0) {
		trigger_error('get_filtered_input: GET and POST have duplicate keys:'.print_r($test_intersect, 1), E_USER_WARNING);
	}

	// if full options are used, merge _GET and _POST "as is"
	if (in_array(FILTER_GET_FULL, $options)) {
		$r = array_merge($r, $_GET);
	}
	if (in_array(FILTER_POST_FULL, $options)) {
		$r = array_merge($r, $_POST);
	}

	$r = array_merge($r, array_fill_keys(array_keys($filter), ''));
	// add filtered _GET
//	echo 'GET:<br />'.print_r($_GET, 1).'<hr />';
	if ((in_array(FILTER_GET_BY_LIST, $options) || in_array(FILTER_GET_FULL, $options)) && ($t = filter_var_array($_GET,  $filter))) {
		foreach ($t as $index => $value) {
			if ($value > '') {
				$r[$index] = $value;
			}
		}
	}

	// and filtered _POST
	if ((in_array(FILTER_POST_BY_LIST, $options) || in_array(FILTER_POST_FULL, $options)) && ($t = filter_var_array($_POST,  $filter))) {
		foreach ($t as $index => $value) {
			if ($value > '') {
				$r[$index] = $value;
			}
		}
	}
	return $r;
}


/**
 * creates yet another GUID
 *
 * @return string newly created GUID
 */
function create_guid() {
	if (function_exists('com_create_guid') === true) {
		return trim(com_create_guid(), '{}');
	}
	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}


/**
 * checks if array contains value with the key given, then tests it against sample array or regexp.
 * returns:
 * 	the value if found and test passed
 * 	default value if key does not exist (FALSE by default)
 * 	false if test fails
 *
 * @param array $array values array
 * @param string $key array key to find
 * @param mixed $default_value value to return if lookup or test failed
 * @param string|array $filter array of test values (value must exactly match one of them) or regexp to test against
 *
 * @return mixed|bool
 */
function get_array_value($array, $key, $default_value = false, $filter = null) {

	// return default if no such key at all
	if (!isset($array[$key])) {
		return $default_value;
	}
	
	$test_value = $array[$key];

	// if filter not set, return "as is"
	if (!isset($filter)) {
		return $test_value;
	}

	// if $filter is array, try to find value
	if (is_array($filter) && in_array($test_value, $filter)) {
		return $test_value;
	}
	
	// if $filter is a string, make regexp test
	if (is_string($filter) && preg_match($filter, $test_value)) {
		return $test_value;
	}
	
	// burp, bad value!
	return false;
}

/**
 * Get MIME type of the file specified. Really just a wrapper for finfo
 *
 * @param string $filename filename to get type of
 * @return string MIME file type
 */
function get_file_mime_type($filename) {
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime_type = finfo_file($finfo, $filename);
	finfo_close($finfo);
	return $mime_type;
}


/**
 * TAG_TODO: написать комментарий сюда
 *
 */
function send_email($mailer, $from, $to, $subject, $body, $headers = array(), $attachments = array(), $server_params = null) {


	logthis('[send_email] : sending email "'.$subject.'" from "'.$from.'" to "'.$to.'"');

	// extract emails
	if (!preg_match('~[a-zA-Z0-9.\-]+@[a-zA-Z0-9.\-]+~', $to, $mail_addresses)) {
		logthis('[send_email] : no addresses found!', ZLOG_LEVEL_ERROR);
		return false;
	}
	
	// $to may contain such structure: Julia (julia@example.com). Round brackets should be replaced with angle brackets
	$to = preg_replace('~[\<\[\(]*([a-zA-Z0-9.\-]+@[a-zA-Z0-9.\-]+)[\>\]\)]*~', '<$1>', $to);

	// encoding data for mail_mime
	$encoding_parameters = array(
		'head_encoding' => 'base64',
		'text_encoding' => 'base64',
		'html_encoding' => 'base64',
		'head_charset'  => 'utf-8',
		'text_charset'  => 'utf-8',
		'html_charset'  => 'utf-8'
	);

	// add some important headers
	$headers_primary = array(
		'From'    => $from,
		'To'      => $to,
		'Subject' => $subject
	);
	$headers = array_merge($headers_primary, $headers);

	// create mail body generator
	$mime = new Mail_mime($encoding_parameters);
	
	// by default, no text part
	$mime->setTXTBody('');
	
	$alarm = 0;
	// replace image links with attached images
	if ($image_count = preg_match_all('~<img[^>]+src="(?!cid:)([^"]+)"[^>]*>~', $body, $img_data)) {
		for ($img_index = 0; $img_index < $image_count; $img_index++) {

			// generate new CID
			$cid = strtolower(str_replace('-', '', create_guid()));
			
			// image full CID, must contain sender domain to be displayed inline instead as attachment
			$cid_full = $cid.'@'.preg_replace('~[^@]*@~', '', $from);
			
			// add image
			$mime->addHTMLImage($img_data[1][$img_index], get_file_mime_type($img_data[1][$img_index]), '', true, $cid);
			
			// replace local image link to inline
			$new_image_link = str_replace($img_data[1][$img_index], 'cid:'.$cid_full, $img_data[0][$img_index]); // new image link
			$body = str_replace($img_data[0][$img_index], $new_image_link, $body);

		}
	}
	// ok, HTML part is ready now
	$mime->setHTMLBody($body);
	
	// add attachments
	foreach($attachments as $attachment) {
		$attachment_filename = $attachment['filename'];
		$attachment_realname = $attachment['realname'];
		$mime->addAttachment(
			$attachment_filename,                         // filename to get content from
			get_file_mime_type($attachment_filename),     // MIME-type
			$attachment_realname,                         // name to display
			true,                                         // yes, filename is really filename but not content
			'base64',                                     // transfer encoding to use for the file data
			'attachment',                                 // content-disposition of this file
			'',                                           // character set of attachment's content
			'',                                           // language of the attachment
			'',                                           // RFC 2557.4 location of the attachment
			'base64',                                     // encoding of the attachment's name in Content-Type
			'utf-8',                                      // encoding of the attachment's filename
			'',                                           // Content-Description header
			'utf-8'                                       // The character set of the headers e.g. filename
		);
	}
	
	// generate final headers
	$headers_ready = $mime->headers($headers);
	
	// get full message body
	$body_ready = $mime->get();

	// now send
	$mail_result = $mailer->send($mail_addresses, $headers_ready, $body_ready);

	// free mem as messages are big
	unset($mime);

	// log result
	if ($mail_result === true) {
		logthis('[send_email] : ok');
	} else {
		logthis('[send_email] : failed mailing to ' . $to.' : '.($mail_result->getMessage()), ZLOG_LEVEL_ERROR);
	}

	return $mail_result;
}

?>