<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Load the Hunt information
require_once(MODEL_PATH . '/Hunt.php');

// Load the Region information
require_once(MODEL_PATH . '/Region.php');

// Load the Weather information
require_once(MODEL_PATH . '/Weather.php');
require_once(MODEL_PATH . '/ZoneWeather.php');

/*!
 * @brief Zone handler class
 * 
 * This class reads the Zone data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Zone extends \System\DataObject
{
	// Zone data
	// ID:s are XIVDB IDs: http://xivdb.com/
	
	// La Noscea
	const LimsaLominsa = 27;
	const MiddleLaNoscea = 30;
	const LowerLaNoscea = 31;
	const EasternLaNoscea = 32;
	const WesternLaNoscea = 33;
	const UpperLaNoscea = 34;
	const OuterLaNoscea = 350;
	const Mist = 425;
	
	// Thanalan
	const Uldah = 51;
	const WesternThanalan = 42;
	const CentralThanalan = 43;
	const EasternThanalan = 44;
	const SouthernThanalan = 45;
	const NorthernThanalan = 46;
	const TheGoblet = 427;
	
	// The Black Shroud
	const Gridania = 39;
	const CentralShroud = 54;
	const EastShroud = 55;
	const SouthShroud = 56;
	const NorthShroud = 57;
	const LavenderBeds = 426;

	// Coerthas
	const Ishgard = 62;
	const CoerthasCentralHighlands = 63;
	const CoerthasWesternHighlands = 2200;
	
	// Mor Dhona
	const MorDhona = 67;

	// Abalathia's Spine
	const TheSeaOfClouds = 2100;
	const AzysLla = 2101;
	
	// Dravania
	const Idyllshire = 2082;
	const TheDravanianForelands = 2000;
	const TheDravanianHinterlands = 2001;
	const TheChurningMists = 2002;
	
	// Gyr Abania
	const RhalgrsReach = 2403;
	const TheFringes = 2406;
	const ThePeaks = 2407;
	const TheLochs = 2408;
	
	// Othard
	const Kugane = 2404;
	const TheRubySea = 2409;
	const Yanxia = 2410;
	const TheAzimSteppe = 2411;
	const Shirogane = 2412;
	
	protected function __construct()
	{
		parent::__construct();
		$this -> data_is_private = FALSE;
	}
	
	public function __postCreate()
	{
		$this -> CacheUniqueKeys(CACHE_TIMEOUT_PERSISTENT);
	}
	
	public static function GetByRegion($value)
	{
		// Get the ID
		if ($value instanceof \Region)
		{
			$id = $value -> GetID();
		}
		
		else if (is_numeric($value))
		{
			\Region::CreateByID($value);
			$id = $value;
		}
		
		else
		{
			throw new \Exception(sprintf(_('Invalid Region value %s'), $value));
		}
		
		return parent::GetAllByAttr('Region', 'i', $id);
	}
	
	public function GetHunts()
	{
		return \Hunt::GetByZone($this -> GetID());
	}
	
	public function SetRegion($value)
	{
		if ($value instanceof \Region)
		{
			return parent::SetRegion($value -> GetID());
		}
		
		if (is_numeric($value))
		{
			\Region::CreateByID($value);
			return parent::SetRegion($value);
		}
		throw new \Exception(sprintf(_('Invalid Region value %s'), $value));
	}
	
	public function GetTranslatedName()
	{
		$lang = strtolower(substr(\System\Translation::Get() -> GetLang(), 0, 2));
		switch ($lang)
		{
		case 'en':
			return $this -> GetName_EN();
		
		case 'ja':
			return $this -> GetName_JP();
		
		case 'de':
			return $this -> GetName_DE();
		
		case 'fr':
			return $this -> GetName_FR();
		}
		
		return $this -> GetName_EN();
	}
	
	public function GetWeather($time = FALSE)
	{
		if ($time === FALSE)
		{
			$time = \Weather::GetCurrentWeatherStart();
		}
		
		$chance = \Weather::GetChance($time);
		return \ZoneWeather::GetWeatherByZoneChance($this, $chance);
	}
}
