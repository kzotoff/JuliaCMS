<?php //>

require_once(__DIR__.'/html.php');

class J_Content extends JuliaCMSModule {


	/**
	 * template to cut out page contents. Must be greedy as tag bay occur inside page contents.
	 *
	 * @const REGEXP_HTML_BODY
	 */
	const REGEXP_HTML_BODY = '~<body>(.*)</body>~smui';

	/**
	 * Search-and-replace template, for contents
	 *
	 * @const REGEXP_TEMPLATE_CONTENT
	 */
	const REGEXP_TEMPLATE_CONTENT = '~<template\s[^>]*?type="content"[^/>]*(/>|>.*?</template>)~';

	/**
	 * Search-and-replace template, for page header
	 *
	 * @const REGEXP_TEMPLATE_TITLE
	 */
	const REGEXP_TEMPLATE_TITLE = '~<template\s[^>]*?type="page_title"[^/>]*(/>|>.*?</template>)~';


	function requestParser($template) {
		
		$DB = Registry::Get('db');
		$USERFILES_DIRS = Registry::Get('USERFILES_DIRS');

		// в некоторых ситуациях требуется прервать дальнейшую обработку и перенаправить на админку снова
		$redirect_target = './?module=content&action=manage';
		$redirect_status = false;

		// фильтруем вход
		$input_filter = array(
			'id'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'alias'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'title'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9а-яА-Я\s\!\@\#\$\%\^\&\*\(\)\-\=\+\,\.\?\:\№]+$~ui')),
			'meta'        => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.*$~smui')),
			'filename'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.(html|php|xml)$~ui')),
			'css'         => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.css$~ui')),
			'js'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.js$~ui')),
			'generator'   => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_][a-zA-Z0-9\_]*$~ui')),
			'p_id'        => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'xsl'         => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-\_.]+\.xslt?$~ui')),
			'pagecontent' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.*$~smui')),
			'action'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'module'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui'))
		);

		$R = get_filtered_input($input_filter);

		// обновление информации о странице
		if ((@$R['module'] == 'content') && (@$R['action'] == 'update') && user_allowed_to('manage pages')) {

			$sql_insert = 'insert into `'.$this->CONFIG['table'].'` (alias, title, filename, custom_css, custom_js, generator, xsl, meta) values (\'%2$s\', \'%3$s\', \'%4$s\', \'%5$s\', \'%6$s\', \'%7$s\', \'%8$s\', \'%9$s\')';
			$sql_update = 'update `'.$this->CONFIG['table'].'` set alias=\'%2$s\', title=\'%3$s\', filename=\'%4$s\', custom_css=\'%5$s\', custom_js=\'%6$s\', generator=\'%7$s\', xsl=\'%8$s\', meta=\'%9$s\' where id=%1$s';

			// проверим, нет ли такого алиаса с другим ID
			if ($DB->querySingle('select count(*) from `'.$this->CONFIG['table'].'` where alias=\''.$R['alias'].'\' and id<>'.$R['id']) <> '0') {
				header('Location: '.$redirect_target);
				terminate();
			}
			
			// выбираем нужный шаблон SQL
			$sql = $R['id'] == -1 ? $sql_insert : $sql_update;

			// вытащим имеющиеся значения, на случай, если неправильный ввод породит пустое значение, а в базе уже непустое
			$q = $DB->query('select * from `'.$this->CONFIG['table'].'` where id='.$R['id']);
			if ($current = $q->fetchArray(SQLITE3_ASSOC)) {
				foreach($current as $index=>$value) {
					if (                                                   // заменяем входное значение на имеющееся, если:
						isset($R[$index]) &&                               // входное поле есть в базе
						($R[$index] == '') &&                              // И в отфильтрованном значении пусто
						(($_POST[$index] > '') || ($_GET[$index] > '')) && // И в запросе НЕ пусто
						($value > '')                                      // И в базе НЕ пусто
						) {
						$R[$index] = $value;
					}
				}
			}

			// готовим данные
			$filename = $R['filename'] > '' ? $R['filename'] : $R['alias'].'.html';

			// добавляем файлик с контентом, если страничка создается и странички еще нет
			if (($R['id'] == '-1') && !file_exists($USERFILES_DIRS['pages']['dir'].$filename)) {
				switch (pathinfo($filename, PATHINFO_EXTENSION)) {
					case 'php':
						// проверим generator, если пустой - придумаем
						if ($R['generator'] == '') {
							$R['generator'] = 'get_page_content_'.$R['alias'];
						}
						$new_content = sprintf(MODULE_CONTENT_NEW_PHP_PAGE, $R['generator']);
						break;
					case 'xml':
						$new_content = '<root>New page</root>';
						break;
					default:
						$new_content = 'New page';
						break;
				}
				file_put_contents($USERFILES_DIRS['pages']['dir'].$filename, $new_content);
			}

			// готовим и засылаем запрос
			// (!) порядок следования аргументов всегда фиксированный: id, alias, title, filename, custom_css, custom_js, generator, xsl, meta
			// (!) должно следовать после куска с записью файла, потому что там задается generator по умолчанию
			$sql = sprintf($sql, $R['id'], $R['alias'], $R['title'], $filename, $R['css'], $R['js'], $R['generator'], $R['xsl'], $R['meta']);
			$DB->query($sql);

			$redirect_status = true;
		}

		// удаление страницы
		if ((@$R['module'] == 'content') && (@$R['action'] == 'delete') && user_allowed_to('manage pages')) {

			// get filename
			$filename = $DB->querySingle('select filename from `'.$this->CONFIG['table'].'` where id='.$R['id']);

			// удаляем запись
			$DB->query('delete from `'.$this->CONFIG['table'].'` where id='.$R['id']);

			// перемещаем файлик в помойку, предварительно проверим наличие помойки
			if (!file_exists($USERFILES_DIRS['trash']['dir'])) {
				mkdir($USERFILES_DIRS['trash']['dir']);
			}
			rename($USERFILES_DIRS['pages']['dir'].$filename, $USERFILES_DIRS['trash']['dir'].$filename);
			$redirect_status = true;
		}

		// обработка сохранения страницы
		if ((@$R['module'] == 'content') && (@$R['action'] == 'savepage') && user_allowed_to('edit pages')) {

			$try_content = $R['pagecontent'];
			$page_id = $R['p_id'];

		
			$q = $DB->query('select * from `'.$this->CONFIG['table'].'` where alias=\''.$page_id.'\'');
			if ($row = $q->fetchArray(SQLITE3_ASSOC)) {
				file_put_contents($USERFILES_DIRS['pages']['dir'].$row['filename'], $try_content);
			}
			
			// при сохранении тоже надо делать редирект, хоть и на самого себя - чтобы post не делался повторно по F5
			$redirect_target = './'.$page_id;
			$redirect_status = true;
		}

		// редирект, если кто-то выше затребовал
		if ($redirect_status) {
			header('Location: '.$redirect_target);
			teminate();
		}

		return $template;
	}

	function contentGenerator($template) {

		$DB = Registry::Get('db');
		$USERFILES_DIRS = Registry::Get('USERFILES_DIRS');
	
		// если этот флажок есть, будет вызван редактор вместо отображения контента
		$edit_mode = isset($_GET['edit']);

		$pages = array();
		$q = $DB->query('select * from `'.$this->CONFIG['table'].'`');
		while ($row = $q->fetchArray(SQLITE3_ASSOC)) {
			$pages[$row['alias']] = $row;
		}

		// идентификатор странички, которую надо вставить в шаблон. валидация не нужна - делается поиск в массиве
		$page_id = isset($_GET['p_id']) ? $_GET['p_id'] : DEFAULT_PAGE_ALIAS;

		if (isset($pages[$page_id])) {
			$page_info = $pages[$page_id];
		} else {
			$page_info = array('filename' => $this->CONFIG['page_404'], 'title' => 'not found', 'meta' => '', 'title' => 'not found');
		}
		// имя файла с контентом
		$content_filename =
			isset($page_info['filename']) && file_exists($USERFILES_DIRS['pages']['dir'].$page_info['filename'])
			? $USERFILES_DIRS['pages']['dir'].$page_info['filename']
			: $this->CONFIG['page_404'] ;
			
			
		// в режиме редактирования текст/xml не генерируем, а показываем в редакторе (textarea)
		if ($edit_mode && user_allowed_to('edit content')) {
			switch (pathinfo($page_info['filename'], PATHINFO_EXTENSION)) {
				case 'php':
				case 'xml':
					$pagehtml = sprintf(MODULE_CONTENT_TEXTAREA_WRAPPER_PHP, $page_id, @file_get_contents($content_filename));
					break;
				default:
					$pagehtml = sprintf(MODULE_CONTENT_TEXTAREA_WRAPPER, $page_id, @file_get_contents($content_filename));
					break;
			}
		} else {
			// если html, тащим как есть, иначе формируем с помошью генератора или XSLT
			switch ($ext = pathinfo($content_filename, PATHINFO_EXTENSION)) {
				case 'php':
					include_once($content_filename);
					$pagehtml = call_user_func($page_info['generator']);
					break;		
				case 'xml':
					$pagehtml = XSLTransform($content_filename, $USERFILES_DIRS['xsl']['dir'].$page_info['xsl'], false, false);
					break;
				default:
					($pagehtml = file_get_contents($content_filename)) or ($pagehtml = 'error reading page content (code CONTENT/001)');
					break;
			}
		}

		// если есть BODY, берем его внутреннее содержимое, иначе весь файл целиком
		if (preg_match(self::REGEXP_HTML_BODY, $pagehtml, $page_body) > 0) {
			$replace = $page_body[1];
		} else {
			$replace = $pagehtml;
		}

		if (isset($_GET['print'])) {
			$template = str_replace('%content%', $replace, MODULE_CONTENT_PRINT_FORM);
		} else {
			$template = preg_replace(self::REGEXP_TEMPLATE_CONTENT, $replace, $template);
		}

		// мета в заголовке. если только буквы-цифры, делаем мету keywords
		if (preg_match('~^[a-zA-Zа-яА-Я0-9,.\-\s]+$~ui', $page_info['meta'], $match)) {
			$template = add_meta($template, 'name', 'keywords', $match[0]);
		} elseif (preg_match_all('~(\(([a-zA-Z\-]*)\|([a-zA-Z\-0-9]+)\|([a-zA-Z\-0-9а-яА-Я.,;:\s+=!@#$%^&*\(\)]*)\))~smui', $page_info['meta'], $matches)) { // не прокатило, попробуем структуру со скобками и пайпами
			for ($i = 0; $i < count($matches[0]); $i++) {
				$template = add_meta($template, $matches[2][$i], $matches[3][$i], $matches[4][$i]);
			}
		} elseif (preg_match_all('~<[a-zA-Z]+\s[^<>]+>~smui', $page_info['meta'], $matches)) { // проверим, возможно вписали сырые теги
			for ($i = 0; $i < count($matches[0]); $i++) {
				$template = str_insert_before('</head>', $matches[0][$i].PHP_EOL, $template);
			}
		}

		// заменяем залоговок страницы, если определен
		if (isset($page_info['title']) && (($replace = $page_info['title']) > '' )) {
			$template = preg_replace(self::REGEXP_TEMPLATE_TITLE, $replace, $template, 1);
		}

		// кастомный CSS, если указан
		if (isset($page_info['custom_css']) && (($css = $page_info['custom_css']) > '' )) {
			$template = add_CSS($template, $USERFILES_DIRS['css']['dir'].$css);
		}

		// кастомный JS, если указан
		if (isset($page_info['custom_js']) && (($js = $page_info['custom_js']) > '' )) {
			$template = add_JS($template, $USERFILES_DIRS['js']['dir'].$js);
		}

		return $template;
	}

	function AJAXHandler() {

		$DB = Registry::Get('db');
	
		// фильтруем вход
		$input_filter = array(
			'id'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'action' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui'))

		);
		$g = get_filtered_input($input_filter);

		// ответ по умолчанию
		$response = 'unknown function';

		switch ($g['action']) {

			// содержимое диалога редактирования/добавления ///////////////////////////////////////////
			case 'edit_elem':
				// элемент, который редактировать будем (-1, если новый)
				if (($elem_id = $g['id']) == '') {
					return 'bad ID';
				}
				$q = $DB->query('select * from `'.$this->CONFIG['table'].'` where id='.$elem_id);
				$row = $q->fetchArray(SQLITE3_ASSOC);

				$response = sprintf(MODULE_CONTENT_SINGLE_PAGE_EDIT_INFO,
					$elem_id,
					$row['title'],
					$row['alias'],
					$row['filename'],
					$row['custom_css'],
					$row['custom_js'],
					$row['generator'],
					$row['xsl'],
					$row['meta']
				);
				break;
		}

		return $response;
	}

	function adminGenerator() {

		$DB = Registry::Get('db');
		$USERFILES_DIRS = Registry::Get('USERFILES_DIRS');
	
		$q = $DB->query('select * from `'.$this->CONFIG['table'].'`');

		// сначала соберем все строки таблицы
		$trs = '';
		while ($row = $q->fetchArray(SQLITE3_ASSOC)) {

			// общая инфа по страничке
			$tr = sprintf(MODULE_CONTENT_HTML_ROW_SINGPLE_PAGE_INFO, $row['id'], $row['alias'], $row['title'], $row['filename'], $row['custom_css'], $row['custom_js'], $row['generator'], $row['xsl']);

			// информация о статусе страницы
			$page_file_status = file_exists($USERFILES_DIRS['pages']['dir'].$row['filename']) ? 'file OK' : 'file missing';
			$tr = str_replace('<file-state />', $page_file_status, $tr);

			$trs .= $tr;
		}
		// обернем в таблицу и добавим кнопки всякие
		$html = sprintf(MODULE_CONTENT_HTML_ALL_PAGES_INFO_TABLE, $trs);
		
		return $html;
	}
}

?>