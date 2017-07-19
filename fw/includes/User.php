<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Special user group levels
define('USER_GROUP_NONE', 0);
define('USER_GROUP_ANY', -2);
define('USER_GROUP_VALID_USER', -3);

// Database group levels
define('USER_GROUP_ADMIN', 1);
define('USER_GROUP_USER', 2);

// Special IDs
define('USER_ID_ALL', 1);
define('USER_ID_UNKNOWN', 2);
define('USER_ID_MIN', 3);

/*!
 * @brief Handle the users in the database
 *
 * A class to handle the user information in
 * the database with the basic data validation
 * as well as advanced password hashing 
 * abilities.
 *
 * $Author: mireiawen $
 * $Id: User.php 441 2017-07-11 21:02:54Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class User extends DataObject
{
	/*!
	 * @brief Debug information handler
	 *
	 * This method returns the data that should be
	 * out put with var_dump. Please note that it
	 * only works with PHP 5.6 and later
	 *
	 * @retval mixed
	 * 	Data to be shown with var_dump
	 */
	public function __debugInfo()
	{
		return array
		(
			'UID' => $this -> vars['ID'],
			'Username' => $this -> vars['Username'],
			'Name' => $this -> vars['Name'],
			'Email' => $this -> vars['Email'],
			'Active' => $this -> vars['Active'],
		);
	}
	
	/*!
	 * @brief The protected constructor
	 * 
	 * Avoid the creation of the class in a usual way
	 * since we are database driven object. 
	 *
	 * The instance should be created by CreateByX 
	 * or CreateNew static methods.
	 *
	 * Initialize the parent class, and set up
	 * default constants for the class to work
	 * if not already set.
	 * 
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		if (!extension_loaded('hash'))
		{
			throw new \Exception(_('Hash extension is required!'));
		}
		
		parent::__construct();
		if (!defined('PASSWORD_SALT_LENGTH'))
		{
			define('PASSWORD_SALT_LENGTH', 32);
		}
		
		if (!defined('PASSWORD_HASH_ALGORITHM'))
		{
			define('PASSWORD_HASH_ALGORITHM', 'sha256');
		}
		
		if (!defined('PASSWORD_ITERATIONS'))
		{
			define('PASSWORD_ITERATIONS', 250);
		}
		
		if (!defined('PASSWORD_KEY_LENGTH'))
		{
			define('PASSWORD_KEY_LENGTH', 32);
		}
	}
	
	/*!
	 * @brief The destructor
	 *
	 * Save the date to the cache backend to 
	 * try to avoid some SQL queries on
	 * page load.
	 */
	public function __destruct()
	{
		$this -> SaveToCache($this -> vars, '', TRUE, CACHE_TIMEOUT_SHORT);
	}
	
	/*!
	 * @brief Get list of all users
	 *
	 * Get all of the users in the database,
	 * optionally only active users.
	 *
	 * @param bool $active_only
	 * 	If set to TRUE, will return only the active users,
	 * 	if FALSE will return all users
	 * @retval array
	 * 	An array of User objects
	 * @throws Exception on error
	 */
	public static function GetUsers($active_only = FALSE)
	{
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Create statement
		$stmt = $db -> prepare('SELECT ' . $db -> escape_identifier('ID') . ' FROM ' . $db -> escape_identifier('User') . ' WHERE ' . $db -> escape_identifier('ID') . ' >= ?' . ($active_only ? ' AND ' . $db -> escape_identifier('Active') . ' = 1' : ''));
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		$min_id = USER_ID_MIN;
		if (!$stmt -> bind_param('i', $min_id))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute statement
		$uids = $db -> fetch_assoc($stmt);
		$users = array();
		foreach ($uids as $uid)
		{
			$users[] = User::CreateByID($uid['ID']);
		}
		
		return $users;
	}
	
	/*!
	 * @brief Get the username for the specified user ID
	 *
	 * Try to get the username for the specified user ID.
	 * This will throw Exception if the user ID does not exist.
	 *
	 * @param int $uid
	 * 	User ID to get the username for
	 * @retval string
	 * 	Username for the user ID $uid
	 * @throws Exception if User object was not created
	 */
	public static function Username($uid)
	{
		$o = User::CreateByID($uid);
		
		if ($o === NULL)
		{
			throw new \Exception(_('Unable to create User object'));
		}
		
		return $o -> GetUsername();
	}
	
	/*!
	 * @brief Get the group name for the specified group ID
	 *
	 * Try to get the group name for the specified group ID.
	 * This will throw Exception if the group ID does not exist.
	 *
	 * @param int $gid
	 * 	Grouo ID to get the group name for
	 * @retval string
	 * 	Group name for the given group ID $gid
	 * @throws Exception on errors
	 */
	public static function Groupname($gid)
	{
		// Prepare a statement
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Prepare the query
		$stmt = $db -> prepare('SELECT ' . $db -> escape_identifier('Name') . ' FROM ' . $db -> escape_identifier('Group') . ' WHERE ' . $db -> escape_identifier('ID') . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}

		// Bind the ID
		if (!$stmt -> bind_param('i', $gid))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query
		$row = $db -> fetch_first($stmt);
		
		// Get group info
		if (empty($row))
		{
			return _('unknown');
		}
		
		return $row['Name'];
	}
	
	/*!
	 * @brief Generate a random string
	 *
	 * Generate a random string, originally used to just 
	 * create a relatively simple random passwords.
	 *
	 * This would not be generating exactly safe passwords, 
	 * but could be enough when users forget their passwords
	 * to generate something to reset their passwrods with.
	 * 
	 * @param int $length
	 * 	Length of the string to create
	 * @retval string
	 * 	A random alphanumeric string
	 */
	public static function GeneratePassword($length = 8)
	{
		$str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
		$len = strlen($str);
		$pw = '';
		
		for ($i=0; $i<$length; $i++)
		{
			$pw .= substr($str, rand(0, $len-1), 1);
		}
		
		return str_shuffle($pw);
	}
	
	/*!
	 * @brief Update the username
	 *
	 * Validate the new username and 
	 * save it if valid.
	 *
	 * Throws Exception if the value is not valid.
	 *
	 * @param string $value
	 * 	New username to set
	 * @retval string
	 * 	The saved value
	 * @throws Exception on error
	 */
	public function SetUsername($value)
	{
		// Check if the new username is same as what is set
		if ($this -> Username === $value)
		{
			return $this -> Username;
		}
		
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// TODO: validity check for value
		
		// Make sure username is not taken
		$stmt = $db -> prepare('SELECT ' . $db -> escape_identifier('ID') . ' FROM ' . $db -> escape_identifier('User') . ' WHERE ' . $db -> escape_identifier('Username') . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		if (!$stmt -> bind_param('s', $value))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Username is taken
		$stmt -> store_result();
		if ($stmt -> num_rows)
		{
			throw new \Exception(sprintf(_('Username "%s" is already taken'), $value));
		}
		
		// Set the username
		return parent::SetUsername($value);
	}
	
	/*!
	 * @brief Update the email
	 *
	 * Validate the new email and 
	 * save it if valid.
	 *
	 * Throws Exception if the value is not valid.
	 *
	 * @param string $value
	 * 	New email to set
	 * @retval string
	 * 	The saved value
	 * @throws Exception on error
	 */
	public function SetEmail($value)
	{
		// Check if the new email is same as what is set
		if ($this -> Email === $value)
		{
			return $this -> Email;
		}
		
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Do basic validation
		$email = filter_var($value, FILTER_VALIDATE_EMAIL);
		if ($email === FALSE)
		{
			throw new \Exception(sprintf(_('Invalid e-mail address "%s"'), $value));
		}
		
		// Make sure email is not in use
		$stmt = $db -> prepare('SELECT ' . $db -> escape_identifier('ID') . ' FROM ' . $db -> escape_identifier('User') . ' WHERE ' . $db -> escape_identifier('Email') . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		if (!$stmt -> bind_param('s', $value))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Username is taken
		$stmt -> store_result();
		if ($stmt -> num_rows)
		{
			throw new \Exception(sprintf(_('Email "%s" is already in use'), $value));
		}
		
		// And save it
		return parent::SetEmail($value);
	}
	
	/*!
	 * @brief Update the name
	 *
	 * Validate the new real name and 
	 * save it if valid.
	 *
	 * Throws Exception if the value is not valid.
	 *
	 * @param string $value
	 * 	New real name to set
	 * @retval string
	 * 	The saved value
	 * @throws Exception on error
	 * @todo validity check for the value
	 */
	public function SetName($value)
	{
		// TODO: validity check for value

		// And save it
		return parent::SetName($value);
	}
	
	/*!
	 * @brief Set user activity status
	 *
	 * Set the user activity status to either
	 * active or inactive. Inactive users are 
	 * not allowed to log in.
	 *
	 * @param bool $value
	 * 	TRUE if user is active, 
	 * 	FALSE if inactive
	 * @retval bool
	 * 	The saved value
	 * @throws Exception on error
	 */
	public function SetActive($value)
	{
		$value = (bool)($value);
		
		// And save it
		parent::SetActive($value);
	}
	
	/*!
	 * @brief Check if the password matches
	 *
	 * Check if the given password matches with the 
	 * user's current password. The check is made
	 * by hashing the given password with the method
	 * and salt from the current  password and then
	 * checking if the hashes match.
	 *
	 * @param string $password
	 * 	The password to test
	 * @retval bool
	 * 	TRUE if the $password matches with the user password,
	 * 	FALSE if not
	 * @throws Exception if user is invalid
	 */
	public function VerifyPassword($password)
	{
		// Validate UID
		if ($this -> ID < USER_ID_MIN)
		{
			throw new \Exception(_('Invalid user ID'));
		}
		
		// Read the current password information
		try
		{
			$data = $this -> ReadHash($this -> Password);
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		// Create hash from input with current salt
		$hash = $this -> Hash($password, $data['salt'], $data['algorithm'], $data['salt_length'], $data['iterations'], $data['length']);
		$test = $this -> ReadHash($hash);
		
		// Get the hash datas
		$hash1 = $data['hash'];
		$len1 = $data['length'];
		
		$hash2 = $test['hash'];
		$len2 = $test['length'];
		
		// Calculate the difference
		$diff = $len1 ^ $len2;
		for($i = 0; $i < $len1 && $i < $len2; $i++)
		{
			$diff |= ord($hash1[$i]) ^ ord($hash2[$i]);
		}
		return (bool)($diff === 0);
	}
	
	/*!
	 * @brief Save a new password
	 *
	 * Save a new password as a hash. This 
	 * creates a new password hash for given 
	 * password and saves it to the database.
	 *
	 * @param string $password
	 * 	The new password to save
	 * @return string
	 * 	The created password hash
	 * @todo password complexity checks by some criteria
	 */
	public function CreatePassword($password)
	{
		// TODO: complexity checks
		$hash = $this -> Hash($password);
		
		if ($this -> _delaywrite)
		{
			return $this -> Password = $hash;
		}
		else
		{
			return $this -> Password = $this -> UpdateDB('password', $hash);
		}
	}

	/*!
	 * @brief Delete the user
	 *
	 * Delete the user from the database. This might
	 * throw database error if there are foreign key
	 * failures, as this will not check those as 
	 * there may be user made extra checks.
	 *
	 * @throws Exception on error
	 */
	public function Delete()
	{
		// Make sure we are in the database
		if (!$this -> GetID())
		{
			return ;
		}
		
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Create statement
		$stmt = $db -> prepare('DELETE FROM ' . $db -> escape_identifier('User') . ' WHERE ' . $db -> escape_identifier('ID') . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		$uid = $this -> GetID();
		if (!$stmt -> bind_param('i', $uid))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute statement
		if ($stmt -> execute() === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Write to the log
		Log::Get() -> Add('Deleted user ' . $this -> Username . ' (' . $this -> ID . ')');
		
		// Set the ID to as we are no longer in DB
		$this -> ID = 0;
	}
	
	/*!
	 * @brief Check if the user has any of the given access levels
	 *
	 * Check if the user has any of the given access levels
	 * in the array.
	 * 
	 * Checking will stop on the first match.
	 * 
	 * @param array $levels
	 * 	Array of access levels to check
	 * @retval bool
	 * 	TRUE if the user has any of the given access levels,
	 * 	FALSE if not
	 * @throws Exception on invalid input
	 * @throws Exception on errors
	 */
	public function HasAnyAccessLevel($levels)
	{
		if (!is_array($levels))
		{
			throw new \Exception(sprintf(_('DEBUG Invalid input; expecting array, got %s') . gettype($level)));
		}
		
		// Go through all levels
		foreach ($levels as $level)
		{
			// Check for access
			if ($this -> HasAccessLevel($level))
			{
				return TRUE;
			}
		}
		
		// None found
		return FALSE;
	}
	
	/*!
	 * @brief Check if the user has the given access level
	 *
	 * Check if the user has the given access level
	 * 
	 * @param int $value
	 * 	Access level to check
	 * @retval bool
	 * 	TRUE if the user has the given access level,
	 * 	FALSE if not
	 * @throws Exception on invalid input
	 * @throws Exception on errors
	 */
	public function HasAccessLevel($value)
	{
		// Do basic validation
		$level = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('minrange' => -10)));
		if ($level === FALSE)
		{
			throw new \Exception(_('Invalid access level given'));
		}
		
		// Check if the user is active
		if (!$this -> GetActive())
		{
			return FALSE;
		}
		
		// None is always accepted
		if ($level === USER_GROUP_NONE)
		{
			return TRUE;
		}
		
		// Check for valid user
		if ($level === USER_GROUP_VALID_USER)
		{
			return (bool)($this -> GetID() >= USER_ID_MIN);
		}
		
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Check for ANY
		if ($level === USER_GROUP_ANY)
		{
			// Prepare statement
			$stmt = $db -> prepare('SELECT ' . $db -> escape_identifier('UID') . ' FROM ' . $db -> escape_identifier('UserGroup') . ' WHERE ' . $db -> escape_identifier('UID') . ' = ?');
			if ($stmt === FALSE)
			{
				throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
			}
			
			// Bind parameters
			if (!$stmt -> bind_param('i', $this -> ID))
			{
				throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
			}
			
			// Execute the query itself
			if (!$stmt -> execute())
			{
				throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
			}
			
			$stmt -> store_result();
			return (bool)($stmt -> num_rows);
		}
		
		// Prepare statement
		$stmt = $db -> prepare('SELECT ' . $db -> escape_identifier('UID') . ' FROM ' . $db -> escape_identifier('UserGroup') . ' WHERE ' . $db -> escape_identifier('UID') . ' = ? AND ' . $db -> escape_identifier('GID') . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		if (!$stmt -> bind_param('ii', $this -> ID, $level))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}

		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		$stmt -> store_result();
		return (bool)($stmt -> num_rows);
	}
	
	/*!
	 * @brief Add the access level to the user
	 *
	 * Add the given access level to the user
	 *
	 * @param int $value
	 * 	Access level to add
	 * @retval bool
	 * 	TRUE on success
	 * @throws Exception on errors
	 */
	public function AddAccessLevel($value)
	{
		// Do not modify system users
		if ($this -> ID < USER_ID_MIN)
		{
			throw new \Exception(sprintf(_('Invalid user %s'), $this -> Username));
		}
		
		// Do basic validation
		$level = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('minrange' => 0)));
		
		// Make sure it looks like valid
		if (($level === FALSE) || ($level === USER_GROUP_NONE) || ($level === USER_GROUP_ANY))
		{
			throw new \Exception(_('Invalid access level given'));
		}
		
		// Check if user already got the access level
		if ($this -> HasAccessLevel($level))
		{
			return TRUE;
		}
		
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Prepare statement
		$stmt = $db -> prepare('INSERT INTO ' . $db -> escape_identifier('UserGroup') . ' (' . $db -> escape_identifier('UID') . ', ' . $db -> escape_identifier('GID') . ') VALUES (?, ?)');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		if (!$stmt -> bind_param('ii', $this -> ID, $level))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		Log::Get() -> Add('Added access level "' . User::Groupname($level) . '" (' . $level . ') for user ' . $this -> Username . ' (' . $this -> ID . ')');
		
		return TRUE;
	}
	
	/*!
	 * @brief Remove the access level from the user
	 *
	 * Remove the given access level from the user
	 *
	 * @param int $value
	 * 	Access level to remove
	 * @retval bool
	 * 	TRUE on success
	 * @throws Exception on errors
	 */
	public function RemoveAccessLevel($value)
	{
		// Do not modify system users
		if ($this -> ID < USER_ID_MIN)
		{
			throw new \Exception(sprintf(_('Invalid user %s'), $this -> Username));
		}
		
		// Do basic validation
		$level = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('minrange' => 0)));
		
		// Make sure it looks like valid
		if (($level === FALSE) || ($level === USER_GROUP_NONE) || ($level === USER_GROUP_ANY))
		{
			throw new \Exception(_('Invalid access level given'));
		}
		
		// Check if user already got the access level
		if (!$this -> HasAccessLevel($level))
		{
			return TRUE;
		}
		
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Prepare statement
		$stmt = $db -> prepare('DELETE FROM ' . $db -> escape_identifier('UserGroup') . ' WHERE ' . $db -> escape_identifier('UID') . ' = ? AND ' . $db -> escape_identifier('GID') . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		if (!$stmt -> bind_param('ii', $this -> ID, $level))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		Log::Get() -> Add('Removed access level "' . User::Groupname($level) . '" (' . $level . ') from user ' . $this -> Username . ' (' . $this -> ID . ')');
		
		return TRUE;
	}
	
	/*!
	 * @brief Get all access levels for the user
	 *
	 * Get all the access levels that are given 
	 * to the user
	 *
	 * @retval array
	 * 	Array of access levels for the user
	 * @throws Exception on errors
	 */
	public function GetAccessLevels()
	{
		// Get database
		$db = Database::Get();
		
		// Check for database
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Create statement
		$stmt = $db -> prepare('SELECT ' . $db -> escape_identifier('UID') . ', ' . $db -> escape_identifier('GID') . ' FROM ' . $db -> escape_identifier('UserGroup') . ' WHERE ' . $db -> escape_identifier('UID') . ' = ?');
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind parameters
		if (!$stmt -> bind_param('i', $this -> ID))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query itself
		return $db -> fetch_assoc($stmt);
	}
	
	/*!
	 * @brief Update the single value to the database
	 *
	 * Update the single value to the database and 
	 * log the change.
	 *
	 * Calls the parent UpdateDB for the actual updating.
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
		parent::UpdateDB($key, $value);
		
		// Log the event
		Log::Get() -> Add('Updated user ' . $this -> Username . ' (' . $this -> ID . ') set ' . $key . ' to "' . $value[$key] . '"');

		return $value;
	}

	/*!
	 * @brief Update all of values to the database
	 *
	 * Update the data to the database and 
	 * log it.
	 *
	 * Calls the parent Write for the actual updating.
	 *
	 * @param bool $delaywrites
	 * 	Set to TRUE if you want to keep delaying writes,
	 * 	FALSE if you want to stop delaying writes
	 * @throws Exception on errors
	 */
	public function Write($delaywrites = TRUE)
	{
		// Check if we have ID, aka are we creating new user
		if (!$this -> GetID())
		{
			Log::Get() -> Add('Creates new user ' . $this -> Username);
		}
		
		// Do the actual write
		parent::Write($delaywrites);
		
		// Log the event
		Log::Get() -> Add('Updated user ' . $this -> Username . ' (' . $this -> ID . ') with Write');
	}
	
	/*!
	 * @brief Generate a hash from the password
	 *
	 * Generate a salted hash with  specified algorithm 
	 * from the given password.
	 *
	 * If salt is not given, will generate a new one.
	 * 
	 * See [PHP documentation](http://php.net/manual/en/function.hash.php) for hash algorithms
	 * 
	 * @param string $password
	 * 	Password to create the hash from
	 * @param string $salt
	 * 	The salt to use, NULL to generate new one
	 * @param string $algorithm
	 * 	The hashing algorithm to use
	 * @param int $salt_length
	 * 	Length of the salt to generate
	 * @param int $iterations
	 * 	Number of iterations to run on the hash
	 * @param int $length
	 * 	
	 * @retval string
	 * 	The salted hash we just generated
	 * @throws Exception on critical failure
	 */
	private function Hash($password, $salt = NULL, $algorithm = PASSWORD_HASH_ALGORITHM, $salt_length = PASSWORD_SALT_LENGTH, $iterations = PASSWORD_ITERATIONS, $length = PASSWORD_KEY_LENGTH)
	{
		// If we don't have salt, create it
		if ($salt === NULL)
		{
			// Try using OpenSSL
			if (function_exists('openssl_random_pseudo_bytes'))
			{
				$salt = openssl_random_pseudo_bytes($salt_length, $strong);
				if (($salt === FALSE) || ($strong === FALSE))
				{
					throw new \Exception(_('Creation of strong password salt failed'));
				}
			}
			
			// Try using mcrypt
			else if (function_exists('mcrypt_create_iv'))
			{
				$salt = mcrypt_create_iv($salt_length, MCRYPT_DEV_URANDOM);
			}
			
			// Fall back to MD5
			else
			{
				trigger_error(_('No secure random found, falling back to cryptographically unsafe methods. You should enable OpenSSL or MCrypt extension'), E_USER_NOTICE);
				$salt = substr(md5(uniqid(rand(), true)), 0, $salt_length);
			}
		}
		
		// Validate the algorithm
		$alg = strtolower($algorithm);
		if (!in_array($alg, hash_algos(), TRUE))
		{
			throw new \Exception(sprintf(_('Invalid hashing algorithm "%s": %s'), $algorithm, _('It was not found')));
		}
		
		// Check the parameters
		if (($iterations < 1) || ($length < 1))
		{
			throw new \Exception(sprintf(_('Invalid hashing parameters')));
		}
		
		// Support old passwords
		if ($iterations === 1)
		{
			return '{' . $algorithm . '#' . $salt_length . ':' . $iterations . ':' . $length . '}' . base64_encode($salt . hash($alg, $password, TRUE));
		}
		
		// Return salted hash
		return '{' . $algorithm . '#' . $salt_length . ':' . $iterations . ':' . $length . '}' . base64_encode($salt . hash_pbkdf2($alg, $password, $salt, $iterations, $length, TRUE));
	}
	
	/*!
	 * @brief Read data from a generated password hash
	 *
	 * Try to read the password data from given hash,
	 * including the algorithm, salt and password
	 * 
	 * The extracted data will be associative array with 
	 * values "algorithm", "salt", "hash" and "salt_length".
	 *
	 * @param string $hash
	 * 	The hash to extract the data from
	 * @retval array
	 * 	The array of the data extracted
	 * @throws Exception if the data cannot be read
	 */
	private function ReadHash($hash)
	{
		$count = preg_match('/^{(?P<algorithm>.+)#(?P<saltlength>[0-9]+)(:(?P<iterations>[0-9]+):(?P<keysize>[0-9]+))?}(?P<data>.+)$/', $hash, $matches);
		if ($count != 1)
		{
			throw new \Exception(sprintf(_('Caught invalid password hash')));
		}
		
		// Check for iterations
		if ((!isset($matches['iterations'])) || (empty($matches['iterations'])))
		{
			$iterations = 1;
		}
		else
		{
			$iterations = $matches['iterations'];
		}
		if ((!isset($matches['keysize'])) || (empty($matches['keysize'])))
		{
			$keysize = 32;
		}
		else
		{
			$keysize = $matches['keysize'];
		}
		$data = base64_decode($matches['data']);
		$salt = substr($data, 0, $matches['saltlength']);
		$hash = substr($data, $matches['saltlength']);
		return array
		(
			'algorithm' => $matches['algorithm'],
			'iterations' => $iterations,
			'length' => $keysize,
			'salt' => $salt,
			'hash' => $hash,
			'salt_length' => $matches['saltlength'],
		);
	}
}
