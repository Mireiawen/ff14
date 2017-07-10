<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load Singleton trait
require_once(SYSTEM_PATH . '/includes/Singleton.php');

/*!
 * @brief The %URL generator class
 *
 * A class that creates both relative and absolute URLs 
 * required to link the pages together. Handles reading
 * of different clean URLs as well as writing of them.
 *
 * This also handles keeping of the last page in history,
 * as well as parsing input for specific text pieces to 
 * replace with the real URLs.
 *
 * The examples in the method documentation assume that 
 * the site is set up in following way:
 * - protocol is https
 * - hostname is example.tld
 * - folder if /path/to/site/
 * - script is index.php
 * - controller is Page
 * - controller parameters are 1
 * - query string is foo=bar
 * 
 * This could be presented in following ways:
 * - https://example.tld/path/to/site/Page/1?foo=bar
 * - https://example.tld/path/to/site/index.php/Pag?foo=bare/1
 * - https://example.tld/path/to/site/?q=/Page/1&foo=bar
 * - https://example.tld/path/to/site/index.php?q=/Page/1&foo=bar
 * - https://example.tld/path/to/site/?q=Page/1&foo=bar
 * - https://example.tld/path/to/site/index.php?q=Page/1&foo=bar
 *
 * When running from the command line, only protocol, 
 * folder and script would be available.
 * 
 * Currently the following replacements are done:
 * - u:self - path to the current page, with all the parameters
 * - u:ajax - path to the current page controller in AJAX mode
 * - u:folder - path to the folder where the script resides, without script name
 * - u:script - path to the script itself
 * - u:controller - path to the current controller
 * - u:SOMETHING - path to the controller SOMETHING
 *
 * Change the "u" to "f" for including protocol and hostname 
 *
 * $Author: mireiawen $
 * $Id: URL.php 435 2017-07-08 20:52:43Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class URL
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 *
	 * In addition to building the Base class,
	 * this constructor tries its best to 
	 * detect the installation path for the
	 * %URL generation support.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		// Make sure separator is set,
		// we default to the old style
		if (!defined('URL_SEPARATOR'))
		{
			define('URL_SEPARATOR', '?q=');
		}
		
		// No known controller path yet
		$this -> controller = FALSE;
		$this -> requested_controller = FALSE;
		
		// Try to detect the paths for URLs
		if (php_sapi_name() === 'cli')
		{
			// CLI, just use our own file name
			$this -> protocol = 'file://';
			$this -> hostname = 'localhost';
			$this -> folder = dirname(__FILE__);
			$this -> script = basename(__FILE__);
			$this -> params = FALSE;
			$this -> query = FALSE;
			$this -> request = FALSE;
		}
		else
		{
			// Check for forwarded connections
			if ((isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) && 
				(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) && (!strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https')))
			{
				$this -> protocol = 'https://';
			}
			
			// Check for HTTPS itself
			else if ((isset($_SERVER['HTTPS'])) && 
				(!empty($_SERVER['HTTPS'])) && (strcasecmp($_SERVER['HTTPS'], 'off')))
			{
				$this -> protocol = 'https://';
			}
			
			// Check for default SSL port
			if ((isset($_SERVER['SERVER_PORT'])) &&
				($_SERVER['SERVER_PORT'] === '443'))
			{
				$this -> protocol = 'https://';
			}
			
			// It is quite likely HTTP only
			else
			{
				$this -> protocol = 'http://';
			}
			
			// Get the data from the server array
			$this -> hostname = $_SERVER['HTTP_HOST'];
			$this -> folder = str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME']));
			$this -> script = basename($_SERVER['SCRIPT_NAME']);
			$this -> query = $_SERVER['QUERY_STRING'];
			$this -> request = $_SERVER['REQUEST_URI'];
		}
		
		// Make sure to not break on root dir install
		$folder = trim($this -> folder, '/');
		if ((empty($folder)) || ($folder === '.'))
		{
			$this -> folder = '/';
		}
		else
		{
			$this -> folder = '/' . $folder . '/';
		}
		
		// Make sure we did get the variables, or things may get messy
		if (empty($this -> script))
		{
			trigger_error(sprintf(_('Unable to determine the PHP script that was called, check your server settings and make sure $_SERVER includes SCRIPT_NAME')), E_USER_ERROR);
		}
		
		if (empty($this -> hostname))
		{
			trigger_error(sprintf(_('Unable to determine the system hostname, check your server settings and make sure $_SERVER includes HTTP_HOST')), E_USER_ERROR);
		}
		
		// Get the previous page from session
		$this -> previous = FALSE;
		if (class_exists('Session'))
		{
			if ((is_array($_SESSION)) && (array_key_exists('url_current', $_SESSION)))
			{
				$this -> previous = $_SESSION['url_current'];
			}
			
			else if (isset($_SERVER['HTTP_REFERER']))
			{
				$this -> previous = $_SERVER['HTTP_REFERER'];
			}
			
			if ($this -> GetSelf() !== $this -> GetPrevious())
			{
				$_SESSION['url_current'] = $this -> GetSelf();
			}
		}
		
		// Binary mode, to avoid parsing of binaries
		$this -> binary = FALSE;
	}
	
	/*!
	 * @brief Set or unset the binary mode
	 *
	 * Set or unset the binary mode, which
	 * avoids the parsing of URLs if the data
	 * is expected to be in binary form, or 
	 * parsing is otherwise not wanted.
	 *
	 * @param bool $value
	 * 	TRUE to stop parsing of URLs from the output,
	 * 	FALSE to parse the data
	 */
	public function SetBinary($value = TRUE)
	{
		$this -> binary = $value;
	}
	
	/*!
	 * @brief Set the path to the current controller
	 *
	 * Set the path to the current page controller
	 * without the %URL parameters
	 *
	 * @param string $path
	 * 	The path to the page controller
	 */
	public function SetCurrentControlPath($path)
	{
		$this -> controller = $path;
	}
	
	/*!
	 * @brief Get the path to the current controller
	 *
	 * Get the name of the current page controller
	 * without the %URL parameters
	 *
	 * For example: Page
	 *
	 * @retval string
	 * 	Name of the current page controller
	 */
	public function GetCurrentControlPath()
	{
		return $this -> controller;
	}
	
	/*!
	 * @brief Set the path to the requested controller
	 *
	 * Set the path to the requested page controller
	 * without the %URL parameters
	 *
	 * @param string $path
	 * 	The path to the requested controller
	 */
	public function SetRequestedControlPath($path)
	{
		if ((defined('CONTROLLER_PATH_ROOT')) && (strncasecmp(CONTROLLER_PATH_ROOT, $path, strlen(CONTROLLER_PATH_ROOT)) === 0))
		{
			$this -> requested_controller = substr($path, strlen(CONTROLLER_PATH_ROOT) - 1);
		}
		else
		{
			$this -> requested_controller = $path;
		}
	}
	
	/*!
	 * @brief Get the path to the requested controller
	 *
	 * Get the name of the requested page controller
	 * without the %URL parameters
	 *
	 * For example: Page
	 *
	 * @retval string
	 * 	Name os the requested page controller
	 */
	public function GetRequestedControlPath()
	{
		return $this -> requested_controller;
	}
	
	/*!
	 * @brief Revert the current page back to the previous
	 *
	 * Set the current page back to what it previously was set. 
	 * This can be useful with the file or AJAX handlers, or some forms
	 * so the user can be moved back to the actual page they were on
	 * and not the form, file or such.
	 *
	 */
	public function RevertCurrent()
	{
		if (class_exists('Session'))
		{
			$_SESSION['url_current'] = $this -> GetPrevious();
		}
	}
	
	/*!
	 * @brief Get the current page path information
	 *
	 * Get the current page path information, this is
	 * pretty much the server's path info if that is 
	 * set, but it will try to get the same information
	 * from the get parameter as well.
	 *
	 * If both are set, the server path info will 
	 * be used.
	 *
	 * @retval string
	 * 	Path information
	 */
	public function GetPathInfo()
	{
		// Path in the GET variable
		if ((isset($_GET['q'])) && (!empty($_GET['q'])))
		{
			// Strip directory separator from start if it
			// exists since CONTROLLER_PATH_ROOT should have it
			if (strncmp($_GET['q'], '/', 1))
			{
				return $_GET['q'];
			}
			else
			{
				return substr($_GET['q'], 1);
			}
		}
		
		// Path in the path info
		if ((isset($_SERVER['PATH_INFO'])) && (!empty($_SERVER['PATH_INFO'])))
		{
			// The first character in PATH_INFO should be directory separator, 
			// which should be in CONTROLLER_PATH_ROOT as well, so strip it
			return substr($_SERVER['PATH_INFO'], 1);
		}
		
		// Work with the REQUEST_URI
		if ((isset($_SERVER['REQUEST_URI'])) && (!empty($_SERVER['REQUEST_URI'])))
		{
			$request = $_SERVER['REQUEST_URI'];
			if (isset($_SERVER['SCRIPT_NAME']))
			{
				$script = $_SERVER['SCRIPT_NAME'];
			}
			else
			{
				$script = '';
			}
			
			// Remove script name from the request
			if ((!empty($script)) && (strncmp($request, $script, strlen($script)) === 0))
			{
				return substr($request, strlen($script));
			}
			else
			{
				return substr($request, 1);
			}
		}
		
		// Nothing known
		return '';
	}
	
	/*!
	 * @brief Get the current protocol used
	 *
	 * Get the current protocol used, this is useful for loading
	 * external content to prevent issues when using SSL secured
	 * connection. For example scripts might not be loaded if site
	 * is loaded with SSL and script loaded without.
	 *
	 * @retval string
	 * 	The protocol used, such as https://
	 */
	public function GetProtocol()
	{
		return $this -> protocol;
	}

	/*!
	 * @brief Get the current hostname
	 *
	 * Get the current hostname we detected
	 *
	 * @retval string
	 * 	The current hostname, such as example.tld
	 */
	public function GetHostname()
	{
		return $this -> hostname;
	}
	
	/*!
	 * @brief Generate a subdomain 
	 *
	 * Generate a URL with protocol for a subdomain,
	 * such as http://sub.domain.tld
	 *
	 * @param string $sub
	 * 	Subdomain to add
	 * @retval string
	 * 	The subdomain and domain part with protocol 
	 * 	used, such as https://sub.domain.tld
	 */
	public function GetSubdomain($sub)
	{
		if (empty($sub))
		{
			return $this -> GetHostname();
		}
		return $sub . '.' . $this -> GetHostname();
	}
	
	/*!
	 * @brief Get the previous page the user was at
	 *
	 * Get the previous page the user was at, if 
	 * it was set
	 *
	 * For example: /path/to/site/Page/1
	 *
	 * @retval mixed
	 * 	Boolean FALSE if the previous page is not set,
	 * 	otherwise a string to the path user previously was
	 */
	public function GetPrevious()
	{
		return $this -> previous;
	}
	
	/*!
	 * @brief Get the current page
	 *
	 * Get the path to the current page
	 *
	 * @param bool $full
	 * 	If TRUE, return the full %URL 
	 * 	with the protocol and the domain
	 * @retval string
	 * 	A path to the current page
	 */
	public function GetSelf($full = FALSE)
	{
		if ($full)
		{
			return $this -> GetProtocol() . $this -> GetHostname() . $this -> request;
		}
		
		return $this -> request;
	}
	
	/*!
	 * @brief Get the current page for AJAX
	 *
	 * Get the path to the current page as AJAX
	 *
	 * @param bool $full
	 * 	If TRUE, return the full %URL 
	 * 	with the protocol and the domain
	 * @retval string
	 * 	A path to the current page
	 */
	public function GetSelfAJAX($full = FALSE)
	{
		if ($this -> controller === FALSE)
		{
			return $this -> Generate('', $full, TRUE);
		}
		else
		{
			return $this -> Generate($this -> controller, $full, TRUE);
		}
	}
	
	/*!
	 * @brief Get the absolute path to the script folder
	 *
	 * Generates an absolute path to the folder where the
	 * script resides.
	 *
	 * For example: /path/to/site/
	 *
	 * @param bool $full
	 * 	If TRUE, return the full %URL 
	 * 	with the protocol and the domain
	 * @retval string
	 * 	Path to the script's folder
	 */
	public function GetFolder($full = FALSE)
	{
		if ($full)
		{
			return $this -> GetProtocol() . $this -> GetHostname() . $this -> folder;
		}
		
		return $this -> folder;
	}
	
	/*!
	 * @brief Get the current script
	 *
	 * Get the path and the script name
	 * we are currently running
	 *
	 * For example: /path/to/site/index.php
	 *
	 * @param bool $full
	 * 	If TRUE, return the full %URL 
	 * 	with the protocol and the domain
	 * @retval string
	 * 	A path to the current page with script
	 */
	public function GetScript($full = FALSE)
	{
		return $this -> GetFolder($full) . $this -> script;
	}
	
	/*!
	 * @brief Get the current controller path
	 *
	 * Get the path to the current controller 
	 * without the parameters
	 *
	 * @param bool $full
	 * 	If TRUE, return the full %URL 
	 * 	with the protocol and the domain
	 * @retval string
	 * 	A path to the current controller
	 */
	public function GetController($full = FALSE)
	{
		if ($this -> controller === FALSE)
		{
			return $this -> Generate('', $full);
		}
		else
		{
			return $this -> Generate($this -> controller, $full);
		}
	}
	
	/*!
	 * @brief Get the requested controller path
	 *
	 * Get the path to the requested controller 
	 * without the parameters
	 *
	 * @param bool $full
	 * 	If TRUE, return the full %URL 
	 * 	with the protocol and the domain
	 * @retval string
	 * 	A path to the current controller
	 */
	public function GetRequestedController($full = FALSE)
	{
		if ($this -> requested_controller === FALSE)
		{
			return $this -> Generate('', $full);
		}
		else
		{
			return $this -> Generate($this -> requested_controller, $full);
		}
	}
	
	/*!
	 * @brief Generate an absolute path to the controller
	 *
	 * Generate the path to the given controller, in a clean or 
	 * non-clean format depending on the current settings.
	 *
	 * For example, with $page being demo: /path/to/site/?q=demo
	 *
	 * @param string $controller
	 * 	The controller to create the path for
	 * @param bool $full
	 * 	If TRUE, return the full %URL 
	 * 	with the protocol and the domain
	 * @param bool $ajax
	 * 	If TRUE, return the path to the AJAX
	 * 	version of the controller
	 * @retval string
	 * 	The path to the specified $page
	 */
	public function Generate($controller, $full = FALSE, $ajax = FALSE)
	{
		// If we are using clean URLs, get the current folder 
		// and append the controller and the possible query 
		// string as they are
		if ((defined('CLEAN_URLS')) && (CLEAN_URLS))
		{
			return $this -> GetFolder($full) . ($ajax ? 'AJAX/' : '') . ltrim($controller, '/');
		}
		
		// Non-clean URLs, split the query string out from controller
		// if the separator uses query strings itself
		$query = '';
		if (strpos(URL_SEPARATOR, '?') !== FALSE)
		{
			$query = FALSE;
			$qstart = strpos($controller, '?');
			if ($qstart !== FALSE)
			{
				$query = substr($controller, $qstart + 1);
				$controller = substr($controller, 0, $qstart);
			}
			
			if (!empty($query))
			{
				$query = '&amp;' . $query;
			}
			else
			{
				$query = '';
			}
		}
		
		// Get the script and append the rebuilt URL 
		return $this -> GetScript($full) . URL_SEPARATOR . ($ajax ? 'AJAX/' : '') . ltrim($controller, '/') . $query;
	}
	
	/*!
	 * @brief Parse the markers from the text
	 *
	 * Parse the markers from the given output and replace them
	 * with paths and URLs to actual resources.
	 *
	 * Currently the following are supported:
       	 * - u:self - path to the current page
	 * - u:something - path to the page 'something'
	 * - f:self - full %URL to the current page
	 * - f:something - full %URL to the page 'something'
	 *
	 * @param string $output
	 * 	The text to parse
	 * @retval string
	 * 	The parsed text
	 */
	public function Parse($output)
	{
		// Binary mode, don't even try parsing
		if ($this -> binary)
		{
			return $output;
		}
		
		// This may give troubles, but it should sanitize most of the things into nice UTF-8
		// At least it stops breaks win Windows prints "Suomen kesäaika" in its own encoding
		if (function_exists('mb_convert_encoding'))
		{
			$output = mb_convert_encoding($output, 'utf-8', 'utf-8');
		}
		
		// Do the actual replacements
		$pattern = '#(?P<separator>["\s])(?P<mode>[uf]):(?P<action>self|ajax|folder|script|controller|handler)?(?P<path>.+?)?([?](?P<query>.+?))?\1#mu';
		$result = preg_replace_callback($pattern, array($this, 'Replace'), $output);
		if (preg_last_error() !== PREG_NO_ERROR)
		{
			switch (preg_last_error())
			{
			case PREG_INTERNAL_ERROR:
				$msg = _('Internal error');
				break;
				
			case PREG_BACKTRACK_LIMIT_ERROR:
				$msg = _('Backtrack limit exhausted');
				break;
				
			case PREG_RECURSION_LIMIT_ERROR:
				$msg = _('Recursion limit exhausted');
				break;
			
			case PREG_BAD_UTF8_ERROR:
				$msg = _('Bad UTF-8');
				break;
			
			case PREG_BAD_UTF8_OFFSET_ERROR:
				$msg = _('Bad UTF-8 offset');
				break;
			
			default:
				$msg = sprintf(_('Unknown error %d'), preg_last_error());
				break;	
			}
			
			trigger_error(sprintf(_('URL replacing failed: %s'), $msg), E_USER_WARNING);
			return sprintf(_('URL replacing failed: %s'), $msg);
		}
		return $result;
	}
	
	/*!
	 * @brief The replacing method
	 *
	 * This does the magic with the 
	 * data from preg_replace_callback
	 *
	 * @param array $matches
	 * 	The array from the preg_replace_callback
	 * @retval string
	 * 	The string to replace the match with
	 */
	public function Replace($matches)
	{
		// Build the path depending on the mode
		switch ($matches['mode'])
		{
		case 'u':
			$full = FALSE;
			break;
			
		case 'f':
			$full = TRUE;
			break;
		}
		
		// Build the path depending on the action
		switch ($matches['action'])
		{
		case 'self':
			$url = rtrim($this  -> GetSelf($full), '/');
			break;
			
		case 'ajax':
			$url = rtrim($this  -> GetSelfAJAX($full), '/');
			break;
			
		case 'folder':
			$url = rtrim($this  -> GetFolder($full), '/');
			break;
			
		case 'script':
			$url = rtrim($this  -> GetScript($full), '/');
			break;
			
		case 'controller':
			$url = rtrim($this  -> GetRequestedController($full), '/');
			break;
			
		case 'handler':
			$url = rtrim($this  -> GetController($full), '/');
			break;
			
		case '':
			if (!isset($matches['path']))
			{
				$matches['path'] = '';
			}
			$url = $this  -> Generate($matches['path'], $full);
			break;
		}
		
		// The requested path itself
		if ((isset($matches['path'])) && (!empty($matches['action'])))
		{
			$url .= '/' . ltrim($matches['path'], '/');
		}
		
		// And possible query string
		if ((isset($matches['query'])) && (!empty($matches['query'])))
		{
			// Get the query part from generated URL
			if (strpos($url, '?') !== FALSE)
			{
				list($url, $query) = explode('?', $url, 2);
			}
			else
			{
				$query ='';
			}
			
			// Turn query strings into arrays
			parse_str($query, $url_params);
			parse_str($matches['query'], $query_params);
			
			// Combine arrays; keys from query string overwrite keys from url
			$params = $query_params + $url_params;
			
			// Recreate the query string
			$url .= '?' . http_build_query($params);
		}
		
		// We don't want to output the stuff, so hide it
		ob_start();
		
		// Start with the first separator
		echo $matches['separator'];
		
		// Get the URL
		echo $url;
		
		// And end the same separator as we started with
		echo $matches['separator'];
		
		// Get the replaced URL
		$r = ob_get_contents();
		ob_end_clean();
		return $r;
	}
}
