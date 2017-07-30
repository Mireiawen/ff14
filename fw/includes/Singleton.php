<?php
namespace System;

/*!
 * @brief A trait for singleton pattern
 *
 * A trait to enable the singleton pattern in a class
 * without rewriting the methods for each class.
 * 
 * Classes can reimplement the methods if they need
 * to pass parameters or check some values.
 * 
 * $Author: mireiawen $
 * $Id: Singleton.php 441 2017-07-11 21:02:54Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
trait Singleton
{
	/*!
	 * @brief Instance of the class
	 *
	 * Private instance of the class itself
	 */
	private static $instance;
	
	/*!
	 * @brief The protected constructor
	 *
	 * Override the constructor visibility to
	 * protected so it is not possible to instantiate
	 * the class outside of itself.
	 *
	 * @return instance of the class itself
	 */
	protected function __construct()
	{
	}
	
	/*!
	 * @brief The instance creation method
	 * 
	 * Creates an instance of the class itself,
	 * can be called multiple times and will not
	 * create a new instance on subsequent calls
	 *
	 * @return instance of the class itself
	 * @throws Exception on errors
	 */
	public static function Create()
	{
		// Create a new instance if it doesn't exist yet
		if (!isset(self::$instance))
		{
			$o = __CLASS__;
			self::$instance = new $o();
		}

		// And return that instance
		return self::$instance;
	}
	
	/*!
	 * @brief The instance getting method
	 *
	 * Returns an instance of the class itself
	 * if it has been created before.
	 *
	 * This allows getting of the existing instance
	 * without needing to pass the required construction
	 * parameters when just looking for existing instance.
	 *
	 * @return instance of the class itself
	 * @throws Exception if instance does not exist
	 */
	public static function Get()
	{
		if (!isset(self::$instance))
		{
			throw new \Exception(sprintf(_('Instance of %s does not exist yet'), get_class()));
		}
		
		return self::$instance;
	}
}
