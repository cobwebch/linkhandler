<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * TCEmain hook
 *
 * class.tx_linkhandler_tcemain.php
 *
 * @author Michael Klapper <klapper@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id$
 * @date $Date$
 * @since 22.05.2009 - 23:03:18
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class tx_linkhandler_tcemain {

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a record is saved.
	 *
	 * We use the tx_linkhandler for backend "save & show" button to display records on the configured detail view page
	 *
	 * @param	array		$fieldArray: The field names and their values to be processed (passed by reference)
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	object		$pObj: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function processDatamap_preProcessFieldArray($fieldArray, $table, $id, $pObj) {

		if ( isset($GLOBALS['_POST']['_savedokview_x']) ) {
			$settingFound   = false;
			$currentPageID  = (version_compare(TYPO3_version,'4.6.0','>=')) ? t3lib_utility_Math::convertToPositiveInteger($GLOBALS['_POST']['popViewId']) : t3lib_div::intval_positive($GLOBALS['_POST']['popViewId']);
			$rootLineStruct = t3lib_BEfunc::BEgetRootLine($currentPageID);
			$defaultPageID  = (isset($rootLineStruct[0]) && array_key_exists('uid', $rootLineStruct[0])) ? $rootLineStruct[0]['uid'] : $currentPageID ;

			$pagesTSC = t3lib_BEfunc::getPagesTSconfig($currentPageID, $rootLineStruct); // get page TSconfig
			$handlerConfigurationStruct = $pagesTSC['mod.']['tx_linkhandler.'];

				// search for the current setting for given table
			foreach ($pagesTSC['mod.']['tx_linkhandler.'] as $key => $handler) {
				if ( (is_array($handler)) && ($handler['listTables'] === $table) ) {
					$settingFound = true;
					$selectedConfiguration = $key;
					break;
				}
			}

			if ($settingFound) {
				t3lib_div::loadTCA($table);
				$l18nPointer = ( array_key_exists('transOrigPointerField', $GLOBALS['TCA'][$table]['ctrl']) ) ? $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] : '';
 				if ((version_compare(TYPO3_version,'4.6.0','>=') && !t3lib_utility_Math::canBeInterpretedAsInteger($id))
 					|| !t3lib_div::testInt($id)) {

 					$id = $pObj->substNEWwithIDs[$id];
 				}
 				if ((version_compare(TYPO3_version,'4.6.0','>=') && t3lib_utility_Math::canBeInterpretedAsInteger($id))
 					|| t3lib_div::testInt($id)) {

 					$recordArray = t3lib_BEfunc::getRecord($table, $id);
 				} else {
 					$recordArray = $fieldArray;
				}

				if ( array_key_exists('previewPageId', $handlerConfigurationStruct[$selectedConfiguration]) && (t3lib_utility_Math::convertToPositiveInteger($handlerConfigurationStruct[$selectedConfiguration]['previewPageId']) > 0) ) {
					$previewPageId = (version_compare(TYPO3_version,'4.6.0','>=')) ? t3lib_utility_Math::convertToPositiveInteger($handlerConfigurationStruct[$selectedConfiguration]['previewPageId']) : t3lib_div::intval_positive($handlerConfigurationStruct[$selectedConfiguration]['previewPageId']);
				} else {
					$previewPageId = (version_compare(TYPO3_version,'4.6.0','>=')) ? t3lib_utility_Math::convertToPositiveInteger($defaultPageID) : t3lib_div::intval_positive($defaultPageID);
				}

				if ($GLOBALS['BE_USER']->workspace != 0) {
					$timeToLiveHours = ( intval($GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.previewLinkTTLHours')) ) ? intval($GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.previewLinkTTLHours')) : 24*2 ;
					$WSPreviewValue = ';' . $GLOBALS['BE_USER']->workspace . ':' . $GLOBALS['BE_USER']->user['uid'] . ':' . (60 * 60 * $timeToLiveHours);

						// get record UID for
					if ( array_key_exists($l18nPointer, $recordArray) && $recordArray[$l18nPointer] > 0 && $recordArray['sys_language_uid'] > 0) {
						$id = $recordArray[$l18nPointer];
					} elseif ( array_key_exists('t3ver_oid', $recordArray) ) // this makes no sense because we already recive the UID of the WS-Placeholder which will be the real record in the LIVE-WS
						 $id = $recordArray['t3ver_oid'];

				} else {
					$WSPreviewValue = '';

					if ( (array_key_exists($l18nPointer, $recordArray) && $recordArray[$l18nPointer] > 0 && $recordArray['sys_language_uid'] > 0) ) {
						$id = $recordArray[$l18nPointer];
					}
				}

				$linkParamValue = 'record:' . $table . ':' . $id ;

				$queryString   = '&eID=linkhandlerPreview&linkParams=' . $linkParamValue . $WSPreviewValue;
				$languageParam = '&L=' . $recordArray['sys_language_uid'];
				$queryString  .= $languageParam . '&authCode=' . t3lib_div::stdAuthCode($linkParamValue . $WSPreviewValue . $recordArray['sys_language_uid'], '', 32);

				$GLOBALS['_POST']['viewUrl'] = '/index.php?id=' . $previewPageId . $queryString . '&y=';
			}
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/service/class.tx_linkhandler_tcemain.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/service/class.tx_linkhandler_tcemain.php']);
}
?>
