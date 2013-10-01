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
 * hook to adjust linkwizard (linkbrowser)
 *
 * @author    Daniel Poetzinger (AOE media GmbH)
 * @package TYPO3
 * @subpackage linkhandler
 */
class ElementBrowserHook implements \TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface {

	/**
	 * Registry of available tab handlers
	 * @var array
	 */
	protected $allAvailableTabHandlers = array();

	/**
	 * @var \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
	 */
	protected $backendUserAuth;

	/**
	 * the browse_links object
	 *
	 * @var \TYPO3\CMS\Rtehtmlarea\BrowseLinks
	 */
	protected $pObj;

	/**
	 * Configurations for the different tabs, array key is the
	 * configuration key.
	 * @var array
	 */
	protected $tabsConfig;

	/**
	 * Initializes global objects
	 */
	public function __construct() {
		$this->backendUserAuth = $GLOBALS['BE_USER'];
	}

	/**
	 * get current href value (diffrent for RTE and normal browselinks)
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
	 * initializes the hook object
	 *
	 * @param \TYPO3\CMS\Rtehtmlarea\BrowseLinks $pObj browse_links object
	 * @param array $params
	 * @return void
	 */
	public function init($pObj, $params) {

		$this->pObj = $pObj;
		$this->checkConfigAndGetDefault();
		$tabs = $this->getTabsConfig();

		if ($this->isRTE()) {
			foreach ($tabs as $key => $tabConfig) {
				$this->pObj->anchorTypes[] = $key;
			}
		}

		$this->allAvailableTabHandlers = $this->getAllRegisteredTabHandlerClassnames();
	}

	/**
	 * returns if the current linkwizard is RTE or not
	 **/
	public function isRTE() {
		if ($this->pObj->mode == 'rte') {
			return TRUE;
		} else {
			return FALSE;
		}

	}

	/**
	 * modifies the menu definition and returns it
	 *
	 * @param array$menuDef menu definition
	 * @return array modified menu definition
	 */
	public function modifyMenuDefinition($menuDef) {

		$tabs = $this->getTabsConfig();

		foreach ($tabs as $key => $tabConfig) {
			$menuDef[$key]['isActive'] = $this->pObj->act == $key;
			$menuDef[$key]['label'] = $tabConfig['label'];
			$menuDef[$key]['url'] = '#';
			$menuDef[$key]['addParams'] = 'onclick="jumpToUrl(\'?act=' . $key . '\');return false;"';
		}

		return $menuDef;
	}

	/**
	 * returns a new tab for the browse links wizard
	 *
	 * @param string $act current link selector action
	 * @return string a tab for the selected link action
	 */
	public function getTab($act) {

		$configuration = $this->getTabConfig($act);

		if (!isset($configuration)) {
			return FALSE;
		}

		//get the tabHandler
		$tabHandlerClass = 'Aoe\\Linkhandler\\Browser\\TabHandler'; //the default tabHandler
		if (class_exists($configuration['tabHandler'])) {
			$tabHandlerClass = $configuration['tabHandler'];
		}

		/** @var TabHandlerInterface $tabHandler */
		$tabHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($tabHandlerClass, $this, $act);
		$content = $tabHandler->getTabContent();

		return $content;
	}


	/**
	 * returns config for a single tab
	 */
	public function getTabConfig($tabKey) {
		$conf = $this->getTabsConfig();
		return $conf[$tabKey];
	}

	/**
	 * adds new items to the currently allowed ones and returns them
	 *
	 * @param array $allowedItems currently allowed items
	 * @return array currently allowed items plus added items
	 */
	public function addAllowedItems($allowedItems) {
		if (is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
				if (is_array($tabConfig)) {
					$key = substr($name, 0, -1);
					$allowedItems[] = $key;
				}
			}
		}
		return $allowedItems;
	}

	/**
	 * checks the current URL and returns a info array. This is used to
	 *    tell the link browser which is the current tab based on the current URL.
	 *    function should at least return the $info array.
	 *
	 * @param    string $href
	 * @param    string $siteUrl
	 * @param    array $info        Current info array.
	 * @return    array                $info        a infoarray for browser to tell them what is current active tab
	 */
	public function parseCurrentUrl($href, $siteUrl, $info) {

		//depending on link and setup the href string can contain complete absolute link
		if (substr($href, 0, 7) == 'http://') {
			if ($_href = strstr($href, '?id=')) {
				$href = substr($_href, 4);
			} else {
				$href = substr(strrchr($href, "/"), 1);
			}
		}

		//ask the registered tabHandlers:
		foreach ($this->allAvailableTabHandlers as $handler) {
			$result = call_user_func($handler . '::getLinkBrowserInfoArray', $href, $this->getTabsConfig());
			if (count($result) > 0 && is_array($result)) {
				$info = array_merge($info, $result);
				break;
			}
		}

		return $info;
	}

	/**
	 * returns current pageid
	 *
	 * @return integer
	 */
	public function getCurrentPageId() {
		if ($this->isRTE()) {
			$confParts = explode(':', $this->pObj->RTEtsConfigParams);
			return $confParts[5];
		} else {
			$P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
			return $P['pid'];
		}
	}

	/*
	*	checks if $this->pObj->thisConfig['tx_linkhandler.'] is set, and if not it trys to load default from
	*	TSConfig key mod.tx_linkhandler.
	*	(in case the hook is called from a RTE, this configuration might exist because it is configured in RTE.defaul.tx_linkhandler)
	*		In mode RTE: the parameter RTEtsConfigParams have to exist
	*		In mode WIzard: the parameter P[pid] have to exist
	*/
	protected function checkConfigAndGetDefault() {

		$currentPid = $this->getCurrentPageId();

		if (!is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			$modTSconfig = $this->backendUserAuth->getTSConfig("mod.tx_linkhandler", \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($currentPid));
			$this->pObj->thisConfig['tx_linkhandler.'] = $modTSconfig['properties'];
		}
	}

	/**
	 * returns the complete configuration (tsconfig) of all tabs
	 **/
	protected function getTabsConfig() {
		$this->initializeTabConfiguration();
		return $this->tabsConfig;
	}

	/**
	 * returns a array of names available tx_linkhandler_tabHandler
	 */
	protected function getAllRegisteredTabHandlerClassnames() {

		$classes = array('Aoe\\Linkhandler\\Browser\\TabHandler');

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'] as $tabHandler) {
				list($file, $class) = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode(':', $tabHandler, 2);
				$classes[] = $class;
			}
		}

		return $classes;
	}

	/**
	 * Initializes the configuration of all configured tabs
	 */
	protected function initializeTabConfiguration() {

		if (isset($this->tabsConfig)) {
			return;
		}

		$this->tabsConfig = array();

		if (is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
				if (is_array($tabConfig)) {
					$key = substr($name, 0, -1);
					$this->tabsConfig[$key] = $tabConfig;
				}
			}
		}
	}

	/**
	 * Returns TRUE if the given key is a valid link handler configuration
	 * key
	 *
	 * @param string $key
	 * @return bool
	 */
	protected function isOneOfLinkhandlerTabs($key) {

		foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {

			if (is_array($tabConfig)) {
				$akey = substr($name, 0, -1);
				if ($akey == $key)
					return TRUE;
			}
		}

		return FALSE;
	}
}