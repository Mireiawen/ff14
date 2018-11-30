<?php

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

/*!
 * @brief Eorzea data
 * 
 *
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Eorzea extends \System\Base
{
	const Hour = 175.0;
	const Multiplier = 3600.0 / self::Hour;
	
	public static function Time($timestamp = FALSE)
	{
		if ($timestamp === FALSE)
		{
			$timestamp = time();
		}
		
		return $timestamp * self::Multiplier;
	}
}
