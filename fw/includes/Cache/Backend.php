<?php
namespace System\Cache;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

// Load the backend interface
require_once(SYSTEM_PATH . '/includes/Cache/Backends/Backend.php');

// Load the exceptions
require_once(SYSTEM_PATH . '/includes/Cache/Exceptions.php');

/*!
 * @brief Cache backend manager class
 *
 * Class to load the actual backend class that handles the
 * the actual caching of the data
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use \System\Singleton;
	
	/*!
	 * @brief The protected constructor
	 * 
	 * Try loading the caching backend
	 * and set up the defaults
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		$this -> backend = FALSE;
		
		// Backends we try to load
		if (!defined('CACHE_BACKENDS'))
		{
			define('CACHE_BACKENDS', 'Redis;Session');
		}
		
		// Try loading the backends
		$backends = explode(';', CACHE_BACKENDS);
		foreach ($backends as $backend)
		{
			try
			{
				// Create file path and class name
				$fn = realpath(SYSTEM_PATH . '/includes/Cache/Backends/' . $backend . '.php');
				$class = '\\System\\Cache\\Backend\\' . $backend;
				
				// Check for the file
				if ((!is_file($fn)) || (!is_readable($fn)))
				{
					throw new \Exception(_('No backend implementation file'));
				}
				
				// Load the handler
				require_once($fn);
				
				// Check the class exists
				if (!class_exists($class, FALSE))
				{
					throw new \Exception(_('No backend implementation class'));
				}
				
				// Make sure the class implements caching backend
				$refl = new \ReflectionClass($class);
				if (!$refl -> implementsInterface('\\System\\Cache\\Backend\\Backend'))
				{
					throw new \Exception(_('Backend does not implement caching interface'));
				}
				
				// Load the backend
				$this -> backend = new $class();
				break;
			}
			
			catch (\Exception $e)
			{
				if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_CACHE')) && (DEBUG_CACHE))
				{
					\Error::Message(sprintf(_('Unable to load cache backend %s: %s'), $backend, $e -> getMessage()), ERROR_LEVEL_INFO);
				}
			}
		}
	}
	
	/*!
	 * @brief Fetch the key from cache
	 *
	 * Fetch the given key value from the 
	 * cache backend
	 *
	 * @param string $key
	 * 	The key to fetch the data for
	 *
	 * @retval mixed
	 * 	The data for the given key
	 *
	 * @throws System\Cache\NotFound if the key was not found
	 * @throws Exception on other errors
	 */
	public function Fetch($key)
	{
		if ($this -> backend === FALSE)
		{
			throw new \System\Cache\NoBackend();
		}
		
		return $this -> backend -> Fetch($key);
	}
	
	/*!
	 * @brief Store the key to the cache
	 *
	 * Store the given key value to the 
	 * cache backend
	 *
	 * @param string $key
	 * 	The key to store the data to
	 * @param mixed $value
	 * 	The key value
	 * @param int $ttl
	 * 	The time the key should live in the cache,
	 * 	0 to not timeout
	 * 	@note may not be supported by all cache backends
	 *
	 * @throws Exception on errors
	 */
	public function Store($key, $value, $ttl = 0)
	{
		if ($this -> backend === FALSE)
		{
			throw new \System\Cache\NoBackend();
		}
		
		$this -> backend -> Store($key, $value, $ttl);
	}
	
	/*!
	 * @brief Flush the key value out from the cache
	 *
	 * Flush (remove) the key value from the cache
	 * backend
	 *
	 * @param string $key
	 * 	The key to flush
	 *
	 * @throws System\Cache\NotFound if the key was not found
	 * @throws Exception on other errors
	 */
	public function Flush($key)
	{
		if ($this -> backend === FALSE)
		{
			throw new \System\Cache\NoBackend();
		}
		
		$this -> backend -> Flush($key);
	}
	
	/*!
	 * @brief List the keys in the cache
	 *
	 * List all the keys in the cache
	 *
	 * @retval array
	 * 	An array of key name strings
	 *
	 * @throws Exception on errors
	 */
	public function Keys()
	{
		if ($this -> backend === FALSE)
		{
			throw new \System\Cache\NoBackend();
		}
		
		return $this -> backend -> Keys();
	}
}
