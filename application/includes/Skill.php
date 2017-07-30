<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/DataObject.php');

// Buff icons
define('ICON_BUFF_STEADY_HAND', '011551');
define('ICON_BUFF_STEADY_HAND2', '011852');
define('ICON_BUFF_INNER_QUIET', '016101');
define('ICON_BUFF_GREAT_STRIDES', '016105');
define('ICON_BUFF_INGENUITY', '016104');
define('ICON_BUFF_WASTE_NOT', '011701');
define('ICON_BUFF_INNOVATION', '011652');
define('ICON_BUFF_COMFORT_ZONE', '011801');

// Special skill: Steady Hand
define('STEADY_HAND_ID', 244);
define('STEADY_HAND_2_ID', 281);
define('STEADY_HAND_DURATION', 5);

// Special skill: Inner Quiet
define('INNER_QUIET_ID', 252);
define('INNER_QUIET_MAX_STACK', 11);

// Special skill: Great Strides
define('GREAT_STRIDES_ID', 260);
define('GREAT_STRIDES_DURATION', 3);

// Special skill: Comfort Zone
define('COMFORT_ZONE_ID', 286);
define('COMFORT_ZONE_TICK', 8);
define('COMFORT_ZONE_DURATION', 10);

// Special skill: Ingenuity
define('INGENUITY_ID', 277);
define('INGENUITY_2_ID', 283);
define('INGENUITY_DURATION', 5);

// Special skill: Waste Not
define('WASTE_NOT_ID', 279);
define('WASTE_NOT_2_ID', 285);
define('WASTE_NOT_DURATION', 4);
define('WASTE_NOT_2_DURATION', 8);

// Special skill: Innovation
define('INNOVATION_ID', 284);
define('INNOVATION_DURATION', 3);

// Special skill: Trained Hand
define('TRAINED_HAND_ID', '100161');

// Touch actions
define('BASIC_TOUCH_ID', '100002');
define('STANDARD_TOUCH_ID', '100004');
define('ADVANCED_TOUCH_ID', '100008');
define('HASTY_TOUCH_ID', '100108');
define('HASTY_TOUCH_2_ID', '100195');
define('PRUDENT_TOUCH_ID', '100227');
define('FOCUSED_TOUCH_ID', '100243');
define('PRECISE_TOUCH_ID', '100128');
define('PATIENT_TOUCH_ID', '100219');
define('INNOVATIVE_TOUCH_ID', '100137');

// Byregot's
define('BYREGOTS_BLESSING_ID', '100009');
define('BYREGOTS_BROW_ID', '100120');
define('BYREGOTS_MIRACLE_ID', '100145');

/*!
 * @brief Skill Job handler class
 * 
 * This class reads the Skill data from the database
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Skill extends \System\DataObject
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
