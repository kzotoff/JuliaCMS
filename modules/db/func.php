<?php //> Й <- UTF mark

/*

common DB working - data change, table generating and so on

(!) this module can use separate database connection

*/
require_once('lib/pdowrapper.php');
require_once('api.php');
require_once('ui.php');
require_once('helpers.php');

if (file_exists('userfiles/_data_modules/db/userapi/userapi.php' )) { include_once('userfiles/_data_modules/db/userapi/userapi.php'); }
if (file_exists('userfiles/_data_modules/db/fields.php'          )) { include_once('userfiles/_data_modules/db/fields.php');          }
if (file_exists('userfiles/_data_modules/db/reports.php'         )) { include_once('userfiles/_data_modules/db/reports.php');         }

class J_DB extends JuliaCMSModule {

	/**
	 * Database connection storage
	 *
	 * @name $DB
	 */
	var $DB;

	/**
	 * Common DB actions such as adding/editing/deleting records, context menu calls and some more useful things
	 *
	 * possible array items:
	 * the first group is responsoble for visual action representation
	 *
	 * 	caption   : item caption. Divider will be created if empty, links and scripts will be ignored
	 * 	image     : image to display left to the caption
	 * 	disabled  : if set to true, item will be shown as disabled. Special class (see XSLT stylesheet) will be
	 * 	            added and all parameters except caption and icon will be ignored
	 * 	style     : direct style definition 2(i.e., "color: blue;")
	 * 	class     : CSS class to add
	 * 	hidden    : item will not be included in the output at all
	 *
	 * the second group is responsible for proper client-server interaction
	 * 	link      : will create javascript onclick="location.href=''". Has the lowest priority (see below).
	 * 	            Note that "http://" prefix is required on absolute links
	 * 	js        : direct javascript for onclick event. Overrides "link" element
	 * 	db_action : call common DB action (call dialog or direct request)
	 * 	api       : force client-side script to make API call with this method name. Will override "link" and "js" items
	 *
	 * the third group defines HTML generators and API handlers
	 *
	 * @name $actions
	 */
	public static $actions = array(

		// just a sample action with all parameters possible
		'sample_action' => array(
			'caption'       => 'Sample item',
			'image'         => 'modules/db/images/pencil.png',
			'disabled'      => false,
			'class'         => 'test_class',
			'style'         => 'color: red;',
			'hidden'        => false,
			'link'          => 'http://ya.ru',
			'js'            => 'alert("OK");',
			'api'           => 'some_api_call'

		),

		// add an empty record (no questions)
		'record_add_empty' => array(
			'caption'       => 'Вставить пустую запись',
			'image'         => 'modules/db/images/blank.png',
			'api'           => 'record_add_empty'
		),

		// add an empty record (no questions)
		'record_add' => array(
			'caption'       => 'Новая запись',
			'image'         => 'modules/db/images/blank.png',
			'api'           => 'record_add'
		),

		// show edit dialog
		'record_edit' => array(
			'caption'       => 'Редактировать',
			'image'         => 'modules/db/images/pencil.png',
			'api'           => 'record_edit'
		),

		// edit a single value in dialog
		'edit_here' => array(
			'caption'       => 'Изменить...',
			'image'         => 'modules/db/images/pencil.png',
			'api'           => 'change_field'
		),

		// delete record, no questions
		'record_delete' => array(
			'caption'       => 'УДАЛИТЬ БЕЗ ВОПРОСОВ',
			'image'         => '',
			'api'           => 'record_delete'
		),

		// delete confirmation dialog
		'record_delete_confirm' => array(
			'caption'       => 'Удалить запись',
			'image'         => '',
			'api'           => 'record_delete_confirm'
		),

		// show comments to the record
		'comments_dialog' => array(
			'caption'       => 'Комментарии и документы',
			'image'         => '',
			'api'           => 'comments_dialog'
		),
		
		// return attached file
		'comments_get_attached' => array(
			'caption'       => 'Загрузить приложенный файл',
			'image'         => '',
			'api'           => 'comments_get_attached'
		),
	);

