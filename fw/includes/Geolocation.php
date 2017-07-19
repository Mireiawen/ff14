<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// User object itself
require_once(SYSTEM_PATH . '/includes/User.php');

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

// Load the GeoIP handler
require_once(SYSTEM_PATH . '/includes/GeoIP.php');

/*!
 * @brief A singleton keeping current location
 *
 * A singleton class that keeps track of the current location of the
 * remote user.
 *
 * This can handle both the HTML5 Geolocation via JavaScript as well as
 * the GeoIP via user remote IP address.
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Geolocation extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 * 
	 * In addition to building the Base class,
	 * this constructor sets up some of the 
	 * default values for the class.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		// Set up the default
		$this -> location = FALSE;
		
		// Try to get set location from session
		if ((class_exists('\\System\\Session')) && (isset($_SESSION['Geolocation'])))
		{
			$this -> location = unserialize($_SESSION['Geolocation']);
		}
	}
	
	/*!
	 * @brief The destructor
	 * 
	 * Save the current location to the session
	 */
	public function __destruct()
	{
		$this -> SaveToSession();
	}
	
	/*!
	 * @brief Set the current location
	 *
	 * This method should be called via JavaScript to set
	 * the current location from the browser
	 *
	 * @param string $location
	 * 	The location data, could be serialized array
	 */
	public function SetLocation($location)
	{
		$this -> location = $location;
		$this -> SaveToSession();
	}
	
	/*!
	 * @brief Remove current location
	 *
	 * Remove the saved location data and fall back to GeoIP
	 */
	public function RemoveLocation()
	{
		$this -> location = FALSE;
		if (class_exists('\\System\\Session'))
		{
			unset($_SESSION['Geolocation']);
		}
	}
	
	/*!
	 * @brief Get the current location
	 *
	 * Get the current location string, will return GeoIP
	 * locality if location is not set
	 *
	 * @param string $address
	 * 	IP or hostname to get the location for, will 
	 * 	default to REMOTE_ADDR
	 * @retval string
	 * 	The location information
	 */
	public function GetLocation($address = FALSE)
	{
		// Check if the location is set
		if ($this -> location !== FALSE)
		{
			return $this -> location;
		}
		
		// Get the remote address
		if ($address === FALSE)
		{
			if (isset($_SERVER['REMOTE_ADDR']))
			{
				$address = $_SERVER['REMOTE_ADDR'];
			}
			else
			{
				$address = '127.0.0.1';
			}
		}

		// Get the GeoIP information
		$geoinfo = GeoIP::Create();
		$country = $geoinfo -> Country($address);
		$city = $geoinfo -> City($address);
		if (empty($country))
		{
			$country = _('Unknown');
		}
		
		// Create country + city info
		$location = $country . (empty($city) ? '' : ', ' . $city);
		
		// Set the result as current location to avoid lookups
		$this -> location = $location;
		return $this -> location;
	}
	
	/*!
	 * @brief Get the Javascript to query location
	 *
	 * Get the required Javascript code to query the
	 * actual location data in the web page
	 *
	 * @note The code is not inside the script tags
	 * @attention The code requires jQuery to work
	 *
	 * @retval string
	 * 	Javascript code block
	 */
	public function GetJS()
	{
		// Location is set, no need to ask it every time
		if ($this -> location !== FALSE)
		{
			return '';
		}
		
		ob_start();
		echo 'if (navigator.geolocation)
{
	navigator.geolocation.getCurrentPosition(
		function(position)
		{
			$.ajax({
				type: \'POST\',
				url: \'' , URL::Get() -> Generate('UpdateLocation') , '\',
				data: JSON.stringify({
					position: position
				}),
				contentType: \'application/json\',
				dataType: \'json\'
			});
		}
	);
}';
		$r = ob_get_contents();
		ob_end_clean();
		return $r;
	}
	
	/*!
	 * @brief Save to the session
	 *
	 * Save the location data to the session
	 */
	protected function SaveToSession()
	{
		if (class_exists('\\System\\Session'))
		{
			$_SESSION['Geolocation'] = serialize($this -> location);
		}
	}
}
