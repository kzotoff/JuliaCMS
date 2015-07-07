<?php //> Й <- UTF mark

/*

context-specific routines

*/


include_once('Mail.php');
include_once('Mail/mime.php');

class UserLogic {

	/**
	 * Userland API actions. The structure is the same as defined in J_DB class
	 *
	 * @name $actions
	 */
	public static $actions = array(
		'template_to_message_dialog' => array(
			'caption'       => 'К отправке...',
			'image'         => '',
			'api'           => 'template_to_message_dialog'
		)
	
	);

	/**
	 * Creates dialog for creating messages using template
	 *
	 */
	public static function templateToMessageDialog($input, &$return_metadata, $DB) {

		$R = Registry::GetInstance();

		// client list as table with checkboxes
		$client_list = '';
		$sql = J_DB_Helpers::getReportMainSQL(1, $DB); // TAG_CRAZY TAG_TODO
		$query = $DB->query($sql);
		while ($data = $query->fetch()) {
			$client_list .= '<tr>';
			$client_list .= '<td><input type="checkbox" name="message_recipients[]" value="'.$data['id'].'" class="target_checker" /></td>';
			$client_list .= '<td>'.$data['first_name'].' '.$data['patronymic'].' '.$data['second_name'].'</td>';
			$client_list .= '<td>'.$data['email'].'</td>';
			$client_list .= '<td>'.$data['labels'].'</td>';
			$client_list .= '<td>'.$data['comments'].'</td>';
			$client_list .= '</tr>';
		}
		$client_list = '<table id="special_addresses" class="sender_table">'.$client_list.'</table>';

		// "from" address selection dropdown
		$from_list = '';
		$query = $DB->query('select * from mailfrom');
		while ($data = $query->fetch()) {
			$from_list .= sprintf('<option value="%s">%s</option>', $data['id'], $data['caption'].' ('.$data['from_addr'].')');
		}

		// subject
		$query = $DB->query('select caption from templates where id=\''.$input['row_id'].'\'');
		if ($data = $query->fetch()) {
			$message_subject = $data['caption'];
		} else {
			$message_subject = 'тема сообщения';
		}
		
		// final HTML
		$html = sprintf(file_get_contents('userapi/html/api_html_dialog_template_to_outbox.html'), $input['row_id'], $from_list, $message_subject, $client_list);

		$return_metadata = array('type'=>'html');
		return $html;
	}


	/**
	 * Creates ready-to-send messages based on the template, from addr and recipient list
	 *
	 */
	public static function templateToMessages($input, &$return_metadata, $DB) {

		// get client list first
		$recipient_list = array();
		$query = $DB->query('select * from clients');
		while ($data = $query->fetch()) {
			$recipient_list[$data['id']] = $data;
		}

		// message body
		$query = $DB->query('select message from templates where id=\''.$input['row_id'].'\'');
		if (!$data = $query->fetch()) {
			terminate('', '404 template not found');
		}
		$message_body = $data['message'];

		// "from" address
		$query = $DB->query('select from_addr from mailfrom where id=\''.$input['special_input_from_addr'].'\'');
		if (!$data = $query->fetch()) {
			terminate('', '404 sender address not found');
		}
		$message_from_addr = $data['from_addr'];

		// now prepare creation statement
		$sql = 'insert into messages (`id`, `subject`, `from`, `to`, `datetime`, `message`, `template`) values (:id, :subject, :from, :to, :datetime, :message, :template)';
		$prepared = $DB->prepare($sql);

		// iterate checked checkboxes
		foreach ($input['message_recipients'] as $client_id) {
			$prepared->execute(array(
				':id'       => create_guid(),
				':subject'  => $input['special_subject'],
				':from'     => $message_from_addr,
				':to'       => $recipient_list[$client_id]['first_name'] .' '.
							   $recipient_list[$client_id]['patronymic'] .' '.
							   $recipient_list[$client_id]['second_name'].' '.
							   '('.$recipient_list[$client_id]['email'].')',
				':datetime' => date('Y.m.d h:i:s'),
				':message'  => $message_body,
				':template' => $input['row_id']
			));
		}

		$return_metadata = array('type'=>'command', 'command'=>'reload');
		return 'OK';

	}
	
	/**
	 * Sends message from the outbox
	 *
	 */
	public static function messagesSend($input, &$return_metadata, $DB) {
		
		ob_start();
		// here all the mail connections will be stored
		$mail_connections = array();

		$query = $DB->query('select * from messages');
		while ($data = $query->fetch()) {
			
			logthis('sending message "'.$data['subject'].'" from "'.$data['from'].'" to "'.$data['to'].'"');

			// create mail connection if not exists 
			if (!isset($mail_connections[$data['from']])) {
				logthis('creating mail connection for "'.$data['from'].'"');
				
				$mail_from_query = $DB->query('select * from mailfrom where from_addr=\''.$data['from'].'\'');
				if (!$mail_from_params = $mail_from_query->fetch()) {
					logthis('"from" address not found in database');
					$mail_connections[$data['from']] = false;
				} else {
					$mailer_params['host']      = $mail_from_params['server'];
					$mailer_params['port']      = $mail_from_params['port'];
					$mailer_params['auth']      = $mail_from_params['auth_type'];
					$mailer_params['localhost'] = $mail_from_params['ehlo'];
					$mailer_params['username']  = $mail_from_params['login'];
					$mailer_params['password']  = $mail_from_params['password'];
					$mailer_params['persist']   = true;
					$mailer_params['debug']     = false;
					$mail_connections[$data['from']] = &Mail::factory('smtp', $mailer_params);
				}
			}
			if (!$mail_connections[$data['from']]) {
				logthis('no connection to send this message, skipping');
				continue;
			}

			// default headers
			$headers = array();

			// attachments array (taken from template) // TAG_TODO переделать на prepared
			$attachments_query = $DB->query('select * from comments where object_id=\''.$data['template'].'\'');
			$attachments_array = array();
			while ($attachment_data = $attachments_query->fetch()) {
				if ($attachment_data['attached_name'] > '') {
					$attachments_array[] = array('filename'=>'userattached/'.$attachment_data['id'], 'realname'=>$attachment_data['attached_name']);
				}
			}
			
//			// reading confirmation request, if required
//			$headers('Disposition-Notification-To') = $some_mail_address;
//			$headers('X-Confirm-Reading-To')        = $some_mail_address;

			
			// all ok, we're now ready to send it!
			$send_result = send_email(
				$mail_connections[$data['from']],
				$data['from'],
				$data['to'],
				$data['subject'],
				$data['message'],
				$headers,
				$attachments_array
			);

//			// delete if sent successfully
//			if ($send_result) {
//				$DB->exec('delete from messages where id='.$data['id']);
//			}

		}

		// close all connections
		logthis('Disconnecting mailers...');
		foreach ($mail_connections as $index => $mail_connection) {
			$mail_connection->disconnect();
			unset($mail_connections[$index]);
		}
		
		$mail_log = ob_get_contents();
		ob_clean();
		logthis('Now full mail log: ', ZLOG_LEVEL_DEBUG);
		logthis('--8<-----------------------------------------------------------------', ZLOG_LEVEL_DEBUG);
		logthis(PHP_EOL.$mail_log, ZLOG_LEVEL_DEBUG);
		logthis('--8<-----------------------------------------------------------------', ZLOG_LEVEL_DEBUG);
		echo '<pre>'; logger_out(ZLOG_LEVEL_MESSAGE, array('target'=>'stdout')); echo '</pre>';
		terminate();
	}
	
}


?>