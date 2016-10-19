<?php
namespace Cobweb\Linkhandler\Domain\Repository;

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

use Cobweb\Linkhandler\Exception\FailedQueryException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Non-Extbase repository for accessing any table contain record links.
 *
 * @package Cobweb\Linkhandler\Domain\Repository
 */
class GenericRepository
{
    /**
     * Fetches a list of records from given table that contain links to records.
     *
     * @param string $table Name of the table to query
     * @param array $listOfFields List of fields to get in the query
     * @throws FailedQueryException
     * @return array
     */
    public function findByRecordLink($table, $listOfFields)
    {
        $records = array();
        // Add a simple condition for detecting record references to avoid getting all rows from the table
        // NOTE: this may catch false positives, but this is okay at that point
        $conditions = array();
        foreach ($listOfFields as $field) {
            $conditions[] = $field . ' LIKE \'%record:%\'';
        }
        $where = implode(' OR ', $conditions) . BackendUtility::deleteClause($table);
        try {
            $fields = implode(', ', $listOfFields);
            $result = $this->getDatabaseConnection()->exec_SELECTgetRows('uid, ' . $fields, $table, $where);
            if ($result === null) {
                throw new FailedQueryException(
                    sprintf(
                        'A SQL error occurred querying table "%s" with fields "%s": %s',
                        $table,
                        $fields,
                        $this->getDatabaseConnection()->sql_error()
                    ),
                    1457441163
                );
            } else {
                $records = $result;
            }
        } catch (\InvalidArgumentException $e) {
            // Nothing to do here
        }
        return $records;
    }

    /**
     * Saves a bunch of records to the database.
     *
     * @param string $table Name of the table to update
     * @param array $records List of records to update
     * @return bool
     */
    public function massUpdate($table, $records)
    {
        $globalResult = true;
        // @todo: this could be improved to provide better reporting on errors (and maybe use transactions to roll everything back)
        foreach ($records as $id => $fields) {
            $result = $this->getDatabaseConnection()->exec_UPDATEquery($table, 'uid = ' . (int)$id, $fields);
            $globalResult &= $result;
        }
        return $globalResult;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}