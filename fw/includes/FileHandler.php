<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load MIME handler
require_once(SYSTEM_PATH . '/includes/MIME.php');

/*!
 * @brief A base class for the file handler pages
 *
 * Provide the basic page structur and some helpers 
 * for the pages that handle different files instead
 * of the usual HTML pages.
 *
 * $Author: mireiawen $
 * $Id: FileHandler.php 448 2017-07-11 22:19:58Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
abstract class FileHandler extends Base implements Page
{
	/*!
	 * @brief The protected constructor
	 * 
	 * Check for required extensions and 
	 * construct the parent class without
	 * templates.
	 * 
	 * @retval object
	 * 	Instance of the class itself
	 */
	public function __construct()
	{
		// Check for extensions
		if (!extension_loaded('fileinfo'))
		{
			throw new \Exception(_('File information extension fileinfo is required!'));
		}
		
		parent::__construct(FALSE);

		// Set a default path to where the files are looked from
		$this -> filepath = APPLICATION_PATH . '/Files';
		
		// Set the relative file name under the path
		$this -> filename = FALSE;
	}
	
	/*!
	 * @brief Validate the file
	 *
	 * Make sure the filename is within the filepath,
	 * exists and is readable.
	 *
	 * @param string $filename
	 * 	The file name to validate
	 * @retval bool
	 * 	TRUE if validation was successful,
	 * 	FALSE otherwise
	 */
	protected function ValidateFile($filename)
	{
		// Generate the full, real path
		$fn = realpath($this -> filepath . '/' . $filename);
		
		// Make sure it is within our filepath
		if (strncmp($fn, $this -> filepath . '/', strlen($this -> filepath)  + 1))
		{
			return FALSE;
		}
		
		// Make sure it is file and readable
		if ((is_file($fn)) && (is_readable($fn)))
		{
			return TRUE;
		}
		
		// Was not a readable file
		return FALSE;
	}
	
	/*!
	 * @brief Find the file
	 *
	 * Find the file from possible translation
	 * first and then from the default folder.
	 *
	 * @param string $filename
	 * 	The file name to find
	 * @retval mixed
	 * 	String with the corrected path to the file,
	 * 	boolean FALSE if file was not found
	 */
	protected function FindFile($filename)
	{
		// Check for localization 
		if (class_exists('\\System\\Translation'))
		{
			// Try reading the file from language path
			$fn = Translation::Get() -> GetLang() . '/' . $filename;
			if ($this -> ValidateFile($fn))
			{
				return $fn;
			}
		}
		
		// Try the default language
		if (defined('LANGUAGE_DEFAULT'))
		{
			// Try reading the file from language path
			$fn = LANGUAGE_DEFAULT . '/' . $filename;
			if ($this -> ValidateFile($fn))
			{
				return $fn;
			}
		}
		
		// Try the file itself
		if ($this -> ValidateFile($filename))
		{
			return $filename;
		}
		
		// Unable to find the file
		return FALSE;
	}
	
	/*!
	 * @brief Try to handle the file
	 *
	 * Try to handle the file, start by generating
	 * the filename from parameters if not done 
	 * already by the child class.
	 *
	 * @param array $params
	 * 	The page path info parameters
	 * @retval mixed
	 * 	Boolean TRUE if page is handled, 
	 * 	boolean FALSE if not,
	 * 	a string if user is to be redirected to other page
	 */
	public function Handles($params)
	{
		// Revert the URL back to what the actual page was
		URL::Get() -> RevertCurrent();

		// Check if the filename was already found by child
		if ($this -> filename === FALSE)
		{
			// Not done, just try the file from path info
			$this -> filename = $this -> FindFile(implode('/', array_reverse($params)));
			if ($this -> filename === FALSE)
			{
				return '/Errors/404';
			}
		}
		
		// Check that the file exists and is readable
		if ($this -> ValidateFile($this -> filename) === FALSE)
		{
			// Not found
			return '/Errors/404';
		}
		
		// We are good
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
		// Get the full path to the file
		$fn = realpath($this -> filepath . '/' . $this -> filename);
		
		// Check that the file exists and is (still) readable
		if ((!is_file($fn)) || (!is_readable($fn)))
		{
			throw new \Exception(sprintf(_('Unable to read the given file "%s"'), $this -> filename));
		}
		
		// Send the MIME type
		header('Content-type: ' . MIME::Type($fn));
		
		// Make sure output buffer is off
		if (ob_get_level())
		{
			ob_end_flush();
		}
		
		// Show the actual file
		readfile($fn);
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
