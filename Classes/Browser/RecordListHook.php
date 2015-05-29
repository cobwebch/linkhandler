<?php
namespace Aoe\Linkhandler\Browser;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;

/**
 * Hooks into the record list to filter records for the element browser.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package Aoe\Linkhandler\Browser
 */
class RecordListHook implements RecordListGetTableHookInterface {
	/**
	 * Modified the List database query to avoid displaying translations when called
	 * for the linkhandler element browser.
	 *
	 * @param string $table The current database table
	 * @param int $pageId The record's page ID
	 * @param string $additionalWhereClause An additional WHERE clause
	 * @param string $selectedFieldsList Comma separated list of selected fields
	 * @param \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $parentObject Parent \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList object
	 * @return void
	 */
	public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject) {
		$parentClass = get_class($parentObject);
		// Act only if the calling object is our own element browser
		if ($parentClass === 'Aoe\Linkhandler\Browser\RecordListRte') {
			// If the table uses localization in same table, add condition to display on records in default language.
			// We don't want the user to be able to point to translations, as these would create wrong links.
			if ($GLOBALS['TCA'][$table]['ctrl']['languageField']
				&& $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
				&& !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']) {
				$additionalWhereClause .= ' AND ' . $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . ' = 0 ';
			}
		}
	}

}