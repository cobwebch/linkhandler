<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Add typolink handler for "record" links
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = 'Cobweb\\Linkhandler\\TypolinkHandler';

// Register signal slots
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect('TYPO3\\CMS\\Core\\Database\\SoftReferenceIndex', 'getTypoLinkParts', 'Cobweb\\Linkhandler\\SoftReferenceHandler', 'getTypoLinkParts', FALSE);
$signalSlotDispatcher->connect('TYPO3\\CMS\\Core\\Database\\SoftReferenceIndex', 'setTypoLinkPartsElement', 'Cobweb\\Linkhandler\\SoftReferenceHandler', 'setTypoLinkPartsElement', FALSE);

/*
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['tx_linkhandler'] = 'Aoe\\Linkhandler\\Linkvalidator\\LinkhandlerLinkType';
*/