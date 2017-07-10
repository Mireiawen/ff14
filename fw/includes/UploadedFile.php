<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// Load the base File class
require_once(SYSTEM_PATH . '/includes/File.php');

/*!
 * @brief Uploaded File handler object
 *
 * Extension to the File class to add additional checks 
 * for the uploaded files and ability to hold the 
 * information from the upload
 */
class UploadedFile extends File
{
	/*!
	 * @brief The filename given when uploaded
	 */
	protected $orig_name;
	
	/*!
	 * @brief The type given when uploaded
	 */
	protected $orig_type;
	
	/*!
	 * @brief The size given when uploaded
	 */
	protected $orig_size;
	
	/*!
	 * @brief The constructor
	 *
	 * Constructs the class and does some sanity checks
	 * for the uploaded file
	 * 
	 * @retval object
	 * 	Instance of the class itself
	 * @throws LengthException if file size differs from given size
	 * @throws UnexpectedValueException if the %MIME type mismatch
	 */
	public function __construct($tmp_name, $name, $type, $size)
	{
		parent::__construct($tmp_name);
		
		$this -> orig_name = $name;
		$this -> orig_type = $type;
		$this -> orig_size = intval($size);

		if ($this -> GetSize() !== $this -> GetOriginalSize())
		{
			throw new LengthException(sprintf(_('File sizes differ; expected %d, got %d bytes'), $this -> GetOriginalSize(), $this -> GetSize()));
		}

		if (strcasecmp($this -> GetMimeType(), $this -> GetOriginalMimeType()))
		{
			throw new UnexpectedValueException(sprintf(_('File type differ; expected "%s", got "%s"'), $this -> GetOriginalMimeType(), $this -> GetMimeType()));
		}
	}
	
	/*!
	 * @brief Gets the original base name of the file
	 *
	 * This method returns the original base name of the file, 
	 * directory, or link without path info
	 *
	 * @param string $suffix
	 * 	Optional suffix to omit from the base name returned
	 * @retval string
	 * 	Returns the base name without path information
	 */
	public function GetOriginalBasename($suffix = '')
	{
		return basename($this -> orig_name, $sufix);
	}

	/*!
	 * @brief Gets the original file extension
	 *
	 * Retrieves the original file extension
	 *
	 * @retval string
	 * 	Returns a string containing the file extension, 
	 * 	or an empty string if the file has no extension
	 */
	public function GetOriginalExtension()
	{
			return pathinfo($this -> orig_name, PATHINFO_EXTENSION);
	}

	/*!
	 * @brief Gets the original filename
	 *
	 * Gets the original filename without any path information
	 *
	 * @retval string
	 * 	The filename
	 */
	public function GetOriginalFilename()
	{
		return $this -> orig_name;
	}

	/*!
	 * @brief Get the given %MIME type for the file
	 *
	 * Get the %MIME type given for the uploaded
	 * file by the user
	 *
	 * @retval string
	 * 	The given %MIME type
	 */
	public function GetOriginalMimeType()
	{
		return $this -> orig_type;
	}
	
	/*!
	 * @brief Gets original file size
	 *
	 * Returns the original file size in bytes
	 *
	 * @retval int
	 * 	The file size in bytes
	 */
	public function GetOriginalSize()
	{
		return $this -> orig_size;
	}

	/*!
	 * @brief Moves an uploaded file to a new location
	 *
	 * This method moves the uploaded file to a new destination. 
	 * It makes sure it is a valid upload file and that the 
	 * destination file does not already exist.
	 *
	 * Please note, the UploadedFile object might not be 
	 * in a valid state after the move
	 *
	 * @param string $destination
	 * 	New destination for the uploaded file
	 * @retval object
	 * 	A File object for the new file location
	 * @throws Exception if the file does not exists, 
	 * 	or if destination already exists
	 */
	public function MoveToDestination($destination)
	{
		// Check that the file is upload file
		if ((!is_file($this -> GetRealpath())) || (!is_uploaded_file($this -> GetRealpath())))
		{
			throw new Exception(sprintf(_('File "%s" is not a valid upload file or no longer exists'), $this -> GetFilename()));
		}
		
		// Check destination file
		if (file_exists($destination))
		{
			throw new Exception(sprintf(_('The destination file already exists')));
		}
		
		// Try to do the move
		if (!@move_uploaded_file($this -> GetRealpath(), $destination))
		{
			// Get the error
			$e = error_get_last();
			if ($e === NULL)
			{
				$e = array('message' => _('Unknown error'));
			}
			
			throw new Exception(sprintf(_('Moving of uploaded file "%s" to its destination "%s" failed: %s"'), 
				$this -> GetFilename(),
				$destination,
				$e['message']
			));
		}
		
		return new File($destination);
	}
}
