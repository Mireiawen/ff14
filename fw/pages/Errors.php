<?php
namespace System;

/*!
 * @brief The default error handler page
 *
 * The default error handler page for the 
 * Rose Framework. Handles all the usual HTTP
 * error codes with cats in the template.
 *
 * $Author: mireiawen $
 * $Id: Errors.php 363 2015-08-23 21:38:40Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Errors extends \Base implements \Page
{
	/*!
	 * @brief Load the templating trait
	 */
	use \SmartyTemplates;
	
	/*!
	 * @brief Constructor for the class
	 * 
	 * Pretty basic constructor that just sets
	 * up Smarty with the parent and then 
	 * sets a default HTTP code
	 *
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on error
	 */
	public function __construct()
	{
		parent::__construct(TRUE);
		$this -> code = '100';
	}
	
	/*!
	 * @brief Check the URL parameters
	 * 
	 * Just get the HTTP code from parameters
	 * if it exists
	 *
	 * @retval mixed
	 * 	Boolean TRUE if page is handled, 
	 * 	boolean FALSE if not,
	 * 	a string if user is to be redirected to other page
	 */
	public function Handles($params)
	{
		// Get the error code if it, and only it, exists
		// in the parameters
		if (count($params) === 1)
		{
			$code = array_pop($params);
			if (is_numeric($code))
			{
				$this -> code = intval($code);
			}
		}

		// Try showing login form
		if (($this -> code === 401) && (! \LoginUser::Get() -> IsLoggedIn()))
		{
			return '/User/Login';
		}
		
		// We show something anyway
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
		// Try setting the HTTP code to the code we got
		http_response_code($this -> code);
		
		// Set up the page title
		\SmartyInstance::Get() -> assign('title', sprintf(_('Error - %s'), $this -> code));

		// Try creating template specific for this code
		$tpl = $this -> CreateTpl('Errors/' . $this -> code . '.tpl.html');
		if ($tpl === FALSE)
		{
			// Template not found, try generic template
			$tpl = $this -> CreateTpl('Errors/Base.tpl.html');
		}

		// Template still not found, just show text
		if ($tpl === FALSE)
		{
			printf(_('Got error %d. Additionally, was unable to load any error template!'), $this -> code);
			return TRUE;
		}

		// Assign the code to template and show it
		$tpl -> assign('code', $this -> code);
		$tpl -> display();
		return TRUE;
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
