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

abstract class Tx_Amqp_Messaging_AbstractExchange implements Tx_Amqp_Messaging_Exchange {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var boolean
	 */
	private $durable;

	/**
	 * @var boolean
	 */
	private $autoDelete;

	/**
	 * @var array
	 */
	private $arguments = array();

	/**
	 * @param string $name The name of the exchange
	 * @param boolean $durable If true the exchange will survive a server restart
	 * @param boolean $autoDelete If true the server deletes the exchange when it is no longer in use
	 * @param array $arguments
	 */
	public function __construct($name, $durable=TRUE, $autoDelete=FALSE, array $arguments=array()) {
		$this->name = $name;
		$this->durable = $durable;
		$this->autoDelete = $autoDelete;
		$this->arguments = $arguments;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return boolean
	 */
	public function isDurable() {
		return $this->durable;
	}

	/**
	 * @param boolean $durable
	 * @return self
	 */
	public function setDurable($durable=TRUE) {
		$this->durable = $durable;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isAutoDelete() {
		return $this->autoDelete;
	}

	/**
	 * @param boolean $autoDelete
	 * @return self
	 */
	public function setAutoDelete($autoDelete=TRUE) {
		$this->autoDelete = $autoDelete;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * @param array $arguments
	 * @return self
	 */
	public function setArguments($arguments) {
		$this->arguments = $arguments;
		return $this;
	}
}

?>
