<?php
namespace System\Cache\Backend;

// Load the backend interface
require_once(SYSTEM_PATH . '/includes/Cache/Backends/Backend.php');

// Load the exceptions
require_once(SYSTEM_PATH . '/includes/Cache/Exceptions.php');

/*!
 * @brief A Redis cache backend implementation
 *
 * This class implements caching to Redis backend. 
 * Redis extension required
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Redis implements Backend
{
	/*!
	 * @brief Constructor for the class
	 *
	 * Class could, for example, check required 
	 * extensions here and throw exception 
	 * early if something is missing.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on error
	 */
	public function __construct()
	{
		if (!extension_loaded('redis'))
		{
			throw new \Exception(_('Redis extension is required!'));
		}
		
		// Make sure we have a cache hostname; this could be a hostname or unix socket
		if (!defined('CACHE_HOSTNAME'))
		{
			define('CACHE_HOSTNAME', 'localhost');
		}
		
		try
		{
			// Initialize the class
			$this -> redis = new \Redis();
			
			if (defined('CACHE_PORT'))
			{
				// Connect with the port
				if ($this -> redis -> pconnect(CACHE_HOSTNAME, CACHE_PORT) === FALSE)
				{
					throw new \Exception(sprintf(_('Unable to connect to "%s:%d"'), CACHE_HOSTNAME, CACHE_PORT));
				}
			}
			
			else
			{
				// Connect to the default port, or possibly socket
				if ($this -> redis -> pconnect(CACHE_HOSTNAME) === FALSE)
				{
					throw new \Exception(sprintf(_('Unable to connect to "%s"'), CACHE_HOSTNAME));
				}
			}
			
			if (defined('CACHE_AUTHENTICATION_PASSWORD'))
			{
				// Authenticate us to the Redis
				if ($this -> redis -> auth(CACHE_AUTHENTICATION_PASSWORD) === FALSE)
				{
					throw new \Exception(sprintf(_('Unable to connect to "%s": %s'), CACHE_HOSTNAME, _('Authentication failure')));
				}
			}
			
			if (defined('CACHE_DATABASE'))
			{
				// Select the cache database
				if ($this -> redis -> select(CACHE_DATABASE) === FALSE)
				{
					throw new \Exception(sprintf(_('Unable to connect to "%s": %s'), CACHE_HOSTNAME, _('Unable to select the cache database')));
				}
			}
			
			// Set up the serialization mode
			if ($this -> redis -> setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP) === FALSE)
			{
				throw new \Exception(sprintf(_('Unable to connect to "%s": %s'), CACHE_HOSTNAME, _('Unable to set the serialization mode')));
			}
			
			// Make sure the connection is up, should throw RedisException on failure
			$this -> redis -> ping();
		}
		
		catch (\RedisException $e)
		{
			throw new \Exception(sprintf(_('Caught Redis exception: %s'), $e -> getMessage()), $e -> getCode(), $e);
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
		try
		{
			// Check if the key exists
			if ($this -> redis -> exists($key) === FALSE)
			{
				throw new \System\Cache\NotFound($key);
			}
			
			return $this -> redis -> get($key);
		}
		
		catch (\RedisException $e)
		{
			throw new \Exception(sprintf(_('Caught Redis exception: %s'), $e -> getMessage()), $e -> getCode(), $e);
		}
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
		try
		{
			if ($ttl)
			{
				$this -> redis -> setex($key, $ttl, $value);
			}
			else
			{
				$this -> redis -> set($key, $value);
			}
		}
		
		catch (\RedisException $e)
		{
			throw new \Exception(sprintf(_('Caught Redis exception: %s'), $e -> getMessage()), $e -> getCode(), $e);
		}
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
		try
		{
			$this -> redis -> delete($key);
		}
		
		catch (\RedisException $e)
		{
			throw new \Exception(sprintf(_('Caught Redis exception: %s'), $e -> getMessage()), $e -> getCode(), $e);
		}
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
		try
		{
			return $this -> redis -> keys('*');
		}
		
		catch (\RedisException $e)
		{
			throw new \Exception(sprintf(_('Caught Redis exception: %s'), $e -> getMessage()), $e -> getCode(), $e);
		}
	}
}
