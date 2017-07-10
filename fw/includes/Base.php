<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load the Smarty template helper trait
require_once(SYSTEM_PATH . '/includes/SmartyTemplates.php');

/*!
 * @brief A common base class
 * 
 * A base class with the common methods for most of
 * the classes used by the framework and its pages
 *
 * $Author: mireiawen $
 * $Id: Base.php 347 2015-07-14 14:48:14Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
abstract class Base
{
	/*!
	 * @brief Array of the actual variables
	 *
	 * Array holding the actual variables of the class. This makes it 
	 * possible to transfer the data with the cache backend easily.
	 */
	protected $vars;
	
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
		return array_filter($this -> vars, function($k) {if ((substr($k, 0, 1) === '_') || ($k === 'Smarty')) { return FALSE;} return TRUE; }, ARRAY_FILTER_USE_KEY);
	}
	
	
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
	 * @param bool $smarty 
	 * 	When set to TRUE, asks the class to load the Smarty if it is available
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct($smarty = FALSE)
	{
		// Create the vars array
		$this -> vars = array();
		
		// Get Smarty
		if (($smarty) && (class_exists('SmartyInstance')))
		{
			$this -> Smarty = SmartyInstance::Get();
		}
	}
	
	/*!
	 * @brief The data get method
	 * 
	 * Overrides the default __get method to provide the 
	 * data from our array instead. This tries to read the 
	 * $key from $vars and returns the $value if possible.
	 *
	 * @param string $key
	 * 	The $key to read from
	 * @retval mixed
	 * 	The value of the $key
	 * @throws Exception if the $key was not found
	 */
	public function & __get($key)
	{
		if (!array_key_exists($key, $this -> vars))
		{
			throw new Exception(sprintf(_('Missing key "%s" in %s'), $key, get_class($this)));
		}
		
		return $this -> vars[$key];
	}
	
	/*!
	 * @brief The data set method
	 * 
	 * Overrides the default __set method to save the 
	 * data to our array instead. This saves the data
	 * in $value to $vars at position $key
	 *
	 * @param string $key
	 * 	The $key to write to
	 * @param mixed $value
	 * 	The data to write
	 */
	public function __set($key, $value)
	{
		$this -> vars[$key] = $value;
	}
	
	/*!
	 * @brief Check if the $key is set
	 *
	 * Overrides the default __isset method to check
	 * if the key is set in our array instead.
	 *
	 * @param string $key
	 * 	The $key to check
	 * @retval bool
	 * 	TRUE if the $key is set in the array, FALSE if not
	 */
	public function __isset($key)
	{
		return array_key_exists($key, $this -> vars);
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
	 * If the method isn't a Get or Set, then this will
	 * throw Exception since there is no parent class to
	 * handle them.
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

		// Get the variable and validate we have something
		$key = substr($method, 3);
		if (empty($key))
		{
			throw new Exception(sprintf(_('%s method call is missing the variable name'), ucfirst($type)));
		}
		
		switch ($type)
		{
		case 'get':
			return $this -> __get($key);
		
		case 'set':
			// Set needs more work; we'll do it after switch
			break;
		
		default:
			throw new Exception(sprintf(_('Invalid call to unknown method "%s"'), $method));
		}
		
		// Validate the arguments
		if (count($args) != 1)
		{
			throw new Exception(sprintf(_('Invalid amount of arguments for method "%s"'), $method));
		}
		
		// Make sure we don't try to change the ID
		if ($key === 'ID')
		{
			throw new Exception(_('Changing of ID is not allowed'));
		}
		
		return $this -> __set($key, $args[0]);
	}
}
