<?php
require('_init.php');
require('lib/xslt.cls.php');
require('lib/errors.cls.php');
header('Content-Type: text/html; charset=utf-8');
$_='';

if (!user_allowed_to('manage users')) {
	header('Location: main.php');
	exit;
}

switch (@$_POST['action']) {
	case 'adduser':
		if (trim(@$_POST['password1'])!=trim(@$_POST['password2'])) {
			header('Location: security.php?msg=2');
			exit;
		}
		if ((($username = get_as_regexp(@$_POST['login'],$re_name)) > '')
			&&(($password = get_as_regexp(trim(@$_POST['password1']),$re_name)) > '')
			) {
				if ($DB->querySingle('select count(*) from users where login=\''.$username.'\'') != '0') {
					header('Location: security.php?msg=3');
					break;
				}
				$DB->exec('insert into '.$tab_users.' (id, login, psw, level) values (\''.create_guid().'\', \''.$username.'\',\''.pass_enc($password).'\',2)');
				header('Location: security.php?msg=1');
				exit;
			}
		break;
	case 'addaccess':
		$id = get_as_regexp(@$_POST['id'], $re_guid);
		if (($id > '') && ($_SESSION['gallery_'.DB_PREFIX]['folder_id'] != $root_guid)&&($DB->querySingle('select count(*) as c from acl where object_id=\''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\' and user_id=\''.$id.'\'') == 0)) {
			$DB->exec('insert into '.$tab_acl.' (id, object_id, user_id, permission) values (\''.create_guid().'\', \''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\', \''.$id.'\', 1)');
			exit;
		}
		break;
	case 'cancelaccess':
		$ids=@$_POST['id'];
		foreach ($ids as $id){
			if ((get_as_regexp($id, $re_guid) > '')&&($_SESSION['gallery_'.DB_PREFIX]['folder_id'] != $root_guid)) {
				$DB->exec('delete from '.$tab_acl.' where object_id=\''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\' and user_id=\''.$id.'\'');
			}
		}
		exit;
		break;
	case 'setpublic':
		if (($_SESSION['gallery_'.DB_PREFIX]['folder_id'] != $root_guid) && (is_numeric($_POST['value']))) {
			$DB->exec('update `'.$tab_files.'` set `public`='.$_POST['value'].' where `id`=\''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\'');
		}
		exit;
		break;

}

switch (@$_GET['msg']) {
	case '1': $_ .= '<message>user created</message>'; break;
	case '2': $_ .= '<message>passwords doesn\'t match</message>'; break;
	case '3': $_ .= '<message>user already exists</message>';
}

$_ .= '<userlist>';
$query = $DB->query('select `id`,`login` from `'.$tab_users.'` order by `login`');
while ($data = $query->fetch(PDO::FETCH_ARRAY)) {
	$_ .= '<user id="'.$data['id'].'">'.$data['login'].'</user>';
}
$_ .= '</userlist>';
if ($_SESSION['gallery_'.DB_PREFIX]['folder_id'] != $root_guid) {
	$_ .= '<current_folder>';
	$_ .= '<id>'.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'</id>';
	$query = $DB->query('select * from `'.$tab_files.'` where `id`=\''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\'');
	if ($data = $query->fetch(PDO::FETCH_ARRAY))) {
		$_ .= '<caption>'.$data['caption'].'</caption>';
		$_ .= '<public>'.($data['public'] == '' ? 0 : $data['public']).'</public>';
		
		$_ .= '<acl>';
		$query = $DB->query('select `a`.`user_id`,`u`.`login` from `'.$tab_acl.'` `a` left join `'.$tab_users.'` `u` on `u`.`id`=`a`.`user_id` where `object_id`=\''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\'');
		while ($data = $query->fetch(PDO::FETCH_ARRAY)) {
			$_.='<user id="'.$data['user_id'].'">'.$data['login'].'</user>';
		}
		$_.='</acl>';
	}
	$_.='</current_folder>';
}

$_='<root>'.$_.'</root>';
echo (isset($_GET['showxml'])?$_:'').XSLTransform($_,'security.xsl');
?>