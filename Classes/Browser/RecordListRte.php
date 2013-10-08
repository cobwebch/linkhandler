<?php
namespace Aoe\Linkhandler\Browser;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TBE_browser_recordListRTE extends TBE_browser_recordList
 * to return correct linkWraps for RTE link browser
 *
 * @author    Daniel Poetzinger (AOE media GmbH)
 */
class RecordListRte extends \TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList {

	/**
	 * @var \TYPO3\CMS\Rtehtmlarea\BrowseLinks
	 */
	protected $browseLinksObj;

	/**
	 * A search query that can be used to filter records, e.g.
	 * tt_content with a defined CType
	 *
	 * @var string
	 */
	protected $additionalSearchQuery;

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
	 *
	 * @param    string $table       Table name
	 * @param    integer $uid        UID (not used here)
	 * @param    string  $code      Title string
	 * @param    array   $row     Records array (from table name)
	 * @return    string
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
			$aOnClick = 'return link_spec(\'' . $recordLink . '\');"';
		} else {
			// Used in wizard mode
			$aOnClick = 'return link_folder(\'' . $recordLink . '\');"';
		}

		$ATag = '<a href="#" onclick="' . $aOnClick . '">';
		$ATag_e = '</a>';

		$blinkArrow = '';
		if ($this->browseLinksObj->curUrlInfo['recordTable'] == $table && $this->browseLinksObj->curUrlInfo['recordUid'] == $uid) {
			$blinkArrow = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_right.gif', 'width="5" height="9"') . ' class="c-blinkArrowL" alt="" />';
		}

		return $ATag . '<img' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' title="' . $lang->getLL('addToList', 1) . '" alt="" />' . $ATag_e . $ATag . $code . $blinkArrow . $ATag_e;
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
		if (!empty($this->additionalSearchQuery)) {
			$searchString .= ' ' . $this->additionalSearchQuery;
		}
		return $searchString;
	}

	/**
	 * Setter for an additional search query that should be append to
	 * any other search query. Can be used to filter records, e.g.
	 * contents with a defined CType
	 *
	 * @param string $searchQuery
	 */
	public function setAdditionalSearchQuery($searchQuery) {
		$this->additionalSearchQuery = trim($searchQuery);
	}

	/**
	 * Setter for the calling link browser instance
	 *
	 * @param \TYPO3\CMS\Rtehtmlarea\BrowseLinks $browseLinksObj
	 */
	public function setBrowseLinksObj($browseLinksObj) {
		$this->browseLinksObj = $browseLinksObj;
	}
}