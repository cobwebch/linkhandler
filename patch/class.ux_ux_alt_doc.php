<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
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

require_once PATH_typo3 . 'alt_doc.php';

/**
 * This XCLASS provide the bug fix #10827: Hide "Save and View"-button when editing a content-element
 * 
 * Big thanks to Steffen Kamper for that patch.
 *
 * {@inheritdoc}
 *
 * class.ux_ux_SC_alt_doc.php
 *
 * @author Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @version $Id$
 * @date $Date$
 * @since 26.10.2009 - 15:15:04
 * @category category
 * @package TYPO3
 * @subpackage tx_linkhandler
 * @access public
 */
class ux_ux_SC_alt_doc extends ux_SC_alt_doc {

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		global $TCA,$LANG;
		$buttons = array(
			'save' => '',
			'save_view' => '',
			'save_new' => '',
			'save_close' => '',
			'close' => '',
			'delete' => '',
			'undo' => '',
			'history' => '',
			'columns_only' => '',
			'csh' => '',
		);

			// Render SAVE type buttons:
			// The action of each button is decided by its name attribute. (See doProcessData())
		if (!$this->errorC && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'])	{

				// SAVE button:
			$buttons['save'] = '<input type="image" class="c-inputButton" name="_savedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'" />';

				// SAVE / VIEW button:
			if ($this->viewId && !$this->noView && t3lib_extMgm::isLoaded('cms') && $this->getNewIconMode($this->firstEl['table'], 'saveDocView')) {
				$buttons['save_view'] = '<input type="image" class="c-inputButton" name="_savedokview"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedokshow.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDocShow',1).'" />';
			}

				// SAVE / NEW button:
			if (count($this->elementsData)==1 && $this->getNewIconMode($this->firstEl['table'])) {
				$buttons['save_new'] = '<input type="image" class="c-inputButton" name="_savedoknew"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedoknew.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveNewDoc',1).'" />';
			}

				// SAVE / CLOSE
			$buttons['save_close'] = '<input type="image" class="c-inputButton" name="_saveandclosedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/saveandclosedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'" />';

				// FINISH TRANSLATION / SAVE / CLOSE
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['explicitConfirmationOfTranslation'])	{
				$buttons['translation_save'] = '<input type="image" class="c-inputButton" name="_translation_savedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/translationsavedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.translationSaveDoc',1).'" />';
			}
		}

			// CLOSE button:
		$buttons['close'] = '<a href="#" onclick="document.editform.closeDoc.value=1; document.editform.submit(); return false;">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/closedok.gif','width="21" height="16"').' class="c-inputButton" title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'" alt="" />'.
				'</a>';


			// DELETE + UNDO buttons:
		if (!$this->errorC && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'] && count($this->elementsData)==1)	{
			if ($this->firstEl['cmd']!='new' && t3lib_div::testInt($this->firstEl['uid']))	{

					// Delete:
				if ($this->firstEl['deleteAccess'] && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'] && !$this->getNewIconMode($this->firstEl['table'],'disableDelete')) {
					$aOnClick = 'return deleteRecord(\''.$this->firstEl['table'].'\',\''.$this->firstEl['uid'].'\',unescape(\''.rawurlencode($this->retUrl).'\'));';
					$buttons['delete'] = '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/deletedok.gif','width="21" height="16"').' class="c-inputButton" title="'.$LANG->getLL('deleteItem',1).'" alt="" />'.
							'</a>';
				}

					// Undo:
				$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->firstEl['table'], 'sys_history').' AND recuid='.intval($this->firstEl['uid']), '', 'tstamp DESC', '1');
				if ($undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes))	{
					$aOnClick = 'window.location.href=\'show_rechis.php?element='.rawurlencode($this->firstEl['table'].':'.$this->firstEl['uid']).'&revert=ALL_FIELDS&sumUp=-1&returnUrl='.rawurlencode($this->R_URI).'\'; return false;';
					$buttons['undo'] = '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/undo.gif','width="21" height="16"').' class="c-inputButton" title="'.htmlspecialchars(sprintf($LANG->getLL('undoLastChange'),t3lib_BEfunc::calcAge(time()-$undoButtonR['tstamp'],$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')))).'" alt="" />'.
							'</a>';
				}
				if ($this->getNewIconMode($this->firstEl['table'],'showHistory'))	{
					$aOnClick = 'window.location.href=\'show_rechis.php?element='.rawurlencode($this->firstEl['table'].':'.$this->firstEl['uid']).'&returnUrl='.rawurlencode($this->R_URI).'\'; return false;';
					$buttons['history'] = '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/history2.gif','width="13" height="12"').' class="c-inputButton" alt="" />'.
							'</a>';
				}

					// If only SOME fields are shown in the form, this will link the user to the FULL form:
				if ($this->columnsOnly)	{
					$buttons['columns_only'] = '<a href="'.htmlspecialchars($this->R_URI.'&columnsOnly=').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' class="c-inputButton" title="'.$LANG->getLL('editWholeRecord',1).'" alt="" />'.
							'</a>';
				}
			}
		}

			// add the CSH icon
		$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'TCEforms', $GLOBALS['BACK_PATH'], '', TRUE);
		$buttons['shortcut'] = $this->shortCutLink();
		$buttons['open_in_new_window'] = $this->openInNewWindowLink();
		return $buttons;
	}

	/**
	 * Function used to look for configuration of buttons in the form: Fx. disabling buttons or showing them at various positions.
	 *
	 * @param	string		The table for which the configuration may be specific
	 * @param	string		The option for look for. Default is checking if the saveDocNew button should be displayed.
	 * @return	string		Return value fetched from USER TSconfig
	 */
	function getNewIconMode($table, $key = 'saveDocNew') {
		$TSconfig = $GLOBALS['BE_USER']->getTSConfig('options.'.$key);
		$output = trim(isset($TSconfig['properties'][$table]) ? $TSconfig['properties'][$table] : $TSconfig['value']);
		if (($key == 'saveDocNew' || $key == 'saveDocView') && $TSconfig['value'] != '0') {
			$output = !(isset($TSconfig['properties'][$table]) && $TSconfig['properties'][$table] == '0');
		}
		// exclude view for sysfolders. Configuration:  options.saveDocView.excludeDokTypes = 254,255,...
		if ($key == 'saveDocView') {

			if (isset($TSconfig['properties']['excludeDokTypes'])) {
				$excludeDokTypes = t3lib_div::trimExplode(',', $TSconfig['properties']['excludeDokTypes'], true);
			} else {
					// exclude sysfolders  and recycler by default
				$excludeDokTypes = array('254', '255');
			}

			if (in_array($this->pageinfo['doktype'], $excludeDokTypes) && ($output !== true || !array_key_exists($table, $TSconfig['properties']) )) {
				$output = false;
			}
		}
		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/patch/class.ux_ux_alt_doc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/patch/class.ux_ux_alt_doc.php']);
}

?>