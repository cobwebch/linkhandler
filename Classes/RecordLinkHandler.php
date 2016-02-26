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

use Psr\Http\Message\ServerRequestInterface;
use Cobweb\Linkhandler\Tree\View\RecordBrowserPageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Recordlist\LinkHandler\AbstractLinkHandler;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Link handler for arbitrary database records
 */
class RecordLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{

    /**
     * Configuration key in TSconfig TCEMAIN.linkHandler.record
     *
     * @var string
     */
    protected $identifier;

    /**
     * Specific TSconfig for the current instance (corresponds to TCEMAIN.linkHandler.record.identifier.configuration)
     * @var array
     */
    protected $configuration = array();

    /**
     * Parts of the current link
     *
     * @var array
     */
    protected $linkParts = [];

    /**
     * @var int
     */
    protected $expandPage = 0;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Initializes the handler.
     *
     * @param AbstractLinkBrowserController $linkBrowser
     * @param string $identifier
     * @param array $configuration Page TSconfig
     *
     * @return void
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        parent::initialize($linkBrowser, $identifier, $configuration);
        $this->identifier = $identifier;
        $this->configuration = $configuration;
    }

    /**
     * Checks if this is the right handler for the given link.
     *
     * Also stores information locally about currently linked record.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (!$linkParts['url']) {
            return false;
        }

        try {
            $isRecordReference = StringUtility::beginsWith($linkParts['url'], 'record:' . $this->identifier . ':');
        } catch (\Exception $e) {
            return false;
        }

        if ($isRecordReference) {
            // Get the related record
            $recordParts = explode(':', $linkParts['url']);
            $table = $recordParts[2];
            $uid = (int)$recordParts[3];
            $record = BackendUtility::getRecord(
                    $table,
                    $uid
            );
            if ($record === null) {
                $linkParts['title'] = $this->getLanguageService()->getLL('recordNotFound');
            } else {
                $recordTitle = BackendUtility::getRecordTitle(
                        $table,
                        $record
                );
                // Store information about that record
                $linkParts['table'] = $table;
                $linkParts['tableName'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
                $linkParts['pid'] = (int)$record['pid'];
                $linkParts['uid'] = $uid;
                $linkParts['title'] = $recordTitle;
            }
            $this->linkParts = $linkParts;
            return true;
        }
        return false;
    }

    /**
     * Formats information for the current record for HTML output.
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        return sprintf(
                '%s: %s [uid: %d]',
                $this->linkParts['tableName'],
                $this->linkParts['title'],
                $this->linkParts['uid']
        );
    }

    /**
     * Renders the link handler.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        // Store the request object for further use
        $this->request = $request;

        // Declare JS module
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Linkhandler/RecordLinkHandler');

        // Define the current page
        $this->expandPage = 0;
        if (isset($request->getQueryParams()['expandPage'])) {
            $this->expandPage = (int)$request->getQueryParams()['expandPage'];
        } elseif (array_key_exists('pid', $this->linkParts)) {
            $this->expandPage = (int)$this->linkParts['pid'];
        } elseif (array_key_exists('storagePid', $this->configuration)) {
            $this->expandPage = (int)$this->configuration['storagePid'];
        }

        /** @var \Cobweb\Linkhandler\Browser\RecordBrowser $databaseBrowser */
        $databaseBrowser = GeneralUtility::makeInstance('Cobweb\\Linkhandler\\Browser\\RecordBrowser');

        // Page tree may be hidden
        if ($this->configuration['hidePageTree']) {
            $tree = '';
        } else {
            $tree = '<td class="c-wCell" valign="top">' . $this->renderPageTree() . '</td>';
        }
        $recordList = $databaseBrowser->displayRecordsForPage(
                $this->expandPage,
                $this->configuration['table'],
                $this->getUrlParameters([])
        );
        $content = '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
				<tr>
					' . $tree . '
					<td class="c-wCell" valign="top">' . $recordList . '</td>
				</tr>
			</table>
		';

        return $content;
    }

    /**
     * Renders the page tree.
     *
     * @return string
     */
    protected function renderPageTree()
    {
        $backendUser = $this->getBackendUser();

        // @todo: Load mount points
        /** @var RecordBrowserPageTreeView $pageTree */
        $pageTree = GeneralUtility::makeInstance(RecordBrowserPageTreeView::class);
        $pageTree->setLinkParameterProvider($this);
        $pageTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $pageTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $pageTree->addField('nav_title');

        $tree = '<h3>' . $this->getLanguageService()->getLL('pageTree') . ':</h3>';
        $tree .= $pageTree->getBrowsableTree();

        return $tree;
    }

    /**
     * Returns attributes for the body tag.
     *
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        $attributes = [
                'data-identifier' => 'record:' . $this->identifier
        ];
        if (array_key_exists('url', $this->linkParts)) {
            $attributes['data-current-link'] = $this->linkParts['url'];
        }
        return $attributes;
    }

    /**
     * Returns all parameters needed to build a URL with all the necessary information.
     *
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     *
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        $pid = isset($values['pid']) ? (int)$values['pid'] : $this->expandPage;
        $parameters = [
                'expandPage' => $pid
        ];
        return array_merge(
                $this->linkBrowser->getUrlParameters($values),
                ['P' => $this->linkBrowser->getParameters()],
                $parameters
        );
    }

    /**
     * Checks if the submitted page matches the current page.
     *
     * @param array $values Values to be checked
     *
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return count($this->linkParts) > 0 && (int)$this->linkParts['pid'] === (int)$values['pid'];
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->linkBrowser->getScriptUrl();
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
