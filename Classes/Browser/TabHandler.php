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

/**
 * Hook to adjust linkwizard (linkbrowser)
 *
 * @author Daniel Poetzinger (AOE media GmbH)
 */
class TabHandler implements \Aoe\Linkhandler\Browser\TabHandlerInterface {

	/**
	 * @var boolean
	 */
	protected $isRTE;

	/**
	 * @var \TYPO3\CMS\Rtehtmlarea\BrowseLinks
	 */
	protected $browseLinksObj;

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * Initialize the class
	 *
	 * @param ElementBrowserHook $elementBrowserHook
	 * @param string $activeTab
	 * @return \Aoe\Linkhandler\Browser\TabHandler
	 */
	public function __construct($elementBrowserHook, $activeTab) {
		$this->browseLinksObj = $elementBrowserHook->getElementBrowser();
		$this->isRTE = $elementBrowserHook->isRTE();
		$this->expandPage = $this->browseLinksObj->expandPage;
		$this->configuration = $elementBrowserHook->getTabConfig($activeTab);
	}

	/**
	 * interface function. should return the correct info array that is required for the link wizard.
	 * It should detect if the current value is a link where this tabHandler should be responsible.
	 * else it should return a emty array
	 *
	 * @param string $href
	 * @param array $tabsConfig
	 * @return array
	 */
	static public function getLinkBrowserInfoArray($href, $tabsConfig) {

		$info = array();

		if (strtolower(substr($href, 0, 7)) == 'record:') {

			$parts = explode(':', $href);

			// check the linkhandler TSConfig and find out  which config is responsible for the current table:
			foreach ($tabsConfig as $key => $tabConfig) {
				if ($parts[1] == $tabConfig['listTables']) {
					$info['act'] = $key;
				}
			}

			$info['recordTable'] = $parts[1];
			$info['recordUid'] = $parts[2];
			$info['prevent-act-override'] = TRUE;
		}

		return $info;
	}

	/**
	 * Build the content of an tab
	 *
	 * @access public
	 * @uses tx_rtehtmlarea_browse_links
	 * @return    string a tab for the selected link action
	 */
	public function getTabContent() {

		$expandPage = NULL;
		if (!isset($this->browseLinksObj->expandPage)) {
			$urlInfo = $this->browseLinksObj->curUrlInfo;
			if ($urlInfo['recordTable'] && $urlInfo['recordUid']) {
				$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($urlInfo['recordTable'], $urlInfo['recordUid']);
				if (isset($record)) {
					$expandPage = $record['pid'];
					$this->browseLinksObj->expandPage = $expandPage;
				}
			}
		}

		$content = '';

		if ($this->isRTE) {
			$content .= $this->browseLinksObj->addAttributesForm();
		}

		/** @var \Aoe\Linkhandler\Browser\PageTree $pagetree */
		/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $beUser */
		$beUser = $GLOBALS['BE_USER'];
		$pagetree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Aoe\\Linkhandler\\Browser\\PageTree');
		$pagetree->ext_showNavTitle = $beUser->getTSConfigVal('options.pageTree.showNavTitle');
		$pagetree->ext_showPageId = $beUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$pagetree->addField('nav_title');

		$pm = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('PM');
		if (isset($expandPage) && !isset($pm)) {
			$pagetree->expandToPage($expandPage);
		}

		$tables = '*';
		if (isset($this->configuration['listTables'])) {
			$tables = $this->configuration['listTables'];
		}

		/** @var \Aoe\Linkhandler\Browser\RecordListRte $recordList */
		$recordList = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Aoe\\Linkhandler\\Browser\\RecordListRte');
		$recordList->setBrowseLinksObj($this->browseLinksObj);
		$this->browseLinksObj->setRecordList($recordList);
		$cElements = $this->browseLinksObj->TBE_expandPage($tables);

		// Outputting Temporary DB mount notice:
		$dbmount = '';
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];
		if (intval($beUser->getSessionData('pageTree_temporaryMountPoint'))) {
			$link = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('setTempDBmount' => 0))) . '">' . $lang->sl('LLL:EXT:lang/locallang_core.xlf:labels.temporaryDBmount', 1) . '</a>';
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $link, '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$dbmount = $flashMessage->render();
		}
		$content .= '
			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">' . $this->browseLinksObj->barheader(($lang->getLL('pageTree') . ':')) . $dbmount . $pagetree->getBrowsableTree() . '</td>
							<td class="c-wCell" valign="top">' . $cElements . '</td>
						</tr>
					</table>

					';
		return $content;
	}
}