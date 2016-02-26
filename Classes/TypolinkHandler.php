<?php
namespace Cobweb\Linkhandler;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\Linkhandler\Exception\MissingConfigurationException;
use Cobweb\Linkhandler\Exception\RecordNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Handler to create typolinks to arbitrary DB records.
 *
 * Based on original code by AOE and modified by a lot of people.
 */
class TypolinkHandler implements SingletonInterface
{

    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * @var array Global configuration defined in plugin.tx_linkhandler
     */
    protected $configuration = array();

    /**
     * @var string The full link handler key (record:[config_index]:[ŧable]:[uid])
     */
    public $linkHandlerKey = '';

    /**
     * @var array All link parameters (including class name, page type, etc.)
     */
    public $linkParameters = array();

    /**
     * @var string The text that should be linked
     */
    public $linkText = '';

    /**
     * @var ContentObjectRenderer
     */
    protected $localContentObjectRenderer;

    /**
     * @var string Key pointing to the linkhandler configuration (e.g. "tx_news")
     */
    protected $configurationKey = '';

    protected $table = '';

    protected $uid = 0;

    /**
     * Record pointed to by the link handler reference
     *
     * @var array
     */
    protected $record = array();

    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $tsfe;


    public function __construct()
    {
        $this->tsfe = $GLOBALS['TSFE'];
        $this->localContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->configuration = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_linkhandler.'];
    }

    /**
     * Processes the link generation.
     *
     * @param string $linkText Text to be used inside the <a> tag
     * @param array $configuration Parent TS configuration
     * @param string $linkHandlerKeyword Keyword that triggered the typolink handler
     * @param string $linkHandlerValue Configuration, table and uid of the requested record like "tx_news:tx_news_domain_model_news:2"
     * @param string $linkParameters Full link parameters like "record:tx_news:tx_news_domain_model_news:2  _top record-link "Read the best news!" class=\"foo\""
     * @param ContentObjectRenderer $contentObjectRenderer
     * @return string
     */
    public function main(
            $linkText,
            $configuration,
            $linkHandlerKeyword,
            $linkHandlerValue,
            $linkParameters,
            $contentObjectRenderer
    ) {

        // Extract the link parts (i.e. parameter, target, class, title and additional parameters
        $this->linkParameters = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($linkParameters);
        // Add information from the parameter
        $linkParameterParts = explode(':', $linkHandlerValue);
        $this->configurationKey = $linkParameterParts[0] . '.';
        $this->table = $linkParameterParts[1];
        $this->uid = (int)$linkParameterParts[2];

        $this->linkText = $linkText;
        //		$this->linkHandlerKey = $linkHandlerKeyword . ':' . $linkHandlerValue;
        $this->contentObjectRenderer = $contentObjectRenderer;

        try {
            $generatedLink = $this->generateLink();
        } catch (\Exception $e) {
            $generatedLink = $this->formatErrorMessage(
                    $e->getMessage()
            );
        }

        return $generatedLink;
    }

    /**
     * Generates a typolink by using the matching configuration.
     *
     * @throws \Exception
     * @return string
     */
    protected function generateLink()
    {
        if (!array_key_exists($this->configurationKey, $this->configuration)) {
            throw new MissingConfigurationException(
                    sprintf(
                            'No linkhandler TypoScript configuration found for key %s.',
                            $this->configurationKey
                    ),
                    1448384257
            );
        }
        $typoScriptConfiguration = $this->configuration[$this->configurationKey]['typolink.'];

        try {
            $this->initRecord();
        } catch (RecordNotFoundException $e) {
            // Unless linking is forced, return only the link text
            // @todo: should we not get the record in this case (using \TYPO3\CMS\Frontend\Page\PageRepository::getRawRecord()) otherwise link generation will be pretty meaningless?
            if (!$this->configuration[$this->configurationKey]['forceLink']) {
                return $this->linkText;
            }
        }

        // Assemble full parameters syntax with additional attributes like target, class or title
        $this->linkParameters['url'] = $typoScriptConfiguration['parameter'];
        $typoScriptConfiguration['parameter'] = GeneralUtility::makeInstance(
                TypoLinkCodecService::class
        )->encode(
                $this->linkParameters
        );

        $hookParams = array(
                'linkInformation' => &$this->linkParameters,
                'typoscriptConfiguration' => &$typoScriptConfiguration,
                'linkText' => &$this->linkText,
                'recordRow' => &$this->record
        );

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'] as $funcRef) {
                // @todo: make that clean with an interface
                GeneralUtility::callUserFunction($funcRef, $hookParams, $this);
            }
        }

        // Build the full link to the record
        $this->localContentObjectRenderer->start(
                $this->record,
                $this->table
        );
        $this->localContentObjectRenderer->parameters = $this->contentObjectRenderer->parameters;
        $link = $this->localContentObjectRenderer->typoLink(
                $this->linkText,
                $typoScriptConfiguration
        );

        // Make the typolink data available in the parent content object
        $this->contentObjectRenderer->lastTypoLinkLD = $this->localContentObjectRenderer->lastTypoLinkLD;
        $this->contentObjectRenderer->lastTypoLinkUrl = $this->localContentObjectRenderer->lastTypoLinkUrl;
        $this->contentObjectRenderer->lastTypoLinkTarget = $this->localContentObjectRenderer->lastTypoLinkTarget;

        return $link;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function formatErrorMessage($message)
    {
        return '<span style="color: red; font-weight: bold;">' . $message . '</span>';
    }

    /**
     * Checks if the defined record exists and is accessible to the user. If yes, the record is returned.
     *
     * @return array|int
     * @throws Exception\RecordNotFoundException
     */
    protected function initRecord()
    {
        $record = $this->tsfe->sys_page->checkRecord(
                $this->table,
                $this->uid
        );
        if ($record === 0) {
            throw new RecordNotFoundException(
                    sprintf(
                            'Record %d of table %s not found or not accessible',
                            $this->uid,
                            $this->table
                    ),
                    1448384669
            );
        }
        $this->record = $record;
    }

    // @todo: added all useful getters and setters for hook
}