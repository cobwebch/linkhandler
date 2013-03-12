<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2005-2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * Adapted for htmlArea RTE by Stanislas Rolland
 *
 * $Id: class.tx_rtehtmlarea_browse_links.php 1676 2006-08-15 04:51:33Z stanrolland $
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 */





/**
 * Script class for the Element Browser window.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class ux_browse_links extends browse_links {


	var $hookObjectsArr=array();


	/**
	 * Constructor:
	 * Initializes a lot of variables, setting JavaScript functions in header etc.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;

			// Main GPvars:
		$this->pointer = t3lib_div::_GP('pointer');
		$this->bparams = t3lib_div::_GP('bparams');
		$this->P = t3lib_div::_GP('P');
		$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		$this->PM = t3lib_div::_GP('PM');

			// Find "mode"
		$this->mode=t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode='rte';
		}


		//init hookObjectArray:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['browseLinksHook'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['browseLinksHook'] as $_classData) {
						$_procObj = & t3lib_div::getUserObj($_classData);
						$_procObj->init($this,array());
						$this->hookObjectsArr[]=$_procObj;
					}
		}
			// Site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');	// Current site url

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

			// CurrentUrl - the current link url must be passed around if it exists
		if ($this->mode=='wizard')	{
			$currentLinkParts = t3lib_div::trimExplode(' ',$this->P['currentValue']);
			$this->curUrlArray = array(
				'target' => $currentLinkParts[1]
			);
			$this->curUrlInfo=$this->parseCurUrl($this->siteURL.'?id='.$currentLinkParts[0],$this->siteURL);
		} else {
			$this->curUrlArray = t3lib_div::_GP('curUrl');
			if ($this->curUrlArray['all'])	{
				$this->curUrlArray=t3lib_div::get_tag_attributes($this->curUrlArray['all']);
			}
			$this->curUrlInfo=$this->parseCurUrl($this->curUrlArray['href'],$this->siteURL);
		}

			// Determine nature of current url:
		$this->act=t3lib_div::_GP('act');
		if (!$this->act)	{
			$this->act=$this->curUrlInfo['act'];
		}

			// Rich Text Editor specific configuration:
		$addPassOnParams='';
		if ((string)$this->mode=='rte')	{
			$RTEtsConfigParts = explode(':',$this->RTEtsConfigParams);
			$addPassOnParams.='&RTEtsConfigParams='.rawurlencode($this->RTEtsConfigParams);
			$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		}

			// Initializing the target value (RTE)
		$this->setTarget = $this->curUrlArray['target'];
		if ($this->thisConfig['defaultLinkTarget'] && !isset($this->curUrlArray['target']))	{
			$this->setTarget=$this->thisConfig['defaultLinkTarget'];
		}

			// Initializing the title value (RTE)
		$this->setTitle = $this->curUrlArray['title'];



			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;

			// BEGIN accumulation of header JavaScript:
		$JScode = '';
		$JScode.= '
				// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
			var add_href="'.($this->curUrlArray['href']?'&curUrl[href]='.rawurlencode($this->curUrlArray['href']):'').'";
			var add_target="'.($this->setTarget?'&curUrl[target]='.rawurlencode($this->setTarget):'').'";
			var add_title="'.($this->setTitle?'&curUrl[title]='.rawurlencode($this->setTitle):'').'";
			var add_params="'.($this->bparams?'&bparams='.rawurlencode($this->bparams):'').'";

			var cur_href="'.($this->curUrlArray['href']?$this->curUrlArray['href']:'').'";
			var cur_target="'.($this->setTarget?$this->setTarget:'').'";
			var cur_title="'.($this->setTitle?$this->setTitle:'').'";

			function setTarget(target)	{	//
				cur_target=target;
				add_target="&curUrl[target]="+escape(target);
			}
			function setTitle(title)	{	//
				cur_title=title;
				add_title="&curUrl[title]="+escape(title);
			}
			function setValue(value)	{	//
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
		';


		if ($this->mode=='wizard')	{	// Functions used, if the link selector is in wizard mode (= TCEforms fields)
			unset($this->P['fieldChangeFunc']['alert']);
			reset($this->P['fieldChangeFunc']);
			$update='';
			while(list($k,$v)=each($this->P['fieldChangeFunc']))	{
				$update.= '
				window.opener.'.$v;
			}

			$P2=array();
			$P2['itemName']=$this->P['itemName'];
			$P2['formName']=$this->P['formName'];
			$P2['fieldChangeFunc']=$this->P['fieldChangeFunc'];
			$addPassOnParams.=t3lib_div::implodeArrayForUrl('P',$P2);

			$JScode.='
				function link_typo3Page(id,anchor)	{	//
					updateValueInMainForm(id+(anchor?anchor:"")+" "+cur_target);
					close();
					return false;
				}
				function link_folder(folder)	{	//
					updateValueInMainForm(folder+" "+cur_target);
					close();
					return false;
				}
				function link_current()	{	//
					if (cur_href!="http://" && cur_href!="mailto:")	{
						var setValue = cur_href+" "+cur_target+" "+cur_title;
						if (setValue.substr(0,7)=="http://")	setValue = setValue.substr(7);
						if (setValue.substr(0,7)=="mailto:")	setValue = setValue.substr(7);
						updateValueInMainForm(setValue);
						close();
					}
					return false;
				}
				function checkReference()	{	//
					if (window.opener && window.opener.document && window.opener.document.'.$this->P['formName'].' && window.opener.document.'.$this->P['formName'].'["'.$this->P['itemName'].'"] )	{
						return window.opener.document.'.$this->P['formName'].'["'.$this->P['itemName'].'"];
					} else {
						close();
					}
				}
				function updateValueInMainForm(input)	{	//
					var field = checkReference();
					if (field)	{
						field.value = input;
						'.$update.'
					}
				}
			';
		} else {	// Functions used, if the link selector is in RTE mode:
			$JScode.='
				function link_typo3Page(id,anchor)	{	//
					var theLink = \''.$this->siteURL.'?id=\'+id+(anchor?anchor:"");
					self.parent.parent.renderPopup_addLink(theLink,cur_target,cur_title);
					return false;
				}
				function link_folder(folder)	{	//
					var theLink = \''.$this->siteURL.'\'+folder;
					self.parent.parent.renderPopup_addLink(theLink,cur_target,cur_title);
					return false;
				}
				function link_spec(theLink)	{	//
					self.parent.parent.renderPopup_addLink(theLink,cur_target,cur_title);
					return false;
				}
				function link_current()	{	//
					if (cur_href!="http://" && cur_href!="mailto:")	{
						self.parent.parent.renderPopup_addLink(cur_href,cur_target,cur_title);
					}
					return false;
				}
			';
		}

			// General "jumpToUrl" function:
		$JScode.='
			function jumpToUrl(URL,anchor)	{	//
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode='.$this->mode.'" : "";
				var theLocation = URL+add_act+add_mode+add_href+add_target+add_title+add_params'.($addPassOnParams?'+"'.$addPassOnParams.'"':'').'+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
		';


		/**
		 * Splits parts of $this->bparams
		 * @see $bparams
		 */
		$pArr = explode('|',$this->bparams);

			// This is JavaScript especially for the TBE Element Browser!
		$formFieldName = 'data['.$pArr[0].']['.$pArr[1].']['.$pArr[2].']';

			// insertElement - Call check function (e.g. for uniqueness handling):
		if ($pArr[4] && $pArr[5]) {
			$JScodeCheck = '
					// Call a check function in the opener window (e.g. for uniqueness handling):
				if (parent.window.opener) {
					var res = parent.window.opener.'.$pArr[5].'("'.addslashes($pArr[4]).'",table,uid,type);
					if (!res.passed) {
						if (res.message) alert(res.message);
						performAction = false;
					}
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			';
		}
			// insertElement - Call helper function:
		if ($pArr[4] && $pArr[6]) {
			$JScodeHelper = '
						// Call helper function to manage data in the opener window:
					if (parent.window.opener) {
						parent.window.opener.'.$pArr[6].'("'.addslashes($pArr[4]).'",table,uid,type,"'.addslashes($pArr[0]).'");
					} else {
						alert("Error - reference to main window is not set properly!");
						parent.close();
					}
			';
		}
			// insertElement - perform action commands:
		if ($pArr[4] && $pArr[7]) {
				// Call user defined action function:
			$JScodeAction = '
					if (parent.window.opener) {
						parent.window.opener.'.$pArr[7].'("'.addslashes($pArr[4]).'",table,uid,type);
						focusOpenerAndClose(close);
					} else {
						alert("Error - reference to main window is not set properly!");
						parent.close();
					}
			';
		} else if ($pArr[0] && !$pArr[1] && !$pArr[2]) {
			$JScodeAction = '
					addElement(filename,table+"_"+uid,fp,close);
			';
		} else {
			$JScodeAction = '
					if (setReferences()) {
						parent.window.opener.group_change("add","'.$pArr[0].'","'.$pArr[1].'","'.$pArr[2].'",elRef,targetDoc);
					} else {
						alert("Error - reference to main window is not set properly!");
					}
					focusOpenerAndClose(close);
			';
		}

		$JScode.='
			var elRef="";
			var targetDoc="";

			function launchView(url)	{	//
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.$BACK_PATH.'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function setReferences()	{	//
				if (parent.window.opener
				&& parent.window.opener.content
				&& parent.window.opener.content.document.editform
				&& parent.window.opener.content.document.editform["'.$formFieldName.'"]
						) {
					targetDoc = parent.window.opener.content.document;
					elRef = targetDoc.editform["'.$formFieldName.'"];
					return true;
				} else {
					return false;
				}
			}
			function insertElement(table, uid, type, filename,fp,filetype,imagefile,action, close)	{	//
				var performAction = true;
				'.$JScodeCheck.'
					// Call performing function and finish this action:
				if (performAction) {
						'.$JScodeHelper.$JScodeAction.'
				}
				return false;
			}
			function addElement(elName,elValue,altElValue,close)	{	//
				if (parent.window.opener && parent.window.opener.setFormValueFromBrowseWin)	{
					parent.window.opener.setFormValueFromBrowseWin("'.$pArr[0].'",altElValue?altElValue:elValue,elName);
					focusOpenerAndClose(close);
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			}
			function focusOpenerAndClose(close)	{	//
				if (close)	{
					parent.window.opener.focus();
					parent.close();
				}
			}
		';

			// Finally, add the accumulated JavaScript to the template object:
		$this->doc->JScode = $this->doc->wrapScriptTags($JScode);

			// Debugging:
		if (FALSE) debug(array(
			'pointer' => $this->pointer,
			'act' => $this->act,
			'mode' => $this->mode,
			'curUrlInfo' => $this->curUrlInfo,
			'curUrlArray' => $this->curUrlArray,
			'P' => $this->P,
			'bparams' => $this->bparams,
			'RTEtsConfigParams' => $this->RTEtsConfigParams,
			'expandPage' => $this->expandPage,
			'expandFolder' => $this->expandFolder,
			'PM' => $this->PM,
		),'Internal variables of Script Class:');
	}

	/**
	 * Rich Text Editor (RTE) link selector (MAIN function)
	 * Generates the link selector for the Rich Text Editor.
	 * Can also be used to select links for the TCEforms (see $wiz)
	 *
	 * @param	boolean		If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return	string		Modified content variable.
	 */
	function main_rte($wiz=0)	{
		global $LANG, $BACK_PATH;

			// Starting content:
		$content=$this->doc->startPage('RTE link');

			// Initializing the action value, possibly removing blinded values etc:
		$allowedItems = array_diff(explode(',','page,file,url,mail,spec'),t3lib_div::trimExplode(',',$this->thisConfig['blindLinkOptions'],1));
		//call hook for extra options
		foreach ($this->hookObjectsArr as $_hookObj) {
			$allowedItems=$_hookObj->addAllowedItems($allowedItems);
		}
		reset($allowedItems);
		if (!in_array($this->act,$allowedItems))	$this->act = current($allowedItems);

			// Making menu in top:
		$menuDef = array();
		if (!$wiz)	{
			$menuDef['removeLink']['isActive'] = $this->act=='removeLink';
			$menuDef['removeLink']['label'] = $LANG->getLL('removeLink',1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="self.parent.parent.renderPopup_unLink();return false;"';
		}
		if (in_array('page',$allowedItems)) {
			$menuDef['page']['isActive'] = $this->act=='page';
			$menuDef['page']['label'] = $LANG->getLL('page',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\'?act=page\');return false;"';
		}
		if (in_array('file',$allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='file';
			$menuDef['file']['label'] = $LANG->getLL('file',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\'?act=file\');return false;"';
		}
		if (in_array('url',$allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='url';
			$menuDef['url']['label'] = $LANG->getLL('extUrl',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\'?act=url\');return false;"';
		}
		if (in_array('mail',$allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='mail';
			$menuDef['mail']['label'] = $LANG->getLL('email',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\'?act=mail\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec',$allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act=='spec';
			$menuDef['spec']['label'] = $LANG->getLL('special',1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\'?act=spec\');return false;"';
		}
		//call hook for extra options
		foreach ($this->hookObjectsArr as $_hookObj) {
			$menuDef=$_hookObj->modifyMenuDefinition($menuDef);
		}
		$content .= $this->doc->getTabMenuRaw($menuDef);

			// Adding the menu and header to the top of page:
		$content.=$this->printCurrentUrl($this->curUrlInfo['info']).'<br />';


			// Depending on the current action we will create the actual module content for selecting a link:
		switch($this->act)	{
			case 'mail':
				$extUrl='

			<!--
				Enter mail address:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
							<tr>
								<td>'.$GLOBALS['LANG']->getLL('emailAddress',1).':</td>
								<td><input type="text" name="lemail"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='mail'?$this->curUrlInfo['info']:'').'" /> '.
									'<input type="submit" value="'.$GLOBALS['LANG']->getLL('setLink',1).'" onclick="setTarget(\'\');setValue(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
			break;
			case 'url':
				$extUrl='

			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td>URL:</td>
								<td><input type="text" name="lurl"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='url'?$this->curUrlInfo['info']:'http://').'" /> '.
									'<input type="submit" value="'.$GLOBALS['LANG']->getLL('setLink',1).'" onclick="setValue(document.lurlform.lurl.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
			break;
			case 'file':
				$foldertree = t3lib_div::makeInstance('rteFolderTree');
				$foldertree->thisScript = $this->thisScript;
				$tree=$foldertree->getBrowsableTree();

				if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act']!='file')	{
					$cmpPath='';
				} elseif (substr(trim($this->curUrlInfo['info']),-1)!='/')	{
					$cmpPath=PATH_site.dirname($this->curUrlInfo['info']).'/';
					if (!isset($this->expandFolder))			$this->expandFolder = $cmpPath;
				} else {
					$cmpPath=PATH_site.$this->curUrlInfo['info'];
				}

				list(,,$specUid) = explode('_',$this->PM);
				$files = $this->expandFolder($foldertree->specUIDmap[$specUid]);

				$content.= '

			<!--
				Wrapper table for folder tree / file list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('folderTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$files.'</td>
						</tr>
					</table>
					';
			break;
			case 'spec':
				if (is_array($this->thisConfig['userLinks.']))	{
					$subcats=array();
					$v=$this->thisConfig['userLinks.'];
					reset($v);
					while(list($k2)=each($v))	{
						$k2i = intval($k2);
						if (substr($k2,-1)=='.' && is_array($v[$k2i.'.']))	{

								// Title:
							$title = trim($v[$k2i]);
							if (!$title)	{
								$title=$v[$k2i.'.']['url'];
							} else {
								$title=$LANG->sL($title);
							}
								// Description:
							$description=$v[$k2i.'.']['description'] ? $LANG->sL($v[$k2i.'.']['description'],1).'<br />' : '';

								// URL + onclick event:
							$onClickEvent='';
							if (isset($v[$k2i.'.']['target']))	$onClickEvent.="setTarget('".$v[$k2i.'.']['target']."');";
							$v[$k2i.'.']['url'] = str_replace('###_URL###',$this->siteURL,$v[$k2i.'.']['url']);
							if (substr($v[$k2i.'.']['url'],0,7)=='http://' || substr($v[$k2i.'.']['url'],0,7)=='mailto:')	{
								$onClickEvent.="cur_href=unescape('".rawurlencode($v[$k2i.'.']['url'])."');link_current();";
							} else {
								$onClickEvent.="link_spec(unescape('".$this->siteURL.rawurlencode($v[$k2i.'.']['url'])."'));";
							}

								// Link:
							$A=array('<a href="#" onclick="'.htmlspecialchars($onClickEvent).'return false;">','</a>');

								// Adding link to menu of user defined links:
							$subcats[$k2i]='
								<tr>
									<td class="bgColor4">'.$A[0].'<strong>'.htmlspecialchars($title).($this->curUrlInfo['info']==$v[$k2i.'.']['url']?'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" />':'').'</strong><br />'.$description.$A[1].'</td>
								</tr>';
						}
					}

						// Sort by keys:
					ksort($subcats);

						// Add menu to content:
					$content.= '

			<!--
				Special userdefined menu:
			-->
						<table border="0" cellpadding="1" cellspacing="1" id="typo3-linkSpecial">
							<tr>
								<td class="bgColor5" class="c-wCell" valign="top"><strong>'.$LANG->getLL('special',1).'</strong></td>
							</tr>
							'.implode('',$subcats).'
						</table>
						';
				}
			break;
			case 'page':
				$pagetree = t3lib_div::makeInstance('rtePageTree');
				$pagetree->thisScript = $this->thisScript;
				$tree=$pagetree->getBrowsableTree();
				$cElements = $this->expandPage();
				$content.= '

			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('pageTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$cElements.'</td>
						</tr>
					</table>
					';
			break;
			//call hook
			default:
				foreach ($this->hookObjectsArr as $_hookObj) {
					$content.=$_hookObj->getTab($this->act);
				}

			break;
		}

		$content .= '



			<!--
				Selecting title for link:
			-->
				<form action="" name="ltitleform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('title',1).'</td>
							<td><input type="text" name="ltitle" onchange="setTitle(this.value);" value="'.htmlspecialchars($this->setTitle).'"'.$this->doc->formWidth(10).' /></td>
							<td><input type="submit" value="'.$GLOBALS['LANG']->getLL('update',1).'" onclick="return link_current();" /></td>
						</tr>
					</table>
				</form>
';

			// Target:
		if ($this->act!='mail')	{
			$ltarget='



			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('target',1).':</td>
							<td><input type="text" name="ltarget" onchange="setTarget(this.value);" value="'.htmlspecialchars($this->setTarget).'"'.$this->doc->formWidth(10).' /></td>
							<td>
								<select name="ltarget_type" onchange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
									<option></option>
									<option value="_top">'.$GLOBALS['LANG']->getLL('top',1).'</option>
									<option value="_blank">'.$GLOBALS['LANG']->getLL('newWindow',1).'</option>
								</select>
							</td>
							<td>';

			if (($this->curUrlInfo['act']=="page" || $this->curUrlInfo['act']=='file') && $this->curUrlArray['href'])	{
				$ltarget.='
							<input type="submit" value="'.$GLOBALS['LANG']->getLL('update',1).'" onclick="return link_current();" />';
			}

			$selectJS = '
				if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0)	{
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+"x"+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					setTarget(document.ltargetform.ltarget.value);
          			setTitle(document.ltitleform.ltitle.value);
					document.ltargetform.popup_width.selectedIndex=0;
					document.ltargetform.popup_height.selectedIndex=0;
				}
			';

			$ltarget.='		</td>
						</tr>
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('target_popUpWindow',1).':</td>
							<td colspan="3">
								<select name="popup_width" onchange="'.htmlspecialchars($selectJS).'">
									<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_width',1).'</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
									<option value="700">700</option>
									<option value="800">800</option>
								</select>
								x
								<select name="popup_height" onchange="'.htmlspecialchars($selectJS).'">
									<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_height',1).'</option>
									<option value="200">200</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
								</select>
							</td>
						</tr>
					</table>
				</form>';

				// Add "target selector" box to content:
			$content.=$ltarget;

				// Add some space
			$content.='<br /><br />';
		}

			// End page, return content:
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}


	/**
	 * For RTE/link: Parses the incoming URL and determines if it's a page, file, external or mail address.
	 *
	 * @param	string		HREF value tp analyse
	 * @param	string		The URL of the current website (frontend)
	 * @return	array		Array with URL information stored in assoc. keys: value, act (page, file, spec, mail), pageid, cElement, info
	 */
	function parseCurUrl($href,$siteUrl)	{
		$href = trim($href);
		if ($href)	{
			$info=array();

				// Default is "url":
			$info['value']=$href;
			$info['act']='url';

			$specialParts = explode('#_SPECIAL',$href);
			if (count($specialParts)==2)	{	// Special kind (Something RTE specific: User configurable links through: "userLinks." from ->thisConfig)
				$info['value']='#_SPECIAL'.$specialParts[1];
				$info['act']='spec';
			} elseif (t3lib_div::isFirstPartOfStr($href,$siteUrl))	{	// If URL is on the current frontend website:
				$rel = substr($href,strlen($siteUrl));
				if (@file_exists(PATH_site.rawurldecode($rel)))	{	// URL is a file, which exists:
					$info['value']=rawurldecode($rel);
					$info['act']='file';
				} else {	// URL is a page (id parameter)
					$uP=@parse_url($rel);
					if (!trim($uP['path']))	{
						$pp = explode('id=',$uP['query']);
						$id = $pp[1];
						if ($id)	{
								// Checking if the id-parameter is an alias.
							if (!t3lib_div::testInt($id))	{
								list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$id);
								$id=intval($idPartR['uid']);
							}

							$pageRow = t3lib_BEfunc::getRecordWSOL('pages',$id);
							$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
							$info['value']=$GLOBALS['LANG']->getLL('page',1)." '".htmlspecialchars(t3lib_div::fixed_lgd_cs($pageRow['title'],$titleLen))."' (ID:".$id.($uP['fragment']?', #'.$uP['fragment']:'').')';
							$info['pageid']=$id;
							$info['cElement']=$uP['fragment'];
							$info['act']='page';
						}
					}
				}
			} else {	// Email link:
				if (strtolower(substr($href,0,7))=='mailto:')	{
					$info['value']=trim(substr($href,7));
					$info['act']='mail';
				}
				else {

				}
			}
			//let the hook have a look
					foreach ($this->hookObjectsArr as $_hookObj) {
						$info=$_hookObj->parseCurrentUrl($href,$siteUrl,$info);
					}
			$info['info'] = $info['value'];
		} else {	// NO value inputted:
			$info=array();
			$info['info']=$GLOBALS['LANG']->getLL('none');
			$info['value']='';
			$info['act']='page';
		}
		return $info;
	}





}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/patch/class.ux_browse_links.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/patch/class.ux_browse_links.php']);
}
?>