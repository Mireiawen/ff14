<?php
namespace System;

/*!
 * @brief A trait for Smarty templates
 *
 * A trait to hold the Smarty template helper
 * methods. These methods do some basic error
 * checking before loading the files with Smarty.
 *
 * $Author: mireiawen $
 * $Id: SmartyTemplates.php 441 2017-07-11 21:02:54Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
trait SmartyTemplates
{
	/*!
	 * @brief Display a template
	 *
	 * A helper method to display a template if
	 * the template file exists.
	 * 
	 * @param string $template
	 * 	The template filename to display
	 * @retval bool
	 * 	on success boolean TRUE,
	 * 	on failure boolean FALSE with additionally E_USER_WARNING triggered if debugging
	 */
	protected function DisplayTpl($template)
	{
		// Make sure we have Smarty
		if (!$this -> __isset('Smarty'))
		{
			if ((defined('DEBUG')) && (DEBUG) && (! $this instanceof Error))
			{
				trigger_error(_('DEBUG warning: Missing Smarty Object'), E_USER_WARNING);
			}
			return FALSE;
		}
		
		// Check that the template exists
		if ($this -> Smarty -> templateExists($template))
		{
			$this -> Smarty -> display($template);
			return TRUE;
		}
		
		// Template was not found
		if ((defined('DEBUG')) && (DEBUG) && (! $this instanceof Error))
		{
			trigger_error(sprintf(_('DEBUG warning: Missing template file "%s"'), $template), E_USER_WARNING);
		}
		
		return FALSE;
	}
	
	/*!
	 * @brief Fetch a template contents
	 * 
	 * A helper method to fetch a template contents
	 * if the template file exists.
	 * 
	 * @param string $template
	 * 	The template filename to fetch
	 * @retval mixed
	 * 	On success the template contents as a string,
	 * 	on failure boolean FALSE with additionally E_USER_WARNING triggered if debugging
	 */
	protected function FetchTpl($template)
	{
		// Make sure we have Smarty
		if (!$this -> __isset('Smarty'))
		{
			if ((defined('DEBUG')) && (DEBUG) && (! $this instanceof Error))
			{
				trigger_error(_('DEBUG warning: Missing Smarty Object'), E_USER_WARNING);
			}
			return FALSE;
		}
		
		// Check that the template exists
		if ($this -> Smarty -> templateExists($template))
		{
			return $this -> Smarty -> fetch($template);
		}
		
		// Template was not found
		if ((defined('DEBUG')) && (DEBUG) && (! $this instanceof Error))
		{
			trigger_error(sprintf(_('DEBUG warning: Missing template file "%s"'), $template), E_USER_WARNING);
		}
		
		return FALSE;
	}
	
	/*!
	 * @brief Create a template object
	 *
	 * A helper method to create a Smarty object from a template 
	 * file if the file exists.
	 *
	 * @param string $template
	 * 	The template filename to fetch
	 * @param mixed $parent
	 * 	The template object to use as the parent,
	 * 	boolean TRUE to use the main Smarty as the parent,
	 * 	boolean FALSE to not set the parent
	 * @retval mixed
	 * 	On success the Smarty template object,
	 * 	on failure boolean FALSE with additionally E_USER_WARNING triggered if debugging
	 */
	protected function CreateTpl($template, $parent = TRUE)
	{
		// Smarty was not foumd
		if (!$this -> __isset('Smarty'))
		{
			if ((defined('DEBUG')) && (DEBUG) && (! $this instanceof Error))
			{
				trigger_error(_('DEBUG warning: Missing Smarty Object'), E_USER_WARNING);
			}
			
			return FALSE;
		}
		
		// Check for parent class
		if ($parent === TRUE)
		{
			$parent = $this -> Smarty -> GetSmarty();
		}
		
		// Check that the template exists
		if ($this -> Smarty -> templateExists($template))
		{
			if ($parent === FALSE)
			{
				return $this -> Smarty -> createTemplate($template);
			}
			else
			{
				return $this -> Smarty -> createTemplate($template, $parent);
			}
		}
		
		// Template was not found
		if ((defined('DEBUG')) && (DEBUG) && (! $this instanceof Error))
		{
			trigger_error(sprintf(_('DEBUG warning: Missing template file "%s"'), $template), E_USER_WARNING);
		}
		
		return FALSE;
	}
}
