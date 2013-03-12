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

require_once PATH_t3lib . 'class.t3lib_softrefproc.php';

/**
 * This XCLASS enable two hooks which allows the processing of linkhandler values to the refindex.
 *
 * {@inheritdoc}
 *
 * class.ux_t3lib_softrefproc.php
 *
 * @author Michael Klapper <klapper@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @version $Id$
 * @date $Date$
 * @since 26.10.2009 - 15:15:04
 * @category category
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class ux_t3lib_softrefproc extends t3lib_softrefproc {

	/**
	 * Analyse content as a TypoLink value and return an array with properties.
	 * TypoLinks format is: <link [typolink] [browser target] [css class]>. See tslib_content::typolink()
	 * The syntax of the [typolink] part is: [typolink] = [page id or alias][,[type value]][#[anchor, if integer = tt_content uid]]
	 * The extraction is based on how tslib_content::typolink() behaves.
	 *
	 * @param	string		TypoLink value.
	 * @return	array		Array with the properties of the input link specified. The key "LINK_TYPE" will reveal the type. If that is blank it could not be determined.
	 * @see tslib_content::typolink(), setTypoLinkPartsElement()
	 */
	function getTypoLinkParts($typolinkValue)	{
		$finalTagParts = array();

			// Split by space into link / target / class
		list($link_param, $browserTarget, $cssClass) = t3lib_div::trimExplode(' ',$typolinkValue,1);
		if (strlen($browserTarget))	$finalTagParts['target'] = $browserTarget;
		if (strlen($cssClass))	$finalTagParts['class'] = $cssClass;

			// Parse URL:
		$pU = @parse_url($link_param);

			// Detecting the kind of reference:
		if(strstr($link_param,'@') && !$pU['scheme'])	{		// If it's a mail address:
			$link_param = preg_replace('/^mailto:/i','',$link_param);

			$finalTagParts['LINK_TYPE'] = 'mailto';
			$finalTagParts['url'] = trim($link_param);
		} elseif ($this->isLinkhandlerValue($link_param)) {

				// Check for link-handler keyword:
			list($linkHandlerKeyword, $linkHandlerValue) = explode(':',trim($link_param), 2);
			if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_softrefproc.php']['typolinkLinkHandler'][$linkHandlerKeyword] && strcmp($linkHandlerValue, '')) {
				$linkHandlerObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_softrefproc.php']['typolinkLinkHandler'][$linkHandlerKeyword]);

				if( $linkHandlerObj instanceof softref_typolinkLinkHandlerHook ) {
					$linkHandlerObj->getTypoLinkParts($finalTagParts, $link_param, $this);
				}
			}

		} else {
			$isLocalFile = 0;
			$fileChar = intval(strpos($link_param, '/'));
			$urlChar = intval(strpos($link_param, '.'));

				// Detects if a file is found in site-root (or is a 'virtual' simulateStaticDocument file!) and if so it will be treated like a normal file.
			list($rootFileDat) = explode('?',rawurldecode($link_param));
			$containsSlash = strstr($rootFileDat,'/');
			$rFD_fI = pathinfo($rootFileDat);
			if (trim($rootFileDat) && !$containsSlash && (@is_file(PATH_site.$rootFileDat) || t3lib_div::inList('php,html,htm',strtolower($rFD_fI['extension']))))	{
				$isLocalFile = 1;
			} elseif ($containsSlash)	{
				$isLocalFile = 2;		// Adding this so realurl directories are linked right (non-existing).
			}

			if($pU['scheme'] || ($isLocalFile!=1 && $urlChar && (!$containsSlash || $urlChar<$fileChar)))	{	// url (external): If doubleSlash or if a '.' comes before a '/'.
				$finalTagParts['LINK_TYPE'] = 'url';
				$finalTagParts['url'] = $link_param;
			} elseif ($containsSlash || $isLocalFile)	{	// file (internal)
				$splitLinkParam = explode('?', $link_param);
				if (file_exists(rawurldecode($splitLinkParam[0])) || $isLocalFile)	{
					$finalTagParts['LINK_TYPE'] = 'file';
					$finalTagParts['filepath'] = rawurldecode($splitLinkParam[0]);
					$finalTagParts['query'] = $splitLinkParam[1];
				}
			} else {	// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to definition in $TCA!)
				$finalTagParts['LINK_TYPE'] = 'page';

				$link_params_parts = explode('#',$link_param);
				$link_param = trim($link_params_parts[0]);		// Link-data del

				if (strlen($link_params_parts[1]))	{
					$finalTagParts['anchor'] = trim($link_params_parts[1]);
				}

					// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/? pair
				$pairParts = t3lib_div::trimExplode(',',$link_param);
				if (count($pairParts)>1)	{
					$link_param = $pairParts[0];
					$finalTagParts['type'] = $pairParts[1];		// Overruling 'type'
				}

					// Checking if the id-parameter is an alias.
				if (strlen($link_param))	{
					if (!t3lib_div::testInt($link_param))	{
						$finalTagParts['alias'] = $link_param;
						$link_param = $this->getPageIdFromAlias($link_param);
					}

					$finalTagParts['page_id'] = intval($link_param);
				}
			}
		}

		return $finalTagParts;
	}

	/**
	 * Inndicate that the given $linkParam is a value from a registered linkhandler.
	 *
	 * @param string $linkParam Contains the value of the href attribute.
	 *
	 * @access protected
	 * @return boolean
	 *
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	protected function isLinkhandlerValue($linkParam) {
		list ($linkHandlerKeyword, , ) = explode(':', $linkParam);
		return array_key_exists($linkHandlerKeyword, $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']);
	}

	/**
	 * Recompile a TypoLink value from the array of properties made with getTypoLinkParts() into an elements array
	 *
	 * @param	array		TypoLink properties
	 * @param	array		Array of elements to be modified with substitution / information entries.
	 * @param	string		The content to process.
	 * @param	integer		Index value of the found element - user to make unique but stable tokenID
	 * @return	string		The input content, possibly containing tokens now according to the added substitution entries in $elements
	 * @see getTypoLinkParts()
	 */
	function setTypoLinkPartsElement($tLP, &$elements, $content, $idx)	{

			// Initialize, set basic values. In any case a link will be shown
		$tokenID = $this->makeTokenID('setTypoLinkPartsElement:'.$idx);
		$elements[$tokenID.':'.$idx] = array();
		$elements[$tokenID.':'.$idx]['matchString'] = $content;

			// Based on link type, maybe do more:
		switch ((string)$tLP['LINK_TYPE'])	{
			case 'mailto':
			case 'url':
					// Mail addresses and URLs can be substituted manually:
				$elements[$tokenID.':'.$idx]['subst'] = array(
					'type' => 'string',
					'tokenID' => $tokenID,
					'tokenValue' => $tLP['url'],
				);
					// Output content will be the token instead:
				$content = '{softref:'.$tokenID.'}';
			break;
			case 'file':
					// Process files found in fileadmin directory:
				if (!$tLP['query'])	{	// We will not process files which has a query added to it. That will look like a script we don't want to move.
					if (t3lib_div::isFirstPartOfStr($tLP['filepath'],$this->fileAdminDir.'/'))	{	// File must be inside fileadmin/

							// Set up the basic token and token value for the relative file:
						$elements[$tokenID.':'.$idx]['subst'] = array(
							'type' => 'file',
							'relFileName' => $tLP['filepath'],
							'tokenID' => $tokenID,
							'tokenValue' => $tLP['filepath'],
						);

							// Depending on whether the file exists or not we will set the
						$absPath = t3lib_div::getFileAbsFileName(PATH_site.$tLP['filepath']);
						if (!@is_file($absPath))	{
							$elements[$tokenID.':'.$idx]['error'] = 'File does not exist!';
						}

							// Output content will be the token instead
						$content = '{softref:'.$tokenID.'}';
					} else return $content;
				} else return $content;
			break;
			case 'linkhandler':
					// Rebuild linkhandler reference
				list($linkHandlerKeyword, $linkHandlerValue) = explode(':',trim($content), 2);
				if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_softrefproc.php']['typolinkLinkHandler'][$linkHandlerKeyword] && strcmp($linkHandlerValue, '')) {
					$linkHandlerObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_softrefproc.php']['typolinkLinkHandler'][$linkHandlerKeyword]);

					if( $linkHandlerObj instanceof softref_typolinkLinkHandlerHook ) {
						$linkHandlerObj->setTypoLinkPartsElement($tokenID, $content, $elements, $idx, $this);
					}
				}

			break;
			case 'page':
					// Rebuild page reference typolink part:
				$content = '';

					// Set page id:
				if ($tLP['page_id'])	{
					$content.= '{softref:'.$tokenID.'}';
					$elements[$tokenID.':'.$idx]['subst'] = array(
						'type' => 'db',
						'recordRef' => 'pages:'.$tLP['page_id'],
						'tokenID' => $tokenID,
						'tokenValue' => $tLP['alias'] ? $tLP['alias'] : $tLP['page_id'],	// Set page alias if that was used.
					);
				}

					// Add type if applicable
				if (strlen($tLP['type']))	{
					$content.= ','.$tLP['type'];
				}

					// Add anchor if applicable
				if (strlen($tLP['anchor']))	{
					if (t3lib_div::testInt($tLP['anchor']))	{	// Anchor is assumed to point to a content elements:
							// Initialize a new entry because we have a new relation:
						$newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:'.$idx);
						$elements[$newTokenID.':'.$idx] = array();
						$elements[$newTokenID.':'.$idx]['matchString'] = 'Anchor Content Element: '.$tLP['anchor'];

						$content.= '#{softref:'.$newTokenID.'}';
						$elements[$newTokenID.':'.$idx]['subst'] = array(
							'type' => 'db',
							'recordRef' => 'tt_content:'.$tLP['anchor'],
							'tokenID' => $newTokenID,
							'tokenValue' => $tLP['anchor'],
						);
					} else {	// Anchor is a hardcoded string
						$content.= '#'.$tLP['type'];
					}
				}
			break;
			default:
				{
					$elements[$tokenID.':'.$idx]['error'] = 'Couldn\t decide typolink mode.';
					return $content;
				}
			break;
		}

			// Finally, for all entries that was rebuild with tokens, add target and class in the end:
		if (strlen($content) && strlen($tLP['target']))	{
			$content.= ' '.$tLP['target'];
			if (strlen($tLP['class']))	{
				$content.= ' '.$tLP['class'];
			}
		}

			// Return rebuilt typolink value:
		return $content;
	}
}

?>