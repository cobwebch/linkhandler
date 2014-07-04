<?php
namespace Aoe\Linkhandler\Browser;

/***************************************************************
 * Copyright notice
 *
 * Copyright (c) 2008, Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Class TBE_browser_recordListRTE extends TBE_browser_recordList
 * to return correct linkWraps for RTE link browser
 *
 * @author Daniel Poetzinger (AOE media GmbH)
 */
class RecordListRte extends \TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList {

	/**
	 * A search query that can be used to filter records, e.g.
	 * tt_content with a defined CType
	 *
	 * @var string
	 */
	protected $additionalSearchQueries;

	/**
	 * @var \TYPO3\CMS\Rtehtmlarea\BrowseLinks
	 */
	protected $browseLinksObj;

	/**
	 * If this is TRUE the searchbox will be rendered after the list.
	 *
	 * @var bool
	 */
	protected $enableSearchBox = TRUE;

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
	 *
	 * @param string $table Table name
	 * @param integer $uid UID (not used here)
	 * @param string $code Title string
	 * @param array $row Records array (from table name)
	 * @return string
	 */
	function linkWrapItems($table, $uid, $code, $row) {

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		if (!$code) {
			$code = '<i>[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', 1) . ']</i>';
		} else {
			$code = BackendUtility::getRecordTitlePrep($code, $this->fixedL);
		}

		$recordLink = implode(':', array('record', $this->browseLinksObj->act, $table, $row['uid']));

		if (@$this->browseLinksObj->mode == 'rte') {
			// Used in RTE mode:
			$aOnClick = 'return link_spec(\'' . $recordLink . '\');';
		} else {
			// Used in wizard mode
			$aOnClick = 'return link_folder(\'' . $recordLink . '\');';
		}

		$ATag = '<a href="#" onclick="' . $aOnClick . '">';
		$ATag_e = '</a>';

		$blinkArrow = '';
		if ($this->browseLinksObj->curUrlInfo['recordTable'] == $table && $this->browseLinksObj->curUrlInfo['recordUid'] == $uid) {
			$blinkArrow = '<img' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_right.gif', 'width="5" height="9"') . ' class="c-blinkArrowL" alt="" />';
		}

		return $ATag . $code . $blinkArrow . $ATag_e;
	}

	/**
	 * Calls the parent makeSearchString() method and appends the value
	 * from the additionalSearchQuery is it is not empty.
	 *
	 * @param string $table
	 * @param int $currentPid
	 * @return string
	 */
	public function makeSearchString($table, $currentPid = -1) {

		$searchString = parent::makeSearchString($table, $currentPid);

		if (!empty($this->additionalSearchQueries[$table])) {
			$searchString .= ' ' . $this->additionalSearchQueries[$table];
		}

		return $searchString;
	}

	/**
	 * Setter for an additional search query that should be append to
	 * any other search query. Can be used to filter records, e.g.
	 * contents with a defined CType
	 *
	 * @param string $table
	 * @param string $searchQuery
	 */
	public function addAdditionalSearchQuery($table, $searchQuery) {
		$this->additionalSearchQueries[$table] = trim($searchQuery);
	}

	/**
	 * Setter for the calling link browser instance
	 *
	 * @param \TYPO3\CMS\Rtehtmlarea\BrowseLinks $browseLinksObj
	 */
	public function setBrowseLinksObj($browseLinksObj) {
		$this->browseLinksObj = $browseLinksObj;
	}

	/**
	 * Creates the listing of records from a single table
	 *
	 * @param string $table Table name
	 * @param integer $id Page id
	 * @param string $rowlist List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @return string HTML table with the listing for the record.
	 */
	public function getTable($table, $id, $rowlist) {

		// Prevent display of clipboard / _REF_ columnm
		$this->dontShowClipControlPanels = TRUE;

		// Prevent display of edit buttons
		$this->calcPerms = FALSE;

		return parent::getTable($table, $id, $rowlist);
	}

	/**
	 * Prevent the display of the field select box.
	 *
	 * @param string $table Table name
	 * @param bool $formFields If TRUE, form-fields will be wrapped around the table.
	 * @return string HTML table with the selector box (name: displayFields['.$table.'][])
	 */
	public function fieldSelectBox($table, $formFields = TRUE) {
		return '';
	}

	/**
	 * Prevent the display of the search box.
	 *
	 * @param bool $formFields If TRUE, the search box is wrapped in its own form-tags
	 * @return string HTML for the search box
	 */
	public function getSearchBox($formFields = TRUE) {
		$searchBox = '';
		if ($this->enableSearchBox) {
			$searchBox = parent::getSearchBox($formFields);
		}
		return $searchBox;
	}

	/**
	 * Set this to FALSE to disable the search box that is enabled by default.
	 *
	 * @param bool $enableSeachBox
	 * @return void
	 */
	public function setEnableSearchBox($enableSeachBox) {
		$this->enableSearchBox = (bool)$enableSeachBox;
	}
}