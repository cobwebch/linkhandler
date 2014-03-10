<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('linkhandler');
$extensionClassesPath = $extensionPath . 'Classes/';
return array(
	'aoe\\linkhandler\\configurationmanager' => $extensionClassesPath . 'ConfigurationManager.php',
	'aoe\\linkhandler\\browser\\elementbrowserhook' => $extensionClassesPath . 'Browser/ElementBrowserHook.php',
	'aoe\\linkhandler\\linkhandler' => $extensionClassesPath . 'LinkHandler.php',
	'aoe\\linkhandler\\browser\\pagetree' => $extensionClassesPath . 'Browser/PageTree.php',
	'aoe\\linkhandler\\browser\\recordlistrte' => $extensionClassesPath . 'Browser/RecordListRte.php',
	'aoe\\linkhandler\\rteparserhook' => $extensionClassesPath . 'RteParserHook.php',
	'aoe\\linkhandler\\softreferencehandler' => $extensionClassesPath . 'SoftReferenceHandler.php',
	'aoe\\linkhandler\\browser\\tabhandler' => $extensionClassesPath . 'Browser/TabHandler.php',
	'aoe\\linkhandler\\browser\\tabhandlerfactory' => $extensionClassesPath . 'Browser/TabHandlerFactory.php',
	'aoe\\linkhandler\\browser\\tabhandlerinterface' => $extensionClassesPath . 'Browser/TabHandlerInterface.php',
);