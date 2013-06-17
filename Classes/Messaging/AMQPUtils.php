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

final class Tx_Amqp_Messaging_AMQPUtils {

	private final function __construct() {}

	public static function acknowledgeMessage(\PhpAmqpLib\Channel\AMQPChannel $channel, \PhpAmqpLib\Message\AMQPMessage $message) {
		$channel->basic_ack($message->delivery_info['delivery_tag']);
	}

	public static function cancelListening(\PhpAmqpLib\Message\AMQPMessage $message) {
		$message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
	}

	public static function throwStopException($message='') {
		throw new Tx_Amqp_Messaging_Exception_StopException($message);
	}
}

?>
