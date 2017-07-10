<?php
namespace System;

/*!
 * @brief GeoIP location update
 *
 * Update the current location from AJAX call
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class UpdateLocation extends \Base implements \Page
{
	/*!
	 * @brief Load the templating trait
	 */
	use \SmartyTemplates;
	
	/*!
	 * @brief Constructor for the class
	 * 
	 * Pretty basic constructor that just sets
	 * up Smarty with the parent.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on error
	 */
	public function __construct()
	{
		// Construct the parent class without Smarty
		parent::__construct(FALSE);
		$this -> position = FALSE;
	}
	
	/*!
	 * @brief Check the URL parameters
	 *
	 * We do not take any URL parameters,
	 * check for POST data instead
	 *
	 * @retval mixed
	 * 	Boolean TRUE if page is handled, 
	 * 	boolean FALSE if not,
	 * 	a string if user is to be redirected to other page
	 */
	public function Handles($params)
	{
		if (count($params))
		{
			return FALSE;
		}

		// Read the request body
		$input = file_get_contents('php://input');
		
		// Convert from JSON
		$data = json_decode($input, TRUE);
		if ($data == NULL)
		{
			// Conversion failed, invalid data
			return FALSE;
		}
		
		// Check for parameter
		if (!isset($data['position']))
		{
			return FALSE;
		}
		
		// Get the position data
		$this -> position = $data['position'];
		
		// We handle all the other cases
		return TRUE;
	}
	
	/*!
	 * @brief Show the page
	 * 
	 * This method is called when the actual page 
	 * should be shown.
	 *
	 * @retval string
	 * 	The actual page contents
	 */
	public function Show()
	{
		// Set the content type
		header('Content-type: application/json');
		
		// Check for position data
		if ($this -> position === FALSE)
		{
			echo json_encode('Position data not found');
			return FALSE;
		}
		
		// Set the position
		\Geolocation::Get() -> SetLocation($this -> position);
		
		// And return
		echo json_encode($this -> position);
		return FALSE;
	}
	
	/*!
	 * @brief Check if user can even try
	 * to access the page
	 *
	 * This function should return an array
	 * of access levels that are allowed 
	 * to access this page. If user does not have
	 * that access, then the rest of the methods
	 * are not called, but user is shown an error
	 * 
	 * @retval array 
	 * 	Array of acceptable access levels
	 */
	public function GetRequiredAccess()
	{
		return array(USER_GROUP_NONE);
	}
}
