<?php
namespace System\User;

/*!
 * @brief Default user login page
 *
 * A simple default page with login form
 *
 * $Author: mireiawen $
 * $Id: Login.php 441 2017-07-11 21:02:54Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Login extends \System\Base implements \System\Page
{
	/*!
	 * @brief Load the templating trait
	 */
	use \System\SmartyTemplates;
	
	/*!
	 * @brief Constructor for the class
	 * 
	 * Pretty basic constructor that just sets
	 * up Smarty with the parent and then 
	 * reverts current page back to where 
	 * user previously was
	 *
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on error
	 */
	public function __construct()
	{
		// Add TRUE to constructor parameter to use Smarty
		parent::__construct(TRUE);
	}
	
	/*!
	 * @brief Check the URL parameters
	 * 
	 * Check we don't have extra parameters
	 * and we have the login template
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
		
		// Check the template
		$form = $this -> CreateTpl('User/Login.tpl.html');
		if ($form === FALSE)
		{
			return '/Errors/404';
		}
		
		// Check if we should revert the current URL to what was previously asked
		if (\System\URL::Get() -> GetRequestedControlPath() === '/User/Login')
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
	 * should be shown.
	 *
	 * @retval string
	 * 	The actual page contents
	 */
	public function Show()
	{
		// Check if we are already logged in
		if (\System\LoginUser::Get() -> IsLoggedIn())
		{
			// Return user to their previous page, or Dashboard
			$url = \System\URL::Get() -> GetRequestedControlPath();
			if (($url === FALSE) || ($url === '/User/Login'))
			{
				$url = \System\URL::Get() -> GetPrevious();
				if (($url === FALSE) || ($url === \System\URL::Get() -> GetSelf()))
				{
					$url = \System\URL::Get() -> Generage('User/Dashboard');
				}
			}
			else
			{
				$url = \System\URL::Get() -> Generate($url);
			}
			header('Location: ' . $url);
			
			// No need to display a template
			return FALSE;
		}
		
		// Create the template
		$form = $this -> CreateTpl('User/Login.tpl.html');
		if ($form === FALSE)
		{
			// Unable to load the template, bail out
			return TRUE;
		}
		
		// Assign page title
		\System\SmartyInstance::Get() -> assign('title', _('Login'));
		
		// Check for the POST data for possible username
		$username = '';
		if ((is_array($_POST)) && (array_key_exists('username', $_POST)))
		{
			$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_EMAIL);
		}
		
		// Assign the variables
		$form -> assign('username', $username);
		
		// Show the form
		$form -> display();
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
	 * Since we are open to everything, we require
	 * no access groups here.
	 *
	 * @retval array 
	 * 	Array of acceptable access levels
	 */
	public function GetRequiredAccess()
	{
		return array(USER_GROUP_NONE);
	}
}
