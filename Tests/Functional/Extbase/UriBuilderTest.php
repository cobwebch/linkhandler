<?php
namespace Aoe\Linkhandler\Tests\Functional\Extbase;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2015, Alexander Stehlik <astehlik.deleteme@intera.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional tests for the Extbase URI builder.
 */
class UriBuilderText extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('extbase', 'frontend');

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array('typo3conf/ext/news', 'typo3conf/ext/linkhandler');

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $typoScriptFrontendController;


	/**
	 * Sets up this test suite.
	 */
	public function setUp() {

		parent::setUp();

		$this->setUpBackendUserFromFixture(1);

		$this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/linkhandler/Tests/Functional/Fixtures/base_structure.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3conf/ext/linkhandler/Tests/Functional/Fixtures/tx_news_domain_model_news.xml');

		/** @var \TYPO3\CMS\Lang\LanguageService $languageService */
		$languageService = $this->getMock('TYPO3\\CMS\\Lang\\LanguageService');
		$GLOBALS['LANG'] = $languageService;

		/** @var \TYPO3\CMS\Core\TimeTracker\TimeTracker $timeTracker */
		$timeTracker = $this->getMock('TYPO3\\CMS\\Core\\TimeTracker\\TimeTracker');
		$GLOBALS['TT'] = $timeTracker;

		/** @var \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository */
		$pageRepository = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');

		/** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $typoScriptFrontendController */
		$typoScriptFrontendController = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], 1, 0);
		$this->typoScriptFrontendController = $typoScriptFrontendController;
		$GLOBALS['TSFE'] = $typoScriptFrontendController;
		$typoScriptFrontendController->sys_page = $pageRepository;
		$typoScriptFrontendController->getPageAndRootline();
		$typoScriptFrontendController->initTemplate();
		$typoScriptFrontendController->getConfigArray();

		// This is needed for the configuration manager to load the correct TSconfig.
		$_GET['P'] = array('pid' => 1);

		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer */
		$contentObjectRenderer = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$contentObjectRenderer->start(array());

		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
		$configurationManager->setContentObject($contentObjectRenderer);

		$this->uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
	}

	/**
	 * @test
	 */
	public function frontendUriBuilderCreatesLinkHandlerUrl() {

		$this->typoScriptFrontendController->tmpl->setup['plugin.']['tx_linkhandler.']['tx_news_news.']['typolink.']['useCacheHash'] = 0;

		$this->uriBuilder->reset()
			->setTargetPageUid('record:tx_news_news:tx_news_domain_model_news:1');

		$generatedUrl = $this->uriBuilder->buildFrontendUri();
		$this->assertEquals('index.php?id=1&tx_news_pi1%5Bnews%5D=1&tx_news_pi1%5Bcontroller%5D=News&tx_news_pi1%5Baction%5D=detail', $generatedUrl);
	}

	/**
	 * @test
	 */
	public function frontendUriBuilderParametersArePassedOnIfConfigured() {

		$this->typoScriptFrontendController->tmpl->setup['plugin.']['tx_linkhandler.']['tx_news_news.']['overrideParentTypolinkConfiguration'] = 1;
		$this->typoScriptFrontendController->tmpl->setup['plugin.']['tx_linkhandler.']['tx_news_news.']['typolink.']['useCacheHash'] = 0;

		$this->uriBuilder->reset()
			->setTargetPageUid('record:tx_news_news:tx_news_domain_model_news:1')
			->setNoCache(TRUE);

		$generatedUrl = $this->uriBuilder->buildFrontendUri();
		$this->assertEquals('index.php?id=1&no_cache=1&tx_news_pi1%5Bnews%5D=1&tx_news_pi1%5Bcontroller%5D=News&tx_news_pi1%5Baction%5D=detail', $generatedUrl);
	}
}
