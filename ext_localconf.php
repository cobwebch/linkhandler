<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// Add linkhandler for "record"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = 'Aoe\\Linkhandler\\LinkHandler';

// Register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] = 'Aoe\\Linkhandler\\Browser\\ElementBrowserHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'][] = 'Aoe\\Linkhandler\\RteParserHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] = 'Aoe\\Linkhandler\\Browser\\ElementBrowserHook';

$linkhandlerExtConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['linkhandler']);

if (is_array($linkhandlerExtConf) && $linkhandlerExtConf['includeTtNewsTsConfig']) {

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('

		RTE.default.tx_linkhandler {
			tx_tt_news_news {
				label = News
				listTables = tt_news
			}
		}
	');
}