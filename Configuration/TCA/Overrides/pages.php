<?php
defined('TYPO3_MODE') || die();

// Register page TSconfig for inclusion
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    'linkhandler',
    'Configuration/TSconfig/Page/news.ts',
    'EXT:linkhandler - Configuration for "news"'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    'linkhandler',
    'Configuration/TSconfig/Page/tt_news.ts',
    'EXT:linkhandler - Configuration for "tt_news"'
);
