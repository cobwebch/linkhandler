<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, Michael Klapper <klapper@aoemedia.de>
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

if ( version_compare(TYPO3_version, '4.4.0', '<') ) {
	require_once t3lib_extMgm::extPath('linkhandler') . 'patch/interfaces/interface.softref_typolinkLinkHandlerHook.php';
} else {
	require_once PATH_t3lib . 'interfaces/interface.softref_typolinkLinkHandlerHook.php';
}

/**
 * {@inheritdoc}
 *
 * class.tx_linkhandler_softref_typolinkLinkHandlerHook.php
 *
 * @author Michael Klapper <klapper@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @version $Id$
 * @date $Date$
 * @since 21.10.2009 - 15:15:04
 * @category category
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class tx_linkhandler_softref_typolinkLinkHandlerHook implements softref_typolinkLinkHandlerHook {

	/**
	 * {@inheritdoc}
	 *
	 * @param	array               the current database table
	 * @param	string              the record's page ID
	 * @param	t3lib_softrefproc   an additional WHERE clause
	 *
	 * @access public
	 * @return	void
	 *
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function getTypoLinkParts(&$finalTagParts, $linkParam, $parentObj) {
		$finalTagParts['LINK_TYPE'] = 'linkhandler';
		$finalTagParts['url']       = trim($linkParam);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $tokenID                    Unique identifyer for a link of an record
	 * @param string $content                    The content to process.
	 * @param array $elements                    Array of elements to be modified with substitution / information entries.
	 * @param string $idx                        Index value of the found element - user to make unique but stable tokenID
	 * @param t3lib_softrefproc $parentObj
	 *
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function setTypoLinkPartsElement(&$tokenID, &$content, &$elements, $idx, $parentObj) {
		list ($handlerKeyWord, $recordTableName, $recordUid ) = explode(':', $content);

		$elements[$tokenID.':'.$idx]['subst'] = array (
			'type' => 'db',
			'recordRef' => $recordTableName . ':' . $recordUid,
			'tokenID' => $tokenID,
			'tokenValue' => $content
		);

		$content = '{softref:'.$tokenID.'}';
	}
}

?>