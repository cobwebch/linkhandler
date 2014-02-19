<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Link handler');

$GLOBALS['TBE_STYLES']['skins']['t3skin']['stylesheetDirectories']['tx_linkhandler_styles'] = 'EXT:linkhandler/Resources/Public/BackendStyles/';