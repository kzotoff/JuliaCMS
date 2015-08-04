<?php //> Й <- UTF mark

session_start();

// for the name of the correct sorting and uppercase!
mb_internal_encoding('UTF-8');

// connect to config and useful things
require_once('conf.php');
require_once('lib/logger.php');
require_once('lib/registry.php');
require_once('lib/common.php');
require_once('lib/pdowrapper.php');
require_once('lib/module_base_class.php');

// shorthand
$R = Registry::GetInstance();

// connect to the DB
$DB = new PDOWrapper('sqlite', DB_PATH);
Registry::Set('db',$DB);

// restore _GET (broken by mod_rewrite) using REQUEST_URI
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $more_params);
$_GET = array_merge($_GET, $more_params);
logthis('GET restored: '.print_r($_GET, 1));

// request default page if not specified
if (!isset($_GET['p_id']) || ($_GET['p_id'] == '')) {
	$_GET['p_id'] = DEFAULT_PAGE_ALIAS;
}
logthis('page alias to show: '.$_GET['p_id']);

// apply security limitations
require_once('security.php');
logthis('securuty applied');

// load module descriptions and load main script files
$modules = array();
$modules_scan = scandir(MODULES_DIR);
foreach ($modules_scan as $module_name) {
	if (substr($module_name, 0, 1) == '.') {
		continue;
	}
	$config_filename = MODULES_DIR.$module_name.'/config.json';
	if (file_exists($config_filename)) {
		$modules[$module_name] = json_decode(file_get_contents($config_filename), true);
		include_once(MODULES_DIR.$module_name.'/'.$modules[$module_name]['main_script']);
		logthis('module "'.$module_name.'" loaded');
	} else {
		logthis('module definition file not found for module '.$module_name, ZLOG_LEVEL_WARNING);
	}
}
logthis('all modules loaded');
$R['modules'] = $modules;

// AJAX-proxy: call special function and just return its output, overriding normal flow

$ajaxproxy = (isset($_POST['ajaxproxy']) ? $_POST['ajaxproxy'] : (isset($_GET['ajaxproxy']) ? $_GET['ajaxproxy'] : false));

if ($ajaxproxy) {
	$module_name = $ajaxproxy;
	if (!isset($R['modules'][$module_name])) {
		terminate('', '404 module not found');
	}
	
	if (isset($module['disabled']) && ($module['disabled'] === true)) {
		terminate('', '403 module disabled');
	}

	$module_object = new $R['modules'][$module_name]['class_name']($module_name);
	echo $module_object->AJAXHandler();
	terminate();
}

// well, this is main template
$template = file_get_contents('template.html');

// add modules' CSS and JS links
foreach ($modules_apply_order as $module_name) {
	if (!isset($R['modules'][$module_name])) {
		logthis('module description not loaded: '.$module_name, ZLOG_LEVEL_WARNING);
		continue;
	}
	logthis('connecting external files for module: '.$module_name);
	$module = $R['modules'][$module_name];
	if (isset($module['disabled']) && ($module['disabled'] === true)) continue; // only if module isn't disabled

	if (isset($module['css'])) {
		foreach ($module['css'] as $add_css) {
			$template = add_CSS($template, MODULES_DIR.$module_name.'/'.$add_css);
			logthis('CSS added: '.MODULES_DIR.$module_name.'/'.$add_css);
		}
	}
	if (isset($module['js'])) {
		foreach ($module['js'] as $add_js) {
			$template = add_JS($template, MODULES_DIR.$module_name.'/'.$add_js);
			logthis('JS added: '.MODULES_DIR.$module_name.'/'.$add_js);
		}
	}

	if (isset($module['break_after']) && ($module['break_after'] === true)) break; // is it last?
}

// module objects will be here
$modules_cache = array();

// call modules' input parsers
foreach ($modules_apply_order as $module_name) {
	logthis('trying input parser for module: '.$module_name);

	// chech if description exists
	if (!isset($R['modules'][$module_name])) {
		logthis('module description not loaded: '.$module_name, ZLOG_LEVEL_WARNING);
		continue;
	}

	$module = $R['modules'][$module_name];
	
	// check if module should be skipped
	if (isset($module['disabled']) && ($module['disabled'] === true)) continue;
	
	// create object instance if not yet
	if (!isset($module_cache[$module_name])) {
		$modules_cache[$module_name] = new $module['class_name']($module_name);
	}

	$template = $modules_cache[$module_name]->RequestParser($template);
	logthis('parser finished for module: '.$module_name);

	if (isset($module['break_after']) && ($module['break_after'] === true)) break;
}

// apply template processors. look for comments a bit before
foreach ($modules_apply_order as $module_name) {
	logthis('trying template processor at module: '.$module_name);

	// chech if description exists
	if (!isset($R['modules'][$module_name])) {
		logthis('module description not loaded: '.$module_name, ZLOG_LEVEL_WARNING);
		continue;
	}
	
	$module = $R['modules'][$module_name];
	if (isset($module['disabled']) && ($module['disabled'] === true)) continue;
	
	if (!isset($modules_cache[$module_name])) {
		$modules_cache[$module_name] = new $module['class_name']($module_name);
	}
	logthis('applying template processor at module: '.$module_name);
	$template = $modules_cache[$module_name]->ContentGenerator($template);
	logthis('template processor finished at module: '.$module_name);
	
	if (isset($module['break_after']) && ($module['break_after'] === true)) break;
}

// remove unused templates
$template = preg_replace('~</?template.*?>~', '', $template);
logthis('unused templates removed');

// sign it!
$template = add_meta($template, 'name', 'generator', 'JuliaCMS Valenok Edition');

// yeah we did it!
logthis('completed, adding log results and flushing!');

echo $template;
//echo '<pre>'.logger_out(ZLOG_LEVEL_DEBUG, array('target'=>'return')).'</pre>';

terminate();

?>