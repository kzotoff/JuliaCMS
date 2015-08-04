<?php //> Й <- UTF mark

class J_Auth extends JuliaCMSModule {
	
	/**
	 * Used for username correctness
	 *
	 * @const REGEXP_USERNAME
	 */
	const REGEXP_USERNAME = '~^[a-zA-Z0-9а-яА-Я_@!().\-]+$~';
	
	/**
	 * HTML to send on successful login or password changing
	 *
	 * @const HTML_MESSAGE_SUCCESS
	 */
	const HTML_MESSAGE_SUCCESS = '<div class="login_success">%s</div>';
	
	/**
	 * HTML to send on failed login or password changing
	 *
	 * @const HTML_MESSAGE_FAIL
	 */
	const HTML_MESSAGE_FAIL = '<div class="login_fail">%s</div>';

	/**
	 * Standard request parser
	 *
	 * @param string $template HTML to modify
	 * @return string modified or not modified template
	 */
	function requestParser($template) {

		// input filtering entirely moved out since it also needed in ajax handler
		$I = $this->getInput();

		$redirect_target = '';
		switch ($I['action']) {
			case 'login':
				$re = '~<template(?=[^>]*?type="auth"[^>]*?)(?=[^>]*?mode="login_message"[^>]*?)[^>]*\s?(/>|>.*?</template>)~';
				$this->tryLogin($I['username'], $I['password'], $login_result_html);
				$template = preg_replace($re, $login_result_html, $template);
				break;
				
			case 'change_password':
				$template = content_replace_body($template, file_get_contents(__DIR__.'/chpass.html'));
				$template = content_replace_title($template, 'Изменение пароля');
				return $template;
				break;

			case 'logout':
				$this->logout();
				$redirect_target = '.';
				break;
		}

		// check "require login" state - if no username stored or it's empty, just show login page instead normal template
		if (
			(isset($this->CONFIG['require_login']) && $this->CONFIG['require_login'])
			&& (!isset($_SESSION[$this->CONFIG['session_username']]) || (trim($_SESSION[$this->CONFIG['session_username']] == '')))
		) {
			$template = content_replace_body($template, file_get_contents(__DIR__.'/login.html'));
			$template = content_replace_title($template, 'Вход');
		}

		if ($redirect_target > '') {
			header('Location: '.$redirect_target);
			terminate();
		}
		return $template;
	}

	/**
	 * Try to login, return OK or NO
	 *
	 * @param string $password
	 * @return string text result
	 */	
	function AJAXHandler() {

		$I = $this->getInput();
		
		switch ($I['action']) {
			case 'login':
				$ok = $this->tryLogin($I['username'], $I['password'], $login_result_html) ? 'OK' : 'NO';
				return $ok.':'.$login_result_html;
				break;
			case 'chpass':
				$ok = $this->tryChangePassword($I['username'], $I['password'], $I['password1'], $I['password2'], $chpass_result_html) ? 'OK' : 'NO';
				return $ok.':'.$chpass_result_html;
				break;
		}
		return '';
	}
	
	/**
	 * Calculates hash of salted password
	 *
	 * @param string $password
	 * @return string
	 */
	function generateHash($password, $mod) {
		return sha1($password.md5($mod));
	}
	
	/**
	 * Checks username and password against "users" table
	 *
	 * @param string $username user login for check
	 * @param string $password password
	 * @return bool
	 */
	function checkPassword($login, $password) {

		$DB = new PDOWrapper(
			$this->CONFIG['database']['server_driver'],
			$this->CONFIG['database']['server_host'],
			$this->CONFIG['database']['server_login'],
			$this->CONFIG['database']['server_password'],
			$this->CONFIG['database']['server_db_name']
		);
		
		if (!preg_match(self::REGEXP_USERNAME, $login)) {
			return false;
		}
		
		// if field specified for "secret", check its value, constant else
		$secret_field = isset($this->CONFIG['secret_field']) && ($this->CONFIG['secret_field'] > '') ? '`'.$this->CONFIG['secret_field'].'`' : '\''.$this->CONFIG['secret_default'].'\'';
		$query = $DB->query("select `{$this->CONFIG['md5_field']}`, {$secret_field} as secret from `{$this->CONFIG['table']}` where `{$this->CONFIG['login_field']}` = '$login'");

		// if no data returned at all, no such user
		if (!($data = $query->fetch())) {
			return false;
		}
		
		// stored password hash
		$saved_md5 = $data[$this->CONFIG['md5_field']];
		
		// calculate test hash. note again that $data['secret'] contains either stored secret or some default value
		$check_md5 = $this->generateHash($password, $data['secret']);
		if ($saved_md5 != $check_md5) {
			return false;
		}

		// all ok, get if out
		return true;
	}

