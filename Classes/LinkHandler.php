<?php
namespace Aoe\Linkhandler;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel P�tzinger <daniel.poetzinger@aoemedia.de>
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

if (!defined('TYPO3_MODE'))
	die ('Access denied.');

/**
 * Linkhandler to process custom linking to any kind of configured record.
 *
 * @author    Daniel Poetzinger <daniel.poetzinger@aoemedia.de>
 * @author    Michael Klapper <michael.klapper@aoemedia.de>
 * @version $Id: $
 * @date 08.04.2009 - 15:06:25
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class LinkHandler {

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
	 * Configuration that will be passed to the typolink function
	 * @var array
	 */
	protected $typolinkConfiguration;

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
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $tsfe;


	public function __construct() {
		$this->tsfe = $GLOBALS['TSFE'];
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
	function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $linkParams, $contentObjectRenderer) {

		$this->contentObjectRenderer = $contentObjectRenderer;
		$this->configuration = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_linkhandler.'];
		list ($this->recordTableName, $this->recordUid) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $linkHandlerValue);
		$this->initRecord();

		if (is_array($this->typolinkConfiguration) && (is_array($this->recordRow) || $this->typolinkConfiguration['forceLink'])) {

			// extract link params like "target", "css-class" or "title"
			$furtherLinkParams = str_replace('record:' . $linkHandlerValue, '', $linkParams);

			/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $localcObj */
			$localcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$localcObj->start($this->recordRow, '');

			$this->typolinkConfiguration['parameter'] .= $furtherLinkParams;

			// build the full link to the record
			$generatedLink = $localcObj->typoLink($linktxt, $this->typolinkConfiguration);
		} else {
			$generatedLink = '<span style="color: red; font-weight: bold;">No linkhandler configuration was found for records from table ' . $this->recordTableName . '. Please make sure you included the required plugin.tx_linkhandler TypoScript configuration in the page.</span>';
		}

		return $generatedLink;
	}

	/**
	 * Initializes the linked record and the record specific configuration.
	 */
	protected function initRecord() {

		if (is_array($this->configuration) && array_key_exists($this->recordTableName . '.', $this->configuration)) {
			$this->typolinkConfiguration = $this->configuration[$this->recordTableName . '.'];
		}

		$this->recordRow = $this->tsfe->sys_page->checkRecord($this->recordTableName, $this->recordUid);
	}
}
