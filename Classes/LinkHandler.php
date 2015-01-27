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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Linkhandler to process custom linking to any kind of configured record.
 *
 * @author Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @author Alexander Stehlik <astehlik.deleteme@intera.de>
 */
class LinkHandler implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObjectRenderer;

	/**
	 * Global configuration defined in plugin.tx_linkhandler
	 * @var array
	 */
	protected $configuration;

	/**
	 * The configuration key that should be used for the current link
	 * @var string
	 */
	protected $configurationKey;

	/**
	 * The full link handler key (record:[config_index]:[ŧable]:[uid])
	 *
	 * @var string
	 */
	public $linkHandlerKey;

	/**
	 * All link parameters (including class name, page type, etc.)
	 *
	 * @var string
	 */
	public $linkParameters;

	/**
	 * The text that should be linked
	 *
	 * @var string
	 */
	public $linkText;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $localContentObjectRenderer;

	/**
	 * Configuration that will be passed to the typolink function
	 * @var array
	 */
	protected $typolinkConfiguration;

	/**
	 * The configuration that was passed to the parent typolink call.
	 *
	 * @var array
	 */
	protected $typolinkConfigurationParent;

	/**
	 * @var array
	 */
	protected $recordRow;

	/**
	 * @var string
	 */
	protected $recordTableName;

	/**
	 * @var int
	 */
	protected $recordUid;

	/**
	 * The TypoScript configuration for the current tab.
	 *
	 * @var array
	 */
	protected $tabConfiguration;

	/**
	 * @var \Aoe\Linkhandler\Browser\TabHandlerFactory
	 */
	protected $tabHandlerFactory;

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $tsfe;


	public function __construct() {
		$this->tsfe = $GLOBALS['TSFE'];
		$this->tabHandlerFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Aoe\\Linkhandler\\Browser\\TabHandlerFactory');
		$this->localContentObjectRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
	}

	/**
	 * Process the link generation
	 *
	 * @param string $linktxt
	 * @param array $conf
	 * @param string $linkHandlerKeyword Define the identifier that an record is given
	 * @param string $linkHandlerValue Table and uid of the requested record like "tt_news:2"
	 * @param string $linkParams Full link params like "record:tt_news:2"
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer
	 * @return string
	 */
	public function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $linkParams, $contentObjectRenderer) {

		$this->linkText = $linktxt;
		$this->linkParameters = $linkParams;
		$this->linkHandlerKey = $linkHandlerKeyword . ':' . $linkHandlerValue;
		$this->contentObjectRenderer = $contentObjectRenderer;
		$this->configuration = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_linkhandler.'];
		$this->typolinkConfigurationParent = (array)$conf;

		try {
			$generatedLink = $this->generateLink();
		} catch (\Exception $ex) {
			$generatedLink = $this->getErrorMessage($ex->getMessage());
		}

		return $generatedLink;
	}

	/**
	 * Generates a typolink by using the matching tab configuration
	 *
	 * @throws \Exception
	 * @return string
	 */
	protected function generateLink() {

		$linkInfo = $this->tabHandlerFactory->getLinkInfoArrayFromMatchingHandler($this->linkHandlerKey);

		if (!count($linkInfo)) {
			throw new \Exception(sprintf('No matching tab handler could be found for link handler key %s.', $this->linkHandlerKey));
		}

		$this->configurationKey = $linkInfo['act'];
		$this->recordTableName = $linkInfo['recordTable'];
		$this->recordUid = $linkInfo['recordUid'];
		$this->initRecord();

		if (!is_array($this->tabConfiguration)) {
			throw new \Exception(sprintf('No configuration was found for %s within plugin.tx_linkhandler.', $this->configurationKey));
		}

		if (!is_array($this->typolinkConfiguration)) {
			throw new \Exception(sprintf('No typolink. configuration was found for %s within plugin.tx_linkhandler.', $this->configurationKey));
		}

		if (!is_array($this->recordRow) && !$this->tabConfiguration['forceLink']) {
			return $this->linkText;
		}

		if (
			isset($this->tabConfiguration['overrideParentTypolinkConfiguration'])
			&& $this->tabConfiguration['overrideParentTypolinkConfiguration']
		) {
			$newTypoLinkConfiguration = $this->typolinkConfigurationParent;
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($newTypoLinkConfiguration, $this->typolinkConfiguration);
			$this->typolinkConfiguration = $newTypoLinkConfiguration;
		}

		// Extract link params like "target", "css-class" or "title"
		$furtherLinkParams = str_replace($this->linkHandlerKey, '', $this->linkParameters);
		$this->typolinkConfiguration['parameter'] .= $furtherLinkParams;

		$hookParams = array(
			'typolinkConfiguration' => &$this->typolinkConfiguration,
			'linkText' => &$this->linkText,
			'recordRow' => &$this->recordRow
		);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'] as $funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $hookParams, $this);
			}
		}

		// Build the full link to the record
		$this->localContentObjectRenderer->start($this->recordRow, '');
		$link = $this->localContentObjectRenderer->typoLink($this->linkText, $this->typolinkConfiguration);

		// Make the typolink data available in the parent content object.
		$this->contentObjectRenderer->lastTypoLinkLD = $this->localContentObjectRenderer->lastTypoLinkLD;
		$this->contentObjectRenderer->lastTypoLinkUrl = $this->localContentObjectRenderer->lastTypoLinkUrl;
		$this->contentObjectRenderer->lastTypoLinkTarget = $this->localContentObjectRenderer->lastTypoLinkTarget;

		return $link;
	}

	/**
	 * @param string $message
	 * @return string
	 */
	protected function getErrorMessage($message) {
		return '<span style="color: red; font-weight: bold;">' . $message . '</span>';
	}

	/**
	 * Initializes the linked record and the record specific configuration.
	 */
	protected function initRecord() {

		if (is_array($this->configuration) && array_key_exists($this->configurationKey . '.', $this->configuration)) {

			$this->tabConfiguration = $this->configuration[$this->configurationKey . '.'];

			if (is_array($this->tabConfiguration) && array_key_exists('typolink.', $this->tabConfiguration)) {
				$this->typolinkConfiguration = $this->tabConfiguration['typolink.'];
			}
		}

		$this->recordRow = $this->tsfe->sys_page->checkRecord($this->recordTableName, $this->recordUid);
	}
}