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
 * Basic AMQP ConnectionFactory implementation.
 * Uses the default port and a default vhost.
 */
class Tx_Amqp_Messaging_AMQPConnectionFactory implements Tx_Amqp_Messaging_AMQPConnectionFactoryInterface {

	protected $host;
	protected $port = 5672;
	protected $username;
	protected $password;
	protected $vhost = '/';

	protected $connectionTimeoutInSeconds = 3;
	protected $readWriteTimeoutInSeconds = 3;
	protected $locale = 'en_US';
	protected $loginMethod = 'AMQPLAIN';
	protected $loginResponse;
	protected $insist = FALSE;

	/**
	 * Creates an AMQP Connection factory using the host given.
	 *
	 * @param string $host
	 */
	public function __construct($host) {
		$this->host = $host;
	}

	/**
	 * @param mixed $host
	 */
	public function setHost($host) {
		$this->host = $host;
	}

	/**
	 * @param int $port
	 */
	public function setPort($port) {
		$this->port = $port;
	}

	/**
	 * @param mixed $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * @param mixed $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * @param string $vhost
	 */
	public function setVhost($vhost) {
		$this->vhost = $vhost;
	}

	/**
	 * @param int $connectionTimeoutInSeconds
	 */
	public function setConnectionTimeoutInSeconds($connectionTimeoutInSeconds) {
		$this->connectionTimeoutInSeconds = $connectionTimeoutInSeconds;
	}

	/**
	 * @param int $readWriteTimeoutInSeconds
	 */
	public function setReadWriteTimeoutInSeconds($readWriteTimeoutInSeconds) {
		$this->readWriteTimeoutInSeconds = $readWriteTimeoutInSeconds;
	}

	/**
	 * @param boolean $insist
	 */
	public function setInsist($insist) {
		$this->insist = $insist;
	}

	/**
	 * @param string $locale
	 */
	public function setLocale($locale) {
		$this->locale = $locale;
	}

	/**
	 * @param string $loginMethod
	 */
	public function setLoginMethod($loginMethod) {
		$this->loginMethod = $loginMethod;
	}

	/**
	 * @param mixed $loginResponse
	 */
	public function setLoginResponse($loginResponse) {
		$this->loginResponse = $loginResponse;
	}

	/**
	 * Convenience method to setup this factory using an associative array of options
	 *
	 * @param array $options
	 * @throws RuntimeException
	 */
	public function setOptions(array $options) {
		foreach($options as $property => $value) {
			if(property_exists($this, $property)) {
				$this->$property = $value;
			} else {
				throw new \RuntimeException(sprintf('Property [%s] does not exist.', $property));
			}
		}
	}

	/**
	 * Creates a new AMQP Connection.
	 *
	 * @return \PhpAmqpLib\Connection\AMQPConnection
	 */
	public function createConnection() {
		return new \PhpAmqpLib\Connection\AMQPConnection($this->host, $this->port, $this->username, $this->password, $this->vhost,
			$this->insist, $this->loginMethod, $this->loginResponse, $this->locale, $this->connectionTimeoutInSeconds, $this->readWriteTimeoutInSeconds);
	}
}

?>
