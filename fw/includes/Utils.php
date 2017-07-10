<?php
/*!
 * @file Utils.php Utility functions
 *
 * Utility functions that are not part of any class
 * and cannot be easily grouped into classes
 *
 * $Id: Utils.php 383 2015-10-07 22:42:20Z mireiawen $
 * $Author: mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */

/*!
 * @brief Check if string starts with another
 *
 * Check if the string $haystack begins with the string $needle
 *
 * @param string $haystack
 * 	The string to test 
 * @param string $needle
 * 	The string to test with
 * @retval bool
 * 	TRUE if the $haystack starts with $needle,
 * 	FALSE otherwise
 */
function StartsWith($haystack, $needle)
{
	$v = strpos($haystack, $needle);
	if ($v === FALSE)
	{
		return FALSE;
	}
	if ($v === 0)
	{
		return TRUE;
	}
	return FALSE;
}

/*!
 * @brief Generate a version 4 UUID
 *
 * This function generates a truly 
 * random UUID of version 4 with OpenSSL
 *
 * @see [IETF specification](http://tools.ietf.org/html/rfc4122#section-4.4)
 * @see [Wikipedia definition](http://en.wikipedia.org/wiki/UUID)
 * @retval binary
 * 	Generated 128-bit UUID
 */
function UUIDv4()
{
	$data = openssl_random_pseudo_bytes(16);
	assert(strlen($data) === 16);
	
	// set version to 0100 (4)
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	
	// set bits 6-7 to 10
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
	
	return $data;
}

/*!
 * @brief Convert UUID binary into string
 *
 * Converts UUID binary data into a human
 * readable string.
 *
 * @param binary $data
 * 	The UUID data
 * @retval string
 * 	Printable string version of the UUID
 */
function FormatUUID($data)
{
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/*!
 * @brief Check if the given IP address is in the given netmask
 *
 * Check if the given IP address, either IPv4 or IPv6, 
 * is in the given netmask.
 *
 * @param string $address
 * 	String form of the IP address
 * @param string $prefix
 * 	CIDR notation of the netmask as string
 *
 * @retval bool
 * 	TRUE if the given address is in the prefix
 * 	FALSE if the given address is not in the prefix
 * @throws Exception with the message if the address or prefix is invalid
 */
function IsAddressInNetmask($address, $prefix)
{
	// Make sure the address is a valid IP
	$ip = filter_var($address, FILTER_VALIDATE_IP);
	if (empty($ip))
	{
		throw new Exception(sprintf(_('Invalid IP address %s'), $address));
	}
	
	// Try to get the prefix address and the prefix bits
	if (strpos($prefix, '/') !== FALSE)
	{
		list($network, $netmask) = explode('/', $prefix, 2);
	}
	else
	{
		$network = $prefix;
	}
	
	// Check if it is IPv4 or IPv6 so we can check correct netmask size
	$val4 = filter_var($network, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV4));
	$val6 = filter_var($network, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV6));
	
	// Validates as IPv6
	if (!empty($val6))
	{
		// If the address to check is not IPv6, as it cannot be in the prefix
		$ipv6 = filter_var($ip, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV6));
		if (empty($ipv6))
		{
			return FALSE;
		}
		
		$max_range = 128;
		$v6 = TRUE;
	}
	
	// Validates as IPv4
	else if (!empty($val4))
	{
		// If the IP to check is not IPv4, it cannot be in the prefix
		$ipv4 = filter_var($ip, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV4));
		if (empty($ipv4))
		{
			return FALSE;
		}
		
		$max_range = 32;
		$v6 = FALSE;
	}
	
	// Not a valid IP
	else
	{
		throw new Exception(sprintf(_('Invalid CIDR notation %s: %s'), $cidr, _('Invalid network address')));
	}
	
	// Make sure netmask is set
	if (!isset($netmask))
	{
		$netmask = $max_range;
	}
	
	// IPv6 specific code to check if address is in the prefix
	// @todo: could this be optimized a bit? using hex string loop feels weird...
	if ($v6)
	{
		// Turn the address data into hex strings
		$network_hex = bin2hex(inet_pton($network));
		$ip_hex = bin2hex(inet_pton($ip));
		$lower_hex = $network_hex;
		$upper_hex = $network_hex;
		
		// Get the flexible bits
		$flex = 128 - $netmask;
		$pos = 31;
		
		// Go through the hex strings and convert flexible data
		while ($flex > 0)
		{
			$val = hexdec(substr($network_hex, $pos, 1));
			$new = $val & (0xFFFFFFFF << (32 - ($flex % 4)));
			$lower_hex = substr_replace($lower_hex, dechex($new), $pos, 1);
			$new = $val | (pow(2, min(4, $flex)) -1);
			$upper_hex = substr_replace($upper_hex, dechex($new), $pos, 1);
			$flex-=4;
			$pos--;
		}
		return (bool)(($ip_hex >= $lower_hex) && ($ip_hex <= $upper_hex));
	}
	
	// IPv4 specific
	else
	{
		// Convert the values into more machine readable form
		$ip = ip2long($address);
		$netmask = 0xFFFFFFFF << (32 - $netmask);
		$network = ip2long($network);
		
		$lower = $network & $netmask;
		$upper = $network | (~$netmask & 0xFFFFFFFF);
		
		return (bool)(($ip >= $lower) && ($ip <= $upper));
	}
}
