<?php
defined('TYPO3_MODE') || die();

// Add tx_linkhandler type to linkvalidator
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'mod.linkvalidator.linktypes := addToList(tx_linkhandler)'
);
