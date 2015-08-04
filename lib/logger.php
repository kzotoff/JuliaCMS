<?php
/**
 * simple logger utility
 *
 * @package Z-logger
 */


/*************************************************************************************************************************
This tool logs call-time memory usage and current time then displays data collected, to stdout, file or as return value.


To start, just create it at any time you need (see below for the parameters supported). Also you may just call
any other method, the logger singleton instance will be created automatically with the default parameters.

	$logger = ZLogger::singleton(array $options);
	or simple variant to use default parameters
	$logger = ZLogger::singleton(int $min_level);


Log your messages with the code like this:

	$logger::log('your message', ZLOG_LEVEL_MESSAGE);
	$logger::log('your message');
	ZLogger::log('your message'); // if you don't want to create separate variable or call globally


Any time you need, make a dump with minimal level to show and some options (nothing at the moment)
	$logger::flushNow(ZLOG_LEVEL_MESSAGE, array('option_name'=>'value));
	or, for "return" output option,
	$result = $logger::flushNow(ZLOG_LEVEL_MESSAGE, array('option_name'=>'value'));
	or
	$logger::flushNow(ZLOG_LEVEL_MESSAGE);
	or even
	$logger::flushNow();


At any time, the entire log can be output with

	$logger->flushAll(ZLOG_LEVEL_MESSAGE, array('option_name'=>'value'));
	where array values will temporarily override initial ones. Return target also supported.


At any point you may also start again with memory and/or time

	$logger->resetCounters(array('memory', 'time'));
	to reset all, just use
	$logger->resetCounters();



At the first call, you may set parameters to the logger.
All subsequent calls will not affect options except flush routines.
Possible init options:
	locale = EN (default), RU, ... - language.
	target = stdout (default), file (writes to file), return (just returns as string).
	format = html - colored, styles allowed (default), or plain - tags will be stripped.
	filename = file to put logs. Ignored if stdout target selected.
	line_delimiter = this string will be added to every output string. Can be "<br />" (default) or PHP_EOL.
	flush = immediate (default), finished - output log messages immediately after receiving or by command (usually at the end of script).
	min_level = minimal event level to be collected. Also will be used as flush level if not specified while dumping.
	mem_unit = byte, kilo (default), mega, auto - unit to use (byte, kilobyte, megabyte) while displaying memory counters
	rewrite_log = true, false (default) - indicates whether or not should logger erase previous file if any

Output format templates:
	%message%               - text message logged
	%level%                 - textual representation of the event level

	%time%                  - timestamp.
	%time_delta_start%      - time from log start.
	%time_delta_prev%       - time from previous message (with the same or higher level).

	%memory%                - memory usage.
	%memory_delta_start%    - memory usage change, from logging start.
	%memory_delta_prev%     - memory usage change, from prev point.

*************************************************************************************************************************/

// define log level constants and using recommendations
define('ZLOG_LEVEL_CRAZY',   10); // ultra-deep mining
define('ZLOG_LEVEL_DEBUG',   20); // standard debug-level catch
define('ZLOG_LEVEL_NOTICE',  30); // not valuable messages (but indicates something unusual)
define('ZLOG_LEVEL_MESSAGE', 40); // standard messages that appear unconditionally
define('ZLOG_LEVEL_WARNING', 50); // bad configs, missing parameters and so on
define('ZLOG_LEVEL_ERROR',   60); // something that prevents some actions
define('ZLOG_LEVEL_FATAL',   70); // so bad that we usually shall to terminate script
define('ZLOG_LEVEL_CRASH',   80); // something even more bad
define('ZLOG_LEVEL_ALARM',   90); // you better have to take action immediately

/**
 * Z-logger
 *
 */
class ZLogger {

	/**
	 * logger inself
	 * @name $instance
	 */
	static $instance;

	/**
	 * all log records will be stored here
	 * @name $log
	 */
	static $log = array();

	/**
	 * logger start time
	 * @name $time_start
	 */
	static $time_start = 0;

	/**
	 * memory usage as start
	 * @name memory_start
	 */
	static $memory_start = 0;

