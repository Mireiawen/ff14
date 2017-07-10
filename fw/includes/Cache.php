<?php
// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load the cache backend handler
require_once(SYSTEM_PATH . '/includes/Cache/Backend.php');

/*!
 * @brief A trait for caching the data
 * 
 * A trait to hold the cache helper methods that
 * handle the session specific data exchange with
 * the caching backend.
 *
 * @attention
 * This class requires that either the trait using class
 * extends from the Base class, or implements similar 
 * functionality and the $vars array in another way.
 * 
 * $Author: mireiawen $
 * $Id: Cache.php 427 2017-06-05 19:49:55Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 * @todo No cache backend exists as of now
 */
trait Cache
{
	/*!
	 * @brief Load the data from the cache
	 * 
	 * A helper method to get the data from
	 * the cache backend into the vars array for
	 * the current session ID.
	 *
	 * If the call is successful, the $vars array
	 * of the caller class is populated with the
	 * data from the cache backend.
	 *
	 * If data could not be retrieved, returns FALSE
	 * and the caller should load the data in a 
	 * traditional way.
	 * 
	 * @param string $id
	 * 	The class specific ID used to save the data with,
	 * 	empty string to use the current object ID
	 * @param bool $private
	 * 	True if the value is private for the session,
	 * 	false if the value is global to the application
	 * 	
	 * @retval bool
	 * 	On success returns boolean TRUE,
	 * 	on failure returns boolean FALSE
	 */
	protected function GetFromCache($id = '', $private = TRUE)
	{
		// Make sure we have cache backend
		if (!class_exists('System\Cache\Backend'))
		{
			return FALSE;
		}
		
		// Make sure session exists
		if (($private) && (!class_exists('Session')))
		{
			return FALSE;
		}
		
		// Make sure we have a ID
		if (empty($id))
		{
			$id = $this -> GetID();
		}
		
		// Check we have unique ID
		if (!$id)
		{
			return FALSE;
		}
		
		// Generate ID
		if ($private)
		{
			$id = get_class($this) . '_' . md5($id) . '_' . md5(Session::Get() -> GetSID());
		}
		else
		{
			$id = get_class($this) . '_' . $id . '_public';
		}
		
		// Project namespace
		if (defined('CACHE_NAMESPACE'))
		{
			$id = CACHE_NAMESPACE . '_' . $id;
		}
		
		// Get the data
		try
		{
			$data = System\Cache\Backend::Get() -> Fetch($id);
		}
		
		// Catch for errors
		catch (\Exception $e)
		{
			// Not in cache, ignore it
			if ($e instanceof System\Cache\NotFound)
			{
				return FALSE;
			}
			
			// Set up error message if debugging
			if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_CACHE')) && (DEBUG_CACHE))
			{
				Error::Message(sprintf(_('Unable to retrieve key %s from cache: %s'), $id, $e -> getMessage()), ERROR_LEVEL_WARNING);
			}
			return FALSE;
		}
		
		// We can always expect encoded array
		if ($data === FALSE)
		{
			return FALSE;
		}
		
		// Unserialize it
		return unserialize($data);
	}
	
	/*!
	 * @brief Save the data to the cache
	 * 
	 * A helper method to put the data in the 
	 * $vars array into the cache backend with
	 * the current session ID.
	 *
	 * If the call is successful, the $vars array
	 * of the caller class is saved to the 
	 * cache backend.
	 *
	 * If data could not be saved, returns FALSE
	 * and the caller could try to save the data 
	 * to other places if it requires storing.
	 * 
	 * @param string $id
	 * 	The class specific ID used to save the data with,
	 * 	empty string to use the current object ID
	 * @param bool $private
	 * 	True if the value is private for the session,
	 * 	false if the value is global to the application
	 * @param int $ttl
	 * 	The time the key should live in the cache,
	 * 	0 to not timeout
	 * 	@note may not be supported by all cache backends
	 * 	
	 * @retval bool
	 * 	On success returns boolean TRUE,
	 * 	on failure returns boolean FALSE
	 */
	protected function SaveToCache($data, $id = '', $private = TRUE, $ttl = 3600)
	{
		// Make sure we have cache backend
		if (!class_exists('System\Cache\Backend'))
		{
			return FALSE;
		}
		
		// Make sure session exists
		if (($private) && (!class_exists('Session')))
		{
			return FALSE;
		}
		
		// Make sure we have a ID
		if (empty($id))
		{
			$id = $this -> GetID();
		}
		
		// Check we have unique ID
		if (!$id)
		{
			return FALSE;
		}
		
		// Generate ID
		if ($private)
		{
			$id = get_class($this) . '_' . md5($id) . '_' . md5(Session::Get() -> GetSID());
		}
		else
		{
			$id = get_class($this) . '_' . $id . '_public';
		}
		
		// Project namespace
		if (defined('CACHE_NAMESPACE'))
		{
			$id = CACHE_NAMESPACE . '_' . $id;
		}
		
		// Store the data
		try
		{
			System\Cache\Backend::Get() -> Store($id, serialize($data), $ttl);
			return TRUE;
		}

		// Catch for errors
		catch (\Exception $e)
		{
			// Set up error message if debugging
			if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_CACHE')) && (DEBUG_CACHE))
			{
				Error::Message(sprintf(_('Unable to store the key %s to cache: %s'), $id, $e -> getMessage()), ERROR_LEVEL_WARNING);
			}
			return FALSE;
		}
	}
	
	/*!
	 * @brief Remove the data from the cache
	 * 
	 * A helper method to remove the data in the 
	 * cache backend with the current session ID.
	 *
	 * If the call is successful, $id is removed 
	 * from the cache backend.
	 * 
	 * @param string $id
	 * 	The class specific ID used to save the data with,
	 * 	empty string to use the current object ID
	 * @param bool $private
	 * 	True if the value is private for the session,
	 * 	false if the value is global to the application
	 *
	 * @retval bool
	 * 	On success returns boolean TRUE,
	 * 	on failure returns boolean FALSE
	 */
	protected function RemoveFromCache($id = '', $private = TRUE)
	{
		// Make sure we have cache backend
		if (!class_exists('System\Cache\Backend'))
		{
			return FALSE;
		}
		
		// Make sure session exists
		if (($private) && (!class_exists('Session')))
		{
			return FALSE;
		}
		
		// Make sure we have a ID
		if (empty($id))
		{
			$id = $this -> GetID();
		}
		
		// Check we have unique ID
		if (!$id)
		{
			return FALSE;
		}
		
		// Generate ID
		if ($private)
		{
			$id = get_class($this) . '_' . md5($id) . '_' . md5(Session::Get() -> GetSID());
		}
		else
		{
			$id = get_class($this) . '_' . $id . '_public';
		}
		
		// Project namespace
		if (defined('CACHE_NAMESPACE'))
		{
			$id = CACHE_NAMESPACE . '_' . $id;
		}
		
		// Flush the data
		try
		{
			System\Cache\Backend::Get() -> Flush($id);
		}
		
		catch (\Exception $e)
		{
			// Not in cache, ignore it
			if ($e instanceof System\Cache\NotFound)
			{
				return TRUE;
			}
			
			// Set up error message if debugging
			if ((defined('DEBUG')) && (DEBUG) && (defined('DEBUG_CACHE')) && (DEBUG_CACHE))
			{
				Error::Message(sprintf(_('Unable to flush key %s from cache: %s'), $id, $e -> getMessage()), ERROR_LEVEL_WARNING);
			}
			return FALSE;
		}
	}
}
