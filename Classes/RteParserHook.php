<?php
namespace Aoe\Linkhandler;

/***************************************************************
 * Copyright notice
 *
 * Copyright (c) 2014, Alexander Stehlik <astehlik.deleteme@intera.de>
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

/**
 * Hook for the RTE link parser.
 *
 * Makes sure that the "data-htmlarea-external" tag is not set for linkhandler links.
 */
class RteParserHook {

	/**
	 * @var \TYPO3\CMS\Core\Html\RteHtmlParser
	 */
	protected $rteHtmlParser;

	/**
	 * Will be called by RteHtmlParser.
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\Core\Html\RteHtmlParser $rteHtmlParser
	 * @return string
	 */
	public function modifyParamsLinksRte($parameters, $rteHtmlParser) {

		$this->rteHtmlParser = $rteHtmlParser;

		$currentBlock = $parameters['currentBlock'];
		$url = $parameters['url'];
		$tagCode = $parameters['tagCode'];
		$external = $parameters['external'];
		$error = $parameters['error'];

		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($url, 'record:')) {
			$external = FALSE;
		}

		return $this->buildOriginalLink($url, $tagCode, $external, $error, $currentBlock);
	}

	/**
	 * Builds the the link. The code is the same as in
	 * \TYPO3\CMS\Core\Html\RteHtmlParser::TS_links_rte()
	 *
	 * @param string $url
	 * @param array $tagCode
	 * @param bool $external
	 * @param string $error
	 * @param string $currentBlock
	 * @return string
	 * @see \TYPO3\CMS\Core\Html\RteHtmlParser::TS_links_rte()
	 */
	protected function buildOriginalLink($url, $tagCode, $external, $error, $currentBlock) {
		$bTag = '<a href="' . htmlspecialchars($url) . '"' . ($tagCode[2] && $tagCode[2] != '-' ? ' target="' . htmlspecialchars($tagCode[2]) . '"' : '') . ($tagCode[3] && $tagCode[3] != '-' ? ' class="' . htmlspecialchars($tagCode[3]) . '"' : '') . ($tagCode[4] ? ' title="' . htmlspecialchars($tagCode[4]) . '"' : '') . ($external ? ' data-htmlarea-external="1"' : '') . ($error ? ' rteerror="' . htmlspecialchars($error) . '" style="background-color: yellow; border:2px red solid; color: black;"' : '') . '>';
		$eTag = '</a>';
		return $bTag . $this->rteHtmlParser->TS_links_rte($this->rteHtmlParser->removeFirstAndLastTag($currentBlock)) . $eTag;
	}
}