	/**
	 * all user-visible strings, consolidated for easy localization
	 * @name $locale
	 */
	static $locale = array();

	/**
	 * all logger options
	 * @name $options
	 */
	static $options = array();

	/**
	 * bad configuration indicator
	 * 
	 * this flag indicates bad configuration options (e.g. empty filename) and suppresses any actions,
	 * all public functions will just return immediately
	 * @name $bad_config
	 */
	static $bad_config = false;

	/**
	 * flushed rows counter
	 * @name $flushed_to
	 */	
	static $flushed_to;

	/**
	 * output formats
	 * @name $var_formats
	 */
	static $var_formats = array(
		'time_current'        => '',
		'time_delta_start'    => '%7.3f',
		'time_delta_prev'     => '%+7.3f',
		'memory'              => '%\' 6.0f',
		'memory_delta_start'  => '%+\' 6.0f',
		'memory_delta_prev'   => '%+\' 6.0f',
	);

	/**
	 * prevent creating new instances
	 *
	 * @param bool $silent suppress error message
	 */
	function __construct($silent = false) {
		if (!$silent) {
			trigger_error('ZLogger creating method not allowed. use $z = ZLogger::Singleton($options_array) instead.', E_USER_WARNING);
		}
	}

	/**
	 * prevent creating new instances
	 */
	function __clone() {}

	/**
	 * prevent creating new instances
	 */
	function __wakeup() {}

	/**
	 * creates a singleton ZLog object
	 *
	 * @param array|int $options create options if array, minimal log level if int
	 *
	 * @return ZLogger
	 */
	public static function singleton($options = array()) {

		if (empty(self::$instance)) {
			self::$instance = new self(true);

			// possible options (first is default)
			$option_control = array(
				'min_level'      => array(ZLOG_LEVEL_DEBUG, ZLOG_LEVEL_MESSAGE, ZLOG_LEVEL_CRAZY, ZLOG_LEVEL_NOTICE, ZLOG_LEVEL_WARNING, ZLOG_LEVEL_ERROR, ZLOG_LEVEL_FATAL, ZLOG_LEVEL_CRASH, ZLOG_LEVEL_ALARM),
				'target'         => array('stdout', 'file', 'return'),
				'locale'         => array('EN', 'RU'),
				'format'         => array('html', 'plain'),
				'line_delimiter' => array('<br />', PHP_EOL),
				'flush'          => array('manual', 'immediate'),
				'mem_unit'       => array('auto', 'kilo', 'byte', 'mega'),
				'rewrite_log'    => array(false, true),
				'default_level'  => array(ZLOG_LEVEL_MESSAGE, ZLOG_LEVEL_DEBUG, ZLOG_LEVEL_CRAZY, ZLOG_LEVEL_NOTICE, ZLOG_LEVEL_WARNING, ZLOG_LEVEL_ERROR, ZLOG_LEVEL_FATAL, ZLOG_LEVEL_CRASH, ZLOG_LEVEL_ALARM)
			);

			// some options are unrestricted, so we only need to check if they are set
			$option_defaults = array(
				'filename'       => 'default.log',
				'output_format'  => '%time_delta_start% (%time_delta_prev%) %memory_color_start%%memory_delta_prev%%memory_color_end% [%level%] %message%'
			);			

			// simple call detection
			if (!is_array($options)) {
				$options = array('min_level'=>$options);
			}
			
			// set default values to unrestricted options if not set
			foreach ($option_defaults as $option_name => $value) {
				if (!isset($options[$option_name])) {
					$options[$option_name] = $value;
				}
			}

			// parse init values and set runtime options
			foreach ($option_control as $option_name => $values) {
				if (!isset($options[$option_name]) || !in_array($options[$option_name], $values)) {
					$options[$option_name] = $values[0];
				}
			}

			// some more checks
			if (($options['target'] == 'file') && ($options['filename'] == '')){
				self::$bad_config = true;
				trigger_error('ZLog: Unable to create logger: target is file, but filename was not specified. No log will be collected.', E_USER_WARNING);

				return self::$instance;
			}

			self::$options = $options;

			// apply language
			self::$instance->setLocale($options['locale']);

			// initial start values
			self::$time_start   = microtime(true);
			self::$memory_start = memory_get_usage();
			self::$flushed_to   = -1;

			// recycle prev log if requested
			if (self::$options['rewrite_log'] && (self::$options['target'] == 'file') && (self::$options['filename'] > '')) {
				if (file_exists(self::$options['filename'])) {
					if (!unlink(self::$options['filename'])) {
						trigger_error('ZLOG: An error occured while deleting previous log ('.(self::$options['filename']).'). Check if file exists and writable.', E_USER_WARNING);
					}
				}
			}

			self::log('***************************************************************', ZLOG_LEVEL_MESSAGE);
			self::log(self::$locale['logger_started'], ZLOG_LEVEL_MESSAGE);

			// dump options in debug mode
			foreach (self::$options as $option_name => $value) {
				self::log('option set: '.$option_name.' = '.$value, ZLOG_LEVEL_DEBUG);
			}

		}

		return self::$instance;
	}

