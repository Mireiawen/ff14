<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

// reCAPTCHA library
require_once(SYSTEM_PATH . '/libraries/recaptcha-php/recaptchalib.php');

/*!
 * @brief A Google ReCAPTCHA singleton handler
 * 
 * A class doing the Google ReCAPTCHA integration into 
 * the framework and handling most of the ReCAPTCHA work,
 * as well as saving the succesful captcha usage into
 * user's session so it is not asked every time
 * 
 * $Author: mireiawen $
 * $Id: ReCAPTCHA.php 255 2015-06-02 21:50:26Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class reCAPTCHA extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 * 
	 * In addition to building the Base class,
	 * this constructor tries to check if the
	 * captcha is already answered in the
	 * user's session.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		// Do parent constructing
		parent::__construct();
		
		// Set validation to false as default
		$this -> validated = FALSE;

		// If we have session, check it
		if (class_exists('Session'))
		{
			if (array_key_exists('reCAPTCHA', $_SESSION))
			{
				$this -> validated = $_SESSION['reCAPTCHA'];
			}
		}
	}
	
	/*!
	 * @brief Get the reCAPTCHA HTML code
	 *
	 * Get the reCAPTCHA HTML code if the
	 * current user does not have it validated
	 * correctly already.
	 * 
	 * @retval string
	 * 	The actual reCAPTCHA HMTL code
	 */
	public static function HTML()
	{
		return reCAPTCHA::Get() -> GetHTML();
	}
	
	/*!
	 * @brief Try to validate the reCAPTCHA challenge
	 *
	 * Try to validate the reCAPTCHA challenge if the
	 * user have not already done it.
	 *
	 * @retval bool
	 *	TRUE if the validation is success,
	 *	FALSE otherwise
	 */
	public static function Valid()
	{
		return reCAPTCHA::Get() -> Validate();
	}
	
	/*!
	 * @brief Get the reCAPTCHA HTML code
	 *
	 * Get the reCAPTCHA HTML code if the
	 * current user does not have it validated
	 * correctly already.
	 * 
	 * @retval string
	 * 	The actual reCAPTCHA HMTL code
	 */
	public function GetHTML()
	{
		// Captcha is good, no need to show it again
		if ($this -> validated)
		{
			// Session handles it, no need for extra code
			if (class_exists('Session'))
			{
				return '';
			}
			
			// Add hidden fields with last known data
			return '<input type="hidden" name="recaptcha_challenge_field" value="' . 
				filter_input(INPUT_POST, 'recaptcha_challenge_field', FILTER_SANITIZE_STRING) . 
				'">' . 
				'<input type="hidden" name="recaptcha_challenge_field" value="' . 
				filter_input(INPUT_POST, 'recaptcha_response_field', FILTER_SANITIZE_STRING) . 
				'">';
		}
		
		return recaptcha_get_html(RECAPTCHA_PUBLIC_KEY);
	}
	
	/*!
	 * @brief Try to validate the reCAPTCHA challenge
	 *
	 * Try to validate the reCAPTCHA challenge if the
	 * user have not already done it, and the forcing
	 * is not set on.
	 *
	 * @param bool $force
	 * 	Force the re-validation of the challenge if TRUE
	 * @retval bool
	 *	TRUE if the validation is success,
	 *	FALSE otherwise
	 */
	public function Validate($force = FALSE)
	{
		if ((!$force) && ($this -> validated))
		{
			return TRUE;
		}
		
		$challenge = filter_input(INPUT_POST, 'recaptcha_challenge_field', FILTER_SANITIZE_STRING);
		$response = filter_input(INPUT_POST, 'recaptcha_response_field', FILTER_SANITIZE_STRING);
		$answer = recaptcha_check_answer(RECAPTCHA_PRIVATE_KEY, $_SERVER['REMOTE_ADDR'], $challenge, $response);
		
		if ($answer -> is_valid)
		{
			$this -> validated = TRUE;
			if (class_exists('Session'))
			{
				$_SESSION['reCAPTCHA'] = TRUE;
			}
			return TRUE;
		}
		
		$this -> Invalidate();
		throw new Exception(sprintf(_('reCAPTCHA verification was incorrect: %s'), $answer -> error));
		return FALSE;
	}
	
	/*!
	 * @brief Invalidate the current session validation
	 *
	 * Invalidate the current challenge validation status.
	 * This should be done for example when user logs out.
	 */
	public function Invalidate()
	{
		$this -> validated = FALSE;
		if (class_exists('Session'))
		{
			$_SESSION['reCAPTCHA'] = FALSE;
		}
	}
}
