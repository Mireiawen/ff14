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

/*!
 * \brief A PHP session handler
 *
 * Basic session creation, validation and invalidation
 * 
 * $Author: mireiawen $
 * $Id: Session.php 412 2016-07-18 13:12:48Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Session extends Base
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
	 * default values for the session to work.
	 *
	 * @param string $name
	 * 	The session identifier string
	 * @param int $limit
	 * 	The session maximum inactive lifetime, in seconds
	 * @param string $path
	 * 	The path session is limited to
	 * @param string $domain
	 * 	The domain session is limited to
	 * @param bool $secure
	 * 	Send cookie securely over HTTPS if true, HTTP otherwise
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct($name, $limit = 0, $path = '/', $domain = NULL, $secure = NULL)
	{
		// Construct parent
		parent::__construct();
		
		// Session won't work on CLI anyway
		if (php_sapi_name() === 'cli')
		{
			return;
		}
		
		// Make sure timeout is defined
		if (!defined('SESSION_TIMEOUT'))
		{
			define('SESSION_TIMEOUT', '360');
		}
		
		// Set the session cookie
		session_name('session_' . $name);
		
		// Set up session domain
		if (!isset($domain))
		{
			$domain = $_SERVER['SERVER_NAME'];
		}
		
		// Use HTTPS?
		if (!isset($secure))
		{
			$secure = isset($_SERVER['HTTPS']);
		}
		
		// Start the session
		session_save_path(SESSION_PATH);
		session_set_cookie_params($limit, $path, $domain, $secure);
		session_start();
		
		// Validate session
		if (!$this -> IsValid())
		{
			$this -> Regenerate(TRUE);
		}
		
		// Update expiry
		$_SESSION['Expires'] = time() + SESSION_TIMEOUT;
	}
	
	/*!
	 * @brief The instance creation method
	 * 
	 * Creates an instance of the class itself,
	 * can be called multiple times and will not
	 * create a new instance on subsequent calls
	 *
	 * @param string $name
	 * 	The session identifier string
	 * @param int $limit
	 * 	The session maximum inactive lifetime, in seconds
	 * @param string $path
	 * 	The path session is limited to
	 * @param string $domain
	 * 	The domain session is limited to
	 * @param bool $secure
	 * 	Send cookie securely over HTTPS if true, HTTP otherwise
	 * @retval object
	 * 	Instance of the class itself
	 */
	public static function Create($name, $limit = 0, $path = '/', $domain = NULL, $secure = NULL)
	{
		if (!isset(self::$instance))
		{
			$o = __CLASS__;
			self::$instance = new $o($name, $limit, $path, $domain, $secure);
		}
		
		return self::$instance;
	}
	
	/*!
	 * @brief Do the session validation
	 * 
	 * Make sure session ID, remote address, user agent etc.
	 * are still same and time limit has not expired
	 *
	 * @retval bool
	 * 	TRUE if session is still valid, FALSE otherwise
	 */
	private function IsValid()
	{
		// Session is empty, no point checking more...
		if (empty($_SESSION))
		{
			$this -> Debug(_('Session is empty'));
			return FALSE;
		}
		
		// Check for required variables
		if ((!is_array($_SESSION)) || 
			(!array_key_exists('Server Generated SID', $_SESSION)) ||
			(!array_key_exists('RemoteIP', $_SESSION)) ||
			(!array_key_exists('UserAgent', $_SESSION)) || 
			(!array_key_exists('Expires', $_SESSION))
		)
		{
			$this -> Debug(_('Session variables mismatch'));
			return FALSE;
		}
		
		// Validate remote address
		if ($_SESSION['RemoteIP'] !== md5($_SERVER['REMOTE_ADDR']))
		{
			$this -> Debug(_('Session remote address mismatch'));
			return FALSE;
		}
		
		// Validate User Agent
		if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
		{
			$ua = md5($_SERVER['HTTP_USER_AGENT']);
		}
		else
		{
			$ua = md5('User-Agent Unknown');
		}
		
		if ($_SESSION['UserAgent'] !== $ua)
		{
			$this -> Debug(_('Session user agent mismatch'));
			return FALSE;
		}
		
		// Check for expiry
		if (time() > $_SESSION['Expires'])
		{
			$this -> Debug(_('Session timed out'));
			return FALSE;
		}
		
		// So far good....
		return TRUE;
	}
	
	/*!
	 * @brief Regenerate the session ID
	 *
	 * Regenerate the session ID to prevent session hijacking
	 * attempts, for example after the user login.
	 *
	 * If the session is cleared, it will clean up all the session
	 * data, otherwise it will only regenerate the ID.
	 *
	 * @param bool $clear
	 * 	If TRUE, will delete all the session specific data.
	 * @retval bool
	 * 	May return FALSE if session is obsolete, TRUE otherwise
	 */
	public function Regenerate($clear = FALSE)
	{
		// Make sure we are not obsolete yet
		if ((is_array($_SESSION)) && 
			(!$clear) && 
			(array_key_exists('OBSOLETE', $_SESSION)) && 
			($_SESSION['OBSOLETE']))
		{
			return FALSE;
		}
		
		// Make sure we don't update expiry on already obsolete session
		if ((!array_key_exists('OBSOLETE', $_SESSION)) || (!$_SESSION['OBSOLETE']))
		{
			$_SESSION['Expires'] = time() + 10;
		}
		
		// Set up expiration for slow AJAX and like
		$_SESSION['OBSOLETE'] = TRUE;
		
		// If session was cleared out,
		// we need to reinitialize its variables
		if ($clear)
		{
			// Unset everything
			session_regenerate_id(TRUE);
			
			// Make sure session is cleared
			$_SESSION = array();
			
			// Set up current IP and Agent
			$_SESSION['RemoteIP'] = md5($_SERVER['REMOTE_ADDR']);
			
			if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
			{
				$_SESSION['UserAgent'] = md5($_SERVER['HTTP_USER_AGENT']);
			}
			else
			{
				$_SESSION['UserAgent'] = md5('User-Agent Unknown');
			}
			
			// Set expiry
			$_SESSION['Expires'] = time() + SESSION_TIMEOUT;
			
			// We have generated the SID
			$_SESSION['Server Generated SID'] = TRUE;
			
			// Show debug warning
			$this -> Debug('Session cleared');
		}
		
		// Otherwise just regenerate ID
		else
		{
			// Regenerate session and preserve data
			session_regenerate_id(FALSE);
			
			// Remove session obsoleting from new session
			unset($_SESSION['OBSOLETE']);
			
			// And update expiry
			$_SESSION['Expires'] = time() + SESSION_TIMEOUT;
		}
		
		return TRUE;
	}

	/*!
	 * @brief Get the current session ID
	 *
	 * Get the current session ID as seen by PHP
	 *
	 * @retval string
	 * 	The session ID
	 */
	public function GetSID()
	{
		return session_id();
	}
	
	/*!
	 * @brief End the current session
	 *
	 * Close the current session and destroy its data
	 */
	public function Close()
	{
		// Regenerate session safely
		$this -> Regenerate();
		
		// And destroy session
		session_destroy();
	}
	
	/**
	 * @brief Pause the current session
	 * 
	 * Write the current session out and "pause" it
	 * to avoid long call blocking the session
	 */
	public function Pause()
	{
		session_write_close();
	}

	/**
	 * @brief Resume the session
	 * 
	 * Resume the paused session
	 */
	public function Resume()
	{
		session_start();
	}
	
	/*!
	 * @brief Session debug printing
	 *
	 * Send debugging warnings on session events if and 
	 * only if both DEBUG and DEBUG_SESSION are set to TRUE
	 *
	 * @param string $msg
	 * 	The warning message to send
	 */
	private function Debug($msg)
	{
		// Check we are debugging
		if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_SESSION')) && (DEBUG_SESSION))
		{
			trigger_error(sprintf(_('Session DEBUG warning: %s'), $msg), E_USER_WARNING);
		}
	}
}
