<?php
// redirect to installer if no config found
if (!file_exists('_config.php')) {
	header('Location: install/start.php');
	exit;
}

if (file_exists('install')) {
	if (isset($_GET['removeinstaller'])) {
		@unlink('install/start.php');
		@unlink('install/write.php');
		@rmdir('install');
	} else {
		die('installation directory must be deleted. <a href="main.php?removeinstaller">Do it!</a>.<br />Should this message appear again, remove "install" directory manually.');
	}
}

//require('../lib/accesslog.php');
//$accesslog=log_access('gallery');

session_start();

// db connect & init ////////////////////////////////////////////////////////////////////
require_once('_config.php');
require_once('pdowrapper.php');

$DB = new PDOWrapper('sqlite', DB_PATH);

// constants ////////////////////////////////////////////////////////////////////////////
$tab_acl   = (DB_PREFIX > '' ? DB_PREFIX.'_' : '') . 'acl';
$tab_files = (DB_PREFIX > '' ? DB_PREFIX.'_' : '') . 'files';
$tab_users = (DB_PREFIX > '' ? DB_PREFIX.'_' : '') . 'users';













// TAG_TODO log admin automatically
$_SESSION['gallery_'.DB_PREFIX]['user_login'] = 'admin';
$_SESSION['gallery_'.DB_PREFIX]['user_id'] = $DB->querySingle('select id from '.$tab_users.' where login=\'admin\'');
$_SESSION['gallery_'.DB_PREFIX]['user_level'] = 0;













$re_guid = '~^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$~';
$re_name = '~^[a-zA-Zа-яёА-ЯЁ0-9_\.\-\s]{1,250}$~u';
$re_alias = '~^[a-zA-Z0-9]{1,100}$~';
$re_url = '~^(?:(?:ht|f)tps?://)?(?:[\\-\\w]+:[\\-\\w]+@)?(?:[0-9a-z][\\-0-9a-z]*[0-9a-z]\\.)+[a-z]{2,6}(?::\\d{1,5})?(?:[?/\\\\#][?!^$.(){}:|=[\\]+\\-/\\\\*;&\~#@,%\\wА-Яа-я]*)?$~';
$root_guid = '00000000-0000-0000-0000-000000000000';
$msg_err_bad_filename = 'Неправильное имя файла';
$msg_err_bad_url = 'Корявая ссылка';
$msg_err_bad_file_format = 'Неизвестный формат файла';
$msg_err_alias_exists = 'Алиас уже используется';
// some more init ///////////////////////////////////////////////////////////////////////
setlocale(LC_ALL, 'ru_RU.UTF-8');
ini_set('magic_quotes_gpc', 'off');

function create_guid() {
	if (function_exists('com_create_guid') === true) {
		return strtolower(trim(com_create_guid(), '{}'));
	}
	return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
}

function get_as_regexp($str, $regexp) {
	return (preg_match($regexp, $str)!=1?'':$str);
}

function pass_enc($password) {
	return sha1(sha1($password).'welgkregl7657kljKJJKHhiiKMM-KK23093pt4iorj3s3mjkjnDwkjfnewkjn');
}

function user_allowed_to($action, $id = null) {
	global $DB, $tab_acl, $tab_files, $tab_users, $re_guid;
	switch ($action) {
		case 'add photo':
			if (isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']<=1)) {
				return true;
			}
			break;
		case 'add album':
			if (isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']<=1)) {
				return true;
			}
			break;
		case 'add album':
			if (isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']==0)) {
				return true;
			}
			break;	
		case 'manage users':
			if (isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']==0)) {
				return true;
			}
			break;
		case 'delete item':
			if ((isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']==0))) {
				return true;
			}
			break;
		case 'move item':
			if ((isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']==0))) {
				return true;
			}
			break;
		case 'view item':
			if (get_as_regexp($id, $re_guid) == '') {
				return false;
			}
			if (($id===0)
				||(isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']==0))
				||($DB->querySingle('select permission from '.$tab_acl.' where user_id=\''.(isset($_SESSION['gallery_'.DB_PREFIX]['user_id'])?$_SESSION['gallery_'.DB_PREFIX]['user_id']:-1).'\' and object_id=\''.$id.'\'')==1)
				||(in_array($DB->querySingle('select public from '.$tab_files.' where id=\''.$id.'\''), array(0, 2)))
				) {
				return true;
			}
			break;
	}
	return false;
}
?>