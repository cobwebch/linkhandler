<?php
namespace Aoe\Linkhandler\Linkvalidator;

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
     * If this is TRUE an error will also be reported if the linked record
     * is disabled. Otherwise the error will only be reported if the
     * record is deleted or does not exist.
     *
     * @var boolean
     */
    protected $reportHiddenRecords;

    /**
     * Tab handler factory for retrieving link information.
     *
     * @var \Aoe\Linkhandler\Browser\TabHandlerFactory
     */
    protected $tabHandlerFactory;

    /**
     * Checks a given URL for validity
     *
     * @param string $url Url to check
     * @param array $softRefEntry The soft reference entry which builds the context of that url
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
     * @return boolean TRUE on success or FALSE on error
     */
    public function checkLink($url, $softRefEntry, $reference)
    {
        $response = true;
        $errorType = '';
        $errorParams = array();
        $this->initializeRequiredClasses();

        $linkInfo = $this->tabHandlerFactory->getLinkInfoArrayFromMatchingHandler($url);
        if (empty($linkInfo)) {
            return true;
        }

        $tableName = $linkInfo['recordTable'];
        $rowid = $linkInfo['recordUid'];
        $row = null;
        $tsConfig = $reference->getTSConfig();
        $this->reportHiddenRecords = (bool)$tsConfig['tx_linkhandler.']['reportHiddenRecords'];

        // First check, if we find a non disabled record if the check
        // for hidden records is enabled.
        if ($this->reportHiddenRecords) {
            $row = $this->getRecordRow($tableName, $rowid, 'disabled');
            if ($row === null) {
                $response = false;
                $errorType = self::ERROR_TYPE_DISABLED;
            }
        }

        // If no enabled record was found or we did not check that see
        // if we can find a non deleted record.
        if ($row === null) {
            $row = $this->getRecordRow($tableName, $rowid, 'deleted');
            if ($row === null) {
                $response = false;
                $errorType = self::ERROR_TYPE_DELETED;
            }
        }

        // If we did not find a non deleted record, check if we find a
        // deleted one.
        if ($row === null) {
            $row = $this->getRecordRow($tableName, $rowid, 'all');
            if ($row === null) {
                $response = false;
                $errorType = '';
            }
        }

        if (!$response) {
            $errorParams['errorType'] = $errorType;
            $errorParams['tablename'] = $tableName;
            $errorParams['uid'] = $rowid;
            $this->setErrorParams($errorParams);
        }

        return $response;
    }

    /**
     * Type fetching method, based on the type that softRefParserObj returns
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
     * Generate the localized error message from the error params saved from the parsing
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage($errorParams)
    {
        $this->initializeRequiredClasses();
        $errorType = $errorParams['errorType'];
        $tableName = $errorParams['tablename'];
        $title = $this->translate('list.report.rowdeleted.default.title');
        if ($GLOBALS['TCA'][$tableName]['ctrl']['title']) {
            $title = $this->languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
        }
        switch ($errorType) {
            case self::ERROR_TYPE_DISABLED:
                $response = $this->getTranslatedErrorMessage('list.report.rownotvisible', $errorParams['uid'], $title);
                break;
            case self::ERROR_TYPE_DELETED:
                $response = $this->getTranslatedErrorMessage('list.report.rowdeleted', $errorParams['uid'], $title);
                break;
            default:
                $response = $this->getTranslatedErrorMessage('list.report.rownotexisting', $errorParams['uid']);
        }
        return $response;
    }

    /**
     * Fetches the record with the given UID from the given table.
     *
     * The filter option accepts two values:
     *
     * "disabled" will filter out disabled and deleted records.
     * "deleted" filters out deleted records but will return disabled records.
     * If nothing is specified all records will be returned (including deleted).
     *
     * @param string $tableName The name of the table from which the record should be fetched.
     * @param string $uid The UID of the record that should be fetched.
     * @param string $filter A filter setting, can be empty or "disabled" or "deleted".
     * @return array The result row as associative array.
     */
    protected function getRecordRow($tableName, $uid, $filter = '')
    {

        $whereStatement = 'uid = ' . (int)$uid;

        switch ($filter) {
            case 'disabled':
                $whereStatement .= BackendUtility::BEenableFields($tableName) . BackendUtility::deleteClause($tableName);
                break;
            case 'deleted':
                $whereStatement .= BackendUtility::deleteClause($tableName);
                break;
        }

        $row = $this->databaseConnection->exec_SELECTgetSingleRow(
                '*',
                $tableName,
                $whereStatement
        );

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
     * Initializes all required classes if required.
     */
    protected function initializeRequiredClasses()
    {
        if (isset($this->tabHandlerFactory)) {
            return;
        }
        $this->tabHandlerFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Aoe\\Linkhandler\\Browser\\TabHandlerFactory');
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