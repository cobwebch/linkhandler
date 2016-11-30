<?php
namespace Cobweb\Linkhandler\Hooks;

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

use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;

/**
 * Interface for classes which hook into \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
 * and do additional getTable processing
 */
class RecordListGetTableHook implements RecordListGetTableHookInterface
{
    /**
     * modifies the DB list query
     *
     * @param string $table The current database table
     * @param int $pageId The record's page ID
     * @param string $additionalWhereClause An additional WHERE clause
     * @param string $selectedFieldsList Comma separated list of selected fields
     * @param \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $parentObject Parent \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList object
     * @return void
     */
    public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject) {
        $additionalWhereClause .= ' AND (sys_language_uid<=0 OR l10n_parent = 0)';
    }
}
