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
 * Maps binding information.
 */
class Tx_Amqp_Messaging_Binding {

	const DESTINATION_EXCHANGE = 'DESTINATION_EXCHANGE';
	const DESTINATION_QUEUE = 'DESTINATION_QUEUE';

	private $destination;

	private $exchange;

	private $routingKey;

	private $arguments = array();

	private $destinationType;

	/**
	 * @param string $destination
	 * @param string $destinationType
	 * @param string $exchange
	 * @param string $routingKey
	 * @param array $arguments
	 * @throws InvalidArgumentException
	 */
	public function __construct($destination, $destinationType=self::DESTINATION_QUEUE, $exchange, $routingKey='', array $arguments=array()) {
		if(!in_array($destinationType, array(self::DESTINATION_QUEUE, self::DESTINATION_EXCHANGE))) {
			throw new \InvalidArgumentException(sprintf('Destination-type must be one of [%s]', implode(', ', array(self::DESTINATION_EXCHANGE, self::DESTINATION_QUEUE))));
		}
		$this->destination = $destination;
		$this->destinationType = $destinationType;
		$this->exchange = $exchange;
		$this->routingKey = $routingKey;
		$this->arguments = $arguments;
	}

	/**
	 * @return array
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * @return mixed
	 */
	public function getDestination() {
		return $this->destination;
	}

	/**
	 * @return string
	 */
	public function getDestinationType() {
		return $this->destinationType;
	}

	/**
	 * @return mixed
	 */
	public function getExchange() {
		return $this->exchange;
	}

	/**
	 * @return mixed
	 */
	public function getRoutingKey() {
		return $this->routingKey;
	}

	public function isDestinationQueue() {
		return $this->destinationType === self::DESTINATION_QUEUE;
	}

	public function __toString() {
		return 'Binding [destination=' . $this->destination . ', exchange=' . $this->exchange . ', routingKey=' . $this->routingKey . ']';
	}
}

?>
