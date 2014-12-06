<?php
namespace Aoe\Linkhandler;

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

use \TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Central handling of the linkhandler configuration.
 *
 * @author Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
 * @author Alexander Stehlik <astehlik.deleteme@intera.de>
 */
class ConfigurationManager implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Cache for the current configuration.
	 *
	 * @var array
	 */
	protected $configuration = NULL;

	/**
	 * Cache for the current page UID.
	 *
	 * @var int
	 */
	protected $currentPageUid = NULL;

	/**
	 * Array containting the configuration for the tabs.
	 *
	 * The tab configuration keys are used as array indexes.
	 *
	 * @var array
	 */
	protected $tabsConfiguration = NULL;

	/**
	 * Returns a array of names available tx_linkhandler_tabHandler
	 *
	 * @return array
	 */
	public function getAllRegisteredTabHandlerClassnames() {

		if (!isset($this->tabHandlerClassnames)) {

			$this->tabHandlerClassnames = array('Aoe\\Linkhandler\\Browser\\TabHandler');

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'] as $tabHandler) {
					list(, $class) = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode(':', $tabHandler, 2);
					$this->tabHandlerClassnames[] = $class;
				}
			}
		}

		return $this->tabHandlerClassnames;
	}

	/**
	 * Returns config for a single tab.
	 *
	 * @param string $tabKey
	 * @return array
	 */
	public function getSingleTabConfiguration($tabKey) {

		$tabsConfiguration = $this->getTabsConfiguration();

		if (isset($tabsConfiguration[$tabKey])) {
			$singleTabConfiguration = $tabsConfiguration[$tabKey];
		} else {
			$singleTabConfiguration = NULL;
		}

		return $singleTabConfiguration;
	}

	/**
	 * Returns the configuration for all tabs.
	 *
	 * @return array
	 */
	public function getTabsConfiguration() {
		$this->loadTabsConfiguration();
		return $this->tabsConfiguration;
	}

	/**
	 * Loads the configuration.
	 *
	 * @param array $activeConfiguration If provided the configuration will be loaded
	 * @param int $currentPageUid
	 * @throws \InvalidArgumentException
	 */
	public function loadConfiguration($activeConfiguration = NULL, $currentPageUid = NULL) {

		if (isset($activeConfiguration)) {
			if (!is_array($activeConfiguration)) {
				throw new \InvalidArgumentException('The paramter $activeConfiguration must be an array.', 1392850296);
			}
			$this->configuration = $activeConfiguration;
			return;
		}

		$this->configuration = array();

		if (isset($currentPageUid) && $currentPageUid > 0) {
			$this->currentPageUid = (int)$currentPageUid;
		}

		if (TYPO3_MODE === 'FE') {
			$this->configuration = array();
			if (
				isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_linkhandler.'])
				&& is_array($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_linkhandler.'])
			) {
				$this->configuration = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_linkhandler.'];
			}
		} elseif (TYPO3_MODE === 'BE') {
			$this->loadBackendConfiguration();
		}
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Loads the configuration if needed and returns it.
	 *
	 * @return array
	 */
	protected function getConfiguration() {

		if (isset($this->configuration)) {
			return $this->configuration;
		}

		$this->loadConfiguration();
		return $this->configuration;
	}

	/**
	 * Tries to determine the current page UID and returns it.
	 *
	 * @return int
	 */
	protected function getCurrentPageUid() {

		if (isset($this->currentPageUid)) {
			return $this->currentPageUid;
		}

		$P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		if (isset($P['pid']) && $P['pid'] > 0) {
			$this->currentPageUid = (int)$P['pid'];
			return $this->currentPageUid;
		}

		return 0;
	}

	/**
	 * Loads the configuration in the Backend context from User TSConfig
	 * and Page TSConfig.
	 */
	protected function loadBackendConfiguration() {

		$backendUserAuthentication = $this->getBackendUserAuthentication();
		if (!isset($backendUserAuthentication)) {
			return;
		}

		$modTSconfig = $backendUserAuthentication->getTSConfig('mod.tx_linkhandler', BackendUtility::getPagesTSconfig($this->getCurrentPageUid()));
		if (is_array($modTSconfig['properties'])) {
			$this->configuration = $modTSconfig['properties'];
		}
	}

	/**
	 * Initializes the configuration of all configured tabs
	 */
	protected function loadTabsConfiguration() {

		if (is_array($this->tabsConfiguration)) {
			return;
		}

		$this->tabsConfiguration = array();

		foreach ($this->getConfiguration() as $name => $tabConfig) {
			if (is_array($tabConfig)) {
				$key = substr($name, 0, -1);
				$this->tabsConfiguration[$key] = $tabConfig;
			}
		}
	}
}