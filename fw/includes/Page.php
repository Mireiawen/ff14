<?php

/*!
 * @brief A page controller interface
 *
 * An interface class for all page controllers
 * that are to be loaded from the Content class
 *
 * $Author: mireiawen $
 * $Id: Page.php 259 2015-06-02 21:59:46Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
interface Page
{
	/*!
	 * @brief Constructor for the class
	 *
	 * Class could, for example, check required 
	 * extensions here and throw exception 
	 * early if something is missing.
	 *
	 * @retval object
	 * 	Instance of the class itself
	 * @throws Exception on error
	 */
	public function __construct();
	
	/*!
	 * @brief Ask the page if it can handle the params
	 *
	 * Ask the page controller if it can handle page if
	 * it is called with given params.
	 *
	 * This should do most of the preparation work of the class.
	 *
	 * @retval mixed
	 * 	Boolean TRUE if page is handled, 
	 * 	boolean FALSE if not,
	 *   	a string if user is to be redirected to other page
	 */
	public function Handles($params);
	
	/*!
	 * @brief Show the page
	 * 
	 * This method is called when the actual page 
	 * should be shown.
	 *
	 * @retval string
	 * 	The actual page contents
	 */
	public function Show();

	/*!
	 * @brief Check if user can even try
	 * to access the page
	 *
	 * This function should return an array
	 * of access levels that are allowed 
	 * to access this page. If user does not have
	 * that access, then the rest of the methods
	 * are not called, but user is shown an error
	 * 
	 * @retval array 
	 * 	Array of acceptable access levels
	 */
	public function GetRequiredAccess();
}
