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
 * SSL AMQP ConnectionFactory
 */
class Tx_Amqp_Messaging_AMQPSSLConnectionFactory extends Tx_Amqp_Messaging_AMQPConnectionFactory implements Tx_Amqp_Messaging_AMQPConnectionFactoryInterface {

	/**
	 * @var array
	 */
	protected $sslOptions = array();

	/**
	 * Overide SSL options.
	 *
	 * Eg.
	 * <code>
	 * array(
	 * 		'cafile' => 'path-to-cert/cacert.pem',
	 * 		'local_cert' => 'path-to-cert/phpcert.pem',
	 * 		'verify_peer' => true
	 * )
	 * </code>
	 *
	 * @param array $sslOptions
	 */
	public function setSslOptions(array $sslOptions=array()) {
		$this->sslOptions = $sslOptions;
	}

	/**
	 * @return \PhpAmqpLib\Connection\AMQPSSLConnection
	 */
	public function createConnection() {
		return new \PhpAmqpLib\Connection\AMQPSSLConnection($this->host, $this->port, $this->username, $this->password, $this->vhost,
			$this->sslOptions, $this->getOptions());
	}

	protected function getOptions() {
		return array(
			'insist' => $this->insist,
			'login_method' => $this->loginMethod,
			'login_response' => $this->loginResponse,
			'locale' => $this->locale,
			'connection_timeout' => $this->connectionTimeoutInSeconds,
			'read_write_timeout' => $this->readWriteTimeoutInSeconds,
		);
	}
}

?>
