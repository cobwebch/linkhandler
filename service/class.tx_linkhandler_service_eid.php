<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
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

require_once PATH_tslib . 'class.tslib_pagegen.php';
require_once PATH_tslib . 'class.tslib_fe.php';
require_once PATH_t3lib . 'class.t3lib_page.php';
require_once PATH_tslib . 'class.tslib_content.php';
require_once PATH_t3lib . 'class.t3lib_userauth.php' ;
require_once PATH_tslib . 'class.tslib_feuserauth.php';
require_once PATH_t3lib . 'class.t3lib_tstemplate.php';
require_once PATH_t3lib . 'class.t3lib_cs.php';
require_once t3lib_extMgm::extPath('linkhandler') . 'class.tx_linkhandler_handler.php';

/**
 * eID script
 *
 * class.tx_linkhandler_service_eid.php
 *
 * @author Michael Klapper <klapper@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id$
 * @date $Date$
 * @since 07.06.2009 - 00:20:44
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class tx_linkhandler_service_eid {

	/**
	 * @example "record:tt_news:2"
	 * @var string
	 */
	protected $linkHandlerParams = '';

	/**
	 * @example "tt_news:2"
	 * @var string
	 */
	protected $linkHandlerValue = '';

	/**
	 * Keyword like "record"
	 *
	 * @example $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record']
	 * @var string
	 */
	protected $linkHandlerKeyword = '';

	/**
	 * Indicate that the current request is from any WS
	 *
	 * @var boolean
	 */
	protected $isWsPreview = false;

	/**
	 * sys_language_uid
	 *
	 * @var integer
	 */
	protected $languageId = 0;

	/**
	 * Contains all required values to build an WS preview link.
	 *
	 * The Value is seperated by ":"#
	 * - Workspace ID
	 * - Backend user ID
	 * - Time to live for the WS link
	 *
	 * @example 1:5:172800
	 * @var string|null
	 */
	protected $WSPreviewValue = null;

	/**
	 * @return void
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function __construct() {
		$authCode         = (string)t3lib_div::_GP('authCode');
		$linkParams       = t3lib_div::_GP('linkParams');
		$this->languageId = t3lib_div::_GP('L');

			// extract the linkhandler and WS preview prameter
		if ( strpos($linkParams, ';') > 0) {
			list ($this->linkHandlerParams, $this->WSPreviewValue)  = explode(';', $linkParams);
			$this->isWsPreview = true;
		} else
			$this->linkHandlerParams = $linkParams;

		list($this->linkHandlerKeyword) = explode(':', $this->linkHandlerParams);
		$this->linkHandlerValue         = str_replace($this->linkHandlerKeyword . ':', '', $this->linkHandlerParams);

			// check the authCode
		if ( t3lib_div::stdAuthCode($linkParams . $this->languageId, '', 32) !== $authCode )  {
			header('HTTP/1.0 401 Access denied.');
			exit('Access denied.');
		}

		$this->initTSFE();
	}

	/**
	 * Initializes tslib_fe and sets it to $GLOBALS['TSFE']
	 *
	 * @return	void
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	protected function initTSFE() {
		$pid = version_compare(TYPO3_version,'4.6.0','>=') ? t3lib_utility_Math::convertToPositiveInteger(t3lib_div::_GP('id')) : t3lib_div::intval_positive(t3lib_div::_GP('id'));

		if ( version_compare(TYPO3_version, '4.3.0', '>=') ) {
			$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $pid, 0, 0, 0);
		} else {
			$tsfeClassName   = t3lib_div::makeInstanceClassName('tslib_fe');
			$GLOBALS['TSFE'] = new $tsfeClassName($GLOBALS['TYPO3_CONF_VARS'], $pid, 0, 0, 0);
		}

		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser(); //!TODO first check if already a fe_user session exists - otherwise this line will overwrite the existing one
		$GLOBALS['TSFE']->checkAlternativeIdMethods();

		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();
		$GLOBALS['TSFE']->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * @example ?eID=linkhandlerPreview&linkParams=record:tx_aoetirepresenter_tire:40&id=23
	 * @return void
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function process() {
		$typoLinkSettingsArray = array (
			'returnLast'       => 'url',
			'additionalParams' => '&L=' . $this->languageId
		);

			// if we need a WS preview link we need to disable the realUrl and simulateStaticDocuments
		if ($this->isWsPreview === true) {
			$GLOBALS['TSFE']->config['config']['tx_realurl_enable'] = 0;
			$GLOBALS['TSFE']->config['config']['simulateStaticDocuments'] = 0;
		}

		$Linkhandler = t3lib_div::makeInstance('tx_linkhandler_handler'); /* @var $Linkhandler tx_linkhandler_handler */

		$linkString = $Linkhandler->main (
			'',
			$typoLinkSettingsArray,
			$this->linkHandlerKeyword,
			$this->linkHandlerValue,
			$this->linkHandlerParams,
			$GLOBALS['TSFE']->cObj
		);

		if ($this->isWsPreview === true) {
			list ($wsId, $userId, $timeToLive) = explode(':', $this->WSPreviewValue);

			$queryString = 'index.php?ADMCMD_prev='.t3lib_BEfunc::compilePreviewKeyword (
				str_replace('index.php?', '', $GLOBALS['TSFE']->cObj->lastTypoLinkLD['totalURL']) . '&ADMCMD_previewWS=' . $wsId,
				$userId,
				$timeToLive
			);
		} else {
			$queryString = $linkString;
		}

		$fullURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $queryString;
		
		header('Location: ' . $fullURL);
		exit();
	}
}

$LinkhandlerService = t3lib_div::makeInstance('tx_linkhandler_service_eid'); /* @var $LinkhandlerService tx_linkhandler_service_eid */
$LinkhandlerService->process();

?>
