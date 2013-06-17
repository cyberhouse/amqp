<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Buchgeher <alexander.buchgeher@cyberhouse.at>, CYBERHOUSE GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


final class Tx_Amqp_Util_Randomizer {

	private final function __construct() {}

	/**
	 * Should generate a universally unique identifier (UUID) according to RFC 4122.
	 *
	 * @return string The UUID
	 */
	public static function generateUUID() {
		// FIXME: The algorithm used here, might not be completely random. (borrowed from the TYPO3 FLOW Project)
		// return uniqid(php_uname('n'));
		$hex = bin2hex(t3lib_div::generateRandomBytes(16));
		return strtolower(substr($hex,0,8) . '-' . substr($hex,8,4) . '-' . substr($hex,12,4) . '-' . substr($hex,16,4) . '-' . substr($hex,20,12));
	}
}

?>
