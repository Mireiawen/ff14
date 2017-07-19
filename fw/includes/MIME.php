<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

/*!
 * @brief %MIME magic handler class
 *
 * A class containing helper utilities for 
 * working with %MIME types.
 *
 * @todo
 * Includes some custom magic that depends on
 * Linux file utility
 *
 * $Author: mireiawen $
 * $Id: MIME.php 441 2017-07-11 21:02:54Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class MIME extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief Try to get the %MIME type for the file
	 *
	 * Tries to detect the specified file %MIME data type
	 * with custom check for files detected as plain text
	 * to determine some common web formats.
	 *
	 * @todo this uses the exec to run Linux shell command
	 * "file" with custom MIME magic database
	 *
	 * @param string $file
	 * 	The file name to detect the %MIME type
	 * @retval string
	 * 	The detected %MIME type
	 */
	public static function Type($file)
	{
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeinfo = finfo_file($finfo, $file);
		finfo_close($finfo);
		if ($mimeinfo === 'text/plain')
		{
			// TODO: some better way for this
			// Validate some plaintext files
			$mime = exec('file -m "' . SYSTEM_PATH . '/plaintext.magic" --mime-type -b "' . $file . '"');
			
			// We got some idea, use it, otherwise
			// we have no idea, and leave it at that
			if (!empty($mime))
			{
				$mimeinfo = $mime;
			}
		}
		return $mimeinfo;
	}
}
