<?php //> Й <- UTF mark

// utility for fast site upload. remove or comment this line after final production deployment
include_once('lib/uploader.php');

session_start();

// some hosters prohibit to use php_value at .htaccess
ini_set('include_path', './PEAR');

// make sure of utf-8 browser encoding
header('Content-Type: text/html; charset=utf-8');

// for the name of the correct sorting and uppercase!
mb_internal_encoding('UTF-8');

// connect to config and useful things
require_once('userfiles/_data_common/conf.php');
require_once('lib/cms.php');
require_once('lib/logger.php');
require_once('lib/xml_to_array.php');
require_once('lib/common.php');
require_once('lib/pdowrapper.php');
require_once('lib/module_base_class.php');

// connect to the DB
CMS::$DB = new PDOWrapper('sqlite', DB_PATH);

// restore _GET (damaged by mod_rewrite) using REQUEST_URI
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $more_params);
$_GET = array_merge($_GET, $more_params);
logthis('GET restored: '.print_r($_GET, 1));

// set default page if not specified in request
$_GET['p_id'] = get_array_value($_GET, 'p_id', DEFAULT_PAGE_ALIAS, '~.+~');
logthis('page alias to show: '.$_GET['p_id']);

// apply security limitations
require_once('lib/security.php');
login_logout();
logthis('security applied');

// check input for intersected keys
if (count(array_intersect_key($_POST, $_GET)) > 0) {
	terminate('POST and GET has duplicate keys', 'POST and GET has duplicate keys', 403);
}

// AJAX-proxy mode: just call special function and return its output, skipping normal flow
if ($module_name = isset($_POST['ajaxproxy']) ? $_POST['ajaxproxy'] : (isset($_GET['ajaxproxy']) ? $_GET['ajaxproxy'] : false)) {
	module_init($module_name);
	echo CMS::$cache[$module_name]['object']->AJAXHandler();
	terminate();
}

// init modules
foreach ($modules_apply_order as $module_name) {
	module_init($module_name);
}

// well, this is main template, we will transform it
$template = file_get_contents('userfiles/template/template.html');

// immediately add core libraries and stylesheets to ensure their minimal priority
add_JS(array(
	'lib/jquery.js',
	'lib/jquery-ui.js',
	'lib/jquery.tablesorter.min.js',
	'tinymce/tinymce.min.js',
	'tinymce/jquery.tinymce.min.js',
	'lib/lib.js',
));

add_CSS(array(
	'lib/jquery-ui.css',
	'lib/tablesorter.css',
	'lib/bootstrap.min.css',
	'lib/core.css',
));

// first loop: add modules' CSS and JS links
foreach ($modules_apply_order as $module_name) {

	// check if module OK
	if (!isset(CMS::$cache[$module_name])) {
		logthis('module description not loaded: '.$module_name, ZLogger::LOG_LEVEL_WARNING);
		continue;
	}
	// also module may be disabled
	if (get_array_value(CMS::$cache[$module_name]['config'], 'disabled', false) === true) {
		continue;
	}

	logthis('connecting external files for module: '.$module_name);
	add_CSS(get_array_value(CMS::$cache[$module_name]['config'], 'css', array()), MODULES_DIR.$module_name.'/');
	add_JS(get_array_value(CMS::$cache[$module_name]['config'], 'js', array()), MODULES_DIR.$module_name.'/');
	
	// check if we should stop here
	if (get_array_value(CMS::$cache[$module_name]['config'], 'break_after', false)) {
		break;
	}

}

// second loop: input parsers
// look previous loop for comments
foreach ($modules_apply_order as $module_name) {
	logthis('trying input parser for module: '.$module_name);

	if (!isset(CMS::$cache[$module_name])) {
		logthis('module description not loaded: '.$module_name, ZLogger::LOG_LEVEL_WARNING);
		continue;
	}
	if (get_array_value(CMS::$cache[$module_name]['config'], 'disabled', false) === true) {
		continue;
	}

	$template = CMS::$cache[$module_name]['object']->requestParser($template);
	logthis('parser finished for module: '.$module_name);

	if (get_array_value(CMS::$cache[$module_name]['config'], 'break_after', false)) {
		break;
	}
}

// third loop: template processors
foreach ($modules_apply_order as $module_name) {
	logthis('trying template processor at module: '.$module_name);

	if (!isset(CMS::$cache[$module_name])) {
		logthis('module description not loaded: '.$module_name, ZLogger::LOG_LEVEL_WARNING);
		continue;
	}
	
	if (get_array_value(CMS::$cache[$module_name]['config'], 'disabled' === true)) {
		continue;
	}
	
	logthis('applying template processor at module: '.$module_name);
	$template = CMS::$cache[$module_name]['object']->ContentGenerator($template);
	logthis('template processor finished at module: '.$module_name);
	
	if (get_array_value(CMS::$cache[$module_name]['config'], 'break_after', false)) {
		break;
	}
}

// remove unused templates
$template = preg_replace('~</?macro.*?>~', '', $template);
$template = preg_replace('~\[/?macro.*?\]~', '', $template);

// back-replace protected templates
$template = str_replace('<protected-macro', '<macro', $template);
$template = str_replace('[protected-macro', '[macro', $template);
$template = str_replace('</protected-macro', '</macro', $template);
$template = str_replace('[/protected-macro', '[/macro', $template);
logthis('unused templates removed');

$template = popup_messages_to_template($template);
logthis('popups added');

// flush CSS and JS storages
$template = flush_CSS($template);
$template = flush_JS($template);

// sign it!
$template = add_meta($template, 'name', 'generator', 'JuliaCMS Valenok Edition');

// yeah we did it!
logthis('completed, adding log results and flushing!');

echo $template;

terminate();

?>