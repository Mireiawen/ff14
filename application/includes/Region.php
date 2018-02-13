<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Load Zone model
require_once(MODEL_PATH . '/Zone.php');

/*!
 * @brief Region handler class
 * 
 * This class reads the Region data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Region extends \System\DataObject
{
	// Region data
	// ID:s are XIVDB IDs: http://xivdb.com/
	const LaNoscea = 502;
	const Thanalan = 505;
	const TheBlackShroud = 507;
	const Coerthas = 508;
	const MorDhona = 509;
	const AbalathiasSpine = 510;
	const Dravania = 511;
	const GyrAbania = 514;
	const Othard = 515;
	
	protected function __construct()
	{
		parent::__construct();
		$this -> data_is_private = FALSE;
	}
	
	public function __postCreate()
	{
		$this -> CacheUniqueKeys(CACHE_TIMEOUT_PERSISTENT);
	}
	
	public function GetZones()
	{
		return \Zone::GetByRegion($this -> GetID());
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
