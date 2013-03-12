<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel P�tzinger <daniel.poetzinger@aoemedia.de>
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
 * Linkhandler to process custom linking to any kind of configured record.
 *
 * @author	Daniel Poetzinger <daniel.poetzinger@aoemedia.de>
 * @author	Michael Klapper <michael.klapper@aoemedia.de>
 * @version $Id: $
 * @date 08.04.2009 - 15:06:25
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class tx_linkhandler_handler {

	/**
	 * @var tslib_cObj
	 */
	protected $localcObj = null;


	/**
	 * Process the link generation
	 *
	 * @param string $linktxt
	 * @param array  $typoLinkConfiguration TypoLink Configuration array
	 * @param string $linkHandlerKeyword Define the identifier that an record is given
	 * @param string $linkHandlerValue Table and uid of the requested record like "tt_news:2"
	 * @param string $linkParams Full link params like "record:tt_news:2"
	 * @param tslib_cObj $pObj
	 * @return string
	 */
	public function main($linktxt, $typoLinkConfiguration, $linkHandlerKeyword, $linkHandlerValue, $linkParams, $pObj) {
		$linkConfigArray   = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_linkhandler.'];
		$generatedLink     = '';
		$furtherLinkParams = str_replace($linkHandlerKeyword . ':' . $linkHandlerValue, '', $linkParams); // extract link params like "target", "css-class" or "title"
		list ($recordTableName, $recordUid) = t3lib_div::trimExplode(':', $linkHandlerValue);

		$recordArray = $this->getCurrentRecord($recordTableName, $recordUid);

		if ( $this->isRecordLinkable($recordTableName, $linkConfigArray, $recordArray) ) {

			$this->localcObj = clone $pObj;
			$this->localcObj->start($recordArray, '');
			$linkConfigArray[$recordTableName . '.']['parameter'] .= $furtherLinkParams;

			$currentLinkConfigurationArray = $this->mergeTypoScript($linkConfigArray , $typoLinkConfiguration, $recordTableName);

				// build the full link to the record
			$generatedLink = $this->localcObj->typoLink($linktxt, $currentLinkConfigurationArray);

			$this->updateParentLastTypoLinkMember($pObj);
		} else {
			$generatedLink = $linktxt;
		}

		return $generatedLink;
	}


	/**
	 * Indicate that the requested link can be created or not.
	 *
	 * @param string $recordTableName The name of database table
	 * @param array $linkConfigArray Global defined TypoScript cofiguration for the linkHandler
	 * @param array $recordArray Requested record to link to it
	 * @access protected
	 * @return boolean
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	protected function isRecordLinkable($recordTableName, $linkConfigArray, $recordArray) {
		$isLinkable = false;

			// record type link configuration available
		if ( is_array($linkConfigArray) && array_key_exists($recordTableName . '.', $linkConfigArray) )  {

			if (
					( is_array($recordArray) && !empty($recordArray) ) // recored available
				||
					( (int)$linkConfigArray[$recordTableName . '.']['forceLink'] === 1 ) // if the record are hidden ore someting else, force link generation
				) {

				$isLinkable = true;
			}
		}

		return $isLinkable;
	}


	/**
	 * Find the current record to work with.
	 *
	 * This method keeps attention on the l18n_parent field and retrieve the original record.
	 *
	 * @param string $recordTableName The name of database table
	 * @param integer $recordUid ID of the record
	 * @access protected
	 * @return array
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	protected function getCurrentRecord($recordTableName, $recordUid) {
		
		static $cache = array();
		$parameterHash = $recordTableName.intval($recordUid);
		
		if (isset($cache[$parameterHash])) {
			return $cache[$parameterHash];
		}
		
		$recordArray = array();
			// check for l18n_parent and fix the recordRow
		$l18nPointer = ( array_key_exists('transOrigPointerField', $GLOBALS['TCA'][$recordTableName]['ctrl']) )
							? $GLOBALS['TCA'][$recordTableName]['ctrl']['transOrigPointerField']
							: '';

		$recordArray = $GLOBALS['TSFE']->sys_page->getRawRecord($recordTableName, $recordUid);

		if ( is_array($recordArray) && (array_key_exists($l18nPointer, $recordArray) && $recordArray[$l18nPointer] > 0 && $recordArray['sys_language_uid'] > 0) ) {
			$recordArray = $GLOBALS['TSFE']->sys_page->getRawRecord($recordTableName, $recordArray[$l18nPointer]);
		}
		
		$cache[$parameterHash] = $recordArray;
		return $recordArray;
	}


	/**
	 * Update the lastTypoLink* member of the $pObj
	 *
	 * @param tslib_cObj $pObj
	 * @access public
	 * @return void
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	protected function updateParentLastTypoLinkMember($pObj) {
		$pObj->lastTypoLinkUrl    = $this->localcObj->lastTypoLinkUrl;
		$pObj->lastTypoLinkTarget = $this->localcObj->lastTypoLinkTarget;
		$pObj->lastTypoLinkLD     = $this->localcObj->lastTypoLinkLD;
	}


	/**
	 * Merge all TypoScript for the typoLink from the global and local defined settings.
	 *
	 * @param array $linkConfigArray Global defined TypoScript cofiguration for the linkHandler
	 * @param array $typoLinkConfigurationArray Local typolink TypoScript configuration for current link
	 * @param array $recordTableName The name of database table
	 * @access protected
	 * @return array
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	protected function mergeTypoScript($linkConfigArray , $typoLinkConfigurationArray, $recordTableName) {

			// precompile the "additionalParams"
		$linkConfigArray[$recordTableName . '.']['additionalParams'] = $this->localcObj->stdWrap($linkConfigArray[$recordTableName . '.']['additionalParams'],$linkConfigArray[$recordTableName . '.']['additionalParams.']);
		unset($linkConfigArray[$recordTableName . '.']['additionalParams.']);

			// merge recursive the "additionalParams" from "$linkConfigArray" with the "$typoLinkConfigurationArray"
		if ( array_key_exists('additionalParams', $typoLinkConfigurationArray) ) {
			$typoLinkConfigurationArray['additionalParams'] = t3lib_div::implodeArrayForUrl ( '',
				t3lib_div::array_merge_recursive_overrule (
					t3lib_div::explodeUrl2Array($linkConfigArray[$recordTableName . '.']['additionalParams']),
					t3lib_div::explodeUrl2Array($typoLinkConfigurationArray['additionalParams'])
				)
			);
		}

		/**
		 * @internal Merge the linkhandler configuration from $linkConfigArray with the current $typoLinkConfiguration.
		 */
		if ( is_array($typoLinkConfigurationArray) && !empty($typoLinkConfigurationArray) ) {
			if ( array_key_exists('parameter.', $typoLinkConfigurationArray) )
				unset($typoLinkConfigurationArray['parameter.']);

			$linkConfigArray[$recordTableName . '.'] = array_merge($linkConfigArray[$recordTableName . '.'], $typoLinkConfigurationArray);
		}

		return $linkConfigArray[$recordTableName . '.'];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/class.tx_linkhandler_handler.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/class.tx_linkhandler_handler.php']);
}

?>
