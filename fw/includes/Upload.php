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

// Load the UploadedFile class
require_once(SYSTEM_PATH . '/includes/UploadedFile.php');

/*!
 * @brief An upload handler class
 * 
 * A class to handle basics of the file uploads. 
 * This does not have any sort of actual file validations,
 * it only does the basic uploaded file data processing and
 * moving to desired destination
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class Upload extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 *
	 * In addition to building the Base class,
	 * this constructor sets up some of the 
	 * default values for the class.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		// Construct the parent class without Smarty
		parent::__construct(FALSE);
		
		// Set up the file array
		$this -> files = FALSE;
	}
	
	/*!
	 * @brief Read the uploaded file information
	 *
	 * Read the information provided about the 
	 * uploaded files from the FILES array
	 *
	 * @param bool $throw_on_error
	 * 	Throw exception on upload error instead of writing message to 
	 * 	user and processing following files
	 * @retval array
	 * 	Array of UploadedFile objects
	 */
	public function GetFiles($throw_on_error = FALSE)
	{
		// Check for already parsed data
		if ($this -> files !== FALSE)
		{
			return $this -> files;
		}
		
		// No files
		if (empty($_FILES))
		{
			$this -> files = array();
			return $this -> files;
		}
		
		// Go through the data
		$this -> files = array();
		foreach ($_FILES as $file)
		{
			// Error with file upload, handle it
			if ($file['error'] !== UPLOAD_ERR_OK)
			{
				// Get the error string
				$msg = $this -> GetUploadErrorMessage($file['error']);
				
				// Throw exception if told to do so
				if ($throw_on_error)
				{
					throw new Exception($msg, $file['error']);
				}
				
				// Show the message to the user and process next upload
				Error::Get() -> AddMessage($msg, ERROR_LEVEL_ERROR, $file['error']);
				continue;
			}
			
			// Add the file to array
			$this -> files[] = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size']);
		}
		
		// Return the array
		return $this -> files;
	}

	/*!
	 * @brief Get the error message 
	 *
	 * Get the error string from upload error code
	 *
	 * @param int $code
	 * 	Upload error code
	 * @retval string
	 * 	Upload error string
	 */
	public function GetUploadErrorMessage($code)
	{
		switch ($code)
		{
		case UPLOAD_ERR_INI_SIZE:
			return _('The uploaded file exceeds the upload_max_filesize directive in php.ini');
		
		case UPLOAD_ERR_FORM_SIZE:
			return _('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
		
		case UPLOAD_ERR_PARTIAL:
			return _('The uploaded file was only partially uploaded');
		
		case UPLOAD_ERR_NO_FILE:
			return _('No file was uploaded');
		
		case UPLOAD_ERR_NO_TMP_DIR:
			return _('Missing a temporary folder');
		
		case UPLOAD_ERR_CANT_WRITE:
			return _('Failed to write file to disk');
		
		case UPLOAD_ERR_EXTENSION:
			return _('A PHP extension stopped the file upload');
		
		default:
			return sprintf(_('Unknown upload error code %d'), $file['error']);
		}
	}
}
