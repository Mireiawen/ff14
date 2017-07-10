<?php
// Check for MySQLi support
if (!extension_loaded('mysqli'))
{
	throw new Exception(_('MySQLi extension is required!'));
}

/*!
 * @brief MySQLi extension class
 *
 * Extend the MySQLi class functionality with our own methods
 *
 * $Author: mireiawen $
 * $Id: MySQLie.php 438 2017-07-10 16:20:48Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
class MySQLie extends mysqli
{
	/*!
	 * @brief The public constructor
	 *
	 * Construct the MySQLi class and set the 
	 * connection character set
	 *
	 * @param string $database 
	 * 	The database name to use
	 * @param string $username, $password 
	 * 	The account to log in with
	 * @param string $hostname 
	 * 	The database host to connect to
	 * @param string $charset
	 * 	The connection character set to use
	 * @retval object
	 * 	Instance of MySQLie class
	 * @throws Exception on connection errors
	 */
	public function __construct($database, $username, $password, $hostname, $charset = 'utf8')
	{
		parent::__construct($hostname, $username, $password, $database);
		if ($this -> errno)
		{
			throw new Exception(sprintf(_('Unable to connect to database: %s'), $this -> connect_error));
		}
		
		if (!$this -> set_charset($charset))
		{
			throw new Exception(sprintf(_('Unable to set character set: %s'), $this -> error));
		}
	}
	
	/*!
	 * @brief Truncate the table
	 *
	 * Truncates the given table to zero rows
	 *
	 * @param string $table
	 * 	Name of the table to be truncated
	 * @throws Exception on error
	 */
	public function truncate($table)
	{
		// Prepare the query
		$sql = sprintf('TRUNCATE TABLE %s', $this -> escape_identifier($table));
		$stmt = $this -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $this -> error));
		}
		
		// Execute it
		if (!$stmt -> execute())
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
	}
	
	/*!
	 * @brief Set the foreign key checks
	 *
	 * Set the foreign key checks on or off
	 *
	 * @param bool $state
	 * 	TRUE to turn foreign key checks on
	 * 	FALSE to turn foreign key checks off,,
	 * @throws Exception on error
	 */
	public function foreign_key_checks($state)
	{
		// Prepare the query
		$sql = 'SET FOREIGN_KEY_CHECKS = ?';
		$stmt = $this -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $this -> error));
		}

		
		// Bind the parameters
		$s = intval($state);
		if (!$stmt -> bind_param('i', $s))
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute it
		if (!$stmt -> execute())
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
	}
	
	/*!
	 * @brief Get the table auto increment value
	 *
	 * @param string $table
	 * 	Name of the table to get the auto increment from
	 * @retval int
	 * 	The auto increment value
	 * @throws Exception on error
	 */
	public function get_autoincrement($table)
	{
		// Prepare the query
		$sql = sprintf('SELECT %s FROM %s.%s WHERE %s = ? AND %s = ?',
			$this -> escape_identifier('AUTO_INCREMENT'),
			$this -> escape_identifier('information_schema'),
			$this -> escape_identifier('tables'),
			$this -> escape_identifier('table_name'),
			$this -> escape_identifier('table_schema'));
		$stmt = $this -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $this -> error));
		}
		
		// Bind the parameters
		$schema = get_class($this);
		if (!$stmt -> bind_param('ss', $table, $schema))
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Execute the query
		$row = $this -> fetch_first($stmt);
		if (isset($row['AUTO_INCREMENT']))
		{
			return $row['AUTO_INCREMENT'];
		}
	}
	
	/*!
	 * @brief Fetch the first row as an associative array
	 *
	 * Fetch the first row of the stmt and return
	 * the result as associative array
	 *
	 * @param mysqli_stmt $stmt
	 * 	A prepared MySQLi statement
	 * @retval array
	 * 	A result row as an associative array
	 * @throws Exception on error
	 */
	public function fetch_first(mysqli_stmt $stmt)
	{
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Easy way; but not compatible with old PHP
		if (method_exists($stmt, 'get_result'))
		{
			// Get result
			if (!$res = $stmt -> get_result())
			{
				throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
			}
			
			// Get first row
			$row = $res -> fetch_assoc();
			
			// Free the result and return rows
			$res -> free();
		}
		else
		{
			// Get the row metadata for assoc array
			$meta = $stmt -> result_metadata();
			while ($field = $meta -> fetch_field())
			{
				$params[] = &$row[$field -> name];
			}
			
			// Done with metadata, free it
			$meta -> free();
			
			// Bind the variables
			call_user_func_array(array($stmt, 'bind_result'), $params);
			
			// Get the row
			$stmt -> fetch();
			// Copy the array contents
			foreach ($row as $k => $v)
			{
				$sheep[$k] = $v;
			}
			$row = $sheep;
			
			// Free the result
			$stmt -> free_result();
		}
		
		// Return it
		return $row;
	}
	
	/*!
	 * @brief Fetch the result as an associative array
	 *
	 * Fetch all the result rows of the stmt and return
	 * those as associative array
	 *
	 * @param mysqli_stmt $stmt
	 * 	A prepared MySQLi statement
	 * @retval array
	 * 	An array of result rows
	 * @throws Exception on error
	 */
	public function fetch_assoc(mysqli_stmt $stmt)
	{
		// Execute the query itself
		if (!$stmt -> execute())
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Easy way; but not compatible
		if (method_exists($stmt, 'get_result'))
		{
			// Get result
			if (!$res = $stmt -> get_result())
			{
				throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
			}
			
			// Get all rows
			$rows = array();
			while ($row = $res -> fetch_assoc())
			{
				$rows[] = $row;
			}
			
			// Free the result and return rows
			$res -> free();
		}
		else
		{
			// Get the row metadata for assoc array
			$meta = $stmt -> result_metadata();
			while ($field = $meta -> fetch_field())
			{
				$params[] = &$row[$field -> name];
			}
			
			// Done with metadata, free it
			$meta -> free();
			
			// Bind the variables
			call_user_func_array(array($stmt, 'bind_result'), $params);
			
			// Get the rows
			$rows = array();
			while ($stmt -> fetch())
			{
				// Copy the array contents
				foreach ($row as $k => $v)
				{
					$sheep[$k] = $v;
				}
				$rows[] = $sheep;
			}
			
			// Free the result
			$stmt -> free_result();
		}
		return $rows;
	}
	
	/*!
	 * @brief Get the current autocommit status
	 *
	 * Get the current status autocommit status
	 * from the database
	 *
	 * @retval bool
	 * 	The autocommit status; TRUE if 
	 * 	it is on, FALSE if it is off
	 * @throws Exception on error
	 */
	public function get_autocommit()
	{
		// Prepare the query
		$sql = 'SELECT @@autocommit';
		$stmt = $this -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $this -> error));
		}
		
		// Get the actual result
		$row = $this -> fetch_first($stmt);
		
		// Return the status
		if (isset($row['@@autocommit']))
		{
			return (bool)($row['@@autocommit']);
		}
		
		// Status was not known, try to set it
		if (!$this -> autocommit(TRUE))
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $this -> error));
		}
		return TRUE;
	}
	
	/*!
	 * @brief Escape an identifier name 
	 *
	 * Escape an identifier name for the SQL query
	 *
	 * @retval string
	 * 	The escaped string
	 * @todo Is there better way to do this
	 */
	public function escape_identifier($name)
	{
		return '`' . $name . '`';
	}
}