	/**
	 * Mapping between external API names and internal methods
	 *
	 * @name $methods
	 */
	private static $methods = array(
		'get_report_as_xml'          => array('class' => 'J_DB_API',  'method' => 'generateTableXML'),
		'record_add_empty'           => array('class' => 'J_DB_API',  'method' => 'recordAddEmpty'),
		'record_add'                 => array('class' => 'J_DB_UI',   'method' => 'recordAdd'),
		'record_edit'                => array('class' => 'J_DB_UI',   'method' => 'recordEdit'),
		'generate_editorial_xml'     => array('class' => 'J_DB_API',  'method' => 'generateEditorialXML'),
		'generate_comments_xml'      => array('class' => 'J_DB_API',  'method' => 'generateCommentsXML'),
		'comments_dialog'            => array('class' => 'J_DB_UI',   'method' => 'commentsDialog'),
		'comments_add'               => array('class' => 'J_DB_API',  'method' => 'commentsAdd'),
		'comments_delete'            => array('class' => 'J_DB_API',  'method' => 'commentsDelete'),
		'record_delete_confirm'      => array('class' => 'J_DB_UI',   'method' => 'recordDeleteConfirm'),
		'record_delete'              => array('class' => 'J_DB_API',  'method' => 'recordDelete'),
		'record_insert'              => array('class' => 'J_DB_API',  'method' => 'recordInsert'),
		'record_save'                => array('class' => 'J_DB_API',  'method' => 'recordSave'),
		'comments_get_attached'      => array('class' => 'J_DB_API',  'method' => 'commentsGetAttached'),

		// TAG_TODO вот эти объявления надо вынести в userapi и совмещать при использовании
		'template_to_message_dialog' => array('class' => 'UserLogic', 'method' => 'templateToMessageDialog'),
		'template_to_messages'       => array('class' => 'UserLogic', 'method' => 'templateToMessages'),
		'send_outbox_messages'       => array('class' => 'UserLogic', 'method' => 'messagesSend'),
		'messages_delete_all'        => array('class' => 'UserLogic', 'method' => 'messagesDeleteAll'),

	);

	/**
	 * Input filter both for AJAX handler and input filter
	 * User API function also will filter input themselves
	 *
	 * @name $input_filter
	 */
	public static $input_filter = array(
		'action'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_0-9]+$~ui')),
		'method'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_0-9]+$~ui')),
		'report_id'  => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_0-9]+$~ui')),
		'row_id'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z0-9_\-]+$~ui')),
		'field_name' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z_0-9]+$~ui'))
	);

	/**
	 *
	 */
	function __construct() {
		$this->DB = new PDOWrapper(
			CMS::$cache['db']['config']['config']['server_driver'],
			CMS::$cache['db']['config']['config']['server_host'],
			CMS::$cache['db']['config']['config']['server_login'],
			CMS::$cache['db']['config']['config']['server_password'],
			CMS::$cache['db']['config']['config']['server_db_name']
		);
		logthis('database connection initiated (but not connected yet)!');
	}

	/**
	 * Standard descendant
	 *
	 * able to call user API
	 */
	function requestParser($template) {

		// use both POST and GET!
		$merged_post_get = array_merge($_GET, $_POST);

		if (!isset($merged_post_get['module']) || ($merged_post_get['module'] != 'db')) {
			return $template;
		}

		// will redirect at the end if "target" become non-empty
		$redirect_target = false;

/*******************************************************************************************************/
		// TAG_TODO why calling API at request parser?

		// add field filters if report specified
		if (isset($merged_post_get['report_id']) && isset($this->REG['db_api_reports'][$merged_post_get['report_id']])) {
			foreach ($this->REG['db_api_reports'][$merged_post_get['report_id']]['fields'] as $field_part1 => $field_part2) {
				$field_definition = $this->getFullFieldDefinition($field_part1, $field_part2);
				$this->input_filter['edit_' . $field_definition['field']] = array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array('regexp' => '~^' . $field_definition['regexp'] . '$~msu')
				);
			}
		}

		// note that full filtering is used here as API functions may require unlimited parameter list
		$filtered_input = get_filtered_input(self::$input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));

		// call API and check if any special flags there
		$return_metadata = array();

		$this->callAPI($filtered_input, $return_metadata);

		if (($return_metadata['type'] == 'command') && ($return_metadata['command'] == 'reload')) {
			$redirect_target = $_SERVER['HTTP_REFERER'];
		}
