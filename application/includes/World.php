<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Load the Datacenter information
require_once(MODEL_PATH . '/Datacenter.php');

/*!
 * @brief World handler class
 * 
 * This class reads the World data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class World extends \System\DataObject
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
	
	public static function GetByDatacenter($value)
	{
		// Get the ID
		if ($value instanceof \Datacenter)
		{
			$id = $value -> GetID();
		}
		
		else if (is_numeric($value))
		{
			\Datacenter::CreateByID($value);
			$id = $value;
		}
		
		else
		{
			$dc = \Datacenter::CreateByName($value);
			$id = $dc -> GetID();
		}
		
		return parent::GetAllByAttr('Datacenter', 'i', $id);
	}
	
	public function SetDatacenter($value)
	{
		if ($value instanceof \Datacenter)
		{
			return parent::SetDatacenter($value -> GetID());
		}
		
		if (is_numeric($value))
		{
			\Datacenter::CreateByID($value);
			return parent::SetDatacenter($value);
		}
		$dc = \Datacenter::CreateByName($value);
		return parent::SetDatacenter($dc -> GetID());
	}
}
