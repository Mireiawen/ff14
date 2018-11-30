<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Load the Weather information
require_once(MODEL_PATH . '/Weather.php');

// Load the Zone information
require_once(MODEL_PATH . '/Zone.php');

/*!
 * @brief ZoneWeather handler class
 * 
 * This class reads the Zone Weather data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class ZoneWeather extends \System\DataObject
{
	protected function __construct()
	{
		parent::__construct();
		$this -> data_is_private = FALSE;
	}
	
	public function __postCreate()
	{
		$this -> CacheUniqueKeys(CACHE_TIMEOUT_PERSISTENT);
	}
	
	public function SetWeather($value)
	{
		if ($value instanceof \Weather)
		{
			return parent::SetWeather($value -> GetID());
		}
		
		if (is_numeric($value))
		{
			\Weather::CreateByID($value);
			return parent::SetWeather($value);
		}
		throw new \Exception(sprintf(_('Invalid Weather value %s'), $value));
	}
	
	public function SetZone($value)
	{
		if ($value instanceof \Zone)
		{
			return parent::SetZone($value -> GetID());
		}
		
		if (is_numeric($value))
		{
			\Zone::CreateByID($value);
			return parent::SetZone($value);
		}
		throw new \Exception(sprintf(_('Invalid Zone value %s'), $value));
	}
	
	public static function GetWeatherByZoneChance($zone, $chance, $time)
	{
		if (is_numeric($zone))
		{
			$zone = \Zone::CreateByID($zone);
		}
		
		if (! $zone instanceof \Zone)
		{
			throw new \Exception(sprintf(_('Invalid Zone value %s'), $value));
		}
		
		// Check for database
		$db = \System\Database::Get();
		if ($db === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
		}
		
		// Prepare the query
		$sql = sprintf('SELECT %s FROM %s WHERE %s = ? AND %s >= ? AND %s <= ?', 
			$db -> escape_identifier('Weather'),
			$db -> escape_identifier(get_called_class()),
			$db -> escape_identifier('Zone'), 
			$db -> escape_identifier('Max'), 
			$db -> escape_identifier('Min'));
		$stmt = $db -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Set up parameters
		$id = $zone -> GetID();
		if (!$stmt -> bind_param('iii', $id, $chance, $chance))
		{
			throw new \Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Convert the rows into objects
		$result = $db -> fetch_first($stmt);
		if (count($result) !== 1)
		{
			throw new Exception(sprintf(_('Unable to find weather for zone %d with chance %d'), $zone -> GetID(), $chance));
		}
		
		$w = \Weather::CreateByID($result['Weather']);
		$w -> SetStartTime($time);
		return $w;
	}
}
