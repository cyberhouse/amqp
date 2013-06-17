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


class Tx_Amqp_Util_ConnectionStatus implements tx_reports_StatusProvider {

	/**
	 * @return array<tx_reports_reports_status_Status>
	 */
	public function getStatus() {
		$reports = array();
		$reports[] = $this->getConnectionStatus();
		$reports[] = $this->getConnectionParams();
		return $reports;
	}

	protected function getConnectionStatus() {
		try {
			$service = new Tx_Amqp_Messaging_AMQPService(Tx_Amqp_Util_ConfigurationHelper::getConnectionFactory());
			$admin = new Tx_Amqp_Messaging_AMQPAdmin($service);

			$queue = $admin->declareQueue();
			$messageBody = 'ping '.microtime(TRUE);
			$service->send(new \PhpAmqpLib\Message\AMQPMessage($messageBody), Tx_Amqp_Messaging_Exchange::DEFAULT_EXCHANGE, $queue->getName());
			$receivedMessage = $service->receive($queue->getName());
			$admin->deleteQueue($queue->getName());

			if($messageBody !== $receivedMessage->body) {
				return new tx_reports_reports_status_Status(
					'AMQP', 'Warning', sprintf('Send and receive not identical. Message sent [%s] differs from received message [%s]', $messageBody, $receivedMessage->body),
					tx_reports_reports_status_Status::WARNING
				);
			}
			return new tx_reports_reports_status_Status(
				'AMQP', 'OK', 'Connection successful. Send and receive OK.', tx_reports_reports_status_Status::OK
			);
		} catch(\Exception $e) {
			$statusMessage = $e->getMessage() . ' (' . get_class($e) . ')';
			return new tx_reports_reports_status_Status(
				'AMQP', 'Error', 'Unable to send and receive messages. Reason: ' . $statusMessage, tx_reports_reports_status_Status::ERROR
			);
		}
	}

	protected function getConnectionParams() {
		$extConf = Tx_Amqp_Util_ConfigurationHelper::getExtensionConfiguration();
		$configAsString = '<code>';
		foreach($extConf as $property => $value) {
			$configAsString.= '<strong>' . $property . ':</strong> ' . ($property == 'password' ? str_repeat('*', strlen($value)) : $value) . '<br/>';
		}
		$configAsString.= '</code>';
		return new tx_reports_reports_status_Status(
			'AMQP Connection Parameters', '', $configAsString, tx_reports_reports_status_Status::NOTICE
		);
	}
}

?>
