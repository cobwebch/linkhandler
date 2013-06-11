<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Link handler');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
RTE.default.tx_linkhandler {
	tt_news {
		label=News
		listTables=tt_news
	}
}

mod.tx_linkhandler {
	tt_news {
		label=News
		listTables=tt_news
	}
}
');
