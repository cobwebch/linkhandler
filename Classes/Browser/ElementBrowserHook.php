<?php
namespace Aoe\Linkhandler\Browser;

/*                                                                        *
 * This script belongs to the TYPO3 extension "linkhandler".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook to adjust linkwizard (linkbrowser).
 *
 * @author Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
 * @author Alexander Stehlik <astehlik.deleteme@intera.de>
 */
class ElementBrowserHook implements ElementBrowserHookInterface {

	/**
	 * @var \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
	 */
	protected $backendUserAuth;

	/**
	 * @var \Aoe\Linkhandler\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	protected $languageService;

	/**
	 * the browse_links object
	 *
	 * @var \TYPO3\CMS\Rtehtmlarea\BrowseLinks
	 */
	protected $pObj;

	/**
	 * @var \Aoe\Linkhandler\Browser\TabHandlerFactory
	 */
	protected $tabHandlerFactory;

	/**
	 * Configurations for the different tabs, array key is the
	 * configuration key.
	 *
	 * @var array
	 */
	protected $tabsConfig;

	/**
	 * Initializes global objects
	 */
	public function __construct() {
		$this->backendUserAuth = $GLOBALS['BE_USER'];
		$this->languageService = $GLOBALS['LANG'];
		$this->configurationManager = GeneralUtility::makeInstance('Aoe\\Linkhandler\\ConfigurationManager');
		$this->tabHandlerFactory = GeneralUtility::makeInstance('Aoe\\Linkhandler\\Browser\\TabHandlerFactory');
	}

	/**
	 * Adds new items to the currently allowed ones and returns them
	 *
	 * @param array $allowedItems currently allowed items
	 * @return array currently allowed items plus added items
	 */
	public function addAllowedItems($allowedItems) {

		foreach ($this->configurationManager->getTabsConfiguration() as $name => $tabConfig) {
			$allowedItems[] = $name;
		}

		return $allowedItems;
	}

	/**
	 * Returns current pageid
	 *
	 * @return int
	 */
	public function getCurrentPageId() {
		if ($this->isRTE()) {
			$confParts = explode(':', $this->pObj->RTEtsConfigParams);
			return $confParts[5];
		} else {
			return NULL;
		}
	}

	/**
	 * Get current href value (diffrent for RTE and normal browselinks)
	 *
	 * @return string
	 */
	public function getCurrentValue() {

		if ($this->isRTE()) {
			$currentValue = $this->pObj->curUrlInfo['value'];
		} else {
			$currentValue = $this->pObj->P['currentValue'];
		}

		return $currentValue;
	}

	/**
	 * @return \TYPO3\CMS\Rtehtmlarea\BrowseLinks
	 */
	public function getElementBrowser() {
		return $this->pObj;
	}

	/**
	 * Returns a new tab for the browse links wizard. Will be called
	 * by the parent link browser.
	 *
	 * @param string $activeTab current link selector action
	 * @return string a tab for the selected link action
	 */
	public function getTab($activeTab) {

		$configuration = $this->configurationManager->getSingleTabConfiguration($activeTab);

		if (!is_array($configuration)) {
			return FALSE;
		}

		$tabHandler = $this->tabHandlerFactory->createTabHandler($configuration, $activeTab, $this);
		$content = $tabHandler->getTabContent();

		return $content;
	}

	/**
	 * Initializes the hook object
	 *
	 * @param \TYPO3\CMS\Rtehtmlarea\BrowseLinks $pObj browse_links object
	 * @param array $params
	 * @return void
	 */
	public function init($pObj, $params) {

		$this->pObj = $pObj;

		$currentPid = $this->getCurrentPageId();
		$activeConfiguration = is_array($this->pObj->thisConfig['tx_linkhandler.']) ? $this->pObj->thisConfig['tx_linkhandler.'] : NULL;
		$this->configurationManager->loadConfiguration($activeConfiguration, $currentPid);

		if ($this->isRTE()) {
			foreach ($this->configurationManager->getTabsConfiguration() as $key => $tabConfig) {
				$this->pObj->anchorTypes[] = $key;
			}
		}
	}

	/**
	 * Returns if the current linkwizard is RTE or not
	 */
	public function isRTE() {
		if ($this->pObj->mode == 'rte') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Modifies the menu definition and returns it
	 *
	 * @param array $menuDef menu definition
	 * @return array modified menu definition
	 */
	public function modifyMenuDefinition($menuDef) {

		$tabs = $this->configurationManager->getTabsConfiguration();

		foreach ($tabs as $key => $tabConfig) {
			$menuDef[$key]['isActive'] = $this->pObj->act == $key;
			$menuDef[$key]['label'] = $this->languageService->sL($tabConfig['label'], TRUE);
			$menuDef[$key]['url'] = '#';
			$menuDef[$key]['addParams'] = 'onclick="jumpToUrl(\'?act=' . $key . '\');return false;"';
		}

		return $menuDef;
	}

	/**
	 * Checks the current URL and returns a info array. This is used to
	 * tell the link browser which is the current tab based on the current URL.
	 * function should at least return the $info array.
	 *
	 * @param string $href
	 * @param string $siteUrl
	 * @param array $info Current info array.
	 * @return array $info a infoarray for browser to tell them what is current active tab
	 */
	public function parseCurrentUrl($href, $siteUrl, $info) {

		// Depending on link and setup the href string can contain complete absolute link
		if (substr($href, 0, 7) == 'http://') {
			if ($_href = strstr($href, '?id=')) {
				$href = substr($_href, 4);
			} else {
				$href = substr(strrchr($href, "/"), 1);
			}
		}

		$newInfo = $this->tabHandlerFactory->getLinkInfoArrayFromMatchingHandler($href);
		$info = array_merge($info, $newInfo);

		return $info;
	}
}