<?php
require('_init.php');

if (isset($_GET['logout'])) {
	unset($_SESSION);
	session_destroy();
} elseif (isset($_POST['login'])) {
	$login = get_as_regexp($_POST['login'],$re_name);
	$password = $_POST['password'];
	echo $tab_users;
	$query = $DB->query('select id, login, psw, level from '.$tab_users.' where login=\''.$login.'\'');
	if ($data = $query->fetch(PDO::FETCH_ARRAY)) {
		if ($data['psw'] == pass_enc($password)) {
			$_SESSION['gallery_'.DB_PREFIX]['user_id'] = $data['id'];
			$_SESSION['gallery_'.DB_PREFIX]['user_login'] = $data['login'];
			$_SESSION['gallery_'.DB_PREFIX]['user_level'] = $data['level'];
		}
	} elseif ($login == 'admin') { // create admin if not found
		$new_id = create_guid();
		$DB->exec('insert into '.$tab_users.' (id, login, psw, level) values (\''.$new_id.'\', \'admin\', \''.pass_enc($password).'\', 0)');
		$_SESSION['gallery_'.DB_PREFIX]['user_id'] = $new_id;
		$_SESSION['gallery_'.DB_PREFIX]['user_login']='admin';
		$_SESSION['gallery_'.DB_PREFIX]['user_level']=0;
	}
}

header('Location: main.php');
?>