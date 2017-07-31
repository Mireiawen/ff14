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
define('ICON_BUFF_INITIAL_PREPARATIONS', '016121');
define('ICON_BUFF_NAME_OF_WIND', '011501');
define('ICON_BUFF_NAME_OF_FIRE', '011554');
define('ICON_BUFF_NAME_OF_ICE', '011601');
define('ICON_BUFF_NAME_OF_EARTH', '011703');
define('ICON_BUFF_NAME_OF_LIGHTNING', '011752');
define('ICON_BUFF_NAME_OF_WATER', '011802');
define('ICON_BUFF_HEART_OF_THE', '016118');
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

// Special Skills: Name of the element
define('NAME_OF_WIND_ID', 4568);
define('NAME_OF_FIRE_ID', 4569);
define('NAME_OF_ICE_ID', 4570);
define('NAME_OF_EARTH_ID', 4571);
define('NAME_OF_LIGHTNING_ID', 4572);
define('NAME_OF_WATER_ID', 4573);
define('NAME_OF_DURATION', 5);

// Special skills: Heart of the specialist
define('HEART_OF_THE_CARPENTER', '100179');
define('HEART_OF_THE_BLACKSMITH', '100180');
define('HEART_OF_THE_ARMORER', '100181');
define('HEART_OF_THE_GOLDSMITH', '100182');
define('HEART_OF_THE_LEATHERWORKER', '100183');
define('HEART_OF_THE_WEAVER', '100184');
define('HEART_OF_THE_ALCHEMIST', '100185');
define('HEART_OF_THE_CULINARIAN', '100186');
define('HEART_OF_THE_DURATION', 7);

// Special Skill: Initial Preparations
define('INITIAL_PREPARATIONS_ID', '100251');

// Specialist action: Reinforce
define('REINFORCE_ID', '100259');

// Specialist action: Refurbish
define('REFURBISH_ID', '100267');
define('REFURBISH_TICK', 65);

// Specialist action: Reflect
define('REFLECT_ID', '100275');
define('REFLECT_TICK', 3);

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

// Special skill: Rumination
define('RUMINATION_ID', 276);

// Special skill: Comfort Zone
define('COMFORT_ZONE_ID', 286);
define('COMFORT_ZONE_TICK', 8);
define('COMFORT_ZONE_DURATION', 10);

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
