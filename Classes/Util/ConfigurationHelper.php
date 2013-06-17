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


final class Tx_Amqp_Util_ConfigurationHelper {

	private static $connectionFactory = NULL;
	private static $extConf = NULL;

	private final function __construct() {}

	/**
	 * Instantiates the configured connectionFactory.
	 *
	 * A connectionFactory must implement the Tx_Amqp_Messaging_AMQPConnectionFactoryInterface and
	 * takes the options as an associative array.
	 *
	 * Example config:
	 * <code>
	 * $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['amqp/connectionFactory'] = array(
	 *  	'className' => 'Tx_Amqp_Messaging_AMQPConnectionFactory',
	 * 		'options' => array(
	 *			'username' => 'guest',
	 *			'password' => 'guest',
	 *			'host' => 'localhost',
	 *			'vhost' => '/',
	 * 		),
	 * );
	 * </code>
	 *
	 * @throws RuntimeException
	 * @return Tx_Amqp_Messaging_AMQPConnectionFactoryInterface
	 */
	public static function getConnectionFactory() {
		if(self::$connectionFactory === NULL) {
			$connectionFactoryConfig = self::getConnectionFactoryConfiguration();
			$clazz = $connectionFactoryConfig['className'];
			$options = $connectionFactoryConfig['options'];
			self::$connectionFactory = t3lib_div::makeInstance($clazz);
			if(!(self::$connectionFactory instanceof Tx_Amqp_Messaging_AMQPConnectionFactoryInterface)) {
				throw new RuntimeException(sprintf('Configured ConnectionFactory [%s] must implement Interface [%s]. ' .
					'See $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'extConf\'][\'amqp/connectionFactory\'][\'className\'].', $clazz, 'Tx_Amqp_Messaging_AMQPConnectionFactoryInterface'));
			}
			self::$connectionFactory->setOptions($options);
		}
		return self::$connectionFactory;
	}

	/**
	 * Returns the connectionFactory configuration.
	 * <code>
	 * array(
	 * 		'className' => 'SomeConnectionFactory',
	 * 		'options' => array(
	 * 			...
	 * 		),
	 * );
	 * </code>
	 *
	 * @return array
	 */
	public static function getConnectionFactoryConfiguration() {
		return $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['amqp/connectionFactory'];
	}

	/**
	 * Returns this extension's configuration array (extConf). Throws a RuntimeException on error.
	 *
	 * @throws RuntimeException
	 * @return array The configuration array.
	 */
	public static function getExtensionConfiguration() {
		if(self::$extConf === NULL) {
			$extensionName = 'amqp';
			self::$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionName]);
			if(!self::$extConf) {
				throw new \RuntimeException(sprintf('The extension [%s] has not been configured. Configure the extension using the ExtensionManager.', $extensionName));
			}
		}
		return self::$extConf;
	}

	/**
	 * @see getExtensionConfiguration()
	 */
	public static function getExtensionConfigurationQuietly() {
		try {
			return self::getExtensionConfiguration();
		} catch(\Exception $ignored) {
		}
		return NULL;
	}
}

?>
