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
 * Basic-Consume implementation with auto message-acknowledging and transactional support.
 */
class Tx_Amqp_Messaging_AMQPMessageListener {

	/**
	 * @var Tx_Amqp_Messaging_AMQPConnectionFactoryInterface
	 */
	protected $connectionFactory;

	protected $transactional = FALSE;

	public function __construct(Tx_Amqp_Messaging_AMQPConnectionFactoryInterface $connectionFactory) {
		$this->connectionFactory = $connectionFactory;
	}

	/**
	 * Whether to wrap the processing of each message in a transaction.
	 *
	 * @param boolean $transactional
	 */
	public function setTransactional($transactional=TRUE) {
		$this->transactional = (boolean)$transactional;
	}

	/**
	 * Start listening on a queue using a messageListener-callback.
	 * A successful message consumption is automatically acknowledged.
	 *
	 * If {@link setTransactional()} is set to TRUE, each message processing is wrapped in a transaction with
	 * an explicit commit or rollback on exception.
	 *
	 * <code>
	 * $listener->listen('my_queue', function(\PhpAmqpLib\Message\AMQPMessage $message) {
	 *		// process message
	 * 		...
	 *
	 *
	 * 		// optional: cancel further listening
	 * 		Tx_Amqp_Messaging_AMQPUtils::cancelListening($message);
	 * });
	 * </code>
	 *
	 * @param string $queue The name of the queue
	 * @param callable $messageListener
	 * @throws InvalidArgumentException
	 */
	public function listen($queue, Closure $messageListener) {
		if($queue === NULL) {
			throw new \InvalidArgumentException('Queue must not be NULL.');
		}
		$connection = $this->connectionFactory->createConnection();
		$channel = $connection->channel();

		$this->addListener($queue, $channel, $messageListener);

		while(sizeof($channel->callbacks) > 0) {
			$channel->wait();
		}
	}

	private function addListener($queue, $channel, $listener) {
		$transactional = $this->transactional;
		$callback = function(\PhpAmqpLib\Message\AMQPMessage $message) use ($channel, $listener, $transactional) {
			if($transactional) {
				$channel->tx_select();
			}
			try {
				call_user_func($listener, $message);
				$channel->basic_ack($message->delivery_info['delivery_tag']);
				if($transactional) {
					$channel->tx_commit();
				}
			} catch(\Exception $e) {
				if($transactional) {
					$channel->tx_rollback();
				}
				throw $e;
			}
		};
		$channel->basic_consume($queue, '', FALSE, FALSE, FALSE, FALSE, $callback);
	}
}

?>
