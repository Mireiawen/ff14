<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load extended MySQLi
require_once(SYSTEM_PATH . '/includes/MySQLie.php');

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

/*!
 * @brief A singleton for database connection class
 *
 * A class that allows us to get single instance of 
 * database connection at any time
 * 
 * $Author: mireiawen $
 * $Id: Database.singleton.php 238 2015-06-02 19:51:57Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Database
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief Creation method
	 * 
	 * Creates an instance of the class itself,
	 * and will set up the MySQLie database connection
	 * on the load. 
	 *
	 * Can be called multiple times and will not
	 * create a new instance on subsequent calls.
	 *
	 * @param string $database 
	 * 	The database name to use
	 * @param string $username, $password 
	 * 	The account to log in with
	 * @param string $hostname 
	 * 	The database host to connect to
	 * @retval object
	 * 	Instance of MySQLie class
	 * @throws Exception on connection errors
	 */
	public static function Create($database, $username = '', $password = '', $hostname = 'localhost')
	{
		// Connect to the database
		if (!isset(self::$instance))
		{
			self::$instance = new MySQLie($database, $username, $password, $hostname);
		}

		// Throw exception if we got error
		if (self::$instance -> connect_errno)
		{
			throw new Exception(sprintf(_('Unable to connect to the database: %s'), self::$instance -> connect_error));
		}
		
		// Return instance
		return self::$instance;
	}
}
