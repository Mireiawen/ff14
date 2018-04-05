<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Load World model
require_once(MODEL_PATH . '/World.php');

/*!
 * @brief Datacenter handler class
 * 
 * This class reads the Datacenter data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Datacenter extends \System\DataObject
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
	
	public function GetWorlds()
	{
		return \World::GetByDatacenter($this -> GetID());
	}
}
