<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

/*!
 * @brief FF14 Category handler class
 * 
 * This class reads the Category data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Category extends \System\DataObject
{
	// Category definitions
	const PROGRESS = 1;
	const QUALITY = 2;
	const BUFF = 3;
	const RESTORE_CP = 4;
	const RESTORE_DURABILITY = 5;
	const SPECIALIST = 6;
	const OTHER = 7;

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
		switch ($this -> GetID())
		{
		case self::PROGRESS:
			return _('Progress');
			
		case self::QUALITY:
			return _('Quality');
			
		case self::BUFF:
			return _('Buff');
			
		case self::RESTORE_CP:
			return _('Restore CP');
			
		case self::RESTORE_DURABILITY:
			return _('Restore durability');
			
		case self::SPECIALIST:
			return _('Specialist action');
			
		case self::OTHER:
			return _('Other');
			
		default:
			return $this -> GetName();
		}
	}
}
