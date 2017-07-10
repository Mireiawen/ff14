<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

// Load the Smarty
require_once(SYSTEM_PATH . '/libraries/Smarty/Smarty.class.php');

/*!
 * \brief Singleton handler for Smarty
 *
 * A class that allows us to get single instance of Smarty
 * 
 * $Author: mireiawen $
 * $Id: SmartyInstance.php 256 2015-06-02 21:51:17Z mireiawen $
 */
final class SmartyInstance extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 * 
	 * In addition to building the Base class,
	 * this constructor creates the Smarty
	 * instance.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		$this -> __set('Smarty', new Smarty());
	}
	
	/*!
	 * @brief Get the Smarty instance
	 *
	 * Get the instance of the smarty class itself
	 *
	 * @retval object 
	 * 	The Smarty object
	 */
	public function GetSmarty()
	{
		return $this -> __get('Smarty');
	}
	
	/*!
	 * @brief Allow calling of Smarty static methods as if they were our own
	 *
	 * Implement the ability to call the static methods from our Smarty 
	 * object as if they were our own methods. This is done by
	 * passing the given method and arguments in our custom
	 * __callStatic method to the Smarty object itself.
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
		return call_user_func_array(array($this -> Smarty, $method), $args);
	}
	
	/*!
	 * @brief Allow calling of Smarty static methods as if they were our own
	 *
	 * Implement the ability to call the static methods from our Smarty 
	 * object as if they were our own methods. This is done by
	 * passing the given method and arguments in our custom
	 * __callStatic method to the Smarty object itself.
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
		return call_user_func_array(array('Smarty', $method), $args);
	}
	
	/*!
	 * @brief Allow getting of Smarty properties as if they were our own
	 *
	 * Implement the ability to get the properties of our Smarty 
	 * object as if they were our own properties. This is done by
	 * passing the given key to the Smarty object __get in our own
	 * __get, as long as the $key requested is not Smarty itself.
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
		if (strcmp($key, 'Smarty'))
		{
			return $this -> Smarty -> __get($key);
		}
		
		return $this -> vars['Smarty'];
	}
	
	/*!
	 * @brief Allow setting of Smarty properties as if they were our own
	 *
	 * Implement the ability to set the properties of our Smarty 
	 * object as if they were our own properties. This is done by
	 * passing the given key and value to the Smarty object __set in 
	 * our own __set, as long as the $key requested is not Smarty itself.
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
		if (strcmp($key, 'Smarty'))
		{
			return $this -> Smarty -> __set($key, $val);
		}
		
		return $this -> vars['Smarty'] = $val;
	}
	
	/*!
	 * @brief Allow checking of Smarty properties as if they were our own
	 *
	 * Implement the ability to checking if the properties of our Smarty 
	 * object are set as if they were our own properties. This is done by
	 * passing the given key to the Smarty object __isset in 
	 * our own __isset, as long as the $key requested is not Smarty itself.
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
		if (strcmp($key, 'Smarty'))
		{
			return $this -> Smarty -> __isset($key);
		}
		
		return TRUE;
	}
}
