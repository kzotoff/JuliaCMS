<?php

class J_Admin extends JuliaCMSModule {
	
	function requestParser($template) {
		global $modules;
		global $modules_cache;
		global $modules_apply_order;

		if (!user_allowed_to('manage site')) {
			return $template;
		}

		// формируем HTML с кнопками
		$html  = '<div class="admin_padder"></div>';                              // распорка для отодвигания остального контента
		$html .= '<div class="admin_box_main">';                                  // контейнер для кнопок, такой же высоты, но фиксированный

		// из всех модулей тащим кнопки вызова админки, если есть. Только модули, включенные в обработку
		foreach ($modules as $module_name => $module_definition) {
			if (isset($module_definition['admin_caption']) && ($module_definition['admin_caption'] > '')) {
				$html .= '<a href="./?module='.$module_name.'&amp;action=manage"'.(@$_GET['module']==$module_name?' class="active_admin" ':'').'>'.$module_definition['admin_caption'].'</a>';
			}
		}
		
		$html .= '<a href="./?logout" style="float:right;">logout</a>';           // выход из админки
		
		// если запрошена админка какого-либо модуля, заменяем весь контент на код админки
		if (isset($_GET['module']) && isset($modules[$_GET['module']]) && isset($_GET['action']) && ($_GET['action'] == 'manage')) {
			$module_name = $_GET['module'];
			$module = $modules[$module_name];

			// кнопка для возврата в стандартный режим (просмотра страниц)
			$html .= '<a href="./">return to content</a>';                        

			// заменим контент на админку модуля, если запрошено
			if (!isset($modules_cache[$module_name])) {
				$modules_cache[$module_name] = new $module['class_name']($module_name);
			}
			$manager_content = $modules_cache[$module_name]->AdminGenerator();
			$template = preg_replace('~<body(.*?)>.*</body>~smui', '<body$1>'.$manager_content.'</body>', $template, 1);

			// добавим таблицу стилей
			if (isset($module['admin_css'])) {
				foreach ($module['admin_css'] as $add_css) {
					$template = add_CSS($template, MODULES_DIR.$module_name.'/'.$add_css);
				}
			}

			// и скрипты
			if (isset($module['admin_js'])) {
				foreach ($module['admin_js'] as $add_css) {
					$template = add_JS($template, MODULES_DIR.$module_name.'/'.$add_css);
				}
			}
		} else { // если не запрошена модуль-админка, показываем кнопки редактирования страницы
			if (isset($_GET['edit'])&&(@$_GET['module'] == 'content')) {         // кнопки в режиме редактирования страницы
				$html .= '<a onclick="document.getElementById(\'editpagecontentform\').submit();">save page</a>';
				$html .= '<a href="./'.@$_GET['p_id'].'">cancel</a>';
			} else {                                                             // и в режиме просмотра
				$html .= '<a href="./'.@$_GET['p_id'].'?edit&amp;module=content">edit this page</a>';
			}
		
		}
		$html .= '</div>';

		
		// в начале BODY вставим бокс с кнопками
		$template = preg_replace('~<body(.*?)>~', '<body$1>'.$html, $template, 1);
		
		return $template;
	}
}

?>