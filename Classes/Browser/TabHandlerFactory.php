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

/**
 * Configuration helper
 */
class TabHandlerFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $tabHandlerClassnames = NULL;

	/**
	 * Runs through the given TypoScript configuration and builds tab
	 * configurations from it. Can be used to prepare the configuration
	 * for the call to getLinkInfoArrayFromMatchingHandler
	 *
	 * @param array $typoScript
	 * @return array
	 */
	public function buildTabConfigurationsFromTypoScript($typoScript) {

		$tabConfigurations = array();

		foreach ($typoScript as $key => $possibleTabConfig) {
			if (is_array($possibleTabConfig['tab.'])) {
				$tabConfigurations[rtrim($key, '.')] = $possibleTabConfig['tab.'];
			}
		}

		return $tabConfigurations;
	}

	/**
	 * @param array $configuration
	 * @param string $activeTab
	 * @param ElementBrowserHook $elementBrowserHook
	 * @return TabHandlerInterface
	 */
	public function createTabHandler($configuration, $activeTab, $elementBrowserHook) {

		if (isset($configuration['tabHandler']) && class_exists($configuration['tabHandler'])) {
			$tabHandlerClass = $configuration['tabHandler'];
		} else {
			$tabHandlerClass = 'Aoe\\Linkhandler\\Browser\\TabHandler';
		}

		/** @var TabHandlerInterface $tabHandler */
		$tabHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($tabHandlerClass, $elementBrowserHook, $activeTab);
		return $tabHandler;
	}

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
	 * Runs through all configured tab handlers and calls the
	 * getLinkBrowserInfoArray() method. If it returns a non empty array
	 * it will return this value.
	 *
	 * @param string $href
	 * @param array $tabsConfiguration
	 * @return array
	 */
	public function getLinkInfoArrayFromMatchingHandler($href, $tabsConfiguration) {

		$result = array();

		foreach ($this->getAllRegisteredTabHandlerClassnames() as $handler) {

			$result = call_user_func($handler . '::getLinkBrowserInfoArray', $href, $tabsConfiguration);

			if (is_array($result) && count($result) > 0) {
				break;
			}
		}

		return $result;
	}
}