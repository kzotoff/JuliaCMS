<?php
header('Content-Type: text/html; charset=utf-8');

require('_init.php');
require('lib/xslt.cls.php');
require('lib/errors.cls.php');

// first try _GET id as id, then as alias
function get_album_id($try = '') {
	global $DB, $tab_files, $re_guid, $re_alias, $root_guid;
	if ($DB->querySingle('select count(`id`) from `'.$tab_files.'` where `id`=\''.get_as_regexp($try, $re_guid).'\'') != 0) {
		return $try;
	}
	if (($id = $DB->querySingle('select `id` from `'.$tab_files.'` where `name`=\''.get_as_regexp($try, $re_alias).'\'')) > '') {
		return $id;
	}
	if (isset($_GET['id'])) {
		return $root_guid;
	}
	return '';
}

if (($id = get_album_id(@$_GET['id'])) > '') {
	$_SESSION['gallery_'.DB_PREFIX]['folder_id'] = $id;
} else {
	$id = isset($_SESSION['gallery_'.DB_PREFIX]['folder_id']) ? $_SESSION['gallery_'.DB_PREFIX]['folder_id'] : $root_guid;
	$_SESSION['gallery_'.DB_PREFIX]['folder_id'] = $id;
}

$_ = '';

if (!user_allowed_to('view item',$id)) {
	$_SESSION['gallery_'.DB_PREFIX]['folder_id'] = $root_guid;
	header('Location: main.php?id=0');
	exit;
}

$caption=($id>0) ? $DB->querySingle('select `caption` from `'.$tab_files.'` where `id`=\''.$id.'\'') : null;
$parent_id=($id>0) ? $DB->querySingle('select `parent_id` from `'.$tab_files.'` where `id`=\''.$id.'\'') : null;

///////////////////////////////////////////////////////////////////////////////////////////////////
function create_list_xml() {
	global $DB, $tab_acl, $tab_files, $tab_users;
	global $id;
	
	$r='';
	$sql=' select * from `'.$tab_files.'` '
		.' where `parent_id`=\''.$id.'\''
		.' and ('.(isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])?$_SESSION['gallery_'.DB_PREFIX]['user_level']:-1).'=0 '
		.' 		or type=0 '
		.'		or public=0 '
		.'		or `id` in (select `object_id` from `'.$tab_acl.'` where `user_id`=\''.(isset($_SESSION['gallery_'.DB_PREFIX]['user_id'])?$_SESSION['gallery_'.DB_PREFIX]['user_id']:-1).'\' and `permission`=1)'
		.'	)'
		.' order by `type` desc, `caption`';
	$query = $DB->query($sql);
	while ($data = $query->fetchArray()) {
		$r.='<albumitem id="'.$data['id'].'">'
			.'<type>' . $data['type'] . '</type>'
			.'<preview>' . ($data['type'] == 1 ? '' : 'preview/'.$data['name']) . '</preview>'
			.'<name>' . ($data['type'] == 1 ? $data['name'] : 'gallery/'.$data['name']) . '</name>'
			.'<caption><![CDATA[' . $data['caption'] . ']]></caption>'
			.'<keywords><![CDATA[' . $data['keywords'] . ']]></keywords>'
			.'<description><![CDATA[' . $data['description'] . ']]></description>'
			.'<public>' . $data['public'] . '</public>'
			.'<full_path>' . ABSOLUTE_DIR.'gallery/'.$data['name'] . '</full_path>'
			.'</albumitem>';
	}
	return '<list>'.$r.'</list>';
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function create_path_xml() {
	global $DB;
	global $tab_acl, $tab_files, $tab_users, $root_guid;
	$r = '';
	$folder_id = $_SESSION['gallery_'.DB_PREFIX]['folder_id'];
	while ($folder_id != $root_guid) {
		$q = $DB->query('select caption, name, parent_id, public from '.$tab_files.' where id=\''.$folder_id.'\'');
		list ($folder_caption, $name, $parent_id, $public) = $q->fetchArray();
		$more = '';
		$more .= '<folder'.($folder_id == $_SESSION['gallery_'.DB_PREFIX]['folder_id'] ? ' current="yes"' : '').'>';
		$more .= '<id>'.($public == 2 ? $folder_id : $name).'</id>';
		$more .= '<caption><![CDATA['.$folder_caption.']]></caption>';
		$more .= '</folder>';
		$r = $more . $r;
		if ($public == 2) {
			$r = '<folder><id>0</id><caption>...</caption></folder>'. $r;
			break;
		}
		$folder_id = $parent_id;
	}
	$r = '<folder root="yes"'.($_SESSION['gallery_'.DB_PREFIX]['folder_id'] == $root_guid ?' current="yes"':'').'><id>0</id><caption>Начало</caption></folder>' . $r;
	return '<folderpath>'.$r.'</folderpath>';
}

///// error reporting and other messaging /////////////////////////////////////////////////////////
if (isset($_SESSION['gallery_'.DB_PREFIX]['message'])&&($_SESSION['gallery_'.DB_PREFIX]['message']>'')) {
	$_ .= '<message>'.$_SESSION['gallery_'.DB_PREFIX]['message'].'</message>';
	unset($_SESSION['gallery_'.DB_PREFIX]['message']);
}

///// folder list /////////////////////////////////////////////////////////////////////////////////
$_ .= '<folderlist>';
$query = $DB->query('select id, caption from '.$tab_files.' where type = 1 order by caption');
while ($data = $query->fetchArray()) {
	$_ .= '<folder id="'.$data['id'].'">'.$data['caption'].'</folder>';
}
$_ .= '</folderlist>';


///// some options ////////////////////////////////////////////////////////////////////////////////
$options = array();
array_push($options,'logged="'.(isset($_SESSION['gallery_'.DB_PREFIX]['user_id'])?'yes':'no').'"');
array_push($options,'ghostmode="'.(@$accesslog=='no_log'?'yes':'no').'"'); // logger may be disabled and variable not initialized
if (isset($_SESSION['gallery_'.DB_PREFIX]['user_level'])&&($_SESSION['gallery_'.DB_PREFIX]['user_level']<=1)) {
	array_push($options,'adder="yes"');
}
if (isset($_SESSION['gallery_'.DB_PREFIX]['user_level']) && ($_SESSION['gallery_'.DB_PREFIX]['user_level'] == 0)) {
	array_push($options,'admin="yes"');
}
array_push($options,'show_path="yes"');
$_.='<options '.implode(' ',$options).' />';

///// misc data ///////////////////////////////////////////////////////////////////////////////////
if (isset($_SESSION['gallery_'.DB_PREFIX]['user_id'])) {
	$_.='<userinfo><id>'.$_SESSION['gallery_'.DB_PREFIX]['user_id'].'</id><login>'.$_SESSION['gallery_'.DB_PREFIX]['user_login'].'</login></userinfo>';
}

$_.=create_list_xml();
$_.=create_path_xml();

$_='<root>'.$_.'</root>';
echo (isset($_GET['showxml'])?$_:'').(isset($_GET['notransform'])?'':XSLTransform($_,'main.xsl'));
?>