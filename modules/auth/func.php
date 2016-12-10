<?php //> Й <- UTF mark

/**
 * Authentication module for JuliaCMS
 *
 * @package J_Auth
 */
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
	public function requestParser($template) {

		// input filtering entirely moved out since it also needed in ajax handler
		$I = $this->getInput();

		$redirect_target = '';

		$template = $this->takeAction($template, $redirect_target);

		// check "require login" state - if no username stored or it's empty, just show login page instead normal template
		if (
			(isset($this->CONFIG['require_login']) && $this->CONFIG['require_login'])
			&& (!isset($_SESSION[$this->CONFIG['session_username']]) || (trim($_SESSION[$this->CONFIG['session_username']] == '')))
		) {
			$template = content_replace_body($template, file_get_contents(__DIR__.'/login.html'));
			$template = content_replace_title($template, 'Вход');
		}

		if ($redirect_target > '') {
			terminate('', 'Location: '.$redirect_target, 302);
		}
		return $template;
	}

	/**
	 * Just redirects to $this->takeAction
	 *
	 * @return string text result
	 */	
	public function AJAXHandler() {
		return $this->takeAction('', $dummy);
	}
	
	/**
	 * This function parses input data for both requestParser and AJAXHandler
	 * 
	 * @param string $template page template for calling from requestParser
	 * @param string &$redirect_target location to redirect to
	 * @return string|bool modified template or true/false
	 */
	private function takeAction($template, &$redirect_target) {
		
		$I = $this->getInput();
		switch ($I['action']) {
			
			// login
			case 'login':
				// check login/password
				$ok = $this->tryLogin($I['username'], $I['password'], $login_result_html);

				// different actions on different call methods (straight vs AJAX)
				if (get_array_value($I, 'module', false) == 'auth') {
					
					while (preg_match(macro_regexp('auth'), $template, $match)) {
						$params = parse_plugin_template($match[0]);
						if (get_array_value($params, 'mode', false) == 'login-message') {
							$template = str_replace($match, $login_result_html, $template);
						}
					}
					return $template;
				}

				if (get_array_value($I, 'ajaxproxy', false) == 'auth') {
					return ($ok ? 'OK' : 'NO') . ':' . $login_result_html;
				}
				break;

			// logout. always returns true
			case 'logout':
				$this->logout();
				$redirect_target = '.';
				return 'OK';
				break;
				
			// password change form. avoid calling it via "ajaxproxy"
			case 'change_password':
				$template = content_replace_body($template, file_get_contents(__DIR__.'/chpass.html'));
				$template = content_replace_title($template, 'Изменение пароля');
				return $template;
				break;
				
			// actual password changing
			case 'chpass':
				if (!user_allowed_to('change other passwords')) {
					terminate('Forbidden', '', 403);
				}

				$ok = $this->tryChangePassword($I['username'], $I['password'], $I['password1'], $I['password2'], $chpass_result_html) ? 'OK' : 'NO';
				return $ok.':'.$chpass_result_html;
				break;
		}
		return $template;
	}
	
	/**
	 * Calculates hash of salted password
	 *
	 * @param string $password
	 * @return string
	 */
	private function generateHash($password, $mod) {
		return sha1($password.md5($mod));
	}
	
	/**
	 * Checks username and password against "users" table
	 *
	 * @param string $username user login for check
	 * @param string $password password
	 * @return bool true when login+password match stored data, false elsewhere
	 */
	private function checkPassword($login, $password) {

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
		
		// if field specified for "secret", use its value, constant else
		$secret_field = isset($this->CONFIG['secret_field']) && ($this->CONFIG['secret_field'] > '') ? '`'.$this->CONFIG['secret_field'].'`' : '\''.$this->CONFIG['secret_default'].'\'';
		try {
			// note that "{$secret_field}" is not wrapped with braces as it can contain either field name or direct string
			$query = $DB->query("select `{$this->CONFIG['md5_field']}`, {$secret_field} as secret from `{$this->CONFIG['table']}` where `{$this->CONFIG['login_field']}` = '{$login}'");
		} catch (Exception $e) {
			return false;
		}

		// if no data returned at all, no such user
		if (($query === false) || !($data = $query->fetch())) {
			return false;
		}
		
		// get stored password hash
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
	private function tryLogin($login, $password, &$result) {

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
	 * @return bool true on success, false elsewhere
	 */
	private function tryChangePassword($login, $old_password, $new_password1, $new_password2, &$result) {

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

		try {
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
		} catch (Exception $e) {
			$result = '[JuliaCMS][AUTH] WARNING: failed changing password: '.$e->getMessage();
			return false;
		}

		// all ok!
		$result = sprintf(self::HTML_MESSAGE_SUCCESS, 'Пароль успешно изменен');
		return true;
	}

	/**
	 * Filters input arrays ($_GET and $_POST)
	 *
	 * @return array filtered GET and POST requests
	 */
	private function getInput() {

		$input_filter = array(
			'username'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Zа-яА-Я0-9!@\-]+$~ui')),
			'password'     => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'password1'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'password2'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'module'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
			'action'       => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^(login|logout|change_password|chpass)$~ui')),
			'ajaxproxy'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '~^[a-zA-Z\_0-9]+$~ui')),
		);
		return get_filtered_input($input_filter);
	}

	/**
	 * Logs current user out
	 */
	private function logout() {
		$_SESSION[$this->CONFIG['session_username']] = '';
		return true;
	}

	
}

?>