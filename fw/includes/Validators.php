<?php
namespace System;

/*!
 * @brief Data validator methods for pages
 *
 * Some data validation methods for use at different pages. 
 * Pages can override the methods set here as they wish.
 *
 * If some method is supposed to be used by AJAX, page needs to
 * implement that method as public, even if they don't modify
 * the actual code.
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
trait Validators
{
	/*!
	 * @brief Validate a string length
	 * 
	 * Validate a string length, if you need to validate its 
	 * contents you should try filter_var or PHP string functions
	 *
	 * @param string $string
	 * 	The string to test
	 * @param int $min 
	 * 	Minimum length
	 * @param int $max
	 * 	Maximum length, FALSE to not test
	 * @retval bool
	 * 	Boolean TRUE if string passes all tests
	 * @throws Exception with the error message if validation fails
	 */
	protected function ValidateString($string, $min = 0, $max = FALSE)
	{
		if (is_string($string) === FALSE)
		{
			throw new \Exception(sprintf(_('It is not a string')));
		}
		
		$len = strlen($string);
		if ($len < $min)
		{
			throw new \Exception(sprintf(_('It is too short, it should be at least %d characters'), $min));
		}
		
		if (($max !== FALSE) && ($len > $max))
		{
			throw new \Exception(sprintf(_('It is too long, it should be less than %d characters'), $max));
		}
		
		return TRUE;
	}
	
	/*!
	 * @brief Validate a integer
	 *
	 * Validate that the given value is integer or convertible to such,
	 * and between the given range
	 *
	 * @param mixed $value 
	 * 	The date to validate
	 * @param int $min
	 * 	Minimum value, FALSE to not test
	 * @param int $max
	 * 	Maximum value, FALSE to not test
	 * @retval bool
	 * 	Boolean TRUE if the value passes all tests
	 * @throws Exception with the error message if validation fails
	 */
	protected function ValidateInt($value, $min = FALSE, $max = FALSE)
	{
		$num = filter_var($value, FILTER_VALIDATE_INT);
		if ($num === FALSE)
		{
			throw new \Exception(sprintf(_('It is not integer')));
		}
		
		if (($min !== FALSE) && ($num < $min))
		{
			throw new \Exception(sprintf(_('It is too small, it should be at least %d'), $min));
		}
		
		if (($max !== FALSE) && ($num > $max))
		{
			throw new \Exception(sprintf(_('It is too large, it should be less than %d'), $max));
		}
		
		return TRUE;
	}
	
	/*!
	 * @brief Validate a date
	 *
	 * Validate that the given value is a valid date or convertable to date.
	 *
	 * Optionally validate it is before or after the current time.
	 *
	 * @param string $value 
	 * 	The date to validate
	 * @param int $time
	 * 	-1 if the date has to be in the past,
	 * 	0 to not check
	 * 	1 if the date has to be in the future
	 * @retval bool
	 * 	Boolean TRUE if the value passes all tests
	 * @throws Exception with the error message if validation fails
	 */
	protected function ValidateDate($value, $time = TRUE)
	{
		// Try to read the input to time
		$t = @strtotime($value);
		if ($t === FALSE)
		{
			return _('Invalid date specification');
		}
		
		// Do not check the date for past or future
		if (!$time)
		{
			return TRUE;
		}
		
		// Check if the time is in the future
		if (($time > 0) && (mktime(0, 0, 0) > $time))
		{
			return _('The date is in the past');
		}
		
		// Check if the time is in the past
		if (($time < 0) && (mktime(0, 0, 0) < $time))
		{
			return _('The date is in the future');
		}
		
		// We were able to read it
		return TRUE;
	}
	
	/**
	 * @brief Validate email address
	 *
	 * Check that the given string is a valid email address
	 *
	 * @param string $value
	 * 	The string to check
	 *
	 * @retval bool
	 * 	TRUE if value is valid
	 * @throws Exception if the password is not valid, with the reason
	 */
	protected function ValidateEmailAddress($value)
	{
		$val = filter_var($value, FILTER_VALIDATE_EMAIL);
		if ($val === FALSE)
		{
			throw new \Exception(_('Not a valid email address'));
		}
		
		return TRUE;
	}

	/**
	 * @brief Validate a host name
	 * 
	 * Check that the given string is a valid host name
	 * 
	 * @param string $value
	 * 	The string to check
	 *
	 * @retval bool
	 * 	TRUE if value is valid
	 * @throws Exception if the password is not valid, with the reason
	 */
	protected function ValidateHostname($value)
	{
		// @source: http://www.regextester.com/23
		$regex = '#^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$#';
		$val = preg_match($regex, $value);
		
		if ($val === FALSE)
		{
			throw new \Exception(_('Regexp failure'));
		}
		
		if ($val === 0)
		{
			throw new \Exception(_('Not a valid hostname'));
		}
		
		return TRUE;
	}
	
	/**
	 * @brief Validate IP address
	 *
	 * Check that the given string is a valid IP address
	 *
	 * @param string $value
	 * 	The string to check
	 *
	 * @retval bool
	 * 	TRUE if value is valid
	 * @throws Exception if the value is not valid
	 */
	protected function ValidateIP($value)
	{
		$val = filter_var($value, FILTER_VALIDATE_IP);
		if ($val === FALSE)
		{
			throw new \Exception(_('Not a valid IP address'));
		}
		
		return TRUE;
	}

	/**
	 * @brief Validate MAC address
	 *
	 * Check that the given string is a valid MAC (hardware) address
	 *
	 * @param string $value
	 * 	The string to check
	 *
	 * @retval bool
	 * 	TRUE if value is valid
	 * @throws Exception if the value is not valid
	 */
	protected function ValidateMAC($value)
	{
		$val = filter_var($value, FILTER_VALIDATE_MAC);
		if ($val === FALSE)
		{
			throw new \Exception(_('Not a valid MAC address'));
		}
		
		return TRUE;
	}
	
	/**
	 * @brief Validate *NIX username
	 * 
	 * Check that the given string is a valid UNIX/Linux username
	 * 
	 * @param string $value
	 * 	The string to check
	 *
	 * @retval bool
	 * 	TRUE if value is valid
	 * @throws Exception if the value is not valid
	 */
	protected function ValidateUsername($value)
	{
		$this -> ValidateString($value, 5, 32);
		
		if (preg_match('/^\w+$/', $value))
		{
			return TRUE;
		}
		
		throw new \Exception(_('It is not a valid username'));
	}
	
	/**
	 * @brief Validate the password
	 *
	 * Check that the given password meets the complexity criteria
	 *
	 * @param User $user
	 * 	The user who to change the password for
	 * @param string $value
	 * 	The password to check
	 *
	 * @retval bool
	 * 	TRUE if value is valid
	 * @throws Exception if the password is not valid, with the reason
	 */
	protected function ValidatePassword(\System\User $user, $value)
	{
		// Make sure it is actually changed
		if (($user -> GetID()) && ($user -> VerifyPassword($value)))
		{
			throw new \Exception(_('Passwords is not changed'));
		}
		
		// Password complezity checks
		if (strlen($value) < 8)
		{
			throw new \Exception(_('Password is too short'));
		}
		if (!strcasecmp($value, $user -> GetUsername()))
		{
			throw new \Exception(_('Password cannot be your username'));
		}
		if (!strcasecmp($value, $user -> GetName()))
		{
			throw new \Exception(_('Password cannot be your name'));
		}
		if (!preg_match('/[0-9]/', $value))
		{
			throw new \Exception(_('Password must include at least one number'));
		}
		if (!preg_match('/[a-z]/', $value))
		{
			throw new \Exception(_('Password must include at least one lowercase letter'));
		}
		if (!preg_match('/[A-Z]/', $value))
		{
			throw new \Exception(_('Password must include at least one uppercase letter'));
		}
		if (!preg_match('/\W/', $value))
		{
			throw new \Exception(_('Password must include at least one special letter'));
		}
		
		return TRUE;
	}
}