/*******************************************************************************************************/

		// make redirection if was requested above
		if ($redirect_target) {
			terminate('', 'Location: '.$redirect_target, 302);
		}

		return $template;
	}


	/**
	 * Content generator - creates table-formed report
	 *
	 */
	function contentGenerator($template) {

		// append userapi scripts and CSS
		if (is_dir('userapi/js/')) {
			$user_js_files = scandir('userapi/js/');
			foreach ($user_js_files as $user_js_file) {
				if (pathinfo($user_js_file, PATHINFO_EXTENSION) == 'js') {
					add_JS('userapi/js/'.$user_js_file);
					logthis('userAPI script added: '.$user_js_file);
				}
			}
		}
		if (is_dir('userapi/css/')) {
			$user_css_files = scandir('userapi/css/');
			foreach ($user_css_files as $user_css_file) {
				if (pathinfo($user_css_file, PATHINFO_EXTENSION) == 'css') {
					add_CSS('userapi/css/'.$user_css_file);
					logthis('userAPI CSS added: '.$user_css_file);
				}
			}
		}

		// replace all templates to generated content
		while (preg_match( macro_regexp('db'), $template, $match) > 0) {

			// parse template parameters into array
			$params = parse_plugin_template($match[0]);

			// generate HTML
			if (!isset($params['id'])) {
				$table_html = '<b>[JuliaCMS][db] error:</b> no ID specified for the table';
			} else {
				// all API/UI require "report_id" parameter
				$params['report_id'] = $params['id'];
				$table_html = J_DB_UI::generateTable($params, $this->DB);
			}

			// replace
			$template = str_replace($match[0], $table_html, $template);
		}

		// yeah we are ready
		return $template;

	}

	/**
	 * AJAX requests handler
	 *
	 * nothing special - mainly API call
	 */
	function AJAXHandler() {

		$filtered_input = get_filtered_input(self::$input_filter, array(FILTER_GET_FULL, FILTER_POST_FULL));

		switch($filtered_input['action']) {
			case 'contextmenu':
				$report_id  = $filtered_input['report_id'];
				$row_id     = null; // TAG_TODO вот тут нужен идентификатор
				$field_name = null; // TAG_TODO и тут нужен
				return J_DB_UI::generateContextMenu($report_id, $row_id, $field_name, $this->DB);
				break;
			case 'call_api':
				return $this->callAPI($filtered_input, $return_metadata);
				break;
			default:
				return 'error: action not set';
				break;
		}
	}

	/**
	 * Entry point for API, UI and USER-API methods.
	 *
	 * Default metadata values are:
	 *    status : OK, type : plain, command : ''
	 * they will be added automatically, so you don't need to set all parameters.
	 * The following metadata values are used:
	 *    status  : OK or ERROR
	 *    type    : content type (plain, html, xml, json, command)
	 *    command : some command for AJAX receiver at client side
	 * These metadata will be sent as additional headers along with the text answer. The headers
	 * will be "X-JuliaCMS-Result-Status", "X-JuliaCMS-Result-Type" and "X-JuliaCMS-Result-Status"
	 * respectively.
	 *
	 * @param array $input data to use for calling deeper (for example, _GET contents may be placed here)
	 * @param array &$return_metadata metadata describing result operation (status, error text etc.)
	 * @param resource $DB (optional) database connection to use. If not specified, instance's connection will be used
	 * @return string API output. May be either something like "OK" as "successful" or data of any kind
	 *
	 */
	public function callAPI($input, &$return_metadata, $DB = false) {
		// start with empty metadata
		$return_metadata = array();

		// we need exactly one pass but with ability to break anywhere from it
		do {

			// determine API method name
			if (!isset($input['method'])) {
				$return_metadata['status'] = 'ERROR';
				$result = 'API ERROR : method not specified ('.__LINE__.')';
				break;
			}
			$method_name = $input['method'];

			// check if method definition exists
			if (!isset(self::$methods[$method_name])) {
				$return_metadata['status'] = 'ERROR';
				$result = 'API ERROR : unknown method ('.__LINE__.')';
				break;
			}
			$method = self::$methods[$method_name];

			// check if class exists (good idea for userland API)
			if (!class_exists($method['class'])) {
				$return_metadata['status'] = 'ERROR';
				$result = 'API ERROR : method class "'.$method['class'].'" not exists ('.__LINE__.')';
				break;
			}
			$method_class = $method['class'];

			// check if class method exists
			if (!method_exists($method['class'], $method['method'])) {
				$return_metadata['status'] = 'ERROR';
				$result = 'API ERROR : method "'.$method['method'].'" not exists ('.__LINE__.')';
				break;
			}
			$method_method = $method['method'];

			// all OK and we can call user API
			$result = $method_class::$method_method($input, $return_metadata, $DB ? $DB : $this->DB);

		} while (false);

		// add default metadata values
		$return_metadata_default = array('status'=>'OK', 'type'=>'plain', 'command'=>'');
		$return_metadata = array_merge($return_metadata_default, $return_metadata);

		// send our special headers
		header('X-JuliaCMS-Result-Status: '.$return_metadata['status']);
		header('X-JuliaCMS-Result-Type: '.$return_metadata['type']);
		header('X-JuliaCMS-Result-Command: '.$return_metadata['command']);

		return $result;

	}

}


?>