<?php
if (!defined ('TYPO3_MODE'))
	die ('Access denied.');

global $TYPO3_CONF_VARS, $_EXTKEY;
$configurationArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

/**
 * Add conditional XCLASS to your TYPO3 environment
 *
 * The patches will be automaticly applyed if the TYPO3 version is lower than 4.2.0
 */
if ( version_compare(TYPO3_version, '4.2.0', '<') ) {
	// register XCLASSES (adds hooks)
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']=t3lib_extMgm::extPath($_EXTKEY) . 'patch/4.1/class.ux_tx_rtehtmlarea_browse_links.php';
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php']=t3lib_extMgm::extPath($_EXTKEY) . 'patch/4.1/class.ux_browse_links.php';
	// patch because of yellow box arround rte links:
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php']=t3lib_extMgm::extPath($_EXTKEY) . 'patch/4.1/class.ux_t3lib_parsehtml_proc.php';

	if ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_content.php'] !='') {
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.ux_tslib_content.php']=t3lib_extMgm::extPath($_EXTKEY) . 'patch/4.1/class.ux_ux_tslib_content.php';
	} else {
	    $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_content.php']=t3lib_extMgm::extPath($_EXTKEY) . 'patch/4.1/class.ux_tslib_content.php';
	}
}

/**
 * Register some hooks
 */
 // Enable softref parser work with linkhandler values
if ( version_compare(TYPO3_version, '4.3.1', '<=') && is_array($configurationArray) && array_key_exists('applyXclassToEnableSoftrefParser', $configurationArray) && ($configurationArray['applyXclassToEnableSoftrefParser']) == 1) {
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_softrefproc.php'] = t3lib_extMgm::extPath($_EXTKEY) . '/patch/class.ux_t3lib_softrefproc.php';
}
// Enalbe  bug fix #10827: Hide "Save and View"-button when editing a content-element
if ( version_compare(TYPO3_version, '4.3.0', '<') && is_array($configurationArray) && array_key_exists('applyXclassHideSaveAndViewButton', $configurationArray) && ($configurationArray['applyXclassHideSaveAndViewButton']) == 1) {
	if ( t3lib_extMgm::isLoaded('languagevisibility') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc.php'] != '' )
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/languagevisibility/patch/class.ux_SC_alt_doc.php'] = t3lib_extMgm::extPath($_EXTKEY) . '/patch/class.ux_ux_alt_doc.php';
	elseif ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc.php'] == '' ) 
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc.php'] = t3lib_extMgm::extPath($_EXTKEY) . '/patch/class.ux_alt_doc.php';
}

// add linkhandler for "record"
// require_once(t3lib_extMgm::extPath($_EXTKEY) . 'class.tx_linkhandler_handler.php');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = 'EXT:linkhandler/class.tx_linkhandler_handler.php:&tx_linkhandler_handler';

// register hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][]='EXT:linkhandler/service/hook/class.tx_linkhandler_browselinkshooks.php:tx_linkhandler_browselinkshooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][]='EXT:linkhandler/service/hook/class.tx_linkhandler_browselinkshooks.php:tx_linkhandler_browselinkshooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][] = 'EXT:linkhandler/service/hook/class.tx_linkhandler_localRecordListGetTableHook.php:tx_linkhandler_localRecordListGetTableHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_softrefproc.php']['typolinkLinkHandler']['record'] = 'EXT:linkhandler/service/hook/class.tx_linkhandler_softref_typolinkLinkHandlerHook.php:tx_linkhandler_softref_typolinkLinkHandlerHook';

	// Register hook to link the "save & show" button to the single view of an record
include_once t3lib_extMgm::extPath($_EXTKEY) . 'service/class.tx_linkhandler_tcemain.php';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:' . $_EXTKEY . '/service/class.tx_linkhandler_tcemain.php:tx_linkhandler_tcemain';

	// Register eID for the link generation used by the "save & show" button - hook
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['linkhandlerPreview'] = 'EXT:' . $_EXTKEY . '/service/class.tx_linkhandler_service_eid.php';

?>
