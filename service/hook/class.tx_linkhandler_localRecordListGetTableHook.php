<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, Michael Klapper <klapper@aoemedia.de>
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

require_once PATH_t3lib . 'interfaces/interface.t3lib_localrecordlistgettablehook.php';

/**
 * {@inheritdoc}
 *
 * class.tx_linkhandler_localRecordListGetTableHook.php
 *
 * @author Michael Klapper <klapper@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @version $Id$
 * @date $Date$
 * @since 21.10.2009 - 15:15:04
 * @category category
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class tx_linkhandler_localRecordListGetTableHook implements t3lib_localRecordListGetTableHook {

	/**
	 * Modifies the DB list query
	 *
	 * Default behavior is that the db list only shows the localisation parent records. If a user have set the
	 * language settings out of the page module, so the user get the specific language of records listed.
	 *
	 * @param	string             the current database table
	 * @param	integer            the record's page ID
	 * @param	string             an additional WHERE clause
	 * @param	string             comma separated list of selected fields
	 * @param	localRecordList    parent localRecordList object
	 *
	 * @access public
	 * @return	void
	 *
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject) {
		global $TCA, $BE_USER;

		if ((bool)$parentObject->localizationView === false ) {
			$mode = t3lib_div::_GP('mode');

			if ( $mode == 'wizard' || $mode == 'rte') {

				if (is_array($TCA[$table]['ctrl']) && array_key_exists('transOrigPointerField', $TCA[$table]['ctrl']) && array_key_exists('languageField', $TCA[$table]['ctrl']) ) {
					$transOrigPointerField = $TCA[$table]['ctrl']['transOrigPointerField'];
					$languageField         = $TCA[$table]['ctrl']['languageField'];
					$sysLanguageId         = $this->getUserSysLanguageUidForLanguageListing();

						// if the page module is configured to display a different language than default
					if ($sysLanguageId > 0) {
						$additionalWhereClause .= 'AND ' . $languageField . ' = ' . $sysLanguageId;
					} else {
							// show only the localisation parent records for selection
						$additionalWhereClause .= 'AND (' . $languageField . ' <= 0 || ' . $transOrigPointerField . ' = 0)';
					}
				}
			}
		}
	}

	/**
	 * Find the selected sys_language_uid which are set by the templavoila page module.
	 *
	 * @access private
	 * @return integer
	 *
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	private function getUserSysLanguageUidForLanguageListing() {
		global $BE_USER;
		$sysLanguageId = 0;
		$moduleKey     = 'web_txtemplavoilaM1';

		if (array_key_exists('web_tvpagemodulM1', $BE_USER->uc['moduleData'])) {
			$moduleKey = 'web_tvpagemodulM1';
		}

		if (
				is_array($BE_USER->uc['moduleData'][$moduleKey])
			&&
				array_key_exists('language', $BE_USER->uc['moduleData'][$moduleKey])
			&&
				((version_compare(TYPO3_version,'4.6.0','>=') && t3lib_utility_Math::convertToPositiveInteger($BE_USER->uc['moduleData'][$moduleKey]['language']) > 0)
					|| t3lib_div::intval_positive($BE_USER->uc['moduleData'][$moduleKey]['language']) > 0)
			) {

			$sysLanguageId = $BE_USER->uc['moduleData'][$moduleKey]['language'];
		}

		return $sysLanguageId;
	}
}

?>