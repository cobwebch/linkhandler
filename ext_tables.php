<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news')) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/tt_news', 'Link handler - tt_news');
}

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/news', 'Link handler - news');
}

$GLOBALS['TBE_STYLES']['skins']['t3skin']['stylesheetDirectories']['tx_linkhandler_styles'] = 'EXT:linkhandler/Resources/Public/BackendStyles/';