<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load base class
require_once(SYSTEM_PATH . '/includes/Base.php');

// Load the Page interface
require_once(SYSTEM_PATH . '/includes/Page.php');

/*!
 * @brief The content handler class
 *
 * A class that does the work of finding the correct controller
 * for the requested %URL, checking permissions and showing the 
 * error pages.
 * 
 * This is a final class, and should not be tried to extend.
 * 
 * $Author: mireiawen $
 * $Id: Content.php 446 2017-07-11 22:10:31Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Content extends Base
{
	/*!
	 * @brief The public constructor
	 *
	 * A public constructor that makes sure we have
	 * some default constants defined, loads the 
	 * Base class and then sets up the default
	 * values.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	public function __construct()
	{
		// Make sure CONTROLLER_EXTENSION is defined
		if (!defined('CONTROLLER_EXTENSION'))
		{
			define('CONTROLLER_EXTENSION', '.php');
		}
		
		// Set up the default controller name
		if (!defined('CONTROLLER_DEFAULT'))
		{
			define('CONTROLLER_DEFAULT', 'Index');
		}
		
		// Set up the root path
		if (!defined('CONTROLLER_PATH_ROOT'))
		{
			define('CONTROLLER_PATH_ROOT', '/');
		}
		
		// Let the parent handle the first construction
		parent::__construct(TRUE);
		
		// List of handlers we have tried
		$this -> tried = array();
		
		// The headers to add to the page
		$this -> headers = array();
		
		// HTTP PUT we have read so far
		$this -> request_body = '';
	}
	
	/*!
	 * @brief Find and show the actual content
	 *
	 * This is the method that does the work of 
	 * detecting the correct controller for the request,
	 * be it AJAX or page request, then making sure 
	 * the currently logged in user has enough 
	 * permissions to try to open the requested page,
	 * then check if the controller can handle the 
	 * request and finally display the results from
	 * the controller back to the browser.
	 *
	 * This method has help from the GetController
	 * method in detecting the actual controller.
	 * 
	 * @retval boolean
	 * 	On success returns TRUE
	 * @throws Exception on any errors
	 */
	public function Show()
	{
		// Get the page from URL
		$path_info = URL::Get() -> GetPathInfo();
		$page = CONTROLLER_PATH_ROOT . $path_info;
		$orig = $page;
		
		// Check for AJAX queries
		$ajax = FALSE;
		if (StartsWith($path_info, 'AJAX/'))
		{
			// Set the error handler in AJAX mode
			Error::Get() -> Hide();
			Error::Get() -> AJAX();
			
			// Trim the path
			$page = rtrim(CONTROLLER_PATH_ROOT, '/') . substr($path_info, 4);
			
			// The last parameter should be the method to call,
			// make sure we don't end with slash
			if (substr($page, -1, 1) === '/')
			{
				http_response_code(500);
				header('Content-Type: application/json');
				echo json_encode(sprintf(_('Invalid AJAX call %s'), $orig));
				return FALSE;
			}
			
			// Get the method out of the path
			$info = pathinfo($page);
			if ((isset($info['dirname'])) && (!empty($info['dirname'])))
			{
				$page = $info['dirname'];
			}
			else
			{
				$page = '/';
			}
			
			// Store the AJAX method name
			$method = $info['basename'];
			
			// Make sure we don't try to call methods 
			// that system requires but might give out
			// information we don't want to
			if (
				(substr($method, 0, 1) === '_')
				|| (!strcasecmp($method, 'Handles'))
				|| (!strcasecmp($method, 'Show'))
				|| (!strcasecmp($method, 'GetRequiredAccess'))
			)
			{
				http_response_code(500);
				header('Content-Type: application/json');
				echo json_encode(sprintf(_('Invalid AJAX call %s'), $orig));
				return FALSE;
			}
			
			// Try to read JSON input
			$data = $this -> ReadJSON();
			
			// AJAX is prepared
			$ajax = TRUE;
		}
		
		// Catch exceptions, mainly for AJAX requirements
		try
		{
			// Set up the requested page 
			$request_page = rtrim($page, '/');
			if (empty($request_page))
			{
				$request_page = '/';
			}
			
			URL::Get() -> SetRequestedControlPath($request_page);
			$p = $this -> GetController($page, $params);
			
			// Check permissions
			if (!LoginUser::Get() -> HasAnyAccessLevel($p -> GetRequiredAccess()))
			{
				// Try to show login page
				if (LoginUser::Get() -> GetID() === USER_ID_UNKNOWN)
				{
					$p = $this -> GetController('/User/Login', $params);
				}
				
				// User is logged in, show acces denied
				else
				{
					// Show more detailed error when debugging
					if ((defined('DEBUG')) && (DEBUG))
					{
						trigger_error(sprintf(_('Access to "%s" is denied for %s'), $page, LoginUser::Get() -> GetUsername()), E_USER_WARNING);
					}
					
					// Just tell it was denied
					else
					{
						trigger_error(_('Access denied'), E_USER_WARNING);
					}
					$p = $this -> GetController('/Errors/401', $params);
				}
			}
			
			// Check for AJAX method in controller
			if ($ajax)
			{
				if (
					(!in_array($method, get_class_methods($p))) 
					|| (!is_callable(array($p, $method)))
				)
				{
					http_response_code(404);
					header('Content-Type: application/json');
					echo json_encode(sprintf(_('Invalid AJAX call %s'), $orig));
					return TRUE;
				}
				
				// Longpoll mode means script will handle its output by itself
				if (
					(in_array('UseLongpoll', get_class_methods($p)))
					&& (is_callable(array($p, 'UseLongpoll')))
					&& (call_user_func(array($p, 'UseLongpoll'), $method))
				)
				{
					return call_user_func_array(array(&$p, $method), $data);
				}
				else
				{
					// Get the output
					ob_start();
					$page = json_encode(call_user_func_array(array(&$p, $method), $data));
					ob_end_clean();
					header('Content-Type: application/json');
					echo $page;
					return TRUE;
				}
			}
		}
		
		// Catch exception if we got one
		catch (\Exception $e)
		{
			// Not AJAX request, just throw it again
			// TODO: could we catch it and show error here instead?
			if (!$ajax)
			{
				throw $e;
			}
			
			// Process the error message
			header('Content-Type: application/json');
			echo json_encode($e -> getMessage());
			return TRUE;
		}
		
		// Ok, show the page
		ob_start();
		$template = $p -> Show();
		$page = ob_get_contents();
		ob_end_clean();
		
		// Show the page
		$this -> ShowContents($page, $template);
		return TRUE;
	}
	
	/*!
	 * @brief The helper function to load a CSS file in the header
	 *
	 * Add a header in to the template for loading an additional 
	 * CSS file from within the page template
	 *
	 * @param string $css
	 * 	Path to the CSS file
	 */
	public function LoadCSS($css)
	{
		$this -> AddCustomHeader('<link href="' . $css . '" rel="stylesheet">');
	}
	
	/*!
	 * @brief The helper function to load a JS file in the header
	 *
	 * Add a header in to the template for loading an additional 
	 * javascript file in the template headers
	 *
	 * @param string $js
	 * 	Path to the JS file
	 */
	public function LoadJS($js)
	{
		$this -> AddCustomHeader('<script src="' . $js . '"></script>');
	}
	
	/*!
	 * @brief The helper function to add additional 
	 * tags into the template header
	 *
	 * Add an additional header to the page template 
	 * headers. The $text needs to be a valid tag for 
	 * the template code, like HTML tag.
	 *
	 * There is no promise to process the headers in any 
	 * specific order!
	 *
	 * @param string $text
	 * 	The text to add
	 */
	public function AddCustomHeader($text)
	{
		$this -> headers[] = $text;
	}
	
	/*!
	 * @brief The helper function to find the correct controller
	 *
	 * This method tries to recursively find the correct
	 * controller for the request.
	 *
	 * This may trigger error with E_USER_WARNING level.
	 *
	 * @param string $page
	 * 	Path to the page that was requested
	 * @param [in,out] array $params
	 * 	Parameters from the %URL for the controller
	 * @param bool $system
	 * 	If TRUE, the page should be searched from the System 
	 * 	space instead of the application space
	 * @retval object
	 * 	Instance of the controller class implementing the Page interface
	 * @throws Exception on a fatal error
	 */
	private function GetController($page, &$params, $system = FALSE)
	{
		if (!is_string($page))
		{
			return $this -> GetController('/Errors/404', $params);
		}
		
		// Save the original path
		$orig = $page;
		$orig_params = $params;
		
		// Check for empty path, default it to the root
		if (empty($page))
		{
			$page = '/';
		}
		
		// Make sure path starts with a slash
		if (substr($page, 0, 1) !== '/')
		{
			return $this -> GetController('/Errors/404', $params);
		}
		
		// Build the filesystem base directory path
		if ($system)
		{
			$base = SYSTEM_PATH . '/pages/';
		}
		else
		{
			$base = CONTROLLER_PATH . '/';
		}
		
		// Try to find the route to controller
		$params = array();
		while (true)
		{
			// Check for the default page in a folder
			$filename = rtrim($base . trim($page, '/'), '/') . '/' . CONTROLLER_DEFAULT . CONTROLLER_EXTENSION;
			$filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
			if ((is_readable($filename))
				&& (is_file($filename))
				&& (!in_array($filename, $this -> tried))
			)
			{
				break;
			}
			
			// Check if we are at the root and did not find default
			if ($page === DIRECTORY_SEPARATOR)
			{
				// Page was not found from system either
				if ($system)
				{
					// Show more detailed error when debugging
					if ((defined('DEBUG')) && (DEBUG))
					{
						Error::Message(sprintf(_('Page "%s" not found'), $orig), ERROR_LEVEL_ERROR);
					}
					
					// Just tell it was not found
					else
					{
						Error::Message(_('Page not found'), ERROR_LEVEL_ERROR);
					}
					
					// Try to show a common 404 page
					if (strncasecmp($orig, '/Errors/', strlen('/Errors/')))
					{
						$params = array();
						return $this -> GetController('/Errors/404', $params);
					}
					
					// Were we looking for error already? If so, then throw Exception
					else
					{
						// Show more detailed error when debugging
						if ((defined('DEBUG')) && (DEBUG))
						{
							throw new \Exception(sprintf(_('Page "%s" not found'), $orig));
						}
						
						// Just tell it was not found
						else
						{
							throw new \Exception(_('Page not found'));
						}
					}
				}
				
				// Try to find the page from System
				$params = array();
				return $this -> GetController($orig, $params, TRUE);
			}
			
			// Check for the script 
			$filename = rtrim($base . trim($page, '/'), '/') . CONTROLLER_EXTENSION;
			$filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
			if ((is_readable($filename))
				&& (is_file($filename))
				&& (!in_array($filename, $this -> tried))
			)
			{
				break;
			}
			
			// Controller was not found yet, 
			// go up one level in the tree
			$info = pathinfo($page);
			if ((isset($info['dirname'])) && (!empty($info['dirname'])))
			{
				$page = $info['dirname'];
				$params[] = $info['basename'];
				continue;
			}
			
			$page = '/Errors/404';
		}
		
		// Set up the current controller path
		if (class_exists('\\System\\URL'))
		{
			// Strip the base path from it
			if (StartsWith($page, CONTROLLER_PATH_ROOT))
			{
				URL::Get() -> SetCurrentControlPath(substr($page, strlen(CONTROLLER_PATH_ROOT)));
			}
		}
		
		// Get the namespaced class name
		$class = '\\' . str_replace('/', '\\', substr($filename, strlen($base), -1 * strlen(CONTROLLER_EXTENSION)));
		if ($system)
		{
			$class = '\\System' . $class;
		}
		
		else if ((defined('USE_PAGESPACE')) && (USE_PAGESPACE))
		{
			$class = '\\Page' . $class;
		}
		
		// Load the file itself
		if ((!file_exists($filename)) || (!is_readable($filename)))
		{
			throw new \Exception(sprintf(_('Page "%s" does not exist or is not readable'), $class));
		}
		
		require_once($filename);
		$this -> tried[] = $filename;
		
		// Try to load it as controller class
		if (!class_exists($class, FALSE))
		{
			throw new \Exception(sprintf(_('Page does not implement required class "%s"'), $class));
		}
		
		// Make sure the class implements our controller interface
		$refl = new \ReflectionClass($class);
		if (!$refl -> implementsInterface('\System\Page'))
		{
			throw new \Exception(sprintf(_('Class "%s" does not implement required interface "%s"'), $class, 'Page'));
		}
		
		// Create instance of the page 
		$p = new $class();
		
		// Do access rights check before checking handling
		if (!LoginUser::Get() -> HasAnyAccessLevel($p -> GetRequiredAccess()))
		{
			// Page exists, but user is not allowed to see it
			$params = array();
			return $this -> GetController('/Errors/401', $params, $system);
		}
		
		$r = $p -> Handles($params);
		if ($r === FALSE)
		{
			// Continue the handler loop in this case
			return $this -> GetController($orig, $orig_params, $system);
		}
		if ($r !== TRUE)
		{
			$params = array();
			return $this -> GetController($r, $params, $system);
		}
		return $p;
	}
	
	/*!
	 * @brief Show the contents with the template
	 *
	 * Show the given content with the default HTML
	 * template unless non-templated output is 
	 * requested. Will parse the content for links
	 * defined with the URL class to the output, 
	 * and will add any messages from the Error class to 
	 * the templated output.
	 *
	 * @param string $content
	 * 	The actual page contents
	 * @param bool $template
	 * 	Set to TRUE if the content should
	 * 	be output inside the template.
	 */
	private function ShowContents($content, $template = TRUE)
	{
		// Output template
		if (($template) && ($this -> Smarty -> templateExists('html.tpl.html')))
		{
			// Assign the headers
			$this -> Smarty -> assign('headers', implode("\n", $this -> headers));
			
			// Get errors
			ob_start();
			Error::Get() -> Show();
			$this -> Smarty -> assign('errors', ob_get_contents());
			ob_end_clean();
			$this -> Smarty -> assign('content', $content);
			echo URL::Get() -> Parse($this -> Smarty -> fetch('html.tpl.html'));
		} 
		
		// No template, just send out contents
		else
		{
			echo URL::Get() -> Parse($content);
		}
	}
	
	/*!
	 * @brief JSON input reader
	 * 
	 * Does the JSON data reading from the HTTP PUT data
	 * as well as some basic error handling.
	 *
	 * @return mixed
	 * 	Decoded JSON data, empty array if no input
	 * @throws Exception if the data was not valid
	 */
	private function ReadJSON()
	{
		// Read the request body
		if (empty($this -> request_body))
		{
			$this -> request_body = file_get_contents('php://input');
		}

		// If it is still empty, just return empty array
		if (empty($this -> request_body))
		{
			return array();
		}
		
		// Convert it
		$data = json_decode($this -> request_body, TRUE);
		if ($data === NULL)
		{
			throw new \Exception(_('Unable to read JSON data'));
		}
		
		// And return the array
		return $data;
	}
}
