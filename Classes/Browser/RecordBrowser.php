<?php
namespace Cobweb\Linkhandler\Browser;

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

use TYPO3\CMS\Recordlist\Browser\DatabaseBrowser;

class RecordBrowser extends DatabaseBrowser
{
    /**
     * @var array
     */
    protected $urlParameters = [];

    /**
     * Main initialization
     *
     * @return void
     */
    protected function initialize()
    {
        $this->determineScriptUrl();
        $this->initVariables();
        $this->pageRenderer->loadRequireJsModule('Cobweb/Linkhandler/RecordLinkHandler');
    }

    /**
     * @return void
     */
    protected function initVariables()
    {

    }

    /**
     * @param int $selectedPage
     * @param string $tables
     * @param array $urlParameters
     *
     * @return string
     */
    public function displayRecordsForPage($selectedPage, $tables, $urlParameters)
    {
        $this->urlParameters = $urlParameters;
        $this->urlParameters['mode'] = 'db';
        $this->expandPage = $selectedPage;
        return $this->renderTableRecords($tables);
    }

    /**
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        return array_merge($this->urlParameters, $values);
    }
}
