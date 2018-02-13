<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// Load database backend
require_once(SYSTEM_PATH . '/includes/Database.singleton.php');

// Load the caching traits
require_once(SYSTEM_PATH . '/includes/Cache.php');

/*!
 * @brief %Database abstraction class
 *
 * This class abstracts the database handling by allowing the
 * creation of child classes with the name of a database table
 * to be able to read and write the database rows without
 * actualy database code.
 *
 * This class implements GetX and SetX methods for the database
 * data so they can be overridden with the class to allow 
 * data validation and permissions.
 * 
 * For this class to work, the database needs to have a 
 * ID field that is unique. 
 *
 * New rows can be created with a static method CreateNew, 
 * and old rows can be read with CreateByX where X is a
 * unique table field.
 *
 * $Author: mireiawen $
 * $Id: DataObject.php 459 2018-02-13 02:34:15Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
abstract class DataObject extends Base implements \Serializable
{
	/*!
	 * @brief Handle the serialization of the class
	 * 
	 * Handle the serialization of the data objects
	 * and return the vars array in serialized form
	 *
	 * @retval string
	 * 	The serialized data
	 */
	public function serialize()
	{
		return serialize($this -> vars);
	}
	
	/*!
	 * @brief Handle the unserialization of the class
	 *
	 * Handle the unserialization of the data objects
	 * and restore the vars array from the serialized
	 * form
	 *
	 * @param string $serialized
	 * 	The serialized data
	 */
	public function unserialize($serialized)
	{
		$this -> vars = unserialize($serialized);
	}

	/*!
	 * @brief Use the Cache trait
	 */
	use Cache;
	
	/*!
	 * @brief The protected constructor
	 * 
	 * Override the constructor visibility to 
	 * protected so classes can implement singleton
	 * pattern if they require it.
	 *
	 * Classes without singleton pattern should
	 * implement their own constructor, which may
	 * simply just call parent constructor
	 * if they have no other requirements.
	 *
	 * In addition to building the Base class,
	 * this constructor sets up some of the 
	 * default values for the data objects
	 * 
	 * @param bool $smarty 
	 * 	When set to TRUE, asks the class to load the Smarty if it is available
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct($smarty = FALSE)
	{
		// Make sure our cache times are defined
		if (!defined('CACHE_TIMEOUT_SHORT'))
		{
			define('CACHE_TIMEOUT_SHORT', 60);
		}
		
		if (!defined('CACHE_TIMEOUT_LONG'))
		{
			define('CACHE_TIMEOUT_LONG', 3600);
		}
		
		if (!defined('CACHE_TIMEOUT_PERSISTENT'))
		{
			define('CACHE_TIMEOUT_PERSISTENT', 0);
		}
		
		// Let the Base handle the constructing
		parent::__construct($smarty);
		
		// By default wait for write command
		$this -> _delaywrite = TRUE;
		
		// Some things assume ID is always available; set it to zero
		$this -> ID = 0;
		
		// Create array for field data
		$this -> _fields = array();
		
		// Assume the cache to be private
		$this -> data_is_private = TRUE;
	}
	
	/*!
	 * @brief GetX and SetX method implementation
	 * 
	 * Implement the GetX and SetX methods for the values in 
	 * the $vars array with custom __call method.
	 * 
	 * Other classes can still override the methods if they 
	 * want to provide their own error checking or access
	 * restrictions, but do not need to do the code
	 * if they don't have the need for such.
	 *
	 * This will write the database on setting the value
	 * unless the writes are delayed, in which case the
	 * caller needs to also call the Write method
	 * when they finish updating the data.
	 * 
	 * If the method isn't a Get or Set, then this will
	 * ask parent class to handle it.
	 * 
	 * @param string $method
	 * 	Method name that was called
	 * @param array $args
	 * 	Arguments passed for the method
	 * @retval mixed
	 * 	Should return the value of the key
	 * @throws Exception on invalid method call
	 * @throws Exception if trying to change the ID property
	 */
	public function __call($method, $args)
	{
		// Extract Get/Set and the name of variable
		$type = strtolower(substr($method, 0, 3));
		
		switch ($type)
		{
		case 'get':
		case 'set':
			break;
		
		default:
			return parent::__call($method, $args);
		}
		
		// Get the variable and validate we have something
		$key = substr($method, 3);
		if (empty($key))
		{
			throw new \Exception(sprintf(_('%s method call is missing the variable name'), ucfirst($type)));
		}
		
		// Did we want to get data
		if ($type === 'get')
		{
			return $this -> __get($key);
		}
		
		// Validate the arguments
		if (count($args) != 1)
		{
			throw new \Exception(sprintf(_('Invalid amount of arguments for method "%s"'), $method));
		}
		$value = $args[0];
		
		// Make sure we don't try to change the ID
		if ($key === 'ID')
		{
			throw new \Exception(_('Changing of ID is not allowed'));
		}
		
		// Check if value is actually changed
		if ($this -> __get($key) === $value)
		{
			return $value;
		}
		
		// Don't write to database (yet), just update the value
		if ($this -> _delaywrite)
		{
			return $this -> __set($key, $value);
		}
		
		// Write to the database and update the value
		else
		{
			return $this -> name = $this -> UpdateDB($key, $value);
		}
	}
	
	/*!
	 * @brief Static creation methods implementation
	 *
	 * Implement the CreateByX methods for the unique values
	 * in the database with the custom __callStatic method.
	 *
	 * This allows us to create the database objects without
	 * writing specific code to each and every case of the unique
	 * fields, while still having ability to override the methods
	 * for access rights or such checks.
	 * 
	 * CreateByX methods create a new instance of the class with
	 * the X being UNIQUE database field, and the first argument 
	 * being the value to search for in the database.
	 *
	 * This method tries to load the data from the cache backend
	 * before executing SQL query, so if caching is enabled the
	 * data might not be exactly same as it is in the database.
	 * 
	 * If the method isn't a CreateBy, then this will
	 * ask parent class to handle it.
	 * 
	 * @param string $method
	 * 	Method name that was called
	 * @param array $args
	 * 	Arguments passed for the method
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on invalid method call
	 */
	public static function __callStatic($method, $args)
	{
		// Check for Create methods
		$type = strtolower(substr($method, 0, strlen('CreateBy')));
		
		if ($type !== 'createby')
		{
			return parent::__callStatic($method, $args);
		}
		
		// Validate the arguments
		if (count($args) != 1)
		{
			throw new \Exception(sprintf(_('Invalid amount of arguments for method "%s"'), $method));
		}
		
		// Get the key name
		$key = substr($method, strlen('CreateBy'));
		
		// Create a new instance of the object
		$o = self::CreateNew();
		
		// And make sure the key is unique
		if (!array_key_exists($key, $o -> _fields))
		{
			throw new \Exception(sprintf(_('"%s" is not a valid object creation key: %s'), $key, _('It does not exist')));
		}
		if (!$o -> _fields[$key]['unique'])
		{
			throw new \Exception(sprintf(_('"%s" is not a valid object creation key: %s'), $key, _('It is not unique')));
		}
		
		// Try loading data from cache
		if (!strcmp($key, 'ID'))
		{
			$data = $o -> GetFromCache($args[0], $o -> data_is_private);
			if ($data !== FALSE)
			{
				$o -> vars = $data;
				return $o;
			}
		}
		else
		{
			$data = $o -> GetFromCache($key . '_' . $args[0], $o -> data_is_private);
			if ($data !== FALSE)
			{
				$o -> vars = $data;
				return $o;
			}
		}
		
		// Create MySQLi statement
		$db = Database::Get();
		
		// Work around timestamp data type
		$fields = array();
		foreach ($o -> _fields as $name => $params)
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
		
		$stmt = $db -> prepare('SELECT ' . implode(', ', $fields) . '
					FROM ' . $db -> escape_identifier(self::__get_called_class_name($o)) . ' 
					WHERE ' . $db -> escape_identifier($key) . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		if (!$stmt -> bind_param($o -> _fields[$key]['type'], $args[0]))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query
		$row = $db -> fetch_first($stmt);
		
		// Make sure row contains something
		if (!$row['ID'])
		{
			throw new \Exception(sprintf(_('Unable to find %s with %s of value %s'), get_class($o), $key, $args[0]));
		}
		
		// Merge the data
		$o -> vars = array_merge($o -> vars, $row);
		
		// Check for post hook
		if (method_exists($o, '__postCreate'))
		{
			$o -> __postCreate();
		}
		
		// And return the object
		return $o;
	}
	
	/*!
	 * @brief Create a new object from data provided
	 *
	 * Create a new instance of the object from the 
	 * data provided in the $data as array.
	 *
	 * This can speed up creation of multiple objects
	 * as a single query is enough to get multiple rows,
	 * and then from those rows can the objects be 
	 * created from.
	 *
	 * @attention
	 * The $data provided needs to be exactly same 
	 * as the database row, with all the fields as
	 * an associative array. Otherwise the creation 
	 * will fail.
	 * 
	 * @param array $data
	 * 	The database row to create object from
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on invalid data
	 * @todo Data type checking
	 */
	public static function CreateFromArray($data)
	{
		// Create a new object of the class
		$class = get_called_class();
		$o = new $class();
		$o -> GetFields();
		foreach ($o -> _fields as $name => $params)
		{
			if (!array_key_exists($name, $data))
			{
				throw new \Exception(sprintf(_('Unable to create %s from array: missing key %s'), get_class($o), $name));
			}
			
			// TODO: type checking
			$o -> vars[$name] = $data[$name];
		}
		
		return $o;
	}
	
	/*!
	 * @brief Create a new object
	 *
	 * Create a new instance of the object with the 
	 * default values. This is used to create new
	 * rows in the database.
	 *
	 * By default, new objects will have delayed 
	 * writes on to prevent problems with missing 
	 * of required fields data.
	 * 
	 * @retval object
	 * 	Instance of the class itself
	 */
	public static function CreateNew()
	{
		// Create a new object of the class
		$class = get_called_class();
		$o = new $class();
		
		// Set up defaults
		$o -> GetFields();
		foreach ($o -> _fields as $name => $params)
		{
			switch ($params['type'])
			{
			case 'i':
				$o -> $name = 0;
				break;
			case 's':
				$o -> $name = '';
				break;
			case 'd':
				$o -> $name = 0.0;
				break;
			case 'b':
				$o -> $name = NULL;
				break;
			}
		}
		
		return $o;
	}
	
	/*!
	 * @brief Get all object instances from the database
	 *
	 * Select all rows from the database and return them
	 * as an array of the objects of the class
	 *
	 * @retval array of objects
	 * 	Array of class objects
	 * @throws Exception on errors
	 */
	public static function GetAll()
	{
		// Get current database connection
		$db = Database::Get();
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Create the SQL query
		$sql = 'SELECT *
			FROM ' . $db -> escape_identifier(self::__get_called_class_name(get_called_class()));
		
		// Prepare the SQL query
		$stmt = $db -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}

		// Convert the rows into objects
		$result = $db -> fetch_assoc($stmt);
		$objects = array();
		foreach ($result as $row)
		{
			$objects[] = self::CreateFromArray($row);
		}
		
		return $objects;
	}
	
	/*!
	 * @brief Get object instances from the database 
	 * with specific value
	 *
	 * Select all rows from the database with specific column value 
	 * and return them as an array of the objects of the class
	 *
	 * @param string $key
	 * 	Column name to match
	 * @param string $type
	 * 	SQL column type
	 * @param mixed $value
	 * 	Value to search for
	 *
	 * @retval array of objects
	 * 	Array of class objects
	 * @throws Exception on errors
	 */
	public static function GetAllByAttr($key, $type, $value)
	{
		// Get current database connection
		$db = Database::Get();
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Create the SQL query
		$sql = sprintf('SELECT * FROM %s WHERE %s = ?',
			$db -> escape_identifier(self::__get_called_class_name(get_called_class())),
			$db -> escape_identifier($key));
		
		// Prepare the SQL query
		$stmt = $db -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Set up parameters
		if (!$stmt -> bind_param($type, $value))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Convert the rows into objects
		$result = $db -> fetch_assoc($stmt);
		$objects = array();
		foreach ($result as $row)
		{
			$objects[] = self::CreateFromArray($row);
		}
		
		return $objects;
	}
	
	/*!
	 * @brief Get the class constants
	 *
	 * Get all of the class defined constants
	 *
	 * @retval array
	 * 	Array of class constants, may be empty
	 */
	public static function GetConstants()
	{
		$refl = new \ReflectionClass(get_called_class());
		return $refl -> getConstants();
	}
	
	/*!
	 * @brief Get the delayed writes status
	 * 
	 * Get the current status of the delayed writes. If 
	 * writes are delayed, the Write method has to be 
	 * called for the actual database writing,
	 * otherwise each object modification triggers a 
	 * database writing.
	 * 
	 * @retval bool
	 * 	TRUE if writes are delayed,
	 * 	FALSE if writes are instant
	 */
	public function GetDelayWrites()
	{
		return $this -> _delaywrite;
	}
	
	/*!
	 * @brief Turn on the delayed writes
	 *
	 * Set the delayed write on to prevent database writes
	 * on each object modification. 
	 *
	 * To turn delayed writes off, you must call Write method
	 * with delayed writes set to off.
	 *
	 * @retval bool
	 * 	TRUE if writes are delayed,
	 * 	FALSE if writes are instant
	 */
	public function DelayWrites()
	{
		return $this -> _delaywrite = TRUE;
	}
	
	/*!
	 * @brief Write the changes to the database
	 *
	 * Write the current changes to the database if writes
	 * are delayed.
	 *
	 * @param bool $delaywrites
	 * 	Set to TRUE if you want to keep delaying writes,
	 * 	FALSE if you want to stop delaying writes
	 * @throws Exception on errors
	 */
	public function Write($delaywrites = TRUE)
	{
		// Check for database
		$db = Database::Get();
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Update the key information
		$this -> GetFields();
		
		// Binary fields
		$bins = array();
		
		// Create statement for updating
		if ($this -> GetID())
		{
			$binds = '';
			$sets = array();
			$vals = array();
			$fields = array();
			
			// Catch the keys, values and bindings
			foreach ($this -> _fields as $name => $opts)
			{
				if (!strcmp($name, 'ID'))
				{
					continue;
				}
				
				// Prepare to upload binary data
				if ($opts['type'] === 'b')
				{
					$bins[] = array('index' => strlen($binds), 'field' =>  $name);
				}
				$sets[] =  $db -> escape_identifier($name) . ' = ?';
				$vals[] = $this -> __get($name);
				switch ($opts['sqltype'])
				{
				case 'timestamp':
					$binds .= 's';
					$fields[] = 'FROM_UNIXTIME(?)';
					break;
				default:
					$binds .= $opts['type'];
					$fields[] = '?';
				}
			}
			
			// Create the query
			$sql = 'UPDATE ' . $db -> escape_identifier(self::__get_called_class_name($this)) . ' 
				SET ' . implode(", \n", $sets) . '
				WHERE ' . $db -> escape_identifier('ID') . ' = ?';
			
			// Insert ID
			$binds .= 'i';
			array_push($vals, $this -> GetID());
			$stmt = $db -> prepare($sql);
		}
		
		// Otherwise create a new row
		else
		{
			$binds = '';
			$keys = array();
			$fields = array();
			
			// Catch the keys, values and bindings
			foreach ($this -> _fields as $name => $opts)
			{
				if (!strcmp($name, 'ID'))
				{
					continue;
				}
				
				// Prepare to upload binary data
				if ($opts['type'] === 'b')
				{
					$bins[] = array('index' => strlen($binds), 'field' =>  $name);
				}
				$keys[] = $db -> escape_identifier($name);
				$vals[] = $this -> __get($name);
				switch ($opts['sqltype'])
				{
				case 'timestamp':
					$binds .= 's';
					$fields[] = 'FROM_UNIXTIME(?)';
					break;
				default:
					$binds .= $opts['type'];
					$fields[] = '?';
				}
			}
			
			// Create the query
			$sql = 'INSERT INTO ' . $db -> escape_identifier(self::__get_called_class_name($this)) . ' 
				(' . implode(', ', $keys) . ') 
				VALUES (' . implode(', ', $fields) . ')';
			$stmt = $db -> prepare($sql);
		}
		
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		$id = $this -> GetID();
		array_unshift($vals, $binds);
		
		// Fix the bindings
		$refs = array();
		foreach ($vals as $key => $val)
		{
			$refs[$key] = &$vals[$key];
		}
		
		if (!call_user_func_array(array($stmt, 'bind_param'), $refs))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Do the actual binary uploading
		foreach ($bins as $row)
		{
			if (!$stmt -> send_long_data($row['index'], $this -> __get($row['field'])))
			{
				throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
			}
		}
		
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		if (!$this -> GetID())
		{
			// Get the last insert ID
			$this -> ID = $stmt -> insert_id;
		}
	}
	
	/*!
	 * @brief Delete the item from the database
	 *
	 * Deletes the current object from the database,
	 * if it has its ID set. After deletion, the ID is 
	 * set to 0.
	 *
	 * @note There is no logging or access rights checks
	 * in this method. 
	 *
	 * @throws Exception on error
	 */
	public function Remove()
	{
		// Make sure we have the ID
		if ($this -> GetID() < 1)
		{
			throw new \Exception(sprintf(_('Unable to delete %s without %s'), get_called_class(), 'ID'));
		}
		
		// Get database connection
		$db = Database::Get();
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Prepare the query
		$sql = 'DELETE FROM ' . $db -> escape_identifier(self::__get_called_class_name(get_called_class())) . ' 
			WHERE ' . $db -> escape_identifier('ID') . ' = ?';
		$stmt = $db -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Set up parameters
		$id = $this -> GetID();
		if (!$stmt -> bind_param('i', $id))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// And execute it
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Done
		$this -> ID = 0;
	}
	
	/*!
	 * @brief Perform a database write for a single key
	 *
	 * Updates the database value for $key when delayed
	 * writes are not on. This will try to create a new row
	 * if ID is not set, but that might fail with SQL error
	 * if all required data is not available.
	 *
	 * @param string $key
	 * 	The database field to write the data to
	 * @param mixed $value
	 * 	The actual data to write
	 * @retval mixed
	 * 	The written data
	 * @throws Exception on errors
	 */
	protected function UpdateDB($key, $value)
	{
		// Check for database
		$db = Database::Get();
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Update the key information
		$this -> GetFields();
		
		// Make sure the key is known
		if (!array_key_exists($key, $this -> _fields))
		{
			throw new \Exception(sprintf(_('Unknown key "%s" in "%s"'), $key, get_class($this)));
		}
		
		// Create new row
		if (!$this -> GetID())
		{
			$this -> Write($this -> _delaywrite);
			return $value;
		}
		
		// Update the value
		$stmt = $db -> prepare('UPDATE ' . $db -> escape_identifier(self::__get_called_class_name($this)) . ' 
				SET ' . $db -> escape_identifier($key) . ' = ?
				WHERE ' . $db -> escape_identifier('ID') . ' = ?');
		
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		$id = $this -> GetID();
		if (!$stmt -> bind_param($this -> _fields[$key]['type'] . 'i', $value, $id))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		return $value;
	}
	
	/*!
	 * @brief Get the database field information
	 *
	 * Get the field name and datatype information
	 * for the table from the database so we can 
	 * set the field type information in writes 
	 * automatically.
	 *
	 * This method tries to use the cache backend 
	 * if it is available to avoid extra queries
	 * to the database.
	 *
	 * @retval bool
	 * 	TRUE on success
	 * @throws Exception on errors
	 */
	protected function GetFields()
	{
		// Check if we already have it set
		if (!empty($this -> _fields))
		{
			return TRUE;
		}
		
		// Make sure we have cache backend
		try
		{
			// Read from the cache
			$data = $this -> GetFromCache('keys', FALSE);
			
			// Check it should be useful
			if ($data !== FALSE)
			{
				$this -> _fields = $data;
				return TRUE;
			}
		}
		
		// Catch for errors
		catch (\Exception $e)
		{
			// Set up error message if debugging
			if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_CACHE')) && (DEBUG_CACHE))
			{
				Error::Message(sprintf(_('Unable to retrieve key %s from cache: %s'), get_class($this) . '_keys', $e -> getMessage()), ERROR_LEVEL_WARNING);
			}
		}
		
		// Check for database
		$db = Database::Get();
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		$stmt = $db -> prepare('DESCRIBE ' . $db -> escape_identifier(self::__get_called_class_name($this)));
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Execute the query itself
		$result = Database::Get() -> fetch_assoc($stmt);
		
		foreach ($result as $row)
		{
			$this -> _fields[$row['Field']] = array(
				'type' => $this -> Mysql2Bind($row['Type']),
				'sqltype' => strtolower($row['Type']),
				'unique' => $this -> Mysql2Unique($row['Key']),
				);
		}
		
		// Store to cache
		try
		{
			// The SQL structure should not change, try persistently storing
			$this -> SaveToCache($this -> _fields, 'keys', FALSE, CACHE_TIMEOUT_PERSISTENT);
			return TRUE;
		}
		
		// Catch for errors
		catch (\Exception $e)
		{
			// Set up error message if debugging
			if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_CACHE')) && (DEBUG_CACHE))
			{
				Error::Message(sprintf(_('Unable to store the key %s to cache: %s'), get_class($this) . '_keys', $e -> getMessage()), ERROR_LEVEL_WARNING);
			}
		}
		
		// All done
		return TRUE;
	}
	
	/*!
	 * @brief Store the data to the cache by all unique keys
	 *
	 * Store the data to the caching backend by all known unique keys
	 * for the current DataObject
	 *
	 * @param int $timeout
	 * 	The timeout for the cache
	 * @throws Exception on failures
	 */
	protected function CacheUniqueKeys($timeout = CACHE_TIMEOUT_LONG)
	{
		$this -> SaveToCache($this -> vars, $this -> ID, $this -> data_is_private, $timeout);
		foreach ($this -> _fields as $k => $v)
		{
			if ($v['unique'])
			{
				$this -> SaveToCache($this -> vars, $k . '_' . $this -> __get($k), $this -> data_is_private, $timeout);
			}
		}
	}
	
	/*!
	 * @brief Convert the MySQL datatype to the MySQLi statement datatype
	 *
	 * Concerts the MySQL table datatype to the MySQLi 
	 * prepared statement bind_param datatype character.
	 *
	 * @param string $type
	 * 	The datatype to find
	 * @retval string
	 * 	A character for MySQLi bind_param
	 * @throws Exception on an invalid datatype
	 */
	protected function Mysql2Bind($type)
	{
		list($type, ) = @explode('(', strtolower($type), 2);
		
			// Types are:
			// i - integer
			// s - string
			// d - double
			// b - blob
		switch ($type)
		{
		case	'integer':
		case	'int':
		case	'smallint':
		case	'tinyint':
		case	'mediumint':
		case	'bigint':
		case	'timestamp':
			return 'i';
		
		case	'decimal':
		case	'numeric':
		case	'float':
		case	'double':
			return 'd';
		
		case	'char':
		case	'varchar':
		case	'text':
		case	'date':
		case	'time':
		case	'datetime':
			return 's';
		
		case	'blob':
		case	'binary':
		case	'varbinary':
			return 'b';
		
		default:
			throw new \Exception(sprintf(_('Unknown data type "%s"'), $type));
		}
	}
	
	/*!
	 * @brief Tell if a database key is of unique type
	 *
	 * Check if the key from database is unique
	 *
	 * @param string $key
	 * 	The key to check
	 * @retval bool
	 * 	TRUE if the key is unique,
	 * 	FALSE if not
	 */
	private function Mysql2Unique($key)
	{
		switch($key)
		{
		case	'PRI':
		case	'UNI':
			return TRUE;
			
		default:
			return FALSE;
		}
	}
	
	protected static function __get_called_class_name($o)
	{
		$refl = new \ReflectionClass($o);
		return $refl -> getShortName();
	}
}
