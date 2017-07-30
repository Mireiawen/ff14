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

// Load Smarty Template trait
require_once(SYSTEM_PATH . '/includes/SmartyTemplates.php');

// Error levels
define('ERROR_LEVEL_ERROR', E_USER_ERROR);
define('ERROR_LEVEL_WARNING', E_USER_WARNING);
define('ERROR_LEVEL_NOTICE', E_USER_NOTICE);
define('ERROR_LEVEL_INFO', -8);

/*!
 * @brief Errors and other messages handler
 *
 * Class to handle different sorts of system messages 
 * from errors to notices to the user.
 *
 * This stores the messages and shows them in a 
 * nice templated form if templates are available.
 *
 * Depending on settings, errors can be sent out on
 * page execution end, or just stored to session
 * at that time.
 *
 * $Author: mireiawen $
 * $Id: Error.php 449 2017-07-13 09:37:16Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Error extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief Use the SmartyTemplates trait
	 */
	use SmartyTemplates;
	
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
		// Construct the parent class with Smarty
		parent::__construct(TRUE);
		
		// Set up the message arrays
		$this -> messages = array(
			'errors' => array(),
			'warnings' => array(),
			'notices' => array(),
			'infos' => array(),
		);
		
		// No ajax or hiding by default
		$this -> ajax = FALSE;
		$this -> hide = FALSE;
		
		// Catch errors
		set_error_handler(array($this, 'CatchError'));
		
		// Catch uncaught exceptions
		set_exception_handler(array($this, 'CatchException'));
		
		// Catch last error before shutdown
		register_shutdown_function(array($this, 'CatchShutdown'));
		
		// Let Smarty mute errors it expects
		if (class_exists('\\System\\SmartyInstance'))
		{
			SmartyInstance::muteExpectedErrors();
			$this -> Smarty = SmartyInstance::Get();
			
			// Make sure errors and notices contain something in the template
			$this -> Smarty -> assign('errors', '');
			$this -> Smarty -> assign('warnings', '');
			$this -> Smarty -> assign('notices', '');
			$this -> Smarty -> assign('info', '');
		}
		
		// Disable "user abort" as it might cause troubles with error handling 
		// on closing time
		ignore_user_abort(TRUE);
		
		// Check for session errors
		if (class_exists('\\System\\Session'))
		{
			// Get errors...
			if (array_key_exists('E_errors', $_SESSION))
			{
				$this -> messages['errors'] = unserialize($_SESSION['E_errors']);
			}
			
			// ...and warnings...
			if (array_key_exists('E_warnings', $_SESSION))
			{
				$this -> messages['warnings'] = unserialize($_SESSION['E_warnings']);
			}
			
			// ...and notices...
			if (array_key_exists('E_notices', $_SESSION))
			{
				$this -> messages['notices'] = unserialize($_SESSION['E_notices']);
			}
			
			// ...and info messages.
			if (array_key_exists('E_infos', $_SESSION))
			{
				$this -> messages['infos'] = unserialize($_SESSION['E_infos']);
			}
		}
	}
	
	/*!
	 * @brief The destructor 
	 *
	 * Save the message arrays into the session 
	 * if it exists and show them unless told to not 
	 * to do so.
	 */
	public function __destruct()
	{
		// Log error messages to a file
		if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_ERROR_LOG_FILE')))
		{
			ob_start();
			var_dump($this -> messages);
			file_put_contents(DEBUG_ERROR_LOG_FILE, ob_get_contents(), FILE_APPEND|LOCK_EX);
			ob_end_clean();
		}
		
		// If we have session available
		if (class_exists('\\System\\Session'))
		{
			// Pack current errors...
			$_SESSION['E_errors'] = serialize($this -> messages['errors']);
			
			// ...and warnings...
			$_SESSION['E_warnings'] = serialize($this -> messages['warnings']);
			
			// ...and notices...
			$_SESSION['E_notices'] = serialize($this -> messages['notices']);
			
			// ... and info messages
			$_SESSION['E_infos'] = serialize($this -> messages['infos']);
		}
		
		if (!$this -> hide)
		{
			// Send out unseen errors if we are debugging, or if content handler was not loaded
			global $page;
			if (($page === FALSE) || ((defined('DEBUG')) && (DEBUG)))
			{
				$this -> Show();
			}
		}
	}
	
	/*!
	 * @brief Error catcher
	 *
	 * This method catches the usual catchable PHP errors 
	 * and handles them by adding them to the message 
	 * array, and optionally halting the execution on a
	 * fatal errors.
	 *
	 * @param int $errno
	 * 	Error code
	 * @param string $errmsg
	 * 	The error message itself
	 * @param string $file
	 * 	The file causing the error
	 * @param int $line
	 * 	The line number causing the error
	 * @param mixed $vars
	 * 	Unused, but required by PHP
	 *
	 * @retval bool
	 * 	TRUE if error was handled,
	 * 	FALSE if it was not and PHP should handle it
	 */
	public function CatchError($errno, $errmsg, $file, $line, $vars)
	{
		// Check for NOTICE class errors
		$notice = FALSE;
		if ((($errno&E_NOTICE) || ($errno&E_USER_NOTICE)) && (error_reporting()))
		{
			if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_NOTICES')) && (DEBUG_NOTICES))
			{
				ob_start();
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$bt = ob_get_contents();
				ob_end_clean();
				$this -> messages['notices'][] = array('message' => $errmsg . '<br><pre>' . $bt . '</pre>');
			}
			else
			{
				$this -> messages['notices'][] = array('message' => $errmsg);
			}
		}
		
		// Check for WARNING class errors
		else if ((($errno&E_WARNING) || ($errno&E_USER_WARNING)) && (error_reporting()))
		{
			if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_WARNINGS')) && (DEBUG_WARNINGS))
			{
				ob_start();
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$bt = ob_get_contents();
				ob_end_clean();
				$this -> messages['warnings'][] = array('message' => $errmsg . '<br><pre>' . $bt . '</pre>');
			}
			else
			{
				$this -> messages['warnings'][] = array('message' => $errmsg);
			}
		}
		
		// Handle error with debugging information
		else if ((defined('DEBUG')) && (DEBUG) && 
			((error_reporting()) || ((defined('DEBUG_SHOW_SUPPRESSED'))) && (DEBUG_SHOW_SUPPRESSED)))
		{
			ob_start();
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$bt = ob_get_contents();
			ob_end_clean();

			if ($this -> ajax)
			{
				throw new Exception((error_reporting() ? '' : '[Suppressed] ') . $errmsg);
			}
			else
			{
				$this -> messages['errors'][] = array(
					'code' => $errno,
					'message' => (error_reporting() ? '' : '[Suppressed] ') . $errmsg,
					'file' => $file,
					'line' => $line,
					'backtrace' => $bt,
				);
			}
		}
		
		// Cleaner errors when not debugging
		else if (error_reporting())
		{
			if ($this -> ajax)
			{
				throw new Exception($errmsg);
			}
			else
			{
				$this -> messages['errors'][] = array(
					'code' => $errno,
					'message' => $errmsg
				);
			}
		}
		
		// Finish on fatal errors
		if ((($errno&E_ERROR) && (!$errno&E_RECOVERABLE_ERROR))
			|| ($errno&E_CORE_ERROR)
			|| ($errno&E_COMPILE_ERROR)
			|| ($errno&E_USER_ERROR))
		{
			exit($errno);
		}
		
		// Tell it is handled	
		return TRUE;
	}
	
	/*!
	 * @brief Unhandled exception catcher
	 *
	 * This method should handle the uncaught Exceptions. 
	 * Exceptions are treated like they were fatal errors,
	 * and as such, the execution should stop after this 
	 * method.
	 * 
	 * @param object $e
	 * 	The exception that was not caught
	 * @retval bool
	 * 	TRUE if error was handled,
	 * 	FALSE if it was not and PHP should handle it
	 */
	public function CatchException($e)
	{
		if ((defined('DEBUG')) && (DEBUG))
		{
			$this -> messages['errors'][] = array(
				'code' => $e -> getCode(),
				'message' => $e -> getMessage(),
				'file' => $e -> getFile(),
				'line' => $e -> getLine(),
				'backtrace' => $e -> getTraceAsString(),
			);
		}
		
		// Cleaner errors when not debugging
		else
		{
			$this -> messages['errors'][] = array(
				'code' => $e -> getCode(),
				'message' => $e -> getMessage(),
			);
		}
		
		// Tell it is handled
		return TRUE;
	}
	
	/*!
	 * @brief Add a message
	 *
	 * Add a message to a specific error level, in case 
	 * the error should be in specific level but cannot be
	 * added via trigger_error. 
	 *
	 * This method does no error handling, only adds 
	 * the message to the array.
	 * 
	 * @param string $message
	 * 	The error message itself
	 * @param int $level
	 * 	The error level
	 * @param int $errno
	 * 	The error number, if any
	 * @retval bool
	 * 	TRUE on success
	 * @throws Exception on invalid error level
	 */
	public function AddMessage($message, $level = ERROR_LEVEL_INFO, $errno = 0)
	{
		switch($level)
		{
		case ERROR_LEVEL_INFO:
			$this -> messages['infos'][] = array('message' => $message);
			return TRUE;
		
		case ERROR_LEVEL_NOTICE:
			$this -> messages['notices'][] = array('message' => $message);
			return TRUE;
			break;
		
		case ERROR_LEVEL_WARNING:
			$this -> messages['warnings'][] = array('message' => $message);
			return TRUE;
		
		case ERROR_LEVEL_ERROR:
			$this -> messages['errors'][] = array('message' => $message, 'code' => $errno);
			return TRUE;
		
		default:
			throw new \Exception(sprintf(_('Unknown error level %s'), $level));
		}
	}
	
	/*!
	 * @brief Static method to add a message
	 * 
	 * Short version to add a message without need to 
	 * first get the instance of the class.
	 *
	 * Add a message to a specific error level, in case 
	 * the error should be in specific level but cannot be
	 * added via trigger_error. 
	 *
	 * This method does no error handling, only adds 
	 * the message to the array.
	 * 
	 * @param string $message
	 * 	The error message itself
	 * @param int $level
	 * 	The error level
	 * @param int $errno
	 * 	The error number, if any
	 * @retval bool
	 * 	TRUE on success
	 * @throws Exception on invalid error level
	 */
	public static function Message($message, $level = ERROR_LEVEL_INFO, $errno = 0)
	{
		return Error::Get() -> AddMessage($message, $level, $errno);
	}

	
	/*!
	 * @brief Set the AJAX mode
	 *
	 * Set the AJAX mode where we (re-)throw all the errors 
	 * for the AJAX handler to catch.
	 *
	 * @param bool $val
	 * 	TRUE to set AJAX handling on, FALSE for off
	 */
	public function AJAX($val = TRUE)
	{
		$this -> ajax = $val;
	}
	
	/*!
	 * @brief Hide the shutdown errors
	 *
	 * Set the hiding of shutdown-time errors. This allows the output
	 * to not show errors when the class is closed, which might be wanted
	 * when the content is not a usual human read page, such as with
	 * pictures or JSON data.
	 * 
	 * @param bool $val
	 * 	TRUE to hide the errors at shutdown, FALSE to show
	 */
	public function Hide($val = TRUE)
	{
		$this -> hide = $val;
	}

	/*!
	 * @brief Get the messages from message array
	 *
	 * Get the current messages as array from the
	 * specified type
	 *
	 * @param string $type
	 * 	Type of the message
	 * @retval array
	 * 	Array of the messages
	 * @throws Exception if type is not found
	 */
	public function GetMessages($type)
	{
		if (!isset($this -> messages[$type]))
		{
			throw new \Exception(sprintf(_('No such message type "%s"'), $type));
		}
		
		return $this -> messages[$type];
	}

	/*!
	 * @brief Set the messages into message array
	 *
	 * Set the message array with the given messages
	 *
	 * @warning Setting incorretly formatted message array
	 *  may cause problems when processing them.
	 *
	 * @param string $type
	 * 	Type of the message
	 * @param array $messages
	 * 	Array of the messages
	 * @throws Exception if type is not found
	 */
	public function SetMessages($type, $messages)
	{
		if (!isset($this -> messages[$type]))
		{
			throw new \Exception(sprintf(_('No such message type "%s"'), $type));
		}
		
		$this -> messages[$type] = $messages;
	}
	
	/*!
	 * @brief Handle the shutdown errors
	 *
	 * Handle errors that cause the shutdown
	 * of the script to be triggered
	 *
	 * @retval bool
	 * 	TRUE if the error was handled,
	 * 	FALSE if the error is to be left for PHP to handle
	 */
	public function CatchShutdown()
	{
		$e = error_get_last();
		if ((is_array($e)) && (defined('DEBUG')) && (DEBUG))
		{
			ob_start();
			debug_print_backtrace();
			$bt = ob_get_contents();
			ob_end_clean();
			$this -> messages['errors'][] = array(
				'code' => $e['type'],
				'message' => $e['message'],
				'file' => $e['file'],
				'line' => $e['line'],
				'backtrace' => $bt,
			);
		}
		
		// Cleaner errors when not debugging
		elseif (is_array($e))
		{
			$this -> messages['errors'][] = array(
				'code' => $e['type'],
				'message' => $e['message'],
			);
		}
		
		// Tell it is handled
		return TRUE;
	}
	
	/*!
	 * @brief Output the errors
	 *
	 * Output the errors in templated form if
	 * template exists, otherwise send them out
	 * as text.
	 *
	 * If you need to catch the output, 
	 * use output buffering.
	 *
	 * @retval bool
	 * 	TRUE on success
	 */
	public function Show()
	{
		// Check if we are supposed to output HTML
		$usehtml = ini_get('html_errors');
		
		if (($usehtml === NULL) || ($usehtml === FALSE))
		{
			$usehtml = TRUE;
		}
		
		// Go through all message arrays
		foreach ($this -> messages as $name => &$data)
		{
			// Check if we have anything to show
			if (empty($data))
			{
				continue;
			}
			
			// Check if we have a template
			$tpl = $this -> CreateTpl($name . '.tpl.html');
			
			// We have template, get the data
			if ($tpl !== FALSE)
			{
				$tpl -> assign($name, $data);
				$tpl -> display();
			}
			
			// No template, create simple output still
			else
			{
				// Just put them inside a PRE tag
				if ($usehtml)
				{
					echo '<pre class="' , $name , '">';
				}
				
				// Print the errors
				foreach ($data as $message)
				{
					if (isset($message['code']))
					{
						echo '[' , $message['code'] , '] ';
					}
					echo $message['message'];
					
					if ((isset($message['file'])) && (isset($message['line'])))
					{
						echo ' in file ' , $message['file'] , ' on line ' , $message['line'];
					}
					if (isset($message['backtrace']))
					{
						echo "\n" , $message['backtrace'];
					}
					echo "\n";
				}
				
				// Close the tag
				if ($usehtml)
				{
					echo '<pre class="' , $name , '">';
				}
			}

			// Clear the messages that are shown
			$data = array();
		}
		
		// We are done here
		return TRUE;
	}
}
