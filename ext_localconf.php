<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

//add linkhandler for "record"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = 'Aoe\\Linkhandler\\LinkHandler';

//register hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] = 'Aoe\\Linkhandler\\Browser\\ElementBrowserHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] = 'Aoe\\Linkhandler\\Browser\\ElementBrowserHook';

?>