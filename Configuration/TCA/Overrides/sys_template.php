<?php
defined('TYPO3_MODE') || die();

// Register static TypoScript templates
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'linkhandler',
        'Configuration/TypoScript/tt_news',
        'Link handler - tt_news'
    );
}
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'linkhandler',
        'Configuration/TypoScript/news',
        'Link handler - news'
    );
}
