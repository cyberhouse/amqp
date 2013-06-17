<?php

class Tx_Amqp_Tests_Unit_Messaging_AMQPAdminTest extends Tx_Amqp_Tests_Unit_BaseTestCase {

	const TEST_QUEUE = 'txqueue_unit_declarequeue';
	const TEST_EXCHANGE = 'txqueue_unit_exchange';

	/**
	 * @var Tx_Amqp_Messaging_AMQPAdmin
	 */
	protected $admin;

	protected function setUp() {
		$this->admin = $this->createAdminService();
	}

	protected function createAdminService() {
		$connectionFactory = Tx_Amqp_Util_ConfigurationHelper::getConnectionFactory();
		$service = new Tx_Amqp_Messaging_AMQPService($connectionFactory);
		return new Tx_Amqp_Messaging_AMQPAdmin($service);
	}

	/**
	 * @test
	 */
	public function declareQueueReturnsNothing() {
		$res = $this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->assertNull($res);
		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 *
	 */
	public function declareTemporaryQueueReturnsQueueDefinition() {
		$temporaryQueue = $this->admin->declareQueue();
		$this->assertNotNull($temporaryQueue, 'Temporary queue did not return definition?');
		$this->assertInstanceOf('Tx_Amqp_Messaging_Queue', $temporaryQueue);

		// cleanup
		$this->admin->deleteQueue($temporaryQueue->getName());
	}

	/**
	 * @test
	 */
	public function deleteQueueReturnsTrue() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$res = $this->admin->deleteQueue(self::TEST_QUEUE);
		$this->assertTrue($res);
	}

	/**
	 * @test
	 */
	public function deleteNonExistingQueueReturnsFalse() {
		$res = $this->admin->deleteQueue(uniqid() . '_non_existing_queue');
		$this->assertFalse($res);
	}

	/**
	 * @test
	 */
	public function purgeQueueReturnsTrue() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$res = $this->admin->purgeQueue(self::TEST_QUEUE);
		$this->assertTrue($res);
	}

	/**
	 * @test
	 */
	public function purgeNonExistingQueueReturnsFalse() {
		$res = $this->admin->purgeQueue(uniqid() . '_non_existing_queue');
		$this->assertFalse($res);
	}

	/**
	 * @test
	 */
	public function declareExchange() {
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
	}

	/**
	 * @test
	 * @depends declareExchange
	 */
	public function deleteExchangeReturnsTrue() {
		$res = $this->admin->deleteExchange(self::TEST_EXCHANGE);
		$this->assertTrue($res);
	}

	/**
	 * @test
	 */
	public function deleteNonExistingExchangeReturnsFalse() {
		$res = $this->admin->deleteExchange(uniqid() . '_non_existing_exchange');
		$this->assertFalse($res);
	}

	/**
	 * @test
	 */
	public function declareBinding() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));
		$this->admin->declareBinding(new Tx_Amqp_Messaging_Binding(self::TEST_QUEUE, Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE, 'testRoutingKey'));
	}

	/**
	 * @test
	 * @depends declareBinding
	 */
	public function deleteBinding() {
		$this->admin->deleteBinding(new Tx_Amqp_Messaging_Binding(self::TEST_QUEUE, Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE, 'testRoutingKey'));
		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 */
	public function declareExchangeToExchangeBinding() {
		$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange(self::TEST_EXCHANGE, FALSE, TRUE));

		$exchangeToQueueBinding = new Tx_Amqp_Messaging_Binding(self::TEST_QUEUE, Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE, self::TEST_EXCHANGE, 'testRoutingKey');
		$this->admin->declareBinding($exchangeToQueueBinding);

		$exchange2 = self::TEST_EXCHANGE . '2';
		$this->admin->declareExchange(new Tx_Amqp_Messaging_DirectExchange($exchange2, FALSE, TRUE));
		$exchangeToExchangeBinding = new Tx_Amqp_Messaging_Binding($exchange2, Tx_Amqp_Messaging_Binding::DESTINATION_EXCHANGE, self::TEST_EXCHANGE, 'testRoutingKey');
		$this->admin->declareBinding($exchangeToExchangeBinding);


		$this->admin->deleteBinding($exchangeToQueueBinding);

		$this->admin->deleteExchange(self::TEST_EXCHANGE);
		$this->admin->deleteExchange($exchange2);

		$this->admin->deleteQueue(self::TEST_QUEUE);
	}

	/**
	 * @test
	 * @expectedException \PhpAmqpLib\Exception\AMQPProtocolChannelException
	 */
	public function deleteDefaultBindingThrowsException() {
		try {
			$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
			$this->admin->deleteBinding(new Tx_Amqp_Messaging_Binding(self::TEST_QUEUE, Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE,
				Tx_Amqp_Messaging_AMQPService::DEFAULT_EXCHANGE, Tx_Amqp_Messaging_AMQPService::DEFAULT_ROUTING_KEY));
		} catch(Exception $e) {
			$admin = $this->createAdminService();
			$admin->deleteQueue(self::TEST_QUEUE);
			throw $e;
		}
	}

	/**
	 * @test
	 * @expectedException \PhpAmqpLib\Exception\AMQPProtocolChannelException
	 */
	public function deleteNonExistentBindingThrowsException() {
		try {
			$this->admin->declareQueue(new Tx_Amqp_Messaging_Queue(self::TEST_QUEUE, FALSE, FALSE, TRUE));
			$this->admin->deleteBinding(new Tx_Amqp_Messaging_Binding(self::TEST_QUEUE, Tx_Amqp_Messaging_Binding::DESTINATION_QUEUE,
				uniqid() . '_non_existent_exchange', uniqid() . '_non_existent_routing'));
		} catch(Exception $e) {
			$admin = $this->createAdminService();
			$admin->deleteQueue(self::TEST_QUEUE);
			throw $e;
		}
	}
}
?>
