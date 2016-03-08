<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Add typolink handler for "record" links
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = \Cobweb\Linkhandler\TypolinkHandler::class;

// Register signal slots
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
        'getTypoLinkParts',
        \Cobweb\Linkhandler\SoftReferenceHandler::class,
        'getTypoLinkParts',
        false
);
$signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
        'setTypoLinkPartsElement',
        \Cobweb\Linkhandler\SoftReferenceHandler::class,
        'setTypoLinkPartsElement',
        false
);

// Register linkvalidator custom type
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['tx_linkhandler'] = \Cobweb\Linkhandler\Linkvalidator\LinkhandlerLinkType::class;
