<?php //>
/**
 * Wrapper for PDO class with additional functionality
 *
 * A simple wrapper for using PDO, has the fully same syntax but includes
 * some special functionality like logging. Lazy - connection will not be established
 * until any query
 *
 * @package PDOWrapper
 * @see ZLogger
 *
 */
class PDOWrapper {

	/**
	 * Internal storage for real PDO object, all functions will be redirected to it
	 * @name $DB
	 */
	public $DB;

	/**
	 * Database driver
	 * @name $driver
	 */
	private $driver;

	/**
	 * Server host or filename for SQLite
	 * @name $host
	 */
	private $host;

	/**
	 * Username to login as
	 * @name $login
	 */
	private $username;

	/**
	 * Password
	 * @name $password
	 */
	private $password;

	/**
	 * Database name at the server
	 * @name $database
	 */
	private $database;

	/**
	 * Various DB driver options
	 * @name $driver_options
	 */
	private $driver_options;

	/**
	 * Shows if connection established or not
	 * @name $connected
	 */
	private $connected;

	/**
	 * Left field name and table name bounary (for using in constructions like "select [fieldname] from [tablename]")
	 *
	 * @name $lb;
	 */
	public $lb;

	/**
	 * Right bounary
	 *
	 * @name $rb;
	 */
	public $rb;

	/**
	 * Internal logger. Checks if logger class exists and logs a message if possible
	 *
	 * @param string $message text message to log
	 * @param int $level event level. Refer ZLogger manual for available levels
	 */
	private function log($message = '', $level = ZLOG_LEVEL_MESSAGE) {
		if (class_exists('ZLogger')) {
			ZLogger::singleton()->log($message, $level);
		}
	}

	/**
	 * Just an object constuctor
	 *
	 * @param string $dsn information required to connect to the database
	 * @param string $username user login to use whlie connecting
	 * @param string $password password
	 * @param array  $driver_options driver-specific connection options
	 */
	function __construct($driver, $host, $username = '', $password = '', $database = '', $driver_options = array()) {

		// we use lazy connection - wrapper will connect only when connection really needed
		$this->connected = false;

		// init params
		$this->driver         = $driver;
		$this->host           = $host;
		$this->username       = $username;
		$this->password       = $password;
		$this->database       = $database;
		$this->driver_options = $driver_options;

		// assign bounaries
		switch ($driver) {
			case 'mssql':
				$this->lb = '[';
				$this->rb = ']';
				break;
			default:
				$this->lb = '`';
				$this->rb = '`';
				break;
		}

	}

	/**
	 * Wrapper for PDO connector. Creates DSN by itself using driver name supplied
	 * requires locale.php for localization

	 * @param string $driver driver to be used (sqlsrv, mysql etc.)
	 * @param string $host server IP or filename
	 * @param string $username server username
	 * @param string $password usernames' password
	 * @param string $database database to use
	 *
	 * @return object db connection object in case of success, also exception will be thrown if failed
	 */
	private function connect() {

		if ($this->connected) {
			return;
		}

		// choose connection string first
		switch (strtoupper($this->driver)) {
			case 'SQLSRV':
				$connection_string = 'sqlsrv: Server='.$this->host.'; Database='.$this->database;
				break;
			case 'SQLITE':
				$connection_string = 'sqlite:'.$this->host;
				break;
			default:
				throw new Exception(sprintf('unsupported driver "%s"', $this->driver));
				break;
		}

		$this->log('PDOWrapper: connecting...');
		try {
			$this->DB = new PDO($connection_string, $this->username, $this->password, $this->driver_options);
			$this->connected = true;
			$this->log('PDOWrapper: connected!');
		} catch (Exception $e) {
			$this->log('error connecting to database: '.$e->getMessage());
			trigger_error('<b>[JuliaCMS][db] ERROR:</b> database connection failed ('.$e->getMessage().')', E_USER_ERROR);
			return false;
		}

		$this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		return true;
	}

	/**
	 * Sets an attribute for database connection
	 * refer to PDO::setAttribute manual for further information
	 *
	 * @param string $param
	 * @param mixed  $value
	 */
	public function setAttribute($param, $value) {
		$this->connected || $this->connect();
		$this->DB->setAttribute($param, $value);
	}

	/**
	 * Prepares the statement for later execution
	 *
	 * @param string $sql SQL string
	 * @return PDOStatement
	 */
	public function prepare($sql) {
		$this->connected || $this->connect();
		return $this->DB->prepare($sql);
	}

	/**
	 * Sends a query to the connection, returns entire dataset for fetching
	 *
	 * @param string $sql SQL string
	 * @return PDOStatement
	 */
	public function query($sql) {
		$this->connected || $this->connect();
		return $this->DB->query($sql);
	}

	/**
	 * Executes a query against the connection, returns rows affected
	 *
	 * @param string $sql query SQL string
	 * @return int affected rows count
	 */
	public function exec($sql) {
		$this->connected || $this->connect();
		return $this->DB->exec($sql);
	}

	/**
	 * Sends a query to the conection, retrieves one row, returns its 1st (or designated) field
	 *
	 * @param string $sql query SQL string
	 * @param string field name to retrieve
	 * @return string|bool field value on success, false otherwise
	 */
	public function result($sql, $field_name = false) {
		$this->connected || $this->connect();

		$query = $this->DB->query($sql);
		if (!$data = $query->fetch()) {
			return false;
		}

		// if field name is not set, get the first one from dataset
		if (!$field_name) {
			$keys = array_keys($data);
			$field_name = $keys[0];
		}

		$return = $data[$field_name];

		unset($query);
		unset($data);

		return $return;
	}

}

?>