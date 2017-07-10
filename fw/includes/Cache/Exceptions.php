<?php
namespace System\Cache;

/*!
 * @brief The not found exception
 *
 * Exception class that is thrown when 
 * the given key was not found
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class NotFound extends \Exception
{
	public function __construct($key, $code = 0, \Exception $previous = null)
	{
		parent::__construct(sprintf(_('The key "%s" was not found in the cache'), $key), $code, $previous);
	}
}

/*!
 * @brief The no backend exception
 *
 * Exception class that is thrown when 
 * there are no backends available
 *
 * $Author$
 * $Id$
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class NoBackend extends \Exception
{
	public function __construct($msg = '', $code = 0, \Exception $previous = null)
	{
		parent::__construct(_('No cache backend available'), $code, $previous);
	}
}
