<?php
namespace System\User;

/*!
 * @brief Default user logout page
 *
 * A simple logout page
 *
 * $Author: mireiawen $
 * $Id: Logout.php 441 2017-07-11 21:02:54Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Logout extends \System\Base implements \System\Page
{
	/*!
	 * @brief Constructor for the class
	 * 
	 * Pretty basic constructor that just sets
	 * up the parent
	 *
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on error
	 */
	public function __construct()
	{
		parent::__construct(FALSE);
	}
	
	/*!
	 * @brief Check the URL parameters
	 * 
	 * Check we don't have extra parameters
	 *
	 * @retval mixed
	 * 	Boolean TRUE if page is handled, 
	 * 	boolean FALSE if not,
	 * 	a string if user is to be redirected to other page
	 */
	public function Handles($params)
	{
		// We don't take parameters
		if (count($params))
		{
			return '/Errors/404';
		}
		
		// Check if we should revert the current URL to what was previously asked
		if (\System\URL::Get() -> GetRequestedControlPath() === '/User/Logout')
		{
			\System\URL::Get() -> RevertCurrent();
		}
		
		// Handle rest
		return TRUE;
	}
	
	/*!
	 * @brief Show the page
	 * 
	 * This method is called when the actual page 
	 * should be shown. We are going to just
	 * redirect the user though.
	 *
	 * @retval string
	 * 	The actual page contents
	 */
	public function Show()
	{
		\System\LoginUser::Get() -> Logout();
		
		// Return user to their previous page, or to defined default page
		$url = \System\URL::Get() -> GetPrevious();
		if (($url === FALSE) || ($url === \System\URL::Get() -> GetSelf()))
		{
			$url = \System\URL::Get() -> Generate(CONTROLLER_PATH_ROOT . CONTROLLER_DEFAULT);
		}
		
		header('Location: ' . $url);
		
		// No need to display a template
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
	 * Would be pointless to have non-logged-in
	 * user to log out, so restrict this to 
	 * valid users.
	 *
	 * @retval array 
	 * 	Array of acceptable access levels
	 */
	public function GetRequiredAccess()
	{
		return array(USER_GROUP_VALID_USER);
	}
}
