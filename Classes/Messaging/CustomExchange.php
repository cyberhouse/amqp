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


/**
 * A custom exchange
 */
class Tx_Amqp_Messaging_CustomExchange extends Tx_Amqp_Messaging_AbstractExchange {

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @param string $name
	 * @param string $type
	 * @param boolean $durable
	 * @param boolean $autoDelete
	 * @param array $arguments
	 */
	public function __construct($name, $type, $durable = TRUE, $autoDelete = FALSE, array $arguments = array()) {
		parent::__construct($name, $durable, $autoDelete, $arguments);
		$this->type = $type;
	}

	/**
	 * A fluent way to build an exchange.
	 *
	 * @param string $name
	 * @param string $type
	 * @return Tx_Amqp_Messaging_FanoutExchange
	 */
	public static function create($name, $type) {
		return new self($name, $type);
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
}

?>