	/**
	 * Checks login/pasword against the DB records, executes login routines,
	 * returns login result wrapped within HTML
	 *
	 * @param string $login
	 * @param string $password
	 * @return bool true is login succeeded, false elsewhere
	 */
	function tryLogin($login, $password, &$result) {
		
		// check if password ok
		if (!$this->checkPassword($login, $password)) {
			$result = sprintf(self::HTML_MESSAGE_FAIL, 'Неверное имя пользователя или пароль');
			return false;
		}

		// yeah we're logged in
		$_SESSION[$this->CONFIG['session_username']] = $login;
		
		// here we can check if user locked
		
		// all ok, access granted!
		$result = sprintf(self::HTML_MESSAGE_SUCCESS, 'Вход выполнен');
		return true;
	}

	/**
	 * Checks login/pasword against the DB records and changes to the new one
	 * returns result wrapped within HTML
	 *
	 * @param string $login
	 * @param string $old_password
	 * @param string $new_password1
	 * @param string $new_password2
	 * @return bool true is login succeeded, false elsewhere
	 */
	function tryChangePassword($login, $old_password, $new_password1, $new_password2, &$result) {

		// check if login correct
		if (!preg_match(self::REGEXP_USERNAME, $login)) {
			$result = sprintf(self::HTML_MESSAGE_FAIL, 'Неверное имя пользователя или пароль');
			return false;
		}

		// check if current password ok
		if (!$this->checkPassword($login, $old_password)) {
			$result = sprintf(self::HTML_MESSAGE_FAIL, 'Неверное имя пользователя или пароль');
			return false;
		}

		// check if new passwords are same
		if ($new_password1 != $new_password2) {
			$result = sprintf(self::HTML_MESSAGE_FAIL, 'Пароли не совпадают');
			return false;
		}

		$DB = new PDOWrapper(
			$this->CONFIG['database']['server_driver'],
			$this->CONFIG['database']['server_host'],
			$this->CONFIG['database']['server_login'],
			$this->CONFIG['database']['server_password'],
			$this->CONFIG['database']['server_db_name']
		);
		if (isset($this->CONFIG['secret_field']) && ($this->CONFIG['secret_field'] > '')) {
			$secret = $DB->querySingle("select `{$this->CONFIG['secret_field']}` from `{$this->CONFIG['table']}` where `{$this->CONFIG['login_field']}` = '$login'");
		} else {
			$secret = $this->CONFIG['secret_default'];
		}

		$new_hash = $this->generateHash($new_password1, $secret);
		$DB->exec("update `{$this->CONFIG['table']}` set `{$this->CONFIG['md5_field']}` = '$new_hash' where `{$this->CONFIG['login_field']}` = '$login'");
		
		// all ok, access granted!
		$result = sprintf(self::HTML_MESSAGE_SUCCESS, 'Пароль успешно изменен');
		return true;
	}

	/**
	 * Filters input arrays ($_GET and $_POST)
	 *
	 * @return array filtered GET and POST requests
	 */
	function getInput() {

		$input_filter = array(
			'username'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9!@\-]+$~ui')),
			'password'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'password1'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'password2'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'module'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'action'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(login|logout|change_password|chpass)$~ui'))
		);
		return get_filtered_input($input_filter);
	}

	/**
	 * Logs current user out
	 */
	function logout() {
		$_SESSION[$this->CONFIG['session_username']] = '';
		return true;
	}

	
}

?>