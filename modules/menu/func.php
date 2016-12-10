<?php //> Й <- UTF mark

/*
// TAG_TODO мысли для переноса в документацию

ordermark - сквозная тема по всему меню, определяет только порядок элементов в пределах одного родителя
шаблоны для разметки лежат в папке /userfiles/_data_modules/menu/templates/, далее в подпапках
catalog_full, catalog_short, navigator, menu.

Имя файла сначала берется из параметров, если там нет, берется "default". Если нет в юзерленде, то смотрим
в папку /templates в самом модуле

параметры макросов:

[macro
	type="menu|navigator"      : показывать меню или навигатор
	navigator-no-root          : не выводить корневой элемент в навигаторе (обычно это просто "точка старта")
	                             значение атрибута может быть любым, достаточно наличия атрибута
	navigator-count            : выводить заданное количество элементов
/]

*/


/*

menu generator

*/

class J_Menu extends JuliaCMSModule {

	/**
	 * While creating it will be a good idea to add our catalogs to filemanager
	 */
	function __construct() {
		CMS::$R['USERFILES_DIRS']['module_menu_catalog_short'] = array(
			'caption'         => '[menu] short catalog XSL',
			'dir'             => 'userfiles/_data_modules/menu/templates/catalog_short/',
			'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.xsl'
		);
		CMS::$R['USERFILES_DIRS']['module_menu_catalog_full'] = array(
			'caption'         => '[menu] detailed catalog XSL',
			'dir'             => 'userfiles/_data_modules/menu/templates/catalog_full/',
			'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.xsl'
		);
		CMS::$R['USERFILES_DIRS']['module_menu_navigator'] = array(
			'caption'         => '[menu] navigators XSL',
			'dir'             => 'userfiles/_data_modules/menu/templates/navigator/',
			'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.xsl'
		);
		CMS::$R['USERFILES_DIRS']['module_menu_menu'] = array(
			'caption'         => '[menu] standard menu XSL',
			'dir'             => 'userfiles/_data_modules/menu/templates/menu/',
			'regexp_filename' => '[a-zA-Zа-яА-Я0-9]+[a-zA-Zа-яА-Я0-9\-_.]*\.xsl'
		);
		
	}
	
	/**
	 * Just a proxy for element-changing functions
	 *
	 * @param string template to modify
	 * @return string modified (or not) template
	 */
	function requestParser($template) {

		if (
			((@$_POST['module'] != 'menu') && (@$_GET['module'] != 'menu'))
			|| !user_allowed_to('manage menu')
		) {
			return $template;
		}
		
		$_INPUT = array_merge($_GET, $_POST);

		// check & sure if "action" parameter is set and set once only
		switch (@$_POST['action'].@$_GET['action']) {
			// avoid circular redirecting
			case 'manage':
				return $template;
				break;

			// yeah, just an updating
			case 'update':
				if (($result = $this->elementUpdate($_INPUT)) !== true) {
					popup_message_add('[MENU] ошибка сохранения записи: '.$result, JCMS_MESSAGE_ERROR);
				}
				break;

			// move the upper
			case 'moveup':
				if (($result = $this->elementMoveUp($_INPUT['id'])) !== true) {
					popup_message_add('[MENU] ошибка изменения порядка элементов: '.$result, JCMS_MESSAGE_ERROR);
				}
				break;

			// movibg down
			case 'movedown':
				if (($result = $this->elementMoveDown($_INPUT['id'])) !== true) {
					popup_message_add('[MENU] ошибка изменения порядка элементов: '.$result, JCMS_MESSAGE_ERROR);
				}
				break;

			// deleting the item
			case 'delete':
				if (($result = $this->elementDelete($_INPUT['id'])) !== true) {
					popup_message_add('[MENU] удаления элемента: '.$result, JCMS_MESSAGE_ERROR);
				}
				($_INPUT['id']);
				break;

			default:
				popup_message_add('[ MENU ] unknown action', JCMS_MESSAGE_WARNING);
				break;
		}
		terminate('', 'Location: ./?module=menu&action=manage', 302);
	}

