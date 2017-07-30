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
 * @brief The translation helper class
 *
 * A class to get the user language and load
 * the correct gettext translation files
 * based on the given language.
 *
 * $Author: mireiawen $
 * $Id: Translation.php 448 2017-07-11 22:19:58Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
final class Translation extends Base
{
	/*!
	 * @brief Use the Singleton trait
	 */
	use Singleton;
	
	/*!
	 * @brief The protected constructor
	 *
	 * In addition to building the Base class,
	 * this constructor checks that the required
	 * environment is set and then tries its best 
	 * to  detect the user's language.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 */
	protected function __construct()
	{
		// Make sure the system language domain is set
		if (!defined('LANGUAGE_DOMAIN_SYSTEM'))
		{
			define('LANGUAGE_DOMAIN_SYSTEM', 'RoseFramework');
		}
		
		// Check for extensions
		if (!extension_loaded('intl'))
		{
			throw new \Exception(_('Internationalization extension intl is required!'));
		}
		
		// Check for translation path
		if (!defined('TRANSLATION_PATH'))
		{
			define('TRANSLATION_PATH', APPLICATION_PATH . '/translations/');
		}
		
		// Construct the parent
		parent::__construct();
		$this -> lang = '';
		
		// Try Accept-Language
		if ((is_array($_SERVER)) && (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)))
		{
			$lang = locale_accept_from_http(@$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		}
		
		// Default language
		else
		{
			$lang = LANGUAGE_DEFAULT;
		}
		
		// Load from session
		if ((class_exists('\\System\\Session')) && (isset($_SESSION['lang'])))
		{
			$lang = $_SESSION['lang'];
		}
		
		// Load from user input
		if ((is_array($_REQUEST)) && (array_key_exists('lang', $_REQUEST)))
		{
			$lang = filter_var($_REQUEST['lang'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
		}
		
		// Set up translation
		$this -> TranslateTo($lang);
	}
	
	/*!
	 * @brief Get the currently selected language
	 *
	 * Get the user's currently selected language
	 *
	 * @retval string
	 * 	Current language code
	 */
	public function GetLang()
	{
		return $this -> lang;
	}
	
	/*!
	 * @brief Set up the environment and load the translation
	 *
	 * Do our best to set up the environment to
	 * load the correct translation file for our
	 * desired language
	 *
	 * @param string $lang
	 * 	The language to use for loading, if empty try default
	 * @param string $domain
	 * 	The application language domain to use
	 * @param string $codeset
	 * 	The codeset to use for language file
	 * @todo validate the given language
	 */
	public function TranslateTo($lang, $domain=LANGUAGE_DOMAIN_SYSTEM, $codeset='UTF-8')
	{
		// Make sure we have a language
		if (empty($lang))
		{
			$lang = LANGUAGE_DEFAULT;
		}
		
		// TODO: validate the language

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			// Windows doesn't know of LC_MESSAGES, use LC_ALL on it
			setlocale(LC_ALL, $lang);
		}
		else
		{
			// Just translate the messages
			setlocale(LC_MESSAGES, $lang);
		}
		
		if ((strcmp($domain, LANGUAGE_DOMAIN_SYSTEM)) && (is_dir(TRANSLATION_PATH)))
		{
			$path = bindtextdomain($domain, TRANSLATION_PATH);
		}
		else
		{
			$path = bindtextdomain($domain, SYSTEM_PATH . '/translations/');
		}
		$domain = textdomain($domain);
		$codeset = bind_textdomain_codeset($domain, $codeset);
		
		// Set it up our variable
		$this -> lang = $lang;
		
		// Set it up in session
		if (class_exists('\\System\\Session'))
		{
			$_SESSION['lang'] = $lang;
		}
	}
}
