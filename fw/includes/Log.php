<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

// Load database backend
require_once(SYSTEM_PATH . '/includes/Database.singleton.php');

// Get current user handling
require_once(SYSTEM_PATH . '/includes/LoginUser.php');

// Get the location handler
require_once(SYSTEM_PATH . '/includes/Geolocation.php');

// Define log levels
define('LOG_LEVEL_DEBUG', 32);
define('LOG_LEVEL_INFO', 16);
define('LOG_LEVEL_WARN', 8);
define('LOG_LEVEL_ERROR', 4);
define('LOG_LEVEL_FATAL', 2);
define('LOG_LEVEL_UNKNOWN', 1);

/*!
 * @brief The log handler class
 *
 * Class to handle the logging of events into the database,
 * and ability to read existing events from the database.
 *
 * $Author: mireiawen $
 * $Id: Log.php 413 2016-07-21 14:49:07Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Log extends DataObject
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 * 
	 * In addition to building the Base class,
	 * this constructor sets up the current
	 * database connection and currently
	 * logged in user.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		parent::__construct();
		$this -> User = LoginUser::Get();
		$this -> GetFields();
	}
	
	/*!
	 * @brief Write an event to the log
	 *
	 * Write an event to the log, automatically
	 * fill in the timestamp, user and such
	 * information
	 *
	 * @param string $msg
	 * 	The actual message to write
	 *
	 * @param int $level
	 * 	The severity of the message
	 *
	 * @retval bool
	 * 	TRUE if writing was success,
	 * 	FALSE on error
	 *
	 * @throws Exception on database error if debugging
	 */
	public function Add($msg, $level = LOG_LEVEL_UNKNOWN)
	{
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			return;
		}
		
		// Get UID
		if ($this -> User)
		{
			$uid = $this -> User -> GetID();
		}
		else
		{
			$uid = USER_ID_UNKNOWN;
		}
		
		// There might be "new" user with UID 0
		if (!$uid)
		{
			$uid = USER_ID_UNKNOWN;
		}
		
		// Try to get calling class name
		$trace = debug_backtrace();
		if (array_key_exists(1, $trace))
		{
			$caller = $trace[1]['class'];
		}
		
		// At least log the file then
		else
		{
			$caller = basename($trace[0]['file']);
		}
		
		// Address info
		if (isset($_SERVER['REMOTE_ADDR']))
		{
			$address = $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			$address = '127.0.0.1';
		}
		
		// Get location
		try
		{
			$location = Geolocation::Get() -> GetLocation($address);
		}
		catch (Exception $e)
		{
			$location = 'Unable to get location: '. $e -> getMessage();
		}
		
		// Make sure location is something
		if (empty($location))
		{
			$location = 'Unknown';
		}
		
		// Create database statement
		$stmt = $db -> prepare('INSERT INTO ' . $db -> escape_identifier('Log') . '
				(' . $db -> escape_identifier('UID') . ', ' . $db -> escape_identifier('Remote') . ', ' . $db -> escape_identifier('Setby') . ', ' . $db -> escape_identifier('Location') . ', ' . $db -> escape_identifier('Message') . ') 
				VALUES (?, ?, ?, ?, ?)');
		if ($stmt === FALSE)
		{
			if ((defined('DEBUG')) && (DEBUG))
			{
				throw new Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
			}
			else
			{
				return FALSE;
			}
		}
		
		if (!$stmt -> bind_param('issss', $uid, $address, $caller, $location, $msg))
		{
			if ((defined('DEBUG')) && (DEBUG))
			{
				throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
			}
			else
			{
				return FALSE;
			}
		}
		
		// Insert into database
		if (!$stmt -> execute())
		{
			if ((defined('DEBUG')) && (DEBUG))
			{
				throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
			}
			else
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/*!
	 * @brief Read messages from the log
	 *
	 * Read the specified number of log messages
	 * starting from the message number $start in the 
	 * log.
	 *
	 * @param mixed $start
	 * 	The starting point where to start reading the messages,
	 * 	FALSE to read from beginning
	 * @param mixed $num
	 * 	The number of messages to read from,
	 * 	FALSE to read the default amount
	 * @retval array
	 * 	Array of LogEntry objects
	 * @throws Exception on error
	 */
	public function Read($start = FALSE, $num = FALSE)
	{
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Work around timestamp data type
		$fields = array();
		foreach ($this -> _fields as $name => $params)
		{
			$n = $db -> escape_identifier($name);
			if ($params['sqltype'] === 'timestamp')
			{
				$fields[] = 'UNIX_TIMESTAMP(' . $n . ') AS ' . $n;
			}
			else
			{
				$fields[] = $n;
			}
		}
		
		// Create database statement
		$stmt = $db -> prepare('SELECT ' . implode(', ', $fields) . ' FROM ' . $db -> escape_identifier('Log') . ' LIMIT ?, ?');
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Check the limits for the query
		$start = filter_var($start, FILTER_VALIDATE_INT);
		$num = filter_var($num, FILTER_VALIDATE_INT);
		
		if ($start === FALSE)
		{
			$start = 0;
		}
		
		if ($num === FALSE)
		{
			$num = LOG_EVENTS_PER_PAGE;
		}
		
		// Bind the limits
		if (!$stmt -> bind_param('ii', $start, $num))
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		$rows = $db -> fetch_assoc($stmt);
		foreach ($rows as $row)
		{
			$events[] = new LogEntry($row);
		}
		return $events;
	}
	
	/*!
	 * @brief Get a single event
	 *
	 * Get a single log event by its ID
	 *
	 * @param int $id
	 * 	The entry ID
	 * @retval LogEntry
	 * 	The entry itself
	 * @throws Exception on error
	 */
	public function GetEvent($id)
	{
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Validate ID format
		$id = filter_var($id, FILTER_VALIDATE_INT);
		if ($id === FALSE)
		{
			throw new Exception(_('Invalid event ID'));
		}
		
		// Work around timestamp data type
		$fields = array();
		foreach ($this -> _fields as $name => $params)
		{
			$n = $db -> escape_identifier($name);
			if ($params['sqltype'] === 'timestamp')
			{
				$fields[] = 'UNIX_TIMESTAMP(' . $n . ') AS ' . $n;
			}
			else
			{
				$fields[] = $n;
			}
		}
		
		// Create database statement
		$stmt = $db -> prepare('SELECT ' . implode(', ', $fields) . ' FROM ' . $db -> escape_identifier('Log') . ' WHERE ' . $db -> escape_identifier('ID') . ' = ? LIMIT 1');
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind the ID
		if (!$stmt -> bind_param('i', $id))
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		$row = $db -> fetch_first($stmt);
		
		// Check it
		if (empty($row))
		{
			throw new Exception(sprintf(_('No such event "%s"'), $id));
		}
		
		// And return the event
		return new LogEntry($row);
	}
}

/*!
 * @brief A class for a single log event
 *
 * A class containing the specified log event
 *
 * $Author: mireiawen $
 * $Id: Log.php 413 2016-07-21 14:49:07Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class LogEntry
{
	/*!
	 * @brief Array of the actual variables
	 *
	 * Array holding the actual variables of the class. This makes it 
	 * possible to transfer the data with the cache backend easily.
	 */
	private	$values;
	
	/*!
	 * @brief The public constructor
	 *
	 * Construct a log entry class.
	 * 
	 * This constructor has to be public because
	 * PHP does not have a "friend class" concept
	 * and we need a way to fill the entry.
	 *
	 * Normally, nobody should construct this class
	 * outside of the Log class.
	 *
	 * @param array $entry
	 * 	The entry data
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on invalid data
	 */
	public function __construct($entry)
	{
		if (empty($entry))
		{
			throw new Exception(_('Invalid log entry; it is empty!'));
		}

		$this -> values = $entry;
	}
	
	/*!
	 * @brief Get the log entry ID
	 * Get the log entry ID
	 *
	 * @retval int 
	 * 	The log entry ID
	 */
	public function GetID()
	{
		return $this -> values['id'];
	}

	/*!
	 * @brief Get the log entry UID
	 *
	 * Get the user ID of the person who caused
	 * the event to trigger
	 *
	 * @retval int
	 * 	Tho log user ID
	 */
	public function GetUID()
	{
		return $this -> values['uid'];
	}
	
	/*!
	 * @brief Get the username for the entry
	 *
	 * Get the username for the person who
	 * caused the event to trigger
	 *
	 * @retval string
	 * 	The username for the log entry
	 */
	public function GetUser()
	{
		if (array_key_exists('username', $this -> values))
		{
			return $this -> values['username'];
		}
		
		try
		{
			$this -> values['username'] = User::Username($this -> values['uid']);
		}
		catch (Exception $e)
		{
			return _('Unknown');
		}
		return $this -> values['username'];
	}
	
	/*!
	 * @brief Get the unix timestamp of the log event
	 *
	 * Get the unix timestamp of the log event
	 *
	 * @retval int
	 * 	Unix timestamp of the log event
	 */
	public function GetTimestamp()
	{
		return $this -> values['timestamp'];
	}
	
	/*!
	 * @brief Get the formatted time for the log event
	 *
	 * Get the formatted time of the log entry timestamp,
	 * see [PHP manual](http://php.net/manual/en/function.date.php)
	 * for the formatting string
	 *
	 * @param string $format
	 * 	Format string for the date
	 * @retval string
	 * 	Formatted timestamp of the log entry
	 */
	public function GetTime($format = 'Y-m-d H:i:s T')
	{
		return date($format, $this -> values['timestamp']);
	}
	
	/*!
	 * @brief Get the setter of the log entry
	 *
	 * Get the class, page or other entity that caused 
	 * the log entry.
	 *
	 * @retval string
	 * 	The code entity that created the log entry
	 */
	public function GetPage()
	{
		return $this -> values['setby'];
	}
	
	/*!
	 * @brief Get the remote address for the log entry
	 * 
	 * Get the remote address for the log entry, this is likely
	 * IPv4 or IPv6 address from the SERVER array. Might not be set
	 *
	 * @retval string
	 * 	Remote address of the log entry
	 */
	public function GetHost()
	{
		return $this -> values['remote'];
	}
	
	/*!
	 * @brief Get the location information of the log entry
	 *
	 * Get the remote location information from the log entry. 
	 * This might be a GeoIP location, GPS coordinates from mobile
	 * device or in theory anything else. Might not be correct 
	 * and might not be set.
	 *
	 * @retval string
	 * 	The location information gathered for the log entry
	 */
	public function GetLocation()
	{
		return $this -> values['location'];
	}
	
	/*!
	 * @brief Get the log message
	 *
	 * Get the actual log message set for the event
	 *
	 * @retval string
	 * 	The message written to the log
	 */
	public function GetMessage()
	{
		return $this -> values['message'];
	}
}
