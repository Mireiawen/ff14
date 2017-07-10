<?php
namespace System;

/*!
 * @brief A sample index page
 *
 * A simple example index page for the framework
 * that shows some templated output.
 *
 * $Author: mireiawen $
 * $Id: Index.php 352 2015-07-21 11:08:59Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Index extends \Base implements \Page
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
		// Construct the parent class with Smarty
		parent::__construct(TRUE);
	}
	
	/*!
	 * @brief Check the URL parameters
	 *
	 * To avoid any problems with the system, as we 
	 * are an Index, do not allow any parameters
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
		
		// Make sure we have a template to show
		$page = $this -> CreateTpl('IndexPage.tpl.html');
		if ($page === FALSE)
		{
			// Not found, try showing error page instead
			return '/Errors/404';
		}
		
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
		// Load the template
		$page = $this -> CreateTpl('IndexPage.tpl.html');
		if ($page === FALSE)
		{
			// Loading failed, bail out
			return TRUE;
		}
		
		// Assign the page title
		\SmartyInstance::Get() -> assign('title', _('Rose Framework - Default Page'));
		
		// Assign user name to our template as name
		$page -> assign('name', \LoginUser::Get() -> GetName());
		
		// And show the page
		$page -> display();
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
