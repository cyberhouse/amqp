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
 * AMQP Service layer
 * Implements send, receive, return and RPC message exchange
 */
class Tx_Amqp_Messaging_AMQPService extends Tx_Amqp_Messaging_AMQPSupport {

	const DEFAULT_EXCHANGE = ''; // default exchange 'amq.direct'

	const DEFAULT_ROUTING_KEY = '';

	const DEFAULT_RPC_REPLY_TIMEOUT_IN_MS = 5000;

	/**
	 * @var Tx_Amqp_Messaging_AMQPConnectionFactoryInterface
	 */
	protected $connectionFactory;

	/**
	 * @var \PhpAmqpLib\Connection\AMQPConnection
	 */
	protected $connection;

	/**
	 * @var \PhpAmqpLib\Channel\AMQPChannel
	 */
	protected $channel;

	/**
	 * @var boolean Internal flag to keep track over an active transaction
	 */
	protected $transactional = FALSE;

	/**
	 * The default exchange to be used if none is set explicitly at method level
	 *
	 * @var string
	 */
	protected $exchange = self::DEFAULT_EXCHANGE;

	/**
	 * The default routingKey to be used if none is set explicitly at method level
	 *
	 * @var string
	 */
	protected $routingKey = self::DEFAULT_ROUTING_KEY;

	/**
	 * The fallback queue if none is explicitly set at method level. Used by {@link receive()}.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * A fixed replyQueue for RPC message exchanges. If none is provided a temporary replyQueue will be created automatically.
	 *
	 * @see sendAndReceive()
	 * @var string
	 */
	protected $replyQueue;

	/**
	 * Timeout in millisecons for RPC message exchanges
	 *
	 * @see sendAndReceive()
	 * @var integer
	 */
	protected $replyTimeoutInMs = self::DEFAULT_RPC_REPLY_TIMEOUT_IN_MS;

	/**
	 * @var boolean
	 */
	protected $mandatory = FALSE;

	/**
	 * @var boolean
	 */
	protected $immediate = FALSE;

	/**
	 * Flag that sets the delivery_mode to either {@link Tx_Amqp_Messaging_MessageDeliveryMode::PERSISTENT} or {@link Tx_Amqp_Messaging_MessageDeliveryMode::NON_PERSISTENT}
	 *
	 * @var boolean
	 */
	protected $persistentDeliveryMode = TRUE;


	/**
	 * @param Tx_Amqp_Messaging_AMQPConnectionFactoryInterface $connectionFactory
	 */
	public function __construct(Tx_Amqp_Messaging_AMQPConnectionFactoryInterface $connectionFactory) {
		$this->connectionFactory = $connectionFactory;
	}

	public function __destruct() {
		$this->close();
	}

	/**
	 * The name of the default exchange to be used when none is provided at method level.
	 * Defaults to the AMQP default exchange {@link DEFAULT_EXCHANGE}.
	 *
	 * @param string $exchange
	 */
	public function setExchange($exchange) {
		$this->exchange = $exchange;
	}

	/**
	 * The name of the default routingKey to be used when none is provided at method level.
	 * Defaults to {@link DEFAULT_ROUTING_KEY}.
	 *
	 * @param string $routingKey
	 */
	public function setRoutingKey($routingKey) {
		$this->routingKey = $routingKey;
	}

	/**
	 * The name of the default queue to be used for receive actions if none is provided explicitly.
	 *
	 * @param mixed $queue
	 */
	public function setQueue($queue) {
		$this->queue = $queue;
	}

	/**
	 * The name of the replyQueue to be used in conjunction with RPC message exchanges.
	 * If none is provided a temporary reply-queue will be created.
	 *
	 * @see sendAndReceive()
	 * @param $replyQueue
	 */
	public function setReplyQueue($replyQueue) {
		$this->replyQueue = $replyQueue;
	}