	/**
	 * puts an event to the log
	 *
	 * @param string $message message to log
	 * @param int $level message level (debug, notice, ...)
	 *
	 * @return void
	 */
	public static function log($message = '', $level = false) {

		self::singleton();
		// if bad config was detected, do nothing
		if (self::$bad_config) {
			return false;
		}

		// message level if not set
		if ($level === false) {
			$level = self::$options['default_level'];
		}

		// if min_level is set, do not collect event at all
		if ($level < self::$options['min_level']) {
			return 0;
		}

		$log_elem = array();

		// these elements are always logged
		$log_elem['time']         = microtime(true);
		$log_elem['time_start']   = self::$time_start;
		$log_elem['memory']       = memory_get_usage();
		$log_elem['memory_start'] = self::$memory_start;
		$log_elem['level']        = $level;
		$log_elem['message']      = $message;

		// here some additional data will be stored
		// ...

		// store and forget
		array_push(self::$log, $log_elem);

		// output immediately if option present
		if (self::$options['flush'] == 'immediate') {
			self::$instance->outputOne(count(self::$log)-1, self::$options['min_level']);
			// don't output these line anymore
			self::$flushed_to = count(self::$log) - 1;
		}

/*		
		if (count(self::$log) > 100) {
			self::flushNow(ZLOG_LEVEL_MESSAGE, array('target'=>'stdout'));
			throw new Exception('oops');
		}
*/
	}

	/**
	 * flushes yet another log portion
	 *
	 * @param int $min_level minimal event level to output
	 * @param array $options
	 *
	 * @return void|false
	 */
	public static function flushNow($min_level = ZLOG_LEVEL_MESSAGE, $override_options = array()) {

		// if bad config was detected, do nothing
		if (self::$bad_config) {
			return false;
		}

		// temporarily change options if needed
		$old_options = self::$options;
		self::$options = array_merge(self::$options, $override_options);

		// iterate all lines and send them out
		$result = ''; // total string for "return" output method
		foreach(self::$log as $record_id => $log_record) {
			if ($record_id > self::$flushed_to) { // prevent double lines
				$result .= self::$instance->outputOne($record_id, $min_level);
			}
		}

		// don't output these lines anymore
		self::$flushed_to = count(self::$log) - 1;
		
		// restore options
		self::$options = $old_options;
	}

	/**
	 * outputs entire log
	 *
	 * unlike flushNow, this method doesn't use and affect already-flushed marker and always shows all events
	 *
	 * @param int $min_level minimal event level to output
	 * @param array $options
	 *
	 * @return string
	 */
	public static function flushAll($min_level = ZLOG_LEVEL_MESSAGE, $override_options = array()) {

		// if bad config was detected, do nothing
		if (self::$bad_config) {
			return false;
		}

		// temporarily change options if needed
		$old_options = self::$options;
		self::$options = array_merge(self::$options, $override_options);

		// iterate all lines and send them out
		$return = '';
		foreach(self::$log as $record_id => $log_record) {
			$return .= self::$instance->outputOne($record_id, $min_level);
		}

		// restore options
		self::$options = $old_options;
		return $return;
	}

