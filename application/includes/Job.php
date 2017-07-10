<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

/*!
 * @brief FF14 Job handler class
 * 
 * This class reads the Job data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Job extends \DataObject
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
}
