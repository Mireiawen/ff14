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

// Define the IP detection override modes
define('GEOIP_IP_AUTO', 1);
define('GEOIP_IP_v4', 4);
define('GEOIP_IP_v6', 6);

/*!
 * @brief A singleton handler for the GeoIP v2 API
 *
 * A singleton class that keeps single instance of the 
 * GeoIP v2 API databases open and call the correct 
 * database for the asked information
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class GeoIP extends Base
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
	 *
	 * @throws throws MaxMind::Db::Reader::InvalidDatabaseException if the database is corrupt or invalid
	 */
	protected function __construct()
	{
	}
	
	/*!
	 * @brief Get the country information
	 *
	 * Get the country information for the address
	 *
	 * @param string $address
	 * 	IPv4 or IPv6 address as a string to retrieve infor for
	 * 
	 * @retval string
	 * 	The retrieved country name
	 *
	 * @throws Exception on failures
	 */
	public function Country($address)
	{
		if (!extension_loaded('geoip'))
		{
			throw new \Exception(sprintf(_('GeoIP extension not loaded')));
		}
		return geoip_country_name_by_name($address);
	}
	
	/*!
	 * @brief Get the city information
	 *
	 * Get the city information for the address
	 *
	 * @param string $address
	 * 	IPv4 or IPv6 address as a string to retrieve infor for
	 * 
	 * @retval string
	 * 	The retrieved city name
	 *
	 * @throws Exception on failures
	 */
	public function City($address)
	{
		if (!extension_loaded('geoip'))
		{
			throw new \Exception(sprintf(_('GeoIP extension not loaded')));
		}
		$r = geoip_record_by_name($address);
		if (isset($r['city']))
		{
			return $r['city'];
		}
		return FALSE;
	}
}
