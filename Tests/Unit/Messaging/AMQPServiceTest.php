<?php

class Tx_Amqp_Tests_Unit_Messaging_AMQPServiceTest extends Tx_Amqp_Tests_Unit_BaseTestCase {

	const TEST_QUEUE = 'txqueue_unit_send_queue';
	const TEST_EXCHANGE = 'txqueue_unit_exchange';

	/**
	 * @var Tx_Amqp_Messaging_AMQPConnectionFactoryInterface
	 */
	protected $connectionFactory;

	/**
	 * @var Tx_Amqp_Messaging_AMQPAdmin
	 */
	protected $admin;

	/**
	 * @var Tx_Amqp_Messaging_AMQPService
	 */
	protected $service;


	protected function setUp() {
		$this->connectionFactory = Tx_Amqp_Util_ConfigurationHelper::getConnectionFactory();
		$this->service = new Tx_Amqp_Messaging_AMQPService($this->connectionFactory);
		$this->admin = new Tx_Amqp_Messaging_AMQPAdmin($this->service);
	}

	/**
	 * @test
	 */
	public function testSend() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage('a test message'), '', self::TEST_QUEUE);
		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 */
	public function sendMessageToDefaultQueue() {
		$messageBody = 'a test message';
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), '', self::TEST_QUEUE);
		$this->service->setQueue(self::TEST_QUEUE);
		$responseMessage = $this->service->receive();
		$this->assertEquals($messageBody, $responseMessage->body);
		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 */
	public function testSendMessageToUnknownExchange() {
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage('a test message'), 'unknown');
	}

	/**
	 * @test
	 */
	public function testExchangeToExchangeBinding() {
		$exchangeToQueueBinding = new Tx_Amqp_Messaging_Binding(self::TEST_QUEUE, Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE, 'testRoutingKey');

		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
		$this->admin->declareBinding($exchangeToQueueBinding);

		$messageBody = 'exchange-to-exchange binding';

		$exchange2 = self::TEST_EXCHANGE . '2';
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange($exchange2, FALSE, TRUE));
		$exchangeToExchangeBinding = new Tx_Amqp_Messaging_Binding(self::TEST_EXCHANGE, Tx_Amqp_Messaging_Binding::DESTINATION_EXCHANGE, $exchange2, 'testRoutingKey');
		$this->admin->declareBinding($exchangeToExchangeBinding);

		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), $exchange2, 'testRoutingKey');
		$message = $this->service->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody, $message->body);

		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 */
	public function testReceive() {
		$messageBody = 'test payload '.microtime(TRUE);

		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), '', self::TEST_QUEUE);

		$receivedMessage = $this->service->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody, $receivedMessage->body);

		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 * @expectedException PhpAmqpLib\Exception\AMQPTimeoutException
	 */
	public function sendAndReceiveWithNoReplyInTimeThrowsTimeoutException() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		try {
			$this->service->setReplyTimeoutInMs(1000);
			$this->service->sendAndReceive(new \PhpAmqpLib\Message\AMQPMessage('test message'), '', self::TEST_QUEUE);
		} catch(Exception $e) {
			$this->admin->deleteQueue(self::TEST_QUEUE);
			throw $e;
		}
	}

	/**
	 * @test
	 */
	public function testSendTransactional() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));

		$this->service->txSelect();
		$messageBody1 = 'test payload '.microtime(TRUE);
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody1), '', self::TEST_QUEUE);
		$messageBody2 = 'test payload '.microtime(TRUE);
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody2), '', self::TEST_QUEUE);
		$this->service->txCommit();

		$responseMessage = $this->service->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody1, $responseMessage->body);
		$responseMessage = $this->service->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody2, $responseMessage->body);

		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 */
	public function testReceiveTransactional() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));

		$this->service->txSelect();
		$messageBody1 = 'test payload (1) '.microtime(TRUE);
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody1), '', self::TEST_QUEUE);
		$messageBody2 = 'test payload (2) '.microtime(TRUE);
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody2), '', self::TEST_QUEUE);
		$this->service->txCommit();

		$consumerService = new Tx_Amqp_Messaging_AMQPService($this->connectionFactory);
		$consumerService->txSelect();
		$responseMessage1 = $consumerService->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody1, $responseMessage1->body, 'Transactional receive of message 1 failed?');
		$responseMessage2 = $consumerService->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody2, $responseMessage2->body, 'Transactional receive of message 2 failed?');
		$consumerService->txRollback();

		$consumerService->txSelect();
		$consumerService->requeueDelivered();
		$responseMessage1 = $consumerService->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody1, $responseMessage1->body, 'Reiceive of message 1 failed?');
		$responseMessage2 = $consumerService->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody2, $responseMessage2->body, 'Reiceive of message 2 failed?');
		$consumerService->txCommit();
		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 * @expectedException PhpAmqpLib\Exception\AMQPProtocolChannelException
	 */
	public function commitNoActiveTransactionThrowsException() {
		$this->service->txCommit();
	}


	/**
	 * @test
	 */
	public function testBinding() {
		$messageBody = 'message binding';
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->admin->purgeQueue(self::TEST_QUEUE);
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
		$binding = new Tx_Amqp_Messaging_Binding(self::TEST_QUEUE, Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE);
		$this->admin->declareBinding($binding);

		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), self::TEST_EXCHANGE);
		$responseMessage = $this->service->receive(self::TEST_QUEUE);
		$this->assertEquals($messageBody, $responseMessage->body);

		$this->admin->deleteBinding($binding);
		// binding is released by the server because of 'autoDelete=TRUE'
		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * Tests a direct-exchange binding with two explicit connections in order
	 * to to leverage the autoDelete functionality of the server.
	 *
	 * @test
	 */
	public function testDirectExchangeBindingWithTwoConnections() {
		$messageBody = 'message binding using two separate connections';
		$routingKey = 'txqueue_unit_routing';

		$this->service->setPersistentDeliveryMode(FALSE);
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
		$queue = $this->admin->declareQueue();
		$binding = new Tx_Amqp_Messaging_Binding($queue->getName(), Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE, $routingKey);
		$this->admin->declareBinding($binding);

		$producerService = new Tx_Amqp_Messaging_AMQPService($this->connectionFactory);
		$producerService->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), self::TEST_EXCHANGE, $routingKey);
		sleep(2); // wait for message to sync (required because of message polling)
		$responseMessage = $this->service->receive($queue->getName());
		$this->assertEquals($messageBody, $responseMessage->body);

		$this->admin->deleteQueue($queue->getName());
	}

	/**
	 * @test
	 */
	public function receiveAsynchronous() {
		$messageBody = 'message binding using two separate connections';
		$routingKey = 'txqueue_unit_routing';

		$this->service->setPersistentDeliveryMode(FALSE);
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
		$queue = $this->admin->declareQueue();
		$binding = new Tx_Amqp_Messaging_Binding($queue->getName(), Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE, $routingKey);
		$this->admin->declareBinding($binding);

		$producerService = new Tx_Amqp_Messaging_AMQPService($this->connectionFactory);
		$producerService->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), self::TEST_EXCHANGE, $routingKey);

		$self = $this;
		$listener = new Tx_Amqp_Messaging_AMQPMessageListener($this->connectionFactory);
		$listener->listen($queue->getName(), function(\PhpAmqpLib\Message\AMQPMessage $message) use($self, $messageBody) {
			$self->assertEquals($messageBody, $message->body);

			// skip listening
			Tx_Amqp_Messaging_AMQPUtils::cancelListening($message);
		});
	}

	/**
	 * @test
	 */
	public function receiveAsynchronous2() {
		$messageBody = 'message binding using two separate connections. ' . microtime(TRUE);
		$routingKey = 'txqueue_unit_routing';

		$this->service->setPersistentDeliveryMode(FALSE);
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
		$queue = $this->admin->declareQueue();
		$binding = new Tx_Amqp_Messaging_Binding($queue->getName(), Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE, $routingKey);
		$this->admin->declareBinding($binding);

		$producerService = new Tx_Amqp_Messaging_AMQPService($this->connectionFactory);
		$producerService->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), self::TEST_EXCHANGE, $routingKey);

		$listener = new Tx_Amqp_Messaging_AMQPMessageListener($this->connectionFactory);
		$self = $this;
		$listener->listen($queue->getName(), function(\PhpAmqpLib\Message\AMQPMessage $message) use($self, $messageBody) {
			$self->assertEquals($messageBody, $message->body);

			// skip listening
			Tx_Amqp_Messaging_AMQPUtils::cancelListening($message);
		});
	}

	/**
	 * Tests the basic.return if a message cannot be routed.
	 *
	 * @test
	 */
	public function routeToUnboundExchangeShouldBasicReturn() {
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
		$this->service->setMandatory(TRUE);
		$self = $this;
		$messageBody = 'basic.return with no binding to destination queue';
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), self::TEST_EXCHANGE);

		$this->service->handleReturn(function(Tx_Amqp_Messaging_UndeliverableMessage $message) use ($self, $messageBody) {
			$self->assertEquals($messageBody, $message->getMessage()->body);
			Tx_Amqp_Messaging_AMQPUtils::throwStopException();
		});
		$this->admin->deleteExchange(self::TEST_EXCHANGE);
	}

	/**
 	 * Tests the basic.return if a message cannot be delivered immediately.
	 *
	 * @test
	 */
	public function routeToQueueWithNoListenersShouldBasicReturn() {
		$this->markTestSkipped('immediate=true has been removed since RabbitMQ 3.0.0');
		$queue = $this->admin->declareQueue();
		$this->service->setImmediate(TRUE);
		$self = $this;
		$messageBody = 'basic.return with no consumers listening';
		$this->service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), Tx_Amqp_Messaging_AMQPService::DEFAULT_EXCHANGE, $queue->getName());
		$this->service->handleReturn(function(Tx_Amqp_Messaging_UndeliverableMessage $message) use ($self, $messageBody) {
			$self->assertEquals($messageBody, $message->getMessage()->body);
			Tx_Amqp_Messaging_AMQPUtils::throwStopException();
		});
		$this->admin->deleteQueue($queue->getName());
	}
}
?>
