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
 * AMQP Admin Service encapsulates the management of queues, exchanges and bindings.
 */
class Tx_Amqp_Messaging_AMQPAdmin {

	/**
	 * @var Tx_Amqp_Messaging_AMQPService
	 */
	protected $service;

	public function __construct(Tx_Amqp_Messaging_AMQPService $service) {
		$this->service = $service;
	}

	/**
	 * Declares an exchange
	 *
	 * @param Tx_Amqp_Messaging_Exchange $exchange
	 * @return void
	 */
	public function declareExchange(Tx_Amqp_Messaging_Exchange $exchange) {
		$this->service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($exchange) {
			$passive = FALSE;
			$internal = FALSE;
			$nowait = FALSE;
			$channel->exchange_declare($exchange->getName(), $exchange->getType(), $passive, $exchange->isDurable(), $exchange->isAutoDelete(), $internal, $nowait, $exchange->getArguments());
		});
	}

	/**
	 * Deletes the exchange
	 *
	 * @param string $exchange The name of the exchange
	 * @return boolean
	 */
	public function deleteExchange($exchange) {
		return $this->service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($exchange) {
			try {
				$channel->exchange_delete($exchange);
				return TRUE;
			} catch(Exception $e) {
				Tx_Amqp_Messaging_ExceptionFilter::ignoreNotFoundException($e);
				return FALSE;
			}
		});
	}

	/**
	 * Declares a queue
	 * If no queue is provided a non-durable, auto-delete queue is created.
	 *
	 * @param Tx_Amqp_Messaging_Queue $queue The queue description. If NULL a temporary queue will be created
	 * @return Tx_Amqp_Messaging_Queue|NULL Returns the queue-description of the temporary created queue or NULL.
	 */
	public function declareQueue(Tx_Amqp_Messaging_Queue $queue=NULL) {
		return $this->service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($queue) {
			if($queue === NULL) {
				list($queueName,,) = $channel->queue_declare();
				return new Tx_Amqp_Messaging_Queue($queueName, FALSE, FALSE, TRUE);
			} else {
				$channel->queue_declare($queue->getName(), FALSE, $queue->isDurable(), $queue->isExclusive(), $queue->isAutoDelete(), FALSE, $queue->getArguments(), NULL);
			}
		});
	}

	/**
	 * Deletes the queue
	 *
	 * @param string $queue The name of the queue
	 * @param boolean $unused If true, the queue will be deleted only if it's unused
	 * @param boolean $empty If true, the queue will be deleted only if it's empty
	 * @return boolean
	 */
	public function deleteQueue($queue, $unused=FALSE, $empty=FALSE) {
		return $this->service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($queue, $unused, $empty) {
			try {
				$channel->queue_delete($queue, $unused, $empty);
				return TRUE;
			} catch(\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
				Tx_Amqp_Messaging_ExceptionFilter::ignoreNotFoundException($e);
				return FALSE;
			}
		});
	}

	/**
	 * Purges the queue
	 *
	 * @param string $queue The name of the queue
	 * @param boolean $noWait If true, does not wait until the purge command is completed
	 * @return boolean
	 */
	public function purgeQueue($queue, $noWait=FALSE) {
		return $this->service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($queue, $noWait) {
			try {
				$channel->queue_purge($queue, $noWait);
				return TRUE;
			} catch(\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
				Tx_Amqp_Messaging_ExceptionFilter::ignoreNotFoundException($e);
				return FALSE;
			}
		});
	}

	/**
	 * Declares a binding
	 *
	 * @param Tx_Amqp_Messaging_Binding $binding
	 * @return void
	 */
	public function declareBinding(Tx_Amqp_Messaging_Binding $binding) {
		$this->service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($binding) {
			$noWait = FALSE;
			if($binding->isDestinationQueue()) {
				$channel->queue_bind($binding->getDestination(), $binding->getExchange(), $binding->getRoutingKey(), $noWait, $binding->getArguments());
			} else {
				$channel->exchange_bind($binding->getDestination(), $binding->getExchange(), $binding->getRoutingKey(), $noWait, $binding->getArguments());
			}
		});
	}

	/**
	 * Deletes the binding
	 *
	 * @param Tx_Amqp_Messaging_Binding $binding
	 * @return void
	 */
	public function deleteBinding(Tx_Amqp_Messaging_Binding $binding) {
		$this->service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use($binding) {
			if ($binding->isDestinationQueue()) {
				$channel->queue_unbind($binding->getDestination(), $binding->getExchange(), $binding->getRoutingKey(), $binding->getArguments());
			} else {
				$channel->exchange_unbind($binding->getExchange(), $binding->getDestination(), $binding->getRoutingKey(), $binding->getArguments());
			}
		});
	}
}

?>
