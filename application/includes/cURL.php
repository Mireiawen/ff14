<?php

/*!
 * @brief Simple cURL wrapper
 * 
 * A cURL wrapper class for easier cURL use
 * 
 * @copyright 2014-2016 Mireiawen Rose
 * @license Licensed under the Apache License, Version 2.0
 * $Id: cURL.php 25 2015-02-26 21:44:02Z mireiawen $
 */
final class cURL
{
	/*!
	 * @brief The actual cURL handle
	 */
	private $handle;
	
	/*!
	 * @brief Construct the class
	 *
	 * Constructs the class and initializes cURL
	 *
	 * @throws Exception if cURL extension is missing
	 */
	public function __construct($url = NULL)
	{
		// Check for extension
		if (!extension_loaded('curl'))
		{
			throw new Exception(_('cURL extension is required!'));
		}
		
		// Initialize handle
		$this -> handle = FALSE;
		$this -> Init($url);
	}
	
	/*!
	 * @brief Destroy the class
	 *
	 * Close connection and destroy the class
	 *
	 * @see Close
	 */
	public function __destruct()
	{
		$this -> Close();
	}
	
	/*!
	 * @brief Get the cURL handle
	 *
	 * Returns the current cURL handle
	 *
	 * @retval mixed
	 * 	boolean FALSE if handle is not initialized,
	 * 	cURL handle otherwise
	 */
	public function GetHandle()
	{
		return $this -> handle;
	}
	
	/*!
	 * @brief Close the current handle
	 *
	 * Close the current handle if it is open
	 */
	public function Close()
	{
		if ($this -> handle !== FALSE)
		{
			curl_close($this -> handle);
		}
		$this -> handle = FALSE;
	}
	
	/*!
	 * @brief URL encodes the given string
	 *
	 * @param string $str
	 * 	String to encode
	 *
	 * @retval string
	 * 	The encoded string
	 * @throws Exception on errors
	 */
	public function Escape($str)
	{
		if ($this -> handle === FALSE)
		{
			$this -> ThrowError();
		}
		
		$r = curl_escape($this -> handle, $str);
		if ($r === FALSE)
		{
			$this -> ThrowError();
		}
		return $r;
	}
	
	/*!
	 * @brief Execute the cURL session
	 *
	 * This function should be called after initializing a cURL session 
	 * and all the options for the session are set.
	 *
	 * @retval mixed
	 * 	Returns boolean TRUE on success.
	 * 	However, if the CURLOPT_RETURNTRANSFER option is set, 
	 * 	it will return the result on success
	 * @throws Exception on failure
	 */
	public function Exec()
	{
		if ($this -> handle === FALSE)
		{
			$this -> ThrowError();
		}
		
		$r = curl_exec($this -> handle);
		if ($r === FALSE)
		{
			$this -> ThrowError();
		}
		return $r;
	}
	
	/*!
	 * @brief Get information regarding the transfer
	 * 
	 * @param int $opt
	 * 	cURL constant to get information for
	 *
	 * @retval mixed
	 * 	If opt is given, returns its value. Otherwise, returns 
	 * 	an associative array
	 * @throws Exception on failure
	 */
	public function GetInfo($opt = FALSE)
	{
		if ($this -> handle === FALSE)
		{
			$this -> ThrowError();
		}
		
		// Workaround for no option set; documented $opt = 0 doesn't seem to work
		if ($opt === FALSE)
		{
			$r = curl_getinfo($this -> handle);
		}
		else
		{
			$r = curl_getinfo($this -> handle, $opt);
		}
		if ($r === FALSE)
		{
			$this -> ThrowError();
		}
		return $r;
	}
	
	/*!
	 * @brief Initialize a cURL session
	 *
	 * Initializes a new cURL session
	 *
	 * @param string $url
	 * 	If provided, the CURLOPT_URL option will be set to its value
	 * 
	 * @throws Exception on failure
	 */
	public function Init($url = NULL)
	{
		if ($this -> handle !== FALSE)
		{
			throw new Exception(sprintf(_('cURL error: already initialized')));
		}
		
		$this -> handle = curl_init($url);
		if ($this -> handle === FALSE)
		{
			throw new Exception('cURL error: Initialization failed');
		}
	}

	/*!
	 * @brief Reset all options of the libcurl session
	 *
	 * This re-initializes all options set to the default values
	 *
	 * @throws Exception on failure
	 */
	public function Reset()
	{
		if ($this -> handle === FALSE)
		{
			$this -> ThrowError();
		}
		
		curl_reset($this -> handle);
	}

	/*!
	 * @brief Set multiple options for a cURL transfer
	 *
	 * @param array $options
	 * 	An array specifying which options to set and their values
	 *
	 * @retval bool
	 * 	Returns TRUE if all options were successfully set
	 * @throws Exception on failure
	 */
	public function SetoptArray($options)
	{
		if ($this -> handle === FALSE)
		{
			$this -> ThrowError();
		}
		
		$r = curl_setopt_array($this -> handle, $options);
		if ($r === FALSE)
		{
			$this -> ThrowError();
		}
		return $r;
	}
	
	/*!
	 * @brief Set an option for a cURL transfer
	 *
	 * @param int $option
	 * 	The CURLOPT_XXX option to set
	 *
	 * @param mixed $value
	 * 	The value to be set on option
	 *
	 * @retval bool
	 * 	Returns TRUE on success
	 * @throws Exception on failure
	 */
	public function Setopt($option, $value)
	{
		if ($this -> handle === FALSE)
		{
			$this -> ThrowError();
		}
		
		$r = curl_setopt($this -> handle, $option, $value);
		if ($r === FALSE)
		{
			$this -> ThrowError();
		}
		return $r;
	}
	
	/*!
	 * @brief Return string describing the given error code
	 *
	 * @retval string
	 * 	Error description or NULL for invalid error code
	 */
	public function StrError($errornum)
	{
		return curl_strerror($errornum);
	}
	
	/*!
	 * @brief Decodes the given URL encoded string
	 *
	 * @param string $str
	 * 	The URL encoded string to be decoded
	 *
	 * @retval string
	 * 	Decoded string
	 * @throws Exception on failure
	 */
	public function Unescape($str)
	{
		if ($this -> handle === FALSE)
		{
			$this -> ThrowError();
		}
		
		$r = curl_unescape($this -> handle, $str);
		if ($r === FALSE)
		{
			$this -> ThrowError();
		}
		return $r;
	}
	
	/*!
	 * @brief Gets cURL version information
	 *
	 * @param int $age
	 * 	Not documented in PHP!
	 *
	 * @retval array
	 * 	Associative array of cURL and library versions 
	 * 	and features
	 */
	public function Version($age = CURLVERSION_NOW)
	{
		return curl_version($age);
	}
	
	/*!
	 * @brief Throw the error Exception
	 *
	 * Check the cURL handle, and throw the error message
	 * depending on handle availability
	 *
	 * @throws Exception always
	 */
	private function ThrowError()
	{
		if ((defined('DEBUG')) && (DEBUG))
		{
			debug_print_backtrace();
		}
		
		if ($this -> handle === FALSE)
		{
			throw new Exception(sprintf(_('cURL error: Not initialized')));
		}
		
		throw new Exception(sprintf(_('cURL error %s: %s'), 
			curl_errno($this -> handle), 
			curl_error($this -> handle)));
	}
}
