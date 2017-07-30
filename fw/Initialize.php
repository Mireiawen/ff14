<?php
/**
 * Framework Initializer
 *
 * Copyright Mireiawen Rose
 * 
 * This file handles the initialization of the framework
 * You can load it with your index.php
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

/*!
 * @brief Define _ function for translations if it is missing
 * 
 * Make sure the _ -function is available, as it is used
 * but intl extension might not have been loaded
 *
 * @note This does not do any actual translation
 *
 * @param string $text
 * 	The text to translate
 * @retval string
 * 	The translated text
 */
if (!function_exists('_'))
{
	function _($text)
	{
		return $text;
	}
}


try
{
	// We have no content handler loaded yet
	$page = FALSE;
	
	// Validate PHP version
	if (version_compare(PHP_VERSION, '5.6.0') < 0)
	{
		throw new Exception(sprintf(_('PHP version must be at least %s'), '5.6'));
	}
	
	// Validate the system path
	if (!defined('SYSTEM_PATH'))
	{
		define('SYSTEM_PATH', realpath(dirname(__FILE__)));
	}
	
	if (!is_dir(SYSTEM_PATH))
	{
		throw new Exception(sprintf(_('%s "%s" was not found or was not directory'), 'SYSTEM_PATH', SYSTEM_PATH));
	}
	
	// Validate the application path
	if (!defined('APPLICATION_PATH'))
	{
		define('APPLICATION_PATH', realpath(SYSTEM_PATH . '/application'));
	}
	
	if (!is_dir(APPLICATION_PATH))
	{
		throw new Exception(sprintf(_('%s "%s" was not found or was not directory'), 'APPLICATION_PATH', APPLICATION_PATH));
	}
	
	// Load configuration variables
	if ((!is_readable(APPLICATION_PATH . '/config.inc.php')) 
		|| (!is_file(APPLICATION_PATH . '/config.inc.php')))
	{
		throw new Exception(sprintf(_('File "%s" does not exist or is not readable'), APPLICATION_PATH . '/config.inc.php'));
	}
	require_once(APPLICATION_PATH . '/config.inc.php');
	
	// Validate Application MVC paths
	if (!defined('MODEL_PATH'))
	{
		define('MODEL_PATH', realpath(APPLICATION_PATH . '/includes'));
	}
	
	if (!is_dir(MODEL_PATH))
	{
		throw new Exception(sprintf(_('%s "%s" was not found or was not directory'), 'MODEL_PATH', MODEL_PATH));
	}
	
	if (!defined('VIEW_PATH'))
	{
		define('VIEW_PATH', realpath(APPLICATION_PATH . '/templates'));
	}
	
	if (!is_dir(VIEW_PATH))
	{
		throw new Exception(sprintf(_('%s "%s" was not found or was not directory'), 'VIEW_PATH', VIEW_PATH));
	}

	if (!defined('CONTROLLER_PATH'))
	{
		define('CONTROLLER_PATH', realpath(APPLICATION_PATH . '/pages'));
	}
	
	if (!is_dir(CONTROLLER_PATH))
	{
		throw new Exception(sprintf(_('%s "%s" was not found or was not directory'), 'CONTROLLER_PATH', CONTROLLER_PATH));
	}
	
	// Check Smarty cache path
	if (!defined('CACHE_PATH'))
	{
		define('CACHE_PATH', realpath(APPLICATION_PATH . '/cache'));
	}
	
	if ((!is_dir(CACHE_PATH)) || (!is_writable(CACHE_PATH)))
	{
		throw new Exception(sprintf(_('%s "%s" was not found, was not directory or is not writable'), 'CACHE_PATH', CACHE_PATH));
	}
	
	// Check session save path
	if (!defined('SESSION_PATH'))
	{
		define('SESSION_PATH', realpath(APPLICATION_PATH . '/sessions'));
	}
	
	if ((!is_dir(SESSION_PATH)) || (!is_writable(SESSION_PATH)))
	{
		throw new Exception(sprintf(_('%s "%s" was not found, was not directory or is not writable'), 'SESSION_PATH', SESSION_PATH));
	}
	
	// Make sure we have timezone set
	if (!defined('TIMEZONE'))
	{
		define('TIMEZONE', 'UTC');
	}
	
	// Set up default timezone
	@date_default_timezone_set(TIMEZONE);
	
	// Load utility functions
	require_once(SYSTEM_PATH . '/includes/Utils.php');
	
	// Make sure session identifier is set
	if (!defined('SESSION_IDENTIFIER'))
	{
		define('SESSION_IDENTIFIER', 'RFSess');
	}
	
	// Load session
	require_once(SYSTEM_PATH . '/includes/Session.php');
	\System\Session::Create(SESSION_IDENTIFIER);
	
	// Translation
	require_once(SYSTEM_PATH . '/includes/Translation.php');
	\System\Translation::Create();
	
	// Templating framework
	require_once(SYSTEM_PATH . '/includes/SmartyInstance.php');
	$Smarty = \System\SmartyInstance::Create();
	
	// Set up paths
	$Smarty -> setTemplateDir(array(VIEW_PATH, SYSTEM_PATH . '/templates'));
	$Smarty -> setCompileDir(CACHE_PATH);
	$Smarty -> setCacheDir(CACHE_PATH);
	$Smarty -> setConfigDir(VIEW_PATH);
	if (defined('TEMPLATE_CONFIGURATION')) 
	{
		$Smarty -> configLoad(TEMPLATE_CONFIGURATION);
	}
	
	// Set default page title to avoid errors with default template
	$Smarty -> assign('title', _('Rose Framework - No title set'));
	
	// Error handler
	require_once(SYSTEM_PATH . '/includes/Error.php');
	\System\Error::Create();
}

// Error handler should take over now, catch exceptions before it
catch (Exception $e)
{
	header('Content-type: text/plain');
	echo _('Error during initialization: ') , $e -> getMessage() , "\n";
	exit($e -> getCode());
}

// Initialize database
if ((defined('DATABASE')) && (DATABASE)	)
{
	// Database backend
	if (!extension_loaded('mysqli'))
	{
		trigger_error(_('MySQLi extension is required!'), E_USER_ERROR);
	}
	
	require_once(SYSTEM_PATH . '/includes/Database.singleton.php');
	\System\Database::Create(DATABASE, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_HOSTNAME);
}

// Load the caching backend
require_once(SYSTEM_PATH . '/includes/Cache/Backend.php');
\System\Cache\Backend::Create();

// Load user info
require_once(SYSTEM_PATH . '/includes/LoginUser.php');
\System\LoginUser::Create();

// Load geolocation
require_once(SYSTEM_PATH . '/includes/Geolocation.php');
\System\Geolocation::Create();

// Load logger
require_once(SYSTEM_PATH . '/includes/Log.php');
\System\Log::Create();

// Load URL generator
require_once(SYSTEM_PATH . '/includes/URL.php');
\System\URL::Create();

// User login
\System\LoginUser::Get() -> ReadLogin();

// User specific initialization
if (is_readable(APPLICATION_PATH . '/Initialize.php'))
{
	require_once(APPLICATION_PATH . '/Initialize.php');
}

// Class that handles page loading
if ((!defined('LOAD_CONTENT')) || (LOAD_CONTENT))
{
	require_once(SYSTEM_PATH . '/includes/Content.php');
	
	$page = new \System\Content();
	\System\SmartyInstance::Get() -> assign('Template', $page);
	\System\SmartyInstance::Get() -> assign('Language', \System\Translation::Get() -> GetLang());
	$page -> Show();
}
