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
 * Encapsulates the properties of an undeliverable message.
 *
 * @see Tx_Amqp_Messaging_AMQPService::handleReturn()
 */
class Tx_Amqp_Messaging_UndeliverableMessage {

	/**
	 * @var integer
	 */
	protected $replyCode;

	/**
	 * @var string
	 */
	protected $replyText;

	/**
	 * @var string
	 */
	protected $exchange;

	/**
	 * @var string
	 */
	protected $routingKey;

	/**
	 * @var \PhpAmqpLib\Message\AMQPMessage
	 */
	protected $message;

	/**
	 * @param integer $replyCode
	 * @param string $replyText
	 * @param string $exchange
	 * @param string $routingKey
	 * @param \PhpAmqpLib\Message\AMQPMessage $message
	 */
	public function __construct($replyCode, $replyText, $exchange, $routingKey, \PhpAmqpLib\Message\AMQPMessage $message) {
		$this->exchange = $exchange;
		$this->message = $message;
		$this->replyCode = $replyCode;
		$this->replyText = $replyText;
		$this->routingKey = $routingKey;
	}

	/**
	 * @return string
	 */
	public function getExchange() {
		return $this->exchange;
	}

	/**
	 * @return \PhpAmqpLib\Message\AMQPMessage
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @return int
	 */
	public function getReplyCode() {
		return $this->replyCode;
	}

	/**
	 * @return string
	 */
	public function getReplyText() {
		return $this->replyText;
	}

	/**
	 * @return string
	 */
	public function getRoutingKey() {
		return $this->routingKey;
	}
}

?>