	/**
	 * resets internal start counters like just started. usual for loop profiling
	 *
	 * @param array $reset counters to reset
	 *
	 * @return void|false
	 */
	public static function resetCounters($reset = array('time', 'memory')) {

		// if bad config was detected, do nothing
		if (self::$instance->bad_config) {
			return false;
		}

		if (in_array('time', $reset)) {
			self::$instance->time_start  = microtime(true);
			self::$instance->log(self::$instance->locale['reset_time'], ZLOG_LEVEL_NOTICE);
		}
		if (in_array('memory', $reset)) {
			self::$instance->memory_start = memory_get_usage();
			self::$instance->log(self::$instance->locale['reset_memory'], ZLOG_LEVEL_NOTICE);
		}
	}

	/**
	 * outputs one log record to target, specified while init
	 *
	 * @param int $record_id
	 * @param int $min_level minimal event level to output
	 *
	 * @return string
	 */
	private static function outputOne($record_id, $min_level) {

		// some default for lazy user
		if (!isset($min_level)) {
			$min_level = self::$options['min_level'];
		}

		// no output for non-valuable events
		if (self::$log[$record_id]['level'] < $min_level) {
			return '';
		}

		// preformatting
		$str = self::$instance->logRecordToString($record_id, $min_level);
		if (self::$options['format'] == 'plain') {
			$str = strip_tags($str);
		}

		// now make it out!
		switch (self::$options['target']) {
			case 'file':
				if (!file_put_contents(self::$options['filename'], $str . self::$options['line_delimiter'], FILE_APPEND)) {
					trigger_error('ZLOG: An error occured while writing log to '.(self::$options['filename']).'. Check if file exists and writable', E_USER_WARNING);
				}
				$return = '';
				break;
			case 'return':
				$return = $str . self::$options['line_delimiter'];
				break;
			case 'stdout':
			default:
				echo $str . self::$options['line_delimiter'];
				$return = '';
				break;
		}
		return $return;
	}

	/**
	 * converts given log record to output string using template given
	 *
	 * @param array $record_id log record id
	 * @param int $min_level minimal event level to use
	 * @return string
	 *
	 * @return string
	 */
	private function logRecordToString($record_id, $min_level) {

		// this record will be used as data source
		$log_record = self::$log[$record_id];

		// now we must find previous event with min level requested. current will be used if nothing will be found
		$prev_record = $log_record;

		$i = $record_id - 1;
		while ($i > 0) {
			if (self::$log[$i]['level'] >= $min_level) {
				$prev_record = self::$log[$i];
				break;
			}
			$i --;
		}

		// default message text
		$text_result = self::$options['output_format'];

		// note that this replacement made first and further replacents will affect message too
		$text_result = str_replace('%message%',            $log_record['message'], $text_result);

		$text_result = str_replace('%date%',               date('Y.m.d', $log_record['time']), $text_result);

		$text_result = str_replace('%time%',               self::timeToString($log_record['time']),                                                         $text_result);
		$text_result = str_replace('%time_delta_start%',   sprintf(self::$var_formats['time_delta_start'], $log_record['time'] - $log_record['time_start']), $text_result);
		$text_result = str_replace('%time_delta_prev%',    sprintf(self::$var_formats['time_delta_prev'],  $log_record['time'] - $prev_record['time']),      $text_result);

		$text_result = str_replace('%memory%',             self::bytesToUnit($log_record['memory'],                               self::$var_formats['memory']),             $text_result);
		$text_result = str_replace('%memory_delta_start%', self::bytesToUnit($log_record['memory'] - $log_record['memory_start'], self::$var_formats['memory_delta_start']), $text_result);
		$text_result = str_replace('%memory_delta_prev%',  self::bytesToUnit($log_record['memory'] - $prev_record['memory'],      self::$var_formats['memory_delta_prev']),  $text_result);

		$text_result = str_replace('%memory_color_start%', (($log_record['memory'] - $prev_record['memory']) > 0 ? '<span style="color:red;">' : '<span style="color:green;">'), $text_result);
		$text_result = str_replace('%memory_color_end%',   '</span>', $text_result);

		switch ($log_record['level']) {
			case ZLOG_LEVEL_DEBUG:   $text_result = str_replace('%level%', self::$locale['level_debug'],   $text_result); break;
			case ZLOG_LEVEL_NOTICE:  $text_result = str_replace('%level%', self::$locale['level_notice'],  $text_result); break;
			case ZLOG_LEVEL_MESSAGE: $text_result = str_replace('%level%', self::$locale['level_message'], $text_result); break;
			case ZLOG_LEVEL_WARNING: $text_result = str_replace('%level%', self::$locale['level_warning'], $text_result); break;
			case ZLOG_LEVEL_ERROR:   $text_result = str_replace('%level%', self::$locale['level_error'],   $text_result); break;
			case ZLOG_LEVEL_FATAL:   $text_result = str_replace('%level%', self::$locale['level_fatal'],   $text_result); break;
			case ZLOG_LEVEL_CRASH:   $text_result = str_replace('%level%', self::$locale['level_crash'],   $text_result); break;
			case ZLOG_LEVEL_ALARM:   $text_result = str_replace('%level%', self::$locale['level_alarm'],   $text_result); break;
			default:                 $text_result = str_replace('%level%', self::$locale['level_unknown'], $text_result); break;
		}

		return $text_result;
	}

