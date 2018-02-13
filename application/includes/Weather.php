<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

/*!
 * @brief Weather handler class
 * 
 * This class reads the Weather data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Weather extends \System\DataObject
{
	// XIVDB IDs for Weathers
	const Blizzards = 16;
	const ClearSkies = 1;
	const Clouds = 3;
	const DustStorms = 11;
	const FairSkies = 2;
	const Fog = 4;
	const Gales = 6;
	const Gloom = 17;
	const HeatWaves = 14;
	const Rain = 7;
	const Showers = 8;
	const Snow = 15;
	const Thunder = 9;
	const Thunderstorms = 10;
	const UmbralStatic = 50;
	const UmbralWind = 49;
	const Wind = 5;
	
	// Weather duration calculation constants
	const Hour = 175.0;
	const Multiplier = 3600.0 / self::Hour;
	const WeatherDuration = 8.0 * self::Hour;
	
	protected function __construct()
	{
		parent::__construct();
		$this -> data_is_private = FALSE;
	}
	
	public function __postCreate()
	{
		$this -> CacheUniqueKeys(CACHE_TIMEOUT_PERSISTENT);
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

	public static function GetConstants()
	{
		$arr = parent::GetConstants();
		unset($arr['Hour']);
		unset($arr['Multiplier']);
		unset($arr['WeatherDuration']);
		return $arr;
	}
	
	public static function GetChance($timestamp = FALSE)
	{
		if ($timestamp === FALSE)
		{
			$timestamp = time();
		}
		
		// Get Eorzea hour for weather start
		$bell = $timestamp / self::Hour;
		
		// Do the magic 'cause for calculations 16:00 is 0, 00:00 is 8 and 08:00 is 16
		$increment = ($bell + 8 - ($bell % 8)) % 24;
		
		// Take Eorzea days since unix epoch
		$days = (int)($timestamp / 4200);
		
		// 0x64 = 100
		$base = $days * 100 + $increment;
		
		// 0xB = 11
		$step1 = self::urs(($base << 11) ^ $base, 0);
		$step2 = self::urs(self::urs($step1,  8) ^ $step1, 0);
		
		// 0x64 = 100
		return $step2 % 100;
	}
	
	public static function GetCurrentWeatherStart()
	{
		$timestamp = time() * self::Multiplier;
		return ($timestamp - $timestamp % (3600*8)) / self::Multiplier;
	}
	
	public static function GetCurrentWeatherEnd()
	{
		return self::GetCurrentWeatherStart() + self::WeatherDuration - 1;
	}
	
	public static function GetWeatherDuration()
	{
		return self::WeatherDuration;
	}
	
	/**
	 * Unsigned Right Shift
	 *
	 * @source: https://stackoverflow.com/a/2643135
	 */
	private static function urs($a, $b)
	{
		if ($b >= 32 || $b < -32)
		{
			$m = (int)($b/32);
			$b = $b-($m*32);
		}
		
		if ($b < 0)
		{
			$b = 32 + $b;
		}
		
		if ($b == 0)
		{
			return (($a>>1)&0x7fffffff)*2+(($a>>$b)&1);
		}
		
		if ($a < 0)
		{
			$a = ($a >> 1);
			$a &= 2147483647;
			$a |= 0x40000000;
			$a = ($a >> ($b - 1));
		}
		
		else
		{
			$a = ($a >> $b);
		}
		
		return $a;
	}
}