	/**
	 * Sets the timeout for RPC message exchange. Defaults to {@link DEFAULT_RPC_REPLY_TIMEOUT_IN_MS}.
	 *
	 * @see sendAndReceive()
	 * @param int $timeoutInMs
	 */
	public function setReplyTimeoutInMs($timeoutInMs) {
		$this->replyTimeoutInMs = $timeoutInMs;
	}

	/**
	 * If true, tells the server to route a published message to at least on queue. If the message cant be routed, it is returned via <code>basic.return</code>.
	 * Defaults to FALSE.
	 *
	 * @see handleReturn()
	 * @param boolean $mandatory
	 */
	public function setMandatory($mandatory=TRUE) {
		$this->mandatory = (boolean)$mandatory;
	}

	/**
	 * If true the server routes a message to a ready consumer. If no consumers available, the message is returned via <code>basic.return</code>.
	 * Defaults to FALSE
	 *
	 * The immediate flag is not supported by RabbitMQ since v. 3.0.0: {@link https://www.rabbitmq.com/specification.html} and {@link http://www.rabbitmq.com/release-notes/README-3.0.0.txt}
	 *
	 * @see handleReturn()
	 * @param boolean $immediate
	 */
	public function setImmediate($immediate=TRUE) {
		$this->immediate = $immediate;
	}

	/**
	 * True if sent messages should survive server restarts. Defaults to TRUE.
	 *
	 * @param boolean $persistent
	 */
	public function setPersistentDeliveryMode($persistent=TRUE) {
		$this->persistentDeliveryMode = (boolean)$persistent;
	}