	/**
	 * converts timestamp to user-friendly string
	 *
	 * @param float $time timestamp to convert
	 *
	 * @return string
	 */
	private static function timeToString($time) {
		return date('H:i:s', $time) . '.' . substr('000'.((int)($time*1000)), -3);
	}

	/**
	 * converts bytes value to a kilobytes or megabytes, according to options. Returns value with unit sign (B, K, M).
	 *
	 * @param int $value
	 * @param string $format
	 *
	 * @return string
	 */
	private static function bytesToUnit($value, $format) {
		switch (self::$options['mem_unit']) {
			case 'byte':
				$letter = 'b';
				break;
			case 'mega':
				$value = $value / 1024 / 1024;
				$letter = 'm';
				break;
			case 'auto':
				$letter = 'b';
				foreach (array('k', 'm', 'g') as $try_more) {
					if (abs($value) > 1024) {
						$value = $value / 1024;
						$letter = $try_more;
					}
				}
				break;
			case 'kilo':
			default:
				$value = $value / 1024;
				$letter = 'k';
				break;
		}
		return sprintf($format, $value) . $letter;
	}

	/**
	 * user-visible messages init
	 *
	 * @param string $set_locale locale code
	 *
	 * @return string
	 */
	private function setLocale($set_locale = 'en') {

		// EN is the default language and initialized anyway. Some/all messages can be replaced with the localized versions
		self::$locale['logger_started'] = 'logger started at %date% %time%';
		self::$locale['logger_already_started'] = 'logger instance already activated, options not redefined';
		self::$locale['default_message'] = '* no message *';

		// event level marks
		self::$locale['level_debug']   = 'DEBUG  ';
		self::$locale['level_notice']  = 'NOTICE ';
		self::$locale['level_message'] = 'MESSAGE';
		self::$locale['level_warning'] = 'WARNING';
		self::$locale['level_error']   = 'ERROR  ';
		self::$locale['level_fatal']   = 'FATAL  ';
		self::$locale['level_crash']   = 'CRASH  ';
		self::$locale['level_alarm']   = 'ALARM * ALARM * ALARM';

		self::$locale['reset_time']   = 'time counter was reset';
		self::$locale['reset_memory']   = 'memory counter was reset';

		switch (strtoupper($set_locale)) {
			case 'RU':
				self::$locale['logger_start_message'] = 'Логгер запущен, %time%';
				break;

			case 'EN':
				// already defined, yeah
				break;

			default:
				self::$log('no localization found for "'.$set_locale.'"', ZLOG_LEVEL_WARNING);
				break;
		}
	}

	/**
	 * returns default log level
	 *
	 * @return int
	 */
	public static function getDefaultLogLevel() {
		ZLogger::singleton();
		return ZLogger::$options['default_level'];
	}

}

?>