	/**
	 *
	 */
	function contentGenerator($template) {
		
		// catalog mode: intercept _GET page alias, look in self elements, display child items list if exists
		$input_filter = array(
			'p_id' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS))
		);
		$_INPUT = get_filtered_input($input_filter, array(FILTER_GET_BY_LIST));

		if ($_INPUT['p_id'] > '') {
	
			// this will mean that nothing was found
			$id = -1;
			$query = CMS::$DB->query("select * from `{$this->CONFIG['table_menu']}` where alias = '{$_INPUT['p_id']}'");
			if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$id          = $row['id'];
				$css_content = $row['style_content'];
				$caption     = $row['caption'];
				$title       = ($row['title'] ? $row['title'] : $row['caption']);
				$meta        = $row['meta'];
			}
			
			// if we found something, insert it instead content
			if ($id >= 0) {
				
						
				while (preg_match(macro_regexp('content'), $template, $match) > 0) {
				
					$params = parse_plugin_template($match[0]);

					$html = $this->generateCatalogPageHTML($_INPUT['p_id'], $params);
					// possibly we will need navigator from the current page
					if (get_array_value($params, 'show-navigator', 'yes') == 'yes') {						
						$html = $this->generateNavigatorHTML($_INPUT['p_id'], $params).$html;
					}
					
					$template = preg_replace(macro_regexp('content'), $html, $template);
					$template = preg_replace(macro_regexp('page_title'), $title, $template);

					// add meta. if only letter and digits, make "keywords" meta (!copy-paste detected!)
					if (preg_match('~^[a-zA-Zа-яА-Я0-9,.\-\s]+$~ui', $meta, $match)) {
						$template = add_meta($template, 'name', 'keywords', $match[0]);
					} elseif (preg_match_all('~(\(([a-zA-Z\-]*)\|([a-zA-Z\-0-9]+)\|([a-zA-Z\-0-9а-яА-Я.,;:\s+=!@#$%^&*\(\)]*)\))~smui', $meta, $matches)) { // не прокатило, попробуем структуру со скобками и пайпами
						for ($i = 0; $i < count($matches[0]); $i++) {
							$template = add_meta($template, $matches[2][$i], $matches[3][$i], $matches[4][$i]);
						}
					} elseif (preg_match_all('~<[a-zA-Z]+\s[^<>]+>~smui', $meta, $matches)) { // check if raw tags there
						for ($i = 0; $i < count($matches[0]); $i++) {
							$template = str_insert_before('</head>', $matches[0][$i].PHP_EOL, $template);
						}
					}
					
					// yeah, nice stylesheets
					add_CSS($css_content, CMS::$R['USERFILES_DIRS']['css']['dir']);
				}
			}
		}

		// standard behavior - menu by macro
		while (preg_match(macro_regexp('menu'), $template, $match) > 0) {

			// parse template parameters into array
			$params = parse_plugin_template($match[0]);

			// generate menu HTML
			$xml = $this->generateMenuAsXML(get_array_value($params, 'start-from', ''));
			$xsl = get_array_value($params, 'menu-template', 'default');
			
			// now test with path
			$xsl_filename = __DIR__.'/../../userfiles/_data_modules/menu/templates/menu/'.$xsl.'.xsl';
			if (!file_exists($xsl_filename)) {
				$xsl_filename = __DIR__.'/templates/'.$catalog_dir.'/'.$xsl.'.xsl';
			}
			
			$html = XSLTransform($xml->saveXML($xml->documentElement), $xsl_filename);

			// replace it
			$template = str_replace($match[0], $html, $template);
		}
		
		// navigator mode
		while (preg_match(macro_regexp('menu-navigator'), $template, $match) > 0) {

			// parse template parameters into array
			$params = parse_plugin_template($match[0]);

			// generate navigator HTML
			$html = $this->generateNavigatorHTML( get_array_value($params, 'start-from', ''), $params );

			// replace it
			$template = str_replace($match[0], $html, $template);
		}

		// yeah we are ready
		return $template;
	}

	/**
	 *
	 */
	function AJAXHandler() {

		$input_filter = array(
			'id'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'alias'   => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'action'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\_\-]+$~ui'))

		);
		$_INPUT = get_filtered_input($input_filter);

		switch ($_INPUT['action']) {

			// edit item form
			case 'edit_elem':

				// some pre-checks
				if (!user_allowed_to('manage menu')) {
					terminate('Forbidden', '', 403);
				}

				$element_id = $_INPUT['id'];
				$insert_mode = $element_id < 0;

				if ($element_id == '') {
					return 'bad ID';
				}

				// ok, get XML and transform it
				// array will be almost empty if we are creating new element, this 2 values are just nesessary
				if ($insert_mode) {
					$row = array(
						'id'        => $element_id,
						'parent_id' => 0
					);
				} else {
					$item_data = CMS::$DB->query("select id, caption, parent_id, page, link, alias, text, picture, style_content, style_item, class_item, hidden, title, meta from `{$this->CONFIG['table_menu']}` where id={$element_id}");
					$row = $item_data->fetch(PDO::FETCH_ASSOC);
				}

				// entire record now converted to XML and will XSL-transformed
				$xml = array_to_xml($row, array('menu-edit-data'));
				$xml->documentElement->appendChild(
					$xml->importNode( aliasCatchersAsXML(array('root'=>'page-list'))->documentElement, true)
				);

				// get menu id - either from existing row (when editing) or from input (when adding)
				$xml->documentElement->appendChild(
					$xml->importNode( $this->generateElemListAsXML($row['parent_id'], array($element_id))->documentElement, true)
				);

				return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/edit.xsl');
				break;
		}
		return 'unknown function';
	}

	/**
	 *
	 */
	function adminGenerator() {

		$xml = $this->generateMenuAsXML('');
		if (is_string($xml)) {
			return '[ MENU ] error building menu: '.$xml;
		}
		return XSLTransform($xml->saveXML($xml->documentElement), __DIR__.'/list.xsl');
	}


	/**
	 * Generates navigator (aka breadcrumbs) XML structure
	 *
	 * Sample XML:
	 * <navigator>
	 *   <elem>
	 *     <caption>element caption</caption>
	 *     <page>page link</page>
	 *     ...
	 *   </elem>
	 *   <elem>
	 *     ...
	 *   </elem>
	 *     ...
	 * </navigator>
	 *
	 * @param int $id element ID to trace to
	 * @param array $options various generating options. Whese are available so far:
	 *                       navigator-no-root : don't include root element (which is usually just a menu identifier)
	 *                                           any value is allowed, it's enough if option just exists
	 *                       navigator-count   : limit items count to this (for example, count=>3 means that output
	 *                                           will contain current node and 2 upper-levels)
	 * @return DOMDocument|string XML structure on success, text result on failure
	 */
	private function generateNavigatorXML($id, $options) {
		
		$xml = new DOMDocument('1.0', 'utf-8');
		
		$xml->appendChild($root_node = $xml->createElement('navigator'));

		// check if ID is numeric (alias may also be there)
		if (!is_numeric($id)) {
			$id = CMS::$DB->querySingle("select id from `{$this->CONFIG['table_menu']}` where `alias` = '$id'");
		}
		// if ID is still non-numeric - it is malformed
		if (!is_numeric($id)) {
			return 'bad ID';
		}

		// ok, start with it
		$current_id = $id;

		// get menu definitions in ID-indexed array, so not using fetchAll
		$menu_data = array();
		$query = CMS::$DB->query("select id, alias, caption, page, parent_id from `{$this->CONFIG['table_menu']}`");
		while ($elem_data = $query->fetch(PDO::FETCH_ASSOC)) {
			$menu_data[$elem_data['id']] = $elem_data;
		}

		// will combine backwards and revert after
		$elem_chain = array();
		while (isset($menu_data[$current_id])) {
			
			// ok, create aanother node
			$more_node = $xml->createElement('elem');
			
			// add element description
			foreach ($menu_data[$current_id] as $key => $value) {
				$more_node->appendChild($xml->createElement($key))->nodeValue = $value;
			}
			$elem_chain[] = $more_node;
			
			// move upper!
			$current_id = $menu_data[$current_id]['parent_id'];
		}

		// when no parent element exists, we are at root, create list
		// s you remember, list was "deepest=first"
		$elem_chain = array_reverse($elem_chain);

		// kill root if requested
		if (isset($options['navigator-no-root'])) {
			array_shift($elem_chain);
		}
		// keep only requested count, if requested
		if (isset($options['navigator-count']) && is_numeric($options['navigator-count'])) {
			$elem_chain = array_slice($elem_chain, -$options['navigator-count']);
		}

		// ok, now ready to create filnal XML
		foreach ($elem_chain as $elem) {
			$root_node->appendChild($elem);
		}
 //$xml->formatOutput = true; echo '<pre>'.htmlspecialchars($xml->saveXML()).'</pre>'; exit;

		return $xml;
	}

	/**
	 * Generates ready-to-use HTML for navigator
	 *
	 * @param mixed $start_from node ID or alias to show path to
	 * @param array $params parameters to use. Usually directly from macros
	 * @return string HTML markup
	 */
	private function generateNavigatorHTML ($start_from, $params) {
		
		// get XML first
		$xml = $this->generateNavigatorXML($start_from, $params);
		if (is_string($xml)) {
			return '[ MENU ] failed to create navigator: '.$xml;
		}
		
		$xsl = get_array_value($params, 'navigator-template', 'default');
		
		// try userland file location first, then default
		$xsl_filename = __DIR__.'/../../userfiles/_data_modules/menu/templates/navigator/'.$xsl.'.xsl';
		if (!file_exists($xsl_filename)) {
			$xsl_filename = __DIR__.'/templates/navigator/'.$xsl.'.xsl';
		}
		return XSLTransform($xml->saveXML($xml->documentElement), $xsl_filename);
	}
	
	/**
	 * Generates catalog page XML data for the given alias
	 *
	 * @param sring $alias
	 * @return DOMDocument|string XML on success, error text on failure
	 */
	private function generateCatalogPageXML($alias) {
		
		if (!preg_match(REGEXP_ALIAS, $alias)) {
			return 'bad alias';
		}
		$parent_query = CMS::$DB->query("select * from `{$this->CONFIG['table_menu']}` where alias = '$alias'");
		
		$parent_data = $parent_query->fetch(PDO::FETCH_ASSOC);
		if ($parent_data == false) {
			return 'alias not exists';
		}
		
		// there are two modes for the catalog - "short" for general tree nodes and "full", where all elements are leaves
		$anyone_has_children = false;

		// ok, let's start
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->appendChild($root_node = $xml->createElement('catalog-node-description'));
		
		// add current node full definition
		$root_node->appendChild($group_definition = $xml->createElement('node-properties'));
		foreach ($parent_data as $key => $value) {
			$group_definition->appendChild($xml->createElement($key))->nodeValue = $value;
		}
		$parent_id = $parent_data['id'];
		
		$root_node->appendChild($catalog_elems = $xml->createElement('elems'));
		$query = CMS::$DB->query("select menu.*, ifnull(c.childcount,0) as childcount from `{$this->CONFIG['table_menu']}` left join (select parent_id, count(*) as childcount from {$this->CONFIG['table_menu']} group by parent_id) c on c.parent_id = menu.id where menu.parent_id = $parent_id");
		while ($item_data = $query->fetch(PDO::FETCH_ASSOC)) {
			$catalog_elems->appendChild($more_node = $xml->createElement('elem'));
			if ($item_data['childcount'] > 0) {
				$anyone_has_children = true;
			}
			foreach ($item_data as $key => $value) {
				$more_node->appendChild($xml->createElement($key))->nodeValue = $value;
			}
		}

		// add mode data
		$root_node->setAttribute('catalog-mode', $anyone_has_children ? 'short' : 'full');
		return $xml;		
	}

	/**
	 * Generates complete HTML for catalog page
	 *
	 * @param mixed $id node ID or alias to show catalog
	 * @param array $params parameters to use. Usually directly from macros
	 * @return string HTML markup
	 */	
	private function generateCatalogPageHTML($alias, $params) {

		// create catalog XML
		$xml = $this->generateCatalogPageXML($alias);
		
		if (is_string($xml)) {
			return '[ MENU ] error creating catalog: '.$xml;
		}

		// determine mode
		if ($xml->documentElement->getAttribute('catalog-mode') == 'short') {
			$catalog_dir = 'catalog_short';
		} else {
			$catalog_dir = 'catalog_full';
		}
		
		// check XSL. Try userland file location first, then default
		// just filename, without path
		$xsl = get_array_value($params, 'catalog-template', 'default');
		
		// now test with path
		$xsl_filename = __DIR__.'/../../userfiles/_data_modules/menu/templates/'.$catalog_dir.'/'.$xsl.'.xsl';
		if (!file_exists($xsl_filename)) {
			$xsl_filename = __DIR__.'/templates/'.$catalog_dir.'/'.$xsl.'.xsl';
		}

		return XSLTransform($xml->saveXML($xml->documentElement), $xsl_filename);
		
	}
	
	/**
	 * Creates all elements list as XML structure
	 *
	 * XML will have the following structure
	 * <menu-elems id="menu_id">
	 *     <elem>
	 *         <id>ELEM_ID</id>
	 *         <parent_id>0</parent_id>
	 *         and so on, all description fields as in tables
	 *     </elem>
	 *     <elem selected="selected"> <!-- this will mark special element -->
	 *         ...
	 *     </elem>
	 * </menu-elems>
	 *
	 * @param int $id element ID to trace to
	 * @param array $hide_elems element IDs to be not included
	 * @return navigator HTML
	 */
	private function generateElemListAsXML($current_id = '', $hide_elems = array()) {

		$query = CMS::$DB->query("select * from `{$this->CONFIG['table_menu']}`");

		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->appendChild($root_node = $xml->createElement('menu-elems'));

		while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
			if (!in_array($row['id'], $hide_elems)) {
				$root_node->appendChild($elem_node = $xml->createElement('elem'));
				if ($current_id == $row['id']) {
					$elem_node->setAttribute('selected', 'selected');
				}
				foreach ($row as $key=>$value) {
					$elem_node->appendChild($xml->createElement($key))->nodeValue = $value;
				}
			}
		}
		return $xml;
	}

	/**
	 * Helper for generateMenuAsXML: recursively adds a subnodes to a node
	 *
	 * @param DOMNode &$node to add elements to
	 * @param array &$items item array to search added elements
	 * @param int $level_count descendant levels to add. 0 means no more levels (stop condition), negative means infinite
	 * @param array $options processing options. look generateMenuAsXML comments for description
	 */
	private function addMenuXMLNodes(&$node, &$items, $level_count, $options) {

		if ($level_count == 0) {
			return;
		}

		$current_node_id = $node->getAttribute('element-id');
		$xml = $node->ownerDocument;

		// by start, child-nodes storage is absent
		$child_elements_node = null;
		foreach ($items as $item_key => $item) {
			// check if some other item should be appended to current
			if ($item['parent_id'] != $current_node_id) {
				continue;
			}

			// skip hidden if no flag or flag is false
			if ($item['hidden'] && (( !isset($options['with-hidden']) ) || ( $options['with-hidden'] == false ))) {
				unset($items[$item_key]);
				continue;
			}

			// ok, something to add. find if child nodes storage was added previously
			if (is_null($child_elements_node)) {
				if ($node->hasChildNodes()) {
					for ($i = 0; $i < $node->childNodes->length; $i++) {
						if ($node->childNodes->item($i)->nodeName == 'child-elements') {
							$child_elements_node = $node->childNodes->item($i);
							break;
						}
					}
				}

				// not found. create it!
				if (is_null($child_elements_node)) {
					$node->appendChild($child_elements_node = $xml->createElement('child-elements'));
				}
			}

			// add subelements with its description
			$child_elements_node->appendChild($more_node = $xml->createElement('elem'));
			$more_node->setAttribute('element-id', $item['id']);
			$more_node->appendChild($description_node = $xml->createElement('description'));
			foreach ($item as $key => $value) {
				$description_node->appendChild($xml->createElement($key))->nodeValue = $value;
			}
			// remove element from array to speed up next searches
			unset($items[$item_key]);

			// now add descendants to new node
			$this->addMenuXMLNodes($more_node, $items, $level_count-1, $options);
		}
	}

	/**
	 * Gnerates menu as XML structure, starting from $start_from
	 *
	 * XML has the following structure:
	 * <menu>
	 * <elem>
	 *   <description>
	 *     <caption>element caption</caption>
	 *     ...
	 *   </description>
	 *   <child-elements>
	 *     <elem>
	 *       ...
	 *     </elem>
	 *     ...
	 *   </child-elements>
	 * </menu>
	 *
	 * @param string|int $start_from XML will include this element's children and all their descendants. Empty string means
	 *                               exactly that must - empty ID (global menu root, will output all elements)
	 * @param array $options building options. Possible keys:
	 *                           depth       : now many levels to include (0 to plain structure)
	 *                           with-hidden : include or not elements with "hidden" mark. Default is false (not include)
	 * @return mixed DOMDocument on success, text message on fail
	 */
	private function generateMenuAsXML($start_from = '', $options = array()) {

		// get alements. note that ORDER BY is required by algorythm
		$sql = "select menu.*, ifnull(c.childcount,0) as childcount from `{$this->CONFIG['table_menu']}` left join (select parent_id, count(*) as childcount from {$this->CONFIG['table_menu']} group by parent_id) c on c.parent_id = menu.id order by menu.parent_id, menu.ordermark";

		$q = CMS::$DB->query($sql);

		// note that is also serves as rowcount check.
		$menu_data = $q->fetchAll(PDO::FETCH_ASSOC);

		// start menu as DOM structure
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->appendChild($root = $xml->createElement('menu'));
		
		// if id is not numeric, try locate alias
		if ((!is_numeric($start_from)) && ($start_from > '') && (preg_match(REGEXP_ALIAS, $start_from))) {
			$start_from = CMS::$DB->querySingle("select id from `{$this->CONFIG['table_menu']}` where alias = '$start_from'");
		}
		
		// not located, start from  root
		if ((!is_numeric($start_from)) && ($start_from > '')) {
			return 'bad ID';
		}	
		
		$root->setAttribute('element-id', $start_from);
		$this->addMenuXMLNodes($root, $menu_data, get_array_value($options, 'depth', -1), $options);

		return $xml;

	}


	/**
	 * Updates menu element record
	 *
	 * @param array $input data to use. It may specially prepared array or even unfiltered POST/GET input
	 * @return mixed true on success, message string on failure
	 */
	private function elementUpdate($input) {

		// configuration check
		if (!preg_match(REGEXP_IDENTIFIER, $this->CONFIG['table_menu'])) {
			return 'некорректное имя таблицы';
		}
		$table = $this->CONFIG['table_menu'];

		// filter input
		$input_filter = array(
			'id'            => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^-?[0-9]+$~ui')),
			'alias'         => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'parent'        => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[0-9]+$~ui')),
			'caption'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Za-яА-Я\s0-9/\-_:.,=+!@#$%^&*()"]+$~ui')),
			'page'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => REGEXP_ALIAS)),
			'link'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(https?://)?[a-zA-Z0-9\-/.,=&?_]+(\?.*)?$~i')),
			'picture'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-_.]+\.(jpg|jpeg|gif|png)$~ui')),
			'text'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Za-яА-Я\s0-9\-_:.,=+!@#$%^&*()<>"/]+$~smui')),
			'style_content' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9\-]+$~ui')),
			'style_item'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(([a-zA-Z\-]+\s*:\s*[a-zA-Z0-9\-;%\s]+)+|[a-zA-Z0-9\s\-_]+)$~ui')),
			'class_item'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\-][a-zA-Z0-9\-\s]*$~ui')),
			'add_more'      => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Za-яА-Я\s0-9\-_:]+$~ui')),
			'hidden'        => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(on|)$~ui')),
			'title'         => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9а-яА-Я\s\!\@\#\$\%\^\&\*\(\)\-\=\+\,\.\?\:\№]+$~ui')),
			'meta'          => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^.*$~smui'))
		);
		$_INPUT = get_filtered_input($input_filter);

		// shorthand
		$element_id = $_INPUT['id'];

		// some special mode
		$insert_mode = $element_id < 0;

		// pull existing values to replace incorrect input
		$q = CMS::$DB->query("select * from $table where id = $element_id");
		if ($current = $q->fetch(PDO::FETCH_ASSOC)) {
			foreach($current as $index=>$value) {
				if (                                                     // replace input value with existing, if:
					isset($_INPUT[$index]) &&                            // input field exists in the base
					($_INPUT[$index] == '') &&                           // AND filtered value is empty
					((@$_POST[$index] > '') || (@$_GET[$index] > '')) && // AND original POST/GET contains non-empty value
					($value > '')                                        // AND existsing value is not empty
					) {                                                  // all that mean that input was incorrect
					$_INPUT[$index] = $value;
				}
			}
		}

		// on insert, we will need new ordermark to
		$new_ordermark = CMS::$DB->querySingle("select ifnull(max(ordermark),0)+1 from $table");

		// choose the proper SQL
		$sql =
			$insert_mode

			? " insert into $table ".
			  " ( parent_id,  caption,  page,  link,  ordermark,  alias,  text,  picture,  style_content,  style_item,  class_item,  hidden,  title,  meta ) ".
			  " values ".
			  " (:parent_id, :caption, :page, :link, :ordermark, :alias, :text, :picture, :style_content, :style_item, :class_item, :hidden, :title, :meta ) "

			: " update $table set ".
			  "     parent_id     = :parent_id,     ".
			  "     caption       = :caption,       ".
			  "     page          = :page,          ".
			  "     link          = :link,          ".
			  "     alias         = :alias,         ".
			  "     text          = :text,          ".
			  "     picture       = :picture,       ".
			  "     style_content = :style_content, ".
			  "     style_item    = :style_item,    ".
			  "     class_item    = :class_item,    ".
			  "     hidden        = :hidden,        ".
			  "     title         = :title,         ".
			  "     meta          = :meta           ".
			  " where id = :id "
		;

		// take caption from linked page title if not set, cancel if nothing found
		if ($_INPUT['caption'] == '') {
			if ($_INPUT['title'] > '') {
				$_INPUT['caption'] = $_INPUT['title'];
			} else {
				if (module_get_config('content', $content_module_config)) {
					$_INPUT['caption'] = CMS::$DB->querySingle("select title from `{$content_module_config['config']['table']}` where alias = '{$_INPUT['link']}'");
				}
			}
		}
		if ($_INPUT['caption'] == '') {
			return 'Некорректный заголовок';
		}

		$query_params = array(
			'id'            => $_INPUT['id'],
			'parent_id'     => $_INPUT['parent'],
			'caption'       => $_INPUT['caption'],
			'page'          => trim($_INPUT['page'] == '') ? '' : $_INPUT['page'],
			'link'          => $_INPUT['link'],
			'ordermark'     => $new_ordermark,
			'alias'         => trim($_INPUT['alias']) > '' ? $_INPUT['alias'] : strtolower(create_guid()),
			'text'          => $_INPUT['text'],
			'picture'       => $_INPUT['picture'],
			'style_content' => $_INPUT['style_content'],
			'style_item'    => $_INPUT['style_item'],
			'class_item'    => $_INPUT['class_item'],
			'hidden'        => $_INPUT['hidden'] > '' ? 1 : 0,
			'title'         => $_INPUT['title'],
			'meta'          => $_INPUT['meta']
		);

		// some items are unnercessary when inserting or updating, PDO will get mad of them, so remove them!
		if ($insert_mode) {
			unset($query_params['id']);
		} else {
			unset($query_params['ordermark']);
		}

		// ok, go
		$prepared = CMS::$DB->prepare($sql);
		if ($prepared->execute($query_params) == false) {
			return $prepared->errorInfo[2].')';
		}
		return true;
	}


	/**
	 * Moves menu element upper in its submenu
	 *
	 * @param string|int $element_id menu element identifier to move
	 * @return mixed true on success, error string on failure
	 */
	private function elementMoveUp($element_id) {

		if (!is_numeric($element_id)) {
			return 'bad ID';
		}
		if (!preg_match(REGEXP_IDENTIFIER, $this->CONFIG['table_menu'])) {
			return 'некорректное имя таблицы';
		}

		$table = CMS::$DB->lb . $this->CONFIG['table_menu'] . CMS::$DB->rb;

		// make it faster and safer
		CMS::$DB->exec("begin transaction");

		// get current order-mark
		$current = CMS::$DB->querySingle("select ordermark from $table where id = $element_id");

		// get maximal previous (not sure that uninterrupted numeric row there)...
		$swap_order = CMS::$DB->querySingle("select max(ordermark) from $table where ordermark < $current and parent_id = (select parent_id from $table where id = $element_id)");

		// check if there place to move
		if ($swap_order == '') {
			CMS::$DB->exec("rollback");
			return true;
		}

		// get ID
		$swap_id = CMS::$DB->querySingle("select id from $table where ordermark = $swap_order");

		// swap items
		CMS::$DB->query("update $table set ordermark = $swap_order where id = $element_id");
		CMS::$DB->query("update $table set ordermark = $current    where id = $swap_id");

		// yeah finished
		CMS::$DB->exec("commit transaction");
		return true;
	}

	/**
	 * Moves menu element lower in its submenu
	 *
	 * @param string|int $element_id menu element identifier to move
	 * @return mixed true on success, error string on failure
	 */
	private function elementMoveDown($element_id) {

		if (!is_numeric($element_id)) {
			return 'bad ID';
		}
		if (!preg_match(REGEXP_IDENTIFIER, $this->CONFIG['table_menu'])) {
			return 'некорректное имя таблицы';
		}

		$table = CMS::$DB->lb . $this->CONFIG['table_menu'] . CMS::$DB->rb;

		// make it faster and safer
		CMS::$DB->exec("begin transaction");

		// get current order-mark
		$current = CMS::$DB->querySingle("select ordermark from $table where id = $element_id");

		// get maximal previous (not sure that uninterrupted numeric row there)...
		$swap_order = CMS::$DB->querySingle("select min(ordermark) from $table where ordermark > $current and parent_id = (select parent_id from $table where id = $element_id)");

		// check if there place to move
		if ($swap_order == '') {
			CMS::$DB->exec("rollback");
			return true;
		}

		// get ID
		$swap_id = CMS::$DB->querySingle("select id from $table where ordermark = $swap_order");

		// swap items
		CMS::$DB->query("update $table set ordermark = $swap_order where id = $element_id");
		CMS::$DB->query("update $table set ordermark = $current    where id = $swap_id");

		// yeah finished
		CMS::$DB->exec("commit transaction");
		return true;
	}


	/**
	 * Deletes menu element
	 *
	 * @param string|int $element_id menu element identifier to delete
	 * @return mixed true on success, error string on failure
	 */
	private function elementDelete($element_id) {

		if (!is_numeric($element_id)) {
			return 'bad ID';
		}
		if (!preg_match(REGEXP_IDENTIFIER, $this->CONFIG['table_menu'])) {
			return 'некорректное имя таблицы';
		}

		$table = CMS::$DB->lb . $this->CONFIG['table_menu'] . CMS::$DB->rb;

		CMS::$DB->exec("update $table set parent_id = '' where parent_id = $element_id");
		CMS::$DB->exec("delete from $table where id = $element_id");
		return true;

	}
}

?>