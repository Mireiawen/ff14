<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// User object itself
require_once(SYSTEM_PATH . '/includes/User.php');

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

/*!
 * \brief A singleton keeping current user
 *
 * A singleton class that keeps the object for currently logged in user
 *
 * $Author: mireiawen $
 * $Id: LoginUser.php 448 2017-07-11 22:19:58Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class LoginUser extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 * 
	 * In addition to building the Base class,
	 * this constructor sets up some of the 
	 * default values for the class.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		// Try to get the current user from the session
		if ((class_exists('\\System\\Session')) && (array_key_exists('uid', $_SESSION)))
		{
			try
			{
				// Try to create by ID
				$this -> __set('User', User::CreateByID($_SESSION['uid']));
			}
			catch (Exception $e)
			{
				// ID not found, try to create unknown
				$this -> __set('User', User::CreateByID(USER_ID_UNKNOWN));
			}
		}
		else
		{
			// No UID in session, create unknown
			$this -> __set('User', User::CreateByID(USER_ID_UNKNOWN));
		}
		
		// Check that we were able to create a user
		if ($this -> __get('User') === NULL)
		{
			throw new Exception(_('Unable to create User object'));
		}
		
		// Check that the user is active
		if (!$this -> __get('User') -> GetActive())
		{
			$this -> Logout();
			$this -> __set('User', User::CreateByID(USER_ID_UNKNOWN));
		}
	}
	
	/*!
	 * @brief Read the login data from the POST
	 *
	 * Try to read the login information in the POST
	 * array, and if it exists, try to log the user in
	 *
	 * Triggers error of E_USER_WARNING level on failed login
	 *
	 * @retval bool
	 * 	TRUE on successful login,
	 * 	FALSE otherwise
	 */
	public function ReadLogin()
	{
		// Check login data
		if ((!is_array($_POST)) || 
			(!array_key_exists('login', $_POST)) ||
			(!array_key_exists('username', $_POST)) || 
			(!array_key_exists('password', $_POST)))
		{
			// No login data
			return FALSE;
		}
		
		// Sanitize username and password
		$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_EMAIL);
		$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
		
		// Try to log in
		try
		{
			$r = $this -> Login($username, $password);
		}
		catch (Exception $e)
		{
			trigger_error($e -> getMessage(), E_USER_WARNING);
			return FALSE;
		}
		return $r;
	}
	
	/*!
	 * @brief Try to do a login
	 *
	 * Try to log the user in with username and password
	 *
	 * This is mostly used with AJAX logins where the data 
	 * might be POST'ed with different format and names.
	 *
	 * @param string $username
	 * 	The username to try to log in with
	 * @param string $password
	 * 	The password to try to log in with
	 * @retval bool
	 * 	TRUE on success
	 * @throws Exception on failed login
	 */
	public function Login($username, $password)
	{
		// Try to create user object for sanitized username 
		try
		{
			$u = User::CreateByUsername(filter_var($username, FILTER_SANITIZE_EMAIL));
		}
		catch (Exception $e)
		{
			// No such user
			throw new Exception(_('Invalid username or password'));
		}
		
		// Make sure the user is active
		if (!$u -> GetActive())
		{
			// User is not active
			throw new Exception(_('Invalid username or password'));
		}
		
		// Try to validate the password
		try
		{
			if (!$u -> VerifyPassword(filter_var($password, FILTER_SANITIZE_STRING)))
			{
				// Invalid password
				throw new Exception(_('Invalid username or password'));
			}
		}
		
		// This can catch both invalid password and invalid user ID from user object
		catch (Exception $e)
		{
			if ((defined('DEBUG')) && (DEBUG))
			{
				throw new Exception(sprintf(_('Invalid username or password: %s'), $e -> getMessage()));
			}
			else
			{
				throw new Exception(_('Invalid username or password'));
			}
		}
		
		// Succesful login; regenerate session ID to prevent hijack
		if (class_exists('\\System\\Session'))
		{
			Session::Get() -> Regenerate();
		}
		
		// Save the variables
		$this -> __set('User', $u);
		$_SESSION['username'] = $this -> GetUsername();
		$_SESSION['uid'] = $this -> GetID();
		
		if ((defined('DEBUG')) && (DEBUG))
		{
			Log::Get() -> Add('Logged in');
		}
		return TRUE;
	}
	
	/*!
	 * @brief Check if a valid user is logged in
	 *
	 * Check if a valid user is logged in. Valid user is
	 * any active user with user ID higher than the 
	 * specified minimum ID.
	 *
	 * @retval bool
	 * 	TRUE if user is logged in,
	 * 	FALSE otherwise
	 */
	public function IsLoggedIn()
	{
		return ($this -> User -> GetID() >= USER_ID_MIN);
	}
	
	/*!
	 * @brief Log the user out
	 *
	 * End the current user session to log them out.
	 */
	public function Logout()
	{
		if (class_exists('\\System\\Session'))
		{
			Session::Get() -> Regenerate(TRUE);
		}
		if ((defined('DEBUG')) && (DEBUG))
		{
			Log::Get() -> Add('Logged out');
		}
	}
	
	/*!
	 * @brief Allow calling of User methods as if they were our own
	 *
	 * Implement the ability to call the methods from our User 
	 * object as if they were our own methods. This is done by
	 * passing the given method and arguments in our custom
	 * __call method to the User object itself.
	 *
	 * @param string $method
	 * 	Method name that was called
	 * @param array $args
	 * 	Array of arguments that were passed
	 * @retval mixed
	 * 	The return value of the actual method
	 * @throws The method called may or may not throw exceptions
	 */
	public function __call($method, $args)
	{
		return call_user_func_array(array($this -> User, $method), $args);
	}
	
	/*!
	 * @brief Allow calling of User static methods as if they were our own
	 *
	 * Implement the ability to call the static methods from our User 
	 * object as if they were our own methods. This is done by
	 * passing the given method and arguments in our custom
	 * __callStatic method to the User object itself.
	 *
	 * @param string $method
	 * 	Method name that was called
	 * @param array $args
	 * 	Array of arguments that were passed
	 * @retval mixed
	 * 	The return value of the actual method
	 * @throws The method called may or may not throw exceptions
	 */
	public static function __callStatic($method, $args)
	{
		return call_user_func_array(array('User', $method), $args);
	}
	
	/*!
	 * @brief Allow getting of User properties as if they were our own
	 *
	 * Implement the ability to get the properties of our User 
	 * object as if they were our own properties. This is done by
	 * passing the given key to the User object __get in our own
	 * __get, as long as the $key requested is not User itself.
	 *
	 * @param string $key
	 * 	The key to get the value from
	 * 	Method name that was called
	 * @retval mixed
	 * 	The value of the actual key
	 * @throws The method called may or may not throw exceptions
	 */
	public function & __get($key)
	{
		if (strcmp($key, 'User'))
		{
			return $this -> User -> __get($key);
		}
		
		return $this -> vars['User'];
	}
	
	/*!
	 * @brief Allow setting of User properties as if they were our own
	 *
	 * Implement the ability to set the properties of our User 
	 * object as if they were our own properties. This is done by
	 * passing the given key and value to the User object __set in 
	 * our own __set, as long as the $key requested is not User itself.
	 *
	 * @param string $key
	 * 	The key to get the value from
	 * @param mixed $val
	 * 	The value to set the $key to
	 * @retval mixed
	 * 	The value of the actual key
	 * @throws The method called may or may not throw exceptions
	 */
	public function __set($key, $val)
	{
		if (strcmp($key, 'User'))
		{
			return $this -> User -> __set($key, $val);
		}
		
		return $this -> vars['User'] = $val;
	}
	
	/*!
	 * @brief Allow checking of User properties as if they were our own
	 *
	 * Implement the ability to checking if the properties of our User 
	 * object are set as if they were our own properties. This is done by
	 * passing the given key to the User object __isset in 
	 * our own __isset, as long as the $key requested is not User itself.
	 *
	 * @param string $key
	 * 	The key check get the value from
	 * @retval bool
	 * 	TRUE if the value is set,
	 * 	FALSE if not
	 * @throws The method called may or may not throw exceptions
	 */
	public function __isset($key)
	{
		if (strcmp($key, 'User'))
		{
			return $this -> User -> __isset($key);
		}
		
		return TRUE;
	}
}
