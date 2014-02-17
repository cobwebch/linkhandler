<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Link handler');

// We register ourselfes as a skin so that we can modify the styles of
// the list in the link browser.
if (TYPO3_MODE == 'BE' || TYPO3_MODE == 'FE' && isset($GLOBALS['BE_USER'])) {
	$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY] = array(
		'name' => 'linkhandler'
	);
}