<?php //> Й <- UTF mark

require(__DIR__.'/html.php');


class J_News extends JuliaCMSModule {

	/**
	 * Input parser 
	 *
	 */
	function requestParser($template) {
		global $DB;

		// много каментов есть в модулях menu и content
		$input_filter = array(
			'id'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^\-?[0-9]+$~')),
			'module'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'action'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'caption' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9\s\-_\\!@#$%^&*()=+.,:;"]+$~ui')),
			'summary' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9\s\-_\\!@#$%^&*()=+.,:;<>"/?]+$~ui')),
			'page'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'link'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z:.%а-яА-Я0-9]+$~')),
			'streams' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9_\-\s]+$~'))
		);

		$R = get_filtered_input($input_filter);

		// в некоторых ситуациях требуется прервать дальнейшую обработку и перенаправить на админку снова
		$redirect_target = './?module=news&action=manage';
		$redirect_status = false;

		$news_add_sql = 'insert into '.$this->CONFIG['table'].' (stamp, caption, link, page, streams, summary) values (datetime(\'now\'), \'%2$s\', \'%3$s\', \'%4$s\', \'%5$s\', \'%6$s\')';
		$news_upd_sql = 'update '.$this->CONFIG['table'].' set caption=\'%2$s\', link=\'%3$s\', page=\'%4$s\', streams=\'%5$s\', summary=\'%6$s\' where id=\'%1$s\' ';

		if ((@$R['module'] == 'news') && (@$R['action'] == 'edit_item') && user_allowed_to('add news')) {
			$sql = ($R['id'] >= 0 ? $news_upd_sql : $news_add_sql);

			// вытащим имеющиеся значения, на случай, если неправильный ввод породит пустое значение, а в базе уже непустое
			$q = $DB->query('select stamp, caption, link, page, streams, summary from '.$this->CONFIG['table'].' where id='.$R['id']);
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

			// при создании линк ставим тоже на страницу, если не указан
			if (($R['id'] < 0) && ($R['link'] == '')) {
				$R['link'] = $R['page'];
			}
			$sql = sprintf($sql, $R['id'], $R['caption'], $R['link'], $R['page'], $R['streams'], $R['summary']);

			$DB->query($sql);
			$redirect_status = true;
		}
		if ((@$R['module'] == 'news') && (@$R['action'] == 'delete') && user_allowed_to('delete news')) {
			$sql = 'delete from '.$this->CONFIG['table'].' where id=\''.$R['id'].'\'';
			$DB->query($sql);
			$redirect_status = true;
		}

		if ($redirect_status) {
			header('Location: '.$redirect_target);
			terminate();
		}

		return $template;
	}

	/**
	 * Generates HTML for a news
	 * template MUST contain exactly 4 parts - timestamp, header, text, link
	 *
	 * @param string $news_template single news template
	 * @param int $stream selects stream for output. Will be shown only news with this stream
	 * @param int $count limits count for output
	 *
	 * @return string HTML code
	 *
	 */
	function newsGenerate($news_template, $stream, $count = -1) {
		global $DB;

		$res = '';
		// запрашиваем новости
		$q = $DB->query('select * from '.$this->CONFIG['table'].' where \' \'||streams||\' \' like \'% '.$stream.' %\' order by stamp desc '.($count >= 0 ? 'limit 0,'.$count : ''));

		// по одной читаем, впихиваем в шаблончик и прицепляем к цельному блоку
		while ($row = $q->fetchArray(SQLITE3_ASSOC)) {
			// берем копию, дальше модифицировать будем
			$copy = $news_template;

			// обрабатываем, что с базы пришло
			// у SQLite нет родного формата времени. Ну или я не нашел.
			$stamp = substr($row['stamp'], 8, 2).'.'.substr($row['stamp'], 5, 2).'.'.substr($row['stamp'], 0, 4);

			// заголовок
			$caption =$row['caption'];

			// основной текст
			$summary = $row['summary'];

			// ссылка, если пустая - пробуем связанную страницу
			$link = $row['link'] ?: $row['page'];

			// вырезаем ссылку, обозначенную как class="news_main_link" если ссылки нет
			if ($link == '') {
				$copy = preg_replace('~<a[^>]*class="news_main_link"[^>]*>(.*?)</a>~ui', '$1', $copy, 1);
			}
			// заменяем данные и дописываем к блоку
			$copy = str_replace('%stamp%', $stamp, $copy);
			$copy = str_replace('%caption%', $caption, $copy);
			$copy = str_replace('%text%', $summary, $copy);
			$copy = str_replace('%link%', $link, $copy);

			$res .= $copy;
		}
		return $res;
	}

	/**
	 * Main content generator
	 *
	 */
	function contentGenerator($template) {

		// образец для замены в шаблоне
		$placeholder = '~<template\s[^>]*?type="news"[^/>]*(/>|>.*?</template>)~';

		// ищем очередное вхождение шаблончика
		while (preg_match($placeholder, $template, $match) > 0) {
			$menu_params = parse_plugin_templateparse_plugin_template($match[0]);

			// выберем, какой шаблон использовать
			$news_template =
				defined('MODULE_NEWS_TEMPLATE_'.@strtoupper($menu_params['template'])) ?
				constant('MODULE_NEWS_TEMPLATE_'.strtoupper($menu_params['template'])) :
				MODULE_NEWS_TEMPLATE_DEFAULT
				;

			// обратим шаблон в html
			$str = $this->newsGenerate($news_template, @$menu_params['stream']?:'default', isset($menu_params['count']) ? $menu_params['count'] : -1);

			// вписываем в основной шаблон
			$template = str_replace($match[0], $str, $template);

		}

		return $template;
	}

	/**
	 * AJAX!
	 *
	 */
	function AJAXHandler() {
		global $DB;

		// фильтруем вход
		$input_filter = array(
			'id'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'action' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui'))

		);
		$R = get_filtered_input($input_filter, array(FILTER_GET_BY_LIST));

		// ответ по умолчанию
		$response = 'unknown function';

		switch ($g['action']) {

			// содержимое диалога редактирования/добавления ///////////////////////////////////////////
			case 'edit_elem':
				// элемент, который редактировать будем (-1, если новый)
				if (($elem_id = $g['id']) == '') {
					return 'bad ID';
				}
				$q = $DB->query('select caption, link, page, streams, summary from '.$this->CONFIG['table'].' where id='.$elem_id);
				$row = $q->fetchArray(SQLITE3_ASSOC);
				$response = sprintf(MODULE_NEWS_MANAGE_ITEM_EDIT,
					$elem_id,
					$row['caption'],
					$row['summary'],
					$row['link'],
					module_menu_get_pages_list($row['page'], 'page'),
					$row['streams']
				);
				break;
		}

		return $response;

	}

	/**
	 * Admin!
	 *
	 */
	function adminGenerator() {
		global $DB;

		$q = $DB->query('select stamp, id, caption, link, page, streams, summary from '.$this->CONFIG['table']);

		// сначала соберем все строки таблицы
		$trs = '';
		while ($row = $q->fetchArray(SQLITE3_ASSOC)) {

			// общая инфа по страничке
			$tr = sprintf(MODULE_NEWS_MANAGE_SINGLE_ROW, $row['id'], $row['caption'], $row['link'], $row['page'], $row['streams'], $row['summary'], $row['stamp']);

			$trs .= $tr;
		}
		// обернем в таблицу и добавим кнопки всякие
		$html = sprintf(MODULE_NEWS_ALL_PAGES_INFO_TABLE, $trs);

		return $html;

	}
}

?>