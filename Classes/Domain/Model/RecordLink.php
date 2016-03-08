<?php
namespace Cobweb\Linkhandler\Domain\Model;

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

/**
 * Container for the parts of a linkhandler record reference.
 *
 * @todo: use that class in all possible places
 *
 * @package Cobweb\Linkhandler\Domain\Model
 */
class RecordLink {
    /**
     * @var string Name of the linkhandler configuration
     */
    protected $configurationKey;

    /**
     * @var string Name of the table being linked to
     */
    protected $table;

    /**
     * @var int Primary key of the record being linked to
     */
    protected $id;

    /**
     * @var string Full record reference in linkhandler syntax, i.e record:key:table:id
     */
    protected $recordReference;

    public function __construct($recordReference = null)
    {
        if ($recordReference !== null) {
            $this->setRecordReference($recordReference);
        }
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getRecordReference()
    {
        return $this->recordReference;
    }

    /**
     * @param string $recordReference
     * @throws \InvalidArgumentException
     */
    public function setRecordReference($recordReference)
    {
        if (empty($recordReference)) {
            throw new \InvalidArgumentException(
                    'Record reference cannot be empty',
                    1457367830
            );
        }
        $referenceParts = explode(':', $recordReference);
        if (count($referenceParts) === 4) {
            $this->recordReference = $recordReference;
            $this->configurationKey = $referenceParts[1];
            $this->table = $referenceParts[2];
            $this->id = (int)$referenceParts[3];
        } else {
            throw new \InvalidArgumentException(
                    'Expected record reference structure is "record:key:table:id"',
                    1457367830
            );
        }
    }

}