<?php
namespace Aoe\Linkhandler\Browser;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel P�tzinger <daniel.poetzinger@aoemedia.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Tabhandler interface
 *
 * @author	Daniel Poetzinger (AOE media GmbH)
 * @version $Id: $
 * @date 08.04.2009 - 15:06:25
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
interface TabHandlerInterface {

	/**
	 * constructur for the tabHandler. Normally used to sets some internal vars
	 *
	 * @param \Aoe\Linkhandler\Browser\ElementBrowserHook $elementBrowserHook
	 * @param string $activeTab
	 */
	public function __construct($elementBrowserHook, $activeTab);

	/**
	 * should return the correct info array that is required for the link wizard.
	 * It should detect if the current value is a link where this tabHandler should be responsible.
	 * else it should return a emty array
	 *
	 * @param string $href
	 * @param array $tabsConfig
	 * @return array
	 */
	static public function getLinkBrowserInfoArray($href, $tabsConfig);

	/**
	 * returns a new tab for the browse links wizard
	 *
	 * @param	string current link selector action
	 * @return	string a tab for the selected link action
	 */
	function getTabContent();
}
