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
 * A queue
 */
class Tx_Amqp_Messaging_Queue {

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
	private $exclusive;

	/**
	 * @var boolean
	 */
	private $autoDelete;

	/**
	 * @var array
	 */
	private $arguments = array();

	/**
	 * @param string $name The name of the queue
	 * @param boolean $durable If true the queue will survive a server restart
	 * @param boolean $exclusive If true the queue is not accessible by other connections
	 * @param boolean $autoDelete If true the server deletes the queue when it is no longer in use
	 * @param array $arguments
	 */
	public function __construct($name, $durable=TRUE, $exclusive=FALSE, $autoDelete=FALSE, array $arguments=array()) {
		$this->name = $name;
		$this->durable = $durable;
		$this->exclusive = $exclusive;
		$this->autoDelete = $autoDelete;
		$this->arguments = $arguments;
	}

	/**
	 * A fluent way to build a queue description.
	 *
	 * @param string $name The name of the queue
	 * @return Tx_Amqp_Messaging_Queue
	 */
	public static function create($name) {
		return new self($name);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isDurable() {
		return $this->durable;
	}

	/**
	 * If true the queue will survive a server restart
	 *
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
	public function isExclusive() {
		return $this->exclusive;
	}

	/**
	 * If true the queue is not accessible by other connections
	 *
	 * @param boolean $exclusive
	 * @return self
	 */
	public function setExclusive($exclusive=TRUE) {
		$this->exclusive = $exclusive;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isAutoDelete() {
		return $this->autoDelete;
	}

	/**
	 * If true the server deletes the queue when it is no longer in use
	 *
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

	public function __toString() {
		return sprintf('Queue(name=%s, durable=%b, autoDelete=%b, exclusive=%b)', $this->name, $this->durable, $this->autoDelete, $this->exclusive);
	}
}

?>