	/**
	 * Marks the underlying channel transactional
	 */
	public function txSelect() {
		$this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) {
			$channel->tx_select();
		});
		$this->transactional = TRUE;
	}

	/**
	 * Commits the active transaction
	 */
	public function txCommit() {
		$this->transactional = FALSE;
		$this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) {
			$channel->tx_commit();
		});
	}

	/**
	 * Rolls back the active transaction
	 */
	public function txRollback() {
		$this->transactional = FALSE;
		$this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) {
			$channel->tx_rollback();
		});
	}

	/**
	 * @return void
	 */
	public function requeueDelivered() {
		$this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) {
			$channel->basic_recover(TRUE);
		});
	}

	/**
	 * Publishes a message.
	 *
	 * If exchange and/or routingKey are NULL its corresponding default value (from this service-instance) is taken.
	 * For an explicit use of the default exchange and/or routing use {@link DEFAULT_EXCHANGE} and {@link DEFAULT_ROUTING_KEY}.
	 *
	 *
	 * Since every queue is automatically bound to the {@link DEFAULT_EXCHANGE} and a routingKey that equals the queue-name, direct access of a queue
	 * without further binding is accomplished by passing the {@link DEFAULT_EXCHANGE} and the queue-name as $routingKey.
	 *
	 * If the delivery_mode property of $message is not explicitly set it will be set according to the value configured via {@link setPersistentDeliveryMode()}.
	 * Default value is {@link Tx_Amqp_Messaging_MessageDeliveryMode::PERSISTENT}
	 *
	 * @param PhpAmqpLib\Message\AMQPMessage $message
	 * @param string $exchange The exchange name
	 * @param string $routingKey The routing key
	 * @return void
	 */
	public function send(\PhpAmqpLib\Message\AMQPMessage $message, $exchange=NULL, $routingKey=NULL) {
		if($exchange === NULL) {
			$exchange = $this->exchange;
		}
		if($routingKey === NULL) {
			$routingKey = $this->routingKey;
		}
		$mandatory = $this->mandatory;
		$immediate = $this->immediate;
		$persistentDeliveryMode = $this->persistentDeliveryMode;
		$this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($message, $exchange, $routingKey, $mandatory, $immediate, $persistentDeliveryMode) {
			if(!$message->has('delivery_mode')) {
				$message->set('delivery_mode', $persistentDeliveryMode ? Tx_Amqp_Messaging_MessageDeliveryMode::PERSISTENT : Tx_Amqp_Messaging_MessageDeliveryMode::NON_PERSISTENT);
			}
			$channel->basic_publish($message, $exchange, $routingKey, $mandatory, $immediate);
		});
	}

	/**
	 * Receives the message by polling. Acknowledges the delivery.
	 *
	 * @param string $queue The name of the queue
	 * @throws InvalidArgumentException
	 * @return \PhpAmqpLib\Message\AMQPMessage The message or NULL if no message was queued
	 */
	public function receive($queue=NULL) {
		if($queue === NULL) {
			$queue = $this->queue;
		}
		if($queue === NULL) {
			throw new \InvalidArgumentException('No queue given. Either explicitly provide a queue or setup this service to use a default queue.');
		}
		$replyTimeoutInMs = $this->replyTimeoutInMs;
		return $this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($queue, $replyTimeoutInMs) {
			$message = $channel->basic_get($queue);
			if($message !== NULL) {
				$channel->basic_ack($message->delivery_info['delivery_tag']);
				return $message;
			}
		});
	}

	/**
	 * RPC message exchange.
	 * Uses either the fixed replyQueue set via {@link setReplyQueue()} or creates a temporary reply-queue.
	 * Implements the correlation identifier pattern ({@link http://www.eaipatterns.com/CorrelationIdentifier.html})
	 *
	 * @param \PhpAmqpLib\Message\AMQPMessage $message
	 * @param string $exchange Optional. Uses the globally set exchange as a default
	 * @param string $routingKey Optional. Uses the globally set routingKey as a default
	 * @return mixed The raw message or NULL if no message was queued
	 */
	public function sendAndReceive(\PhpAmqpLib\Message\AMQPMessage $message, $exchange=NULL, $routingKey=NULL) {
		$exchange === NULL ? $this->exchange : $exchange;
		$routingKey === NULL ? $this->routingKey : $routingKey;
		return $this->doSendAndReceive($message, $exchange, $routingKey);
	}

	/**
	 * Returnlistener to be used for messages published using mandatory=TRUE.
	 * The returnListener receives a Tx_Amqp_Messaging_ReturnedMessage object.
	 *
	 * Example usage:
	 * <code>
	 * $service->handleReturn(function(Tx_Amqp_Messaging_ReturnedMessage $returnedMessage) {
	 * 	echo $returnedMessage->getMessage()->body;
	 * 	Tx_Amqp_Messaging_AMQPUtils::throwStopException(); // quit further message handling
	 * });
	 * </code>
	 *
	 * @param callable $returnListener
	 * @return void
	 * @throws PhpAmqpLib\Exception\AMQPTimeoutException If no message is consumed within {@link replyTimeoutInMs}
	 */
	public function handleReturn(Closure $returnListener) {
		$replyTimeoutInMs = $this->replyTimeoutInMs;
		$this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($returnListener, $replyTimeoutInMs) {
			$channel->set_return_listener(function($replyCode, $replyText, $exchange, $routingKey, \PhpAmqpLib\Message\AMQPMessage $message) use ($returnListener) {
				call_user_func($returnListener, new Tx_Amqp_Messaging_UndeliverableMessage($replyCode, $replyText, $exchange, $routingKey, $message));
			});
			try {
				while (TRUE) {
					$channel->wait(NULL, FALSE, $replyTimeoutInMs/1000);
				}
			} catch(Tx_Amqp_Messaging_Exception_StopException $ignored) {
			}
		});
	}

	/**
	 * Executes the closure by passing an AMQPChannel object.
	 *
	 * Example usage:
	 * <code>
	 * $service->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) {
	 * 		...
	 * });
	 * </code>
	 *
	 * @param Closure $closure
	 * @return mixed
	 * @throws Exception
	 */
	public function execute(Closure $closure) {
		if ($this->channel === NULL) {
			$this->channel = $this->getConnection()->channel();
		}
		try {
			$res = call_user_func($closure, $this->channel);
			return $res;
		} catch (\Exception $e) {
			// TODO: convert to own exception hierarchy?
			throw $e;
		}
	}



	/**
	 * @param \PhpAmqpLib\Message\AMQPMessage $message
	 * @param string $exchange
	 * @param string $routingKey
	 *
	 * @throws RuntimeException
	 * @throws Exception|PhpAmqpLib\Exception\AMQPTimeoutException
	 * @return mixed
	 */
	protected function doSendAndReceive(\PhpAmqpLib\Message\AMQPMessage $message, $exchange, $routingKey) {
		if($message->has('correlation_id')) {
			throw new RuntimeException('Illegal API usage. RPC style exchange can only be used if no correlationId property is set on beforehand.');
		}
		if($message->has('reply_to')) {
			throw new RuntimeException('Illegal API usage. RPC style exchange can only be used if no replyTo property is set on beforehand.');
		}

		$replyQueue = $this->replyQueue;
		$replyTimeoutInMs = $this->replyTimeoutInMs;
		$mandatory = $this->mandatory;
		$immediate = $this->immediate;
		return $this->execute(function(\PhpAmqpLib\Channel\AMQPChannel $channel) use ($message, $exchange, $routingKey, $replyQueue, $replyTimeoutInMs, $mandatory, $immediate) {
			$correlationId = Tx_Amqp_Util_Randomizer::generateUUID();
			if($replyQueue === NULL) {
				// create a temporary (non-durable) exclusive auto-delete reply-queue
				list($replyQueue,,) = $channel->queue_declare('', FALSE, FALSE, TRUE, TRUE);
			}
			$message->set('reply_to', $replyQueue);
			$message->set('correlation_id', $correlationId);

			$response = NULL;
			$callback = function(\PhpAmqpLib\Message\AMQPMessage $message) use ($channel, $correlationId, &$response) {
				if($correlationId == $message->get('correlation_id')) {
					$channel->basic_ack($message->delivery_info['delivery_tag']);
					$message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
					$response = $message->body;
				}
			};
			$channel->basic_consume($replyQueue, '', FALSE, FALSE, FALSE, FALSE, $callback);
			$channel->basic_publish($message, $exchange, $routingKey, $mandatory, $immediate);
			while (count($channel->callbacks)) {
				try {
					$channel->wait(NULL, FALSE, (float)$replyTimeoutInMs/1000.0);
				} catch(\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
					// TODO: convert to own exception hierarchy?
					throw $e;
				}
			}
			return $response;
		});
	}

	protected function getConnection() {
		if($this->connection === NULL) {
			$this->connection = $this->connectionFactory->createConnection();
		}
		return $this->connection;
	}

	protected function open() {
		if ($this->channel !== NULL) {
			throw new BadMethodCallException('Illegal API usage. Channel was already open.');
		}
		$this->channel = $this->getConnection()->channel();
	}

	protected function close() {
		if ($this->channel !== NULL) {
			try {
				// $this->channel->close();
				// assume this channel has already been closed
			} catch (\PhpAmqpLib\Exception\AMQPRuntimeException $ignored) {
			}
			$this->channel = NULL;
		}
		try {
			// causes an error under certain conditions: -> Call to a member function send_channel_method_frame() on a non-object
			// $this->getConnection()->close();
		} catch (\Exception $ignored) {
		}
	}

//	/**
//	 * FIXME: find a way to unit-test sendAndReceice
//	 */
//	public function sendTestReply($reply, $correlationId) {
//		$channel = $this->getConnection()->channel();
//		$message = 'TEST: ' . $correlationId;
//		$amqpMessage = new \PhpAmqpLib\Message\AMQPMessage($message, array(
//			'correlation_id' => $correlationId
//		));
//		$channel->basic_publish($amqpMessage, $this->exchange, $reply, FALSE, FALSE);
//		$channel->close();
//	}
}

?>
