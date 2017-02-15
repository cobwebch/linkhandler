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
use TYPO3\CMS\Core\Utility\ArrayUtility;
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

    /**
     * @var array TypoScript configuration which triggered the link rendering
     */
    protected $parentTypoScriptConfiguration = array();

    /**
     * @var array Final configuration assembled for the typolink
     */
    protected $typolinkConfiguration = array();

    /**
     * @var string Name of the table being linked to
     */
    protected $table = '';

    /**
     * @var int Id of the record being linked to
     */
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

        // if parameter is set as array ("parameter."), the link can not be resolved, so unset
        if (is_array($configuration['parameter.'])) {
            unset($configuration['parameter.']);
        }

        // Extract the link parts (i.e. parameter, target, class, title and additional parameters
        $this->linkParameters = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($linkParameters);
        // Add information from the parameter
        $linkParameterParts = explode(':', $linkHandlerValue);
        $this->configurationKey = $linkParameterParts[0] . '.';
        $this->parentTypoScriptConfiguration = $configuration;
        $this->table = $linkParameterParts[1];
        $this->uid = (int)$linkParameterParts[2];

        $this->linkText = $linkText;
        $this->contentObjectRenderer = $contentObjectRenderer;

        // Restore values (because this object is a singleton)
        $this->record = array();
        $this->typolinkConfiguration = array();

        try {
            $generatedLink = $this->generateLink();
        } catch (\Exception $e) {
            $generatedLink = $this->formatErrorMessage($e->getMessage());
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
                sprintf('No linkhandler TypoScript configuration found for key %s.', $this->configurationKey),
                1448384257
            );
        }
        $typoScriptConfiguration = $this->configuration[$this->configurationKey]['typolink.'];

        try {
            $this->getLinkedRecord((bool)$this->configuration[$this->configurationKey]['forceLink']);
        } catch (RecordNotFoundException $e) {
            return $this->linkText;
        }

        // Assemble full parameters syntax with additional attributes like target, class or title
        $this->linkParameters['url'] = $typoScriptConfiguration['parameter'];
        $typoScriptConfiguration['parameter'] = GeneralUtility::makeInstance(TypoLinkCodecService::class)
            ->encode($this->linkParameters);
        if (array_key_exists('mergeWithLinkhandlerConfiguration', $this->parentTypoScriptConfiguration)) {
            $this->typolinkConfiguration = $this->parentTypoScriptConfiguration;
            ArrayUtility::mergeRecursiveWithOverrule($this->typolinkConfiguration, $typoScriptConfiguration);
        } else {
            $this->typolinkConfiguration = $typoScriptConfiguration;
        }

        // Call registered hooks
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'] as $className) {
                $linkParameterProcessor = GeneralUtility::makeInstance($className);
                if ($linkParameterProcessor instanceof ProcessLinkParametersInterface) {
                    $linkParameterProcessor->process($this);
                }
            }
        }

        // Build the full link to the record
        $this->localContentObjectRenderer->start($this->record, $this->table);
        $this->localContentObjectRenderer->parameters = $this->contentObjectRenderer->parameters;
        $link = $this->localContentObjectRenderer->typoLink($this->linkText, $this->typolinkConfiguration);

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
     * @param bool $forceFetch If true, the record will be fetched disregarding all permissions and visibility settings
     * @return void
     * @throws Exception\RecordNotFoundException
     */
    protected function getLinkedRecord($forceFetch = false)
    {
        if ($forceFetch) {
            $record = $this->tsfe->sys_page->getRawRecord($this->table, $this->uid);
        } else {
            $record = $this->tsfe->sys_page->checkRecord($this->table, $this->uid);
        }
        if ($record === 0) {
            throw new RecordNotFoundException(
                sprintf('Record %d of table %s not found or not accessible', $this->uid, $this->table),
                1448384669
            );
        }
        $this->record = $record;
    }

    /**
     * @return array
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @param array $record
     */
    public function setRecord($record)
    {
        $this->record = $record;
    }

    /**
     * @return string
     */
    public function getLinkText()
    {
        return $this->linkText;
    }

    /**
     * @param string $linkText
     */
    public function setLinkText($linkText)
    {
        $this->linkText = $linkText;
    }

    /**
     * @return array
     */
    public function getLinkParameters()
    {
        return $this->linkParameters;
    }

    /**
     * @param array $linkParameters
     */
    public function setLinkParameters($linkParameters)
    {
        $this->linkParameters = $linkParameters;
    }

    /**
     * @return array
     */
    public function getTypolinkConfiguration()
    {
        return $this->typolinkConfiguration;
    }

    /**
     * @param array $typolinkConfiguration
     */
    public function setTypolinkConfiguration($typolinkConfiguration)
    {
        $this->typolinkConfiguration = $typolinkConfiguration;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getConfigurationKey()
    {
        return $this->configurationKey;
    }

    /**
     * @param string $configurationKey
     */
    public function setConfigurationKey($configurationKey)
    {
        $this->configurationKey = $configurationKey;
    }

}