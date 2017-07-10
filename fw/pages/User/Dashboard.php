<?php
namespace System\User;

/*!
 * @brief Default user dashboard
 *
 * A simple deshboard where user can change some
 * of their settings themselves
 *
 * $Author: mireiawen $
 * $Id: Dashboard.php 354 2015-07-22 16:37:45Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Dashboard extends \Base implements \Page
{
	/*!
	 * @brief Load the templating trait
	 */
	use \SmartyTemplates;
	
	/*!
	 * @brief Constructor for the class
	 * 
	 * Pretty basic constructor that just sets
	 * up Smarty with the parent and some
	 * defaults for the user data.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on error
	 */
	public function __construct()
	{
		parent::__construct(TRUE);
		$this -> user = FALSE;
		$this -> name = FALSE;
		$this -> email = FALSE;
	}
	
	/*!
	 * @brief Check the URL parameters
	 * 
	 * Check we don't have extra parameters,
	 * check for updated user information
	 * and check for the template
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
		
		// User needs to be logged in for this to work
		if (!\LoginUser::Get() -> IsLoggedIn())
		{
			return '/Errors/403';
		}
		
		// Check the template
		$tpl = $this -> CreateTpl('User/Dashboard.tpl.html');
		if ($tpl === FALSE)
		{
			return '/Errors/404';
		}
		
		// Get the logged in user
		$this -> user = \LoginUser::Get() -> User;
		$this -> name = $this -> user -> GetName();
		$this -> email = $this -> user -> GetEmail();
		
		// Validate and update new data
		if ((is_array($_POST)) && (array_key_exists('update', $_POST)))
		{
			$this -> UpdateEmail($this -> email);
			$this -> UpdateName($this -> name);
			$this -> UpdatePassword();
			$this -> user -> Write();
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
		// Create the template
		$tpl = $this -> CreateTpl('User/Dashboard.tpl.html');
		if ($tpl === FALSE)
		{
			return TRUE;
		}
		
		// Set the page title
		\SmartyInstance::Get() -> assign('title', sprintf(_('Dashboard for %s'), $this -> user -> GetUsername()));
		
		// Assign information back to template
		$tpl -> assign('user', $this -> user);
		$tpl -> assign('email', $this -> email);
		$tpl -> assign('name', $this -> name);
		
		// And show the template
		$tpl -> display();
		return TRUE;
	}
	
	/*!
	 * @brief Update the user's email
	 *
	 * Update the user email, do some 
	 * sanity check on the value
	 *
	 * @param [out] string $email 
	 * 	New value read for the email
	 * @retval bool
	 * 	 TRUE on success, 
	 * 	 FALSE if email was not updated
	 * 
	 */
	protected function UpdateEmail(&$email)
	{
		if (!array_key_exists('email', $_POST))
		{
			// Not enough input; likely not form submission
			return FALSE;
		}
		
		// Read the input
		$value = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		if ($value === FALSE)
		{
			// Invalid input; return sanitized form
			$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
			trigger_error(sprintf(_('Invalid email address %s'), $email), E_USER_WARNING);
			return FALSE;
		}
		
		// Input is good; save it
		if ($value !== $this -> user -> GetEmail())
		{
			$this -> user -> SetEmail($value);
			$email = $value;
			trigger_error(_('Email updated succesfully'), E_USER_NOTICE);
		}
		
		return TRUE;
	}
	
	/*!
	 * @brief Update the user's name
	 *
	 * Update the user name, do some 
	 * sanity check on the value
	 *
	 * @param [out] string $name 
	 * 	New value read for the name
	 * @retval bool
	 * 	 TRUE on success, 
	 * 	 FALSE if name was not updated
	 * 
	 */
	protected function UpdateName(&$name)
	{
		if (!array_key_exists('name', $_POST))
		{
			// Not enough input; likely not form submission
			return FALSE;
		}
		
		// Read the input
		$value = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
		
		// Input is good; save it
		if ($value !== $this -> user -> GetName())
		{
			$this -> user -> SetName($value);
			$name = $value;
			trigger_error(_('Name updated succesfully'), E_USER_NOTICE);
		}
		
		return TRUE;
	}
	
	/*!
	 * @brief Update the user's password
	 *
	 * Update the user password, do some 
	 * sanity check on the value 
	 *
	 * @param bool $require_old
	 * 	TRUE if old password should be checked,
	 * 	FALSE otherwise such as admin reset
	 * 
	 * @retval bool
	 * 	 TRUE on success, 
	 * 	 FALSE if name was not updated
	 */
	protected function UpdatePassword($require_old = TRUE)
	{
		if ((($require_old) && (!array_key_exists('password', $_POST))) ||
			(!array_key_exists('password_1', $_POST)) ||
			(!array_key_exists('password_2', $_POST)))
		{
			// Not enough input; likely not form submission
			return FALSE;
		}
		
		if ($require_old)
		{
			$pwd = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
		}
		else
		{
			$pwd = '';
		}
		
		$pwd1 = filter_input(INPUT_POST, 'password_1', FILTER_SANITIZE_STRING);
		$pwd2 = filter_input(INPUT_POST, 'password_2', FILTER_SANITIZE_STRING);
		
		// Check if new password is being set
		if ((empty($pwd1)) && (empty($pwd2)))
		{
			return FALSE;
		}
		
		// Check old password matches
		if (($require_old) && (!$this -> user -> VerifyPassword($pwd)))
		{
			trigger_error(_('Old password does not match'), E_USER_WARNING);
			return FALSE;
		}
		
		// Check new passwords match
		if (strcmp($pwd1, $pwd2))
		{
			trigger_error(_('Passwords do not match'), E_USER_WARNING);
			return FALSE;
		}
		
		// Make sure it is actually changed
		if (!strcmp($pwd, $pwd1))
		{
			trigger_error(_('Passwords is not changed'), E_USER_WARNING);
			return FALSE;
		}
		
		// Password complezity checks
		if (strlen($pwd1) < 8)
		{
			trigger_error(_('Password is too short'), E_USER_WARNING);
			return FALSE;
		}
		if (!strcmp($pwd1, $this -> user -> GetUsername()))
		{
			trigger_error(_('Password cannot be your username'), E_USER_WARNING);
			return FALSE;
		}
		if (!strcmp($pwd1, $this -> user -> GetName()))
		{
			trigger_error(_('Password cannot be your name'), E_USER_WARNING);
			return FALSE;
		}
		if (!preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$/', $pwd1))
		{
			trigger_error(_('Password is too simple'), E_USER_WARNING);
			return FALSE;
		}
		
		// Input is good; save it
		$this -> user -> CreatePassword($pwd1);
		trigger_error(_('Password updated succesfully'), E_USER_NOTICE);
		
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
		return array(USER_GROUP_VALID_USER);
	}
}
