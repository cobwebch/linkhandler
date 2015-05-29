<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// Add linkhandler for "record"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = 'Aoe\\Linkhandler\\LinkHandler';

// Register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] = 'Aoe\\Linkhandler\\Browser\\ElementBrowserHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] = 'Aoe\\Linkhandler\\Browser\\ElementBrowserHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][] = 'Aoe\\Linkhandler\\Browser\\RecordListHook';

// Register signal slots
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect('TYPO3\\CMS\\Core\\Database\\SoftReferenceIndex', 'getTypoLinkParts', 'Aoe\\Linkhandler\\SoftReferenceHandler', 'getTypoLinkParts', FALSE);
$signalSlotDispatcher->connect('TYPO3\\CMS\\Core\\Database\\SoftReferenceIndex', 'setTypoLinkPartsElement', 'Aoe\\Linkhandler\\SoftReferenceHandler', 'setTypoLinkPartsElement', FALSE);

$linkhandlerExtConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['linkhandler']);

if (
	is_array($linkhandlerExtConf)
	&& $linkhandlerExtConf['includeDefaultTsConfig']
) {
	if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news')) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
			<INCLUDE_TYPOSCRIPT: source="FILE: EXT:linkhandler/Configuration/TypoScript/tt_news/setup.txt">
			mod.tx_linkhandler.tx_tt_news_news < plugin.tx_linkhandler.tx_tt_news_news
			RTE.default.tx_linkhandler.tx_tt_news_news < plugin.tx_linkhandler.tx_tt_news_news
		');
	}

	if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
			<INCLUDE_TYPOSCRIPT: source="FILE: EXT:linkhandler/Configuration/TypoScript/news/setup.txt">
			mod.tx_linkhandler.tx_news_news < plugin.tx_linkhandler.tx_news_news
			RTE.default.tx_linkhandler.tx_news_news < plugin.tx_linkhandler.tx_news_news
		');
	}
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['tx_linkhandler'] = 'Aoe\\Linkhandler\\Linkvalidator\\LinkhandlerLinkType';

unset($linkhandlerExtConf);