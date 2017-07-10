<?php
namespace System\Cache\Backend;

/*!
 * @brief A session cache backend implementation
 *
 * This implements caching of data into current
 * session. It is not very effective method, 
 * but may be a workaround with slow SQL service
 * and memory based sessions.
 *
 * Session class is required.
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Session implements Backend
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
		if (!class_exists('\\Session'))
		{
			throw new \Exception(_('Session support is required!'));
		}
		
		// Make sure the session cache array exists
		if (!isset($_SESSION['cache']))
		{
			$_SESSION['cache'] = array();
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
		if (!isset($_SESSION['cache'][$key]))
		{
			throw new \System\Cache\NotFound($key);
		}
		
		// Check the key hasn't expired
		if ((!$_SESSION['cache'][$key]['expire']) || ($_SESSION['cache'][$key]['expire'] > time()))
		{
			return $_SESSION['cache'][$key]['value'];
		}
		
		// Timed out, flush it
		$this -> Flush($key);
		throw new \System\Cache\NotFound($key);
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
		if ($ttl)
		{
			$expire = time() + $ttl;
		}
		else
		{
			$expire = 0;
		}
		
		$_SESSION['cache'][$key] = array(
			'value' => $value,
			'expire' => $expire,
		);
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
		if (!isset($_SESSION['cache'][$key]))
		{
			throw new \System\Cache\NotFound($key);
		}
		
		unset($_SESSION['cache'][$key]);
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
		return array_keys($_SESSION['cache']);
	}
}
