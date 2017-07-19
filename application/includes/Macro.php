<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

/*!
 * @brief Skill Macro handler class
 * 
 * This class reads the Macro data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Macro extends \System\DataObject
{
	// Define the default skill and buff wait times
	const DEFAULT_WAIT_SKILL = 3;
	const DEFAULT_WAIT_BUFF = 2;

	protected function __construct()
	{
		parent::__construct();
		$this -> data_is_private = FALSE;
	}
	
	public function __postCreate()
	{
		$this -> CacheUniqueKeys(CACHE_TIMEOUT_SHORT);
	}
}
