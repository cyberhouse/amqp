<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

require_once(t3lib_extMgm::extPath($_EXTKEY) . 'Resources/Private/ClassLoader.php');
$classLoader = new \CYBERHOUSE\Queue\ClassLoader();
$classLoader->add('PhpAmqpLib', t3lib_extMgm::extPath($_EXTKEY) . 'Resources/Private/php-amqplib');
$classLoader->register();


// EXTConf AMQP connectionFactory config
if(!isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY . '/connectionFactory'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY . '/connectionFactory'] = array(
		'className' => 'Tx_Amqp_Messaging_AMQPConnectionFactory',
		'options' => Tx_Amqp_Util_ConfigurationHelper::getExtensionConfigurationQuietly(),
	);
}

?>
