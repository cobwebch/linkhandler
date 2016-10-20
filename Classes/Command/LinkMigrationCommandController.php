<?php
namespace Cobweb\Linkhandler\Command;

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

use Cobweb\Linkhandler\Domain\Repository\GenericRepository;
use Cobweb\Linkhandler\Exception\FailedQueryException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Command-line controller for migrating old record links (with a syntax like record:table:id)
 * to the new syntax (record:key:table:id).
 *
 * @package TYPO3\CMS\Extbase\Command
 */
class LinkMigrationCommandController extends CommandController
{
    /**
     * Default list of fields where to search for record references to migrate
     */
    const DEFAULT_FIELDS = 'tt_content.header_link, tt_content.bodytext, sys_file_reference.link';

    /**
     * @var array List of fields to handle, grouped by table
     */
    protected $tablesAndFields = array();

    /**
     * @var array List of tables being linked to in record links
     */
    protected $tablesForMigration = array();

    /**
     * @var array Structured list of records that contain data to migrate
     */
    protected $recordsForMigration = array();

    /**
     * @var GenericRepository
     */
    protected $genericRepository;

    /**
     * @var ConsoleOutput
     */
    protected $console;

    /**
     * @param GenericRepository $repository
     * @return void
     */
    public function injectGenericRepository(GenericRepository $repository)
    {
        $this->genericRepository = $repository;
    }

    /**
     * @param ConsoleOutput $consoleOutput
     * @return void
     */
    public function injectConsole(ConsoleOutput $consoleOutput)
    {
        $this->console = $consoleOutput;
    }

    /**
     * Migrates old-style records links (syntax: "record:table:id") to new-style record links (syntax: "record:key:table:id").
     *
     * @param string $fields Name of the field to migrate (syntax is "table.field"; use comma-separated values for several fields). Ignore to migrate default fields (tt_content.header_link, tt_content.bodytext, sys_file_reference.link)
     */
    public function migrateCommand($fields = '')
    {
        // Set default value if argument is empty
        if ($fields === '') {
            $fields = self::DEFAULT_FIELDS;
        }
        try {
            $this->setFields($fields);
            // Loop on all tables and fields
            foreach ($this->tablesAndFields as $table => $listOfFields) {
                $this->gatherRecordsToMigrate($table, $listOfFields);
            }
            // Ask the user for configuration key for each table
            $this->getConfigurationKeys();
            // Replace in fields and save modified data
            $this->migrateRecords();
        } catch (\InvalidArgumentException $e) {
            $this->outputLine($e->getMessage() . ' (' . $e->getCode() . ')');
            $this->quit(1);
        }
    }

    /**
     * Sets the internal list of fields to handle.
     *
     * @param string $fields Comma-separated list of fields (syntax table.field)
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function setFields($fields)
    {
        $listOfFields = GeneralUtility::trimExplode(',', $fields, true);
        foreach ($listOfFields as $aField) {
            list($table, $field) = explode('.', $aField);
            if (empty($table) || empty($field)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid argument "%s". Use "table.field" syntax', $aField),
                    1457434202
                );
            } else {
                if (!array_key_exists($table, $this->tablesAndFields)) {
                    $this->tablesAndFields[$table] = array();
                }
                $this->tablesAndFields[$table][] = $field;
            }
        }
    }

    /**
     * Gathers all records that contain data to migrate.
     *
     * Also extracts a list of all the different tables being linked to.
     * This is used later to ask the user about a configuration key for each table.
     *
     * @param string $table Name of the table to check
     * @param array $listOfFields List of fields to check
     * @return void
     */
    protected function gatherRecordsToMigrate($table, $listOfFields)
    {
        try {
            $records = $this->genericRepository->findByRecordLink($table, $listOfFields);
            foreach ($records as $record) {
                $id = (int)$record['uid'];
                foreach ($listOfFields as $field) {
                    $matches = array();
                    // Find all element that have a syntax like "record:string:string(:string)"
                    // The last string is optional. If it exists, it is already a 4-part record reference,
                    // i.e. a reference using the new syntax and which does not need to be migrated.
                    preg_match_all('/record:(\w+):(\w+)(:\w+)?/', $record[$field], $matches);
                    foreach ($matches as $index => $match) {
                        // Consider only matches that have 3 parts (i.e. 4th part is empty)
                        // NOTE: although not captured, the first part is "record:"
                        if ($matches[3][$index] === '') {
                            $linkedTable = $matches[1][$index];
                            // First, add the table to the list of table that are targeted by record links
                            if (!array_key_exists($linkedTable, $this->tablesForMigration)) {
                                $this->tablesForMigration[$linkedTable] = '';
                            }
                            // Next keep track of the record that needs migration
                            // Data is stored per table, per record, per field and per record link to migrate
                            if (!array_key_exists($table, $this->recordsForMigration)) {
                                $this->recordsForMigration[$table] = array();
                            }
                            if (!array_key_exists($id, $this->recordsForMigration[$table])) {
                                $this->recordsForMigration[$table][$id] = array();
                            }
                            if (!array_key_exists($field, $this->recordsForMigration[$table][$id])) {
                                $this->recordsForMigration[$table][$id][$field] = array(
                                    'content' => $record[$field],
                                    'matches' => array(),
                                );
                            }
                            $this->recordsForMigration[$table][$id][$field]['matches'][] = $matches[0][$index];
                        }
                    }
                }
            }
        } catch (FailedQueryException $e) {
            $this->outputLine(sprintf('Table "%s" skipped. An error occurred: %s', $table, $e->getMessage()));
        }
    }

    /**
     * Asks the user to give a configuration key for each table that is being linked to.
     *
     * @return void
     */
    protected function getConfigurationKeys()
    {
        foreach ($this->tablesForMigration as $table => &$dummy) {
            $key = null;
            do {
                try {
                    $key = $this->console->ask(
                        sprintf('Please enter the configuration key to use for table "%s": ', $table)
                    );
                } catch (\Exception $e) {
                    // Do nothing, just let it try again
                }
            } while ($key === null);
            $dummy = $key;
        }
    }

    /**
     * Updates all fields that needed some migration and saves the modified data.
     *
     * @return void
     */
    protected function migrateRecords()
    {
        foreach ($this->recordsForMigration as $table => $records) {
            $recordsForTable = array();
            foreach ($records as $id => $fields) {
                foreach ($fields as $field => $fieldInformation) {
                    $updatedField = $fieldInformation['content'];
                    foreach ($fieldInformation['matches'] as $link) {
                        $linkParts = explode(':', $link);
                        $newLink = 'record:' . $this->tablesForMigration[$linkParts[1]] . ':' . $linkParts[1] . ':' . $linkParts[2];
                        $updatedField = str_replace($link, $newLink, $updatedField);
                    }
                    if (!array_key_exists($id, $recordsForTable)) {
                        $recordsForTable[$id] = array();
                    }
                    $recordsForTable[$id][$field] = $updatedField;
                }
            }
            $result = $this->genericRepository->massUpdate($table, $recordsForTable);
            if (!$result) {
                $this->outputLine('Some database updates failed for table "%s"', $table);
            }
        }
    }
}