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
 * @brief A filesystem file object
 *
 * A class that represents files in filesystem as 
 * objects and allows getting certain parameters
 * from them directly via the object methods
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class File extends \SplFileInfo
{
	/*!
	 * @brief The constructor
	 *
	 * Constructs the SplFileInfo class with the filename,
	 * and makes sure we have a file
	 * 
	 * @retval object
	 * 	Instance of the class itself
	 * @throws InvalidArgumentException if the given filename is not a file
	 */
	public function __construct($filename)
	{
		parent::__construct($filename);
		
		if (!$this -> isFile())
		{
			throw new \InvalidArgumentException(sprintf(_('Invalid argument "%s": %s'), $filename, _('It is not a file')));
		}
	}
	
	/*!
	 * @brief Get the %MIME type for the file
	 *
	 * Tries to detect the actual %MIME type
	 * for the current file
	 *
	 * @retval string
	 * 	The detected %MIME type
	 */
	public function GetMimeType()
	{
		return MIME::Type($this -> GetRealpath());
	}
	
	/*!
	 * @brief Opens the file
	 *
	 * Opens the file and returns the resource handle
	 *
	 * @param string $mode
	 * 	The mode parameter specifies the type 
	 * 	of access you require to the stream
	 *
	 * 	Same as PHP fopen
	 * @retval object
	 * 	Returns a file pointer resource on success
	 * @throws Exception if file opening fails
	 */
	public function Open($mode)
	{
		// Try opening the handle
		$handle = @fopen($this -> GetRealpath(), $mode);
		if ($handle === FALSE)
		{
			// Get the error
			$e = error_get_last();
			if ($e === NULL)
			{
				$e = array('message' => _('Unknown error'));
			}
			
			// And throw the exception
			throw new \Exception(sprintf(_('Unable to open the file "%s": %s'), $this -> GetRealpath(), $e['message']));
		}
		
		// Return the handle
		return $handle;
	}
}
