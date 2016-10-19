<?php
namespace Cobweb\Linkhandler\Linkvalidator;

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

use Cobweb\Linkhandler\Domain\Model\RecordLink;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Enhanced linkhandler link type for the linkvalidator Extension.
 */
class LinkhandlerLinkType extends AbstractLinktype
{

    /**
     * This error occurs when the related record is deleted.
     *
     * @const
     */
    const ERROR_TYPE_DELETED = 'deleted';

    /**
     * This error occurs when the related record is disabled.
     *
     * @var string
     */
    const ERROR_TYPE_DISABLED = 'disabled';

    /**
     * This error occurs when the related record does not exist at all.
     *
     * @var string
     */
    const ERROR_TYPE_MISSING = 'missing';

    /**
     * This error occurs when the related record does not exist at all.
     *
     * @var string
     */
    const ERROR_TYPE_INVALID = 'invalid';

    /**
     * TYPO3 database connection.
     *
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * The language file from where the labels are read.
     *
     * @var string
     */
    protected $languageFile = 'EXT:linkhandler/Resources/Private/Language/locallang.xlf';

    /**
     * TYPO3 language service.
     *
     * @var \TYPO3\CMS\Lang\LanguageService
     */
    protected $languageService;

    /**
     * @var RecordLink
     */
    protected $recordLink;

    /**
     * Checks a given URL for validity.
     *
     * @param string $url Url to check
     * @param array $softRefEntry The soft reference entry which builds the context of that url
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $linkAnalyzer Parent instance
     * @return boolean TRUE on success or FALSE on error
     */
    public function checkLink($url, $softRefEntry, $linkAnalyzer)
    {
        $response = true;
        $errorType = '';
        $errorParams = array();
        $this->initializeRequiredClasses();

        try {
            $this->recordLink = GeneralUtility::makeInstance(RecordLink::class, $url);
        } catch (\Exception $e) {
            // Set error type to invalid (record reference) and return early
            $this->setErrorParams(
                array(
                    'errorType' => self::ERROR_TYPE_INVALID,
                    'url' => $url,
                )
            );
            return false;
        }

        // Get hidden records reporting parameter from TSconfig
        // If hidden records reporting is true, an error will be raised for hidden records and not just for deleted or missing records
        $tsConfig = $linkAnalyzer->getTSConfig();
        $reportHiddenRecords = (bool)$tsConfig['tx_linkhandler.']['reportHiddenRecords'];

        // Get the record without enable fields
        // If reporting about "hidden" records, also get the record with enable fields
        $rawRecord = $this->getRecordRow();
        $enabledRecord = $rawRecord;
        if ($reportHiddenRecords) {
            $enabledRecord = $this->getRecordRow(true);
        }

        // If the record was not found without any condition, it is completely missing from the database
        if ($rawRecord === null) {
            $response = false;
            $errorType = self::ERROR_TYPE_MISSING;
        } else {
            // If the record was found, but its "delete" flag is set, it is a deleted record
            $deleteFlag = (!empty($GLOBALS['TCA'][$this->recordLink->getTable()]['ctrl']['delete']))
                ? $GLOBALS['TCA'][$this->recordLink->getTable()]['ctrl']['delete'] : '';
            if ($deleteFlag !== '') {
                $deleted = (bool)$rawRecord[$deleteFlag];
                if ($deleted) {
                    $response = false;
                    $errorType = self::ERROR_TYPE_DELETED;
                }
            }
            // If no record was fetched when applying the "enable fields" conditions, the record is currently disabled
            if ($enabledRecord === null) {
                $response = false;
                $errorType = self::ERROR_TYPE_DISABLED;
            }
        }

        if (!$response) {
            $errorParams['errorType'] = $errorType;
            $errorParams['tablename'] = $this->recordLink->getTable();
            $errorParams['uid'] = $this->recordLink->getId();
            $this->setErrorParams($errorParams);
        }

        return $response;
    }

    /**
     * Returns the type of link.
     *
     * If we detect a link starting with "record:", we consider it to be one of "ours".
     *
     * @param array $value Reference properties
     * @param string $type Current type
     * @param string $key Validator hook name
     * @return string fetched type
     */
    public function fetchType($value, $type, $key)
    {
        if (strtolower(substr($value['tokenValue'], 0, 7)) === 'record:') {
            $type = 'tx_linkhandler';
        }
        return $type;
    }

    /**
     * Generates the localized error message from the error params saved from the parsing.
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage($errorParams)
    {
        $this->initializeRequiredClasses();
        $errorType = $errorParams['errorType'];

        // For invalid reference, return early with simple error message
        if ($errorType === self::ERROR_TYPE_INVALID) {
            return $this->translate('list.report.invalidurl');
        }

        $tableName = $errorParams['tablename'];
        $title = $this->translate('list.report.rowdeleted.default.title');
        if ($GLOBALS['TCA'][$tableName]['ctrl']['title']) {
            $title = $this->languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
        }
        switch ($errorType) {
            case self::ERROR_TYPE_DISABLED:
                $message = $this->translate('list.report.rownotvisible');
                $response = sprintf($message, $title, $errorParams['uid']);
                break;
            case self::ERROR_TYPE_DELETED:
                $message = $this->translate('list.report.rowdeleted');
                $response = sprintf($message, $title, $errorParams['uid']);
                break;
            // Default is missing record
            default:
                $message = $this->translate('list.report.rownotexisting');
                $response = sprintf($message, $title, $errorParams['uid']);
        }
        return $response;
    }

    /**
     * Fetches the record corresponding to the current record reference.
     *
     * @param bool $applyEnableFields TRUE to apply enable fields condition to
     * @return array The result row as associative array.
     */
    protected function getRecordRow($applyEnableFields = false)
    {

        $whereStatement = 'uid = ' . $this->recordLink->getId();
        if ($applyEnableFields) {
            $whereStatement .= BackendUtility::BEenableFields($this->recordLink->getTable());
        }

        $row = $this->databaseConnection->exec_SELECTgetSingleRow('*', $this->recordLink->getTable(), $whereStatement);

        // Since exec_SELECTgetSingleRow can return NULL or FALSE we
        // make sure we always return NULL if no row was found.
        if ($row === false) {
            $row = null;
        }

        return $row;
    }

    /**
     * Fetches the translation with the given key and replaces the ###uid### and ###title### markers.
     *
     * @param string $translationKey
     * @param int $uid
     * @param string $title
     * @return string
     */
    protected function getTranslatedErrorMessage($translationKey, $uid, $title = null)
    {
        $message = $this->translate($translationKey);
        $message = str_replace('###uid###', $uid, $message);
        if (isset($title)) {
            $message = str_replace('###title###', $title, $message);
        }
        return $message;
    }

    /**
     * Initializes all required classes.
     */
    protected function initializeRequiredClasses()
    {
        $this->languageService = $GLOBALS['LANG'];
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Returns the translation for the given key.
     *
     * @param string $key
     * @return string
     */
    protected function translate($key)
    {
        return $this->languageService->sL('LLL:' . $this->languageFile . ':' . $key);
    }
}