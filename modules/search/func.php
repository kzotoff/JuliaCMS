<?php

require(__DIR__.'/html.php');

// сколько текста показывать вместе с найденным
define('MODULE_SEARCH_CHARS_TO_INCLUDE', 60);

class J_Search extends JuliaCMSModule {
	
	function requestParser($template) {

		if (
			(isset($_GET['p_id'])   && ($_GET['p_id']   != 'search')) ||
			(isset($_GET['module']) && ($_GET['module'] != 'search')) ) {
			return $template;
		}

		global $DB;

		$html = '';
		$results = 0;

		// распарсим, что ищем
		if (preg_match_all('~[^\s]{2,}~smui', $_GET['search'], $matches) == 0) {
			$template = preg_replace(MODULE_CONTENT_REGEXP_TEMPLATE_CONTENT, 'Некорректный запрос', $template);
			return $template;
		}

		$search = $matches[0];
		// тупо перебираем все странички и проверям наличие текста, если есть - выводим кусок контента с подсветкой
		$files = scandir('userfiles/pages/');
		foreach ($files as $file) {
			if (substr($file, 0, 1) == '.') {                                 // исключаем ".", ".." и .htaccess
				continue;
			}

			if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {               // в генераторах тоже не ищем
				continue;
			}
			$content = file_get_contents('userfiles/pages/'.$file);
			$content = preg_replace('~<.*?>~', ' ', $content);                // стрипаем теги
			$content = preg_replace('~\s+~', ' ', $content);                  // убираем двойные пробелы, все переносы, табуляции и прочую требуху

			// проверяем каждое слово запроса, если нашлось хотя бы одно - добавляем к результатам и выводим все куски с вхождениями
			foreach ($search as $string) {
				if (($pos = mb_stripos($content, $string)) !== false) {

					// тащим заголовок и сцылу. не найдется - не показываем
					$query = $DB->query('select alias, title from `'.DB_TABLE_PAGES.'` where filename = \''.$file.'\'');
					if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
						$results ++;

						// каждое вхождение выделяем жирным в своем контексте
						$highlight = '';
						foreach ($search as $string) {
							if (($pos = mb_stripos($content, $string)) !== false) {
								$cut = 
									'...'.
									mb_substr($content, max(0, $pos-MODULE_SEARCH_CHARS_TO_INCLUDE), min($pos, MODULE_SEARCH_CHARS_TO_INCLUDE)).
									'<b>'.
									mb_substr($content, $pos, mb_strlen($string)).
									'</b>'.
									mb_substr($content, $pos + mb_strlen($string), MODULE_SEARCH_CHARS_TO_INCLUDE).
									'...'
									;
								$highlight .= $cut;
							}
						}
						$more = MODULE_SEARCH_RESULT_DEFAULT;
						$more = str_replace('%alias%', $row['alias'], $more);
						$more = str_replace('%title%', $row['title'], $more);
						$more = str_replace('%highlight%', $highlight, $more);
						$html .= $more;
					}
				}
			}
				
		}
		$result = MODULE_SEARCH_ENTIRE_CONTENT;
		$result = str_replace('%search_pattern%', implode($search, ' '), $result);
		$result = str_replace('%result_count%', $results, $result);
		$result = str_replace('%html%', $html, $result);

		$template = preg_replace(MODULE_CONTENT_REGEXP_TEMPLATE_CONTENT, $result, $template);

		// еще главный title заменим
		$template = preg_replace('~<template\s[^>]*?type="page_title"[^/>]*(/>|>.*?</template>)~', 'Поиск: '.implode($search, ' '), $template);

		return $template;
	}
}

?>