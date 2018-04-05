<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Load the Rank information
require_once(MODEL_PATH . '/Rank.php');

// Load the Zone information
require_once(MODEL_PATH . '/Zone.php');

/*!
 * @brief Hunt handler class
 * 
 * This class reads the Hunt data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Hunt extends \System\DataObject
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
	
	public static function GetByZone($value)
	{
		// Get the ID
		if ($value instanceof \Zone)
		{
			$id = $value -> GetID();
		}
		
		else if (is_numeric($value))
		{
			\Zone::CreateByID($value);
			$id = $value;
		}
		
		else
		{
			throw new \Exception(sprintf(_('Invalid Zone value %s'), $value));
		}
		
		return parent::GetAllByAttr('Zone', 'i', $id);
	}
	
	public static function GetByRank($value)
	{
		// Get the ID
		if ($value instanceof \Rank)
		{
			$id = $value -> GetID();
		}
		
		else if (is_numeric($value))
		{
			\Rank::CreateByID($value);
			$id = $value;
		}
		
		else
		{
			$id = \Rank::CreateByName($value) -> GetID();
		}
		
		return parent::GetAllByAttr('Rank', 'i', $id);
	}
	
	public static function GetByLevel($value)
	{
		return parent::GetAllByAttr('Level', 'i', $value);
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
			return parent::SetDatacenter($value);
		}
		throw new \Exception(sprintf(_('Invalid Zone value %s'), $value));
	}

	public function Rank()
	{
		return \Rank::CreateByID($this -> GetRank());
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
}
