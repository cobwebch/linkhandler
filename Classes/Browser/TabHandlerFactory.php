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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for instantiating tab handlers.
 *
 * @author Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
 * @author Alexander Stehlik <astehlik.deleteme@intera.de>
 */
class TabHandlerFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $tabHandlerClassnames = NULL;

	/**
	 * Builds a tab handler instance and provides its configuration.
	 *
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
	 * Runs through all configured tab handlers and calls the
	 * getLinkBrowserInfoArray() method. If it returns a non empty array
	 * it will return this value.
	 *
	 * @param string $href
	 * @return array
	 */
	public function getLinkInfoArrayFromMatchingHandler($href) {

		$result = array();
		$configurationManager = $this->getConfigurationManager();

		foreach ($configurationManager->getAllRegisteredTabHandlerClassnames() as $handler) {

			$result = call_user_func($handler . '::getLinkBrowserInfoArray', $href, $configurationManager->getTabsConfiguration());

			if (is_array($result) && count($result) > 0) {
				break;
			}
		}

		return $result;
	}

	/**
	 * @return \Aoe\Linkhandler\ConfigurationManager
	 */
	protected function getConfigurationManager() {
		return GeneralUtility::makeInstance('Aoe\\Linkhandler\\ConfigurationManager');
	}
}