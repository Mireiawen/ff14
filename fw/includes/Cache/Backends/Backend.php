<?php
namespace System\Cache\Backend;

// Load the exceptions
require_once(SYSTEM_PATH . '/includes/Cache/Exceptions.php');

/*!
 * @brief A cache backend interface definition
 *
 * This class defines caching backend interface
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
interface Backend
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
	public function __construct();
	
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
	public function Fetch($key);
	
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
	public function Store($key, $value, $ttl = 0);

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
	public function Flush($key);

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
	public function Keys();
}
