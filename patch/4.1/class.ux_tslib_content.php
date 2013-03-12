<?php
/*
patch http://bugs.typo3.org/view.php?id=5701

*/
class ux_tslib_cObj extends tslib_cObj {
/**
	 * Implements the "typolink" property of stdWrap (and others)
	 * Basically the input string, $linktext, is (typically) wrapped in a <a>-tag linking to some page, email address, file or URL based on a parameter defined by the configuration array $conf.
	 * This function is best used from internal functions as is. There are some API functions defined after this function which is more suited for general usage in external applications.
	 * Generally the concept "typolink" should be used in your own applications as an API for making links to pages with parameters and more. The reason for this is that you will then automatically make links compatible with all the centralized functions for URL simulation and manipulation of parameters into hashes and more.
	 * For many more details on the parameters and how they are intepreted, please see the link to TSref below.
	 *
	 * @param	string		The string (text) to link
	 * @param	array		TypoScript configuration (see link below)
	 * @return	string		A link-wrapped string.
	 * @see stdWrap(), tslib_pibase::pi_linkTP()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=321&cHash=59bd727a5e
	 */
	function typoLink($linktxt, $conf)	{
		
		$finalTagParts = array();
		$finalTagParts['aTagParams'] = $this->getATagParams($conf);

		$link_param = trim($this->stdWrap($conf['parameter'],$conf['parameter.']));

		$sectionMark = trim($this->stdWrap($conf['section'],$conf['section.']));
		$sectionMark = $sectionMark ? (t3lib_div::testInt($sectionMark)?'#c':'#').$sectionMark : '';
		$initP = '?id='.$GLOBALS['TSFE']->id.'&type='.$GLOBALS['TSFE']->type;
		$this->lastTypoLinkUrl = '';
		$this->lastTypoLinkTarget = '';
		if ($link_param)	{
			$link_paramA = t3lib_div::unQuoteFilenames($link_param,true);

				// Check for link-handler keyword:
			list($linkHandlerKeyword,$linkHandlerValue) = explode(':',trim($link_paramA[0]),2);
			if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][$linkHandlerKeyword] && strcmp($linkHandlerValue, '')) {
				$linkHandlerObj = &t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][$linkHandlerKeyword]);

				if(method_exists($linkHandlerObj, 'main')) {
					return $linkHandlerObj->main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, $this);
				}
			}

			$link_param = trim($link_paramA[0]);	// Link parameter value
			$linkClass = trim($link_paramA[2]);		// Link class
			if ($linkClass=='-')	$linkClass = '';	// The '-' character means 'no class'. Necessary in order to specify a title as fourth parameter without setting the target or class!
			$forceTarget = trim($link_paramA[1]);	// Target value
			$forceTitle = trim($link_paramA[3]);	// Title value
			if ($forceTarget=='-')	$forceTarget = '';	// The '-' character means 'no target'. Necessary in order to specify a class as third parameter without setting the target!
				// Check, if the target is coded as a JS open window link:
			$JSwindowParts = array();
			$JSwindowParams = '';
			$onClick = '';
			if ($forceTarget && ereg('^([0-9]+)x([0-9]+)(:(.*)|.*)$',$forceTarget,$JSwindowParts))	{
					// Take all pre-configured and inserted parameters and compile parameter list, including width+height:
				$JSwindow_tempParamsArr = t3lib_div::trimExplode(',',strtolower($conf['JSwindow_params'].','.$JSwindowParts[4]),1);
				$JSwindow_paramsArr=array();
				foreach($JSwindow_tempParamsArr as $JSv)	{
					list($JSp,$JSv) = explode('=',$JSv);
					$JSwindow_paramsArr[$JSp]=$JSp.'='.$JSv;
				}
					// Add width/height:
				$JSwindow_paramsArr['width']='width='.$JSwindowParts[1];
				$JSwindow_paramsArr['height']='height='.$JSwindowParts[2];
					// Imploding into string:
				$JSwindowParams = implode(',',$JSwindow_paramsArr);
				$forceTarget = '';	// Resetting the target since we will use onClick.
			}

				// Internal target:
			$target = isset($conf['target']) ? $conf['target'] : $GLOBALS['TSFE']->intTarget;
			if ($conf['target.'])	{$target=$this->stdWrap($target, $conf['target.']);}

				// Title tag
			$title = $conf['title'];
			if ($conf['title.'])	{$title=$this->stdWrap($title, $conf['title.']);}

				// Parse URL:
			$pU = parse_url($link_param);

				// Detecting kind of link:
			if(strstr($link_param,'@') && (!$pU['scheme'] || $pU['scheme']=='mailto'))	{		// If it's a mail address:
				$link_param = eregi_replace('^mailto:','',$link_param);
				list($this->lastTypoLinkUrl,$linktxt) = $this->getMailTo($link_param,$linktxt,$initP);
				$finalTagParts['url']=$this->lastTypoLinkUrl;
				$finalTagParts['TYPE']='mailto';
			} else {
				$isLocalFile=0;
				$fileChar=intval(strpos($link_param, '/'));
				$urlChar=intval(strpos($link_param, '.'));

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
					$target = isset($conf['extTarget']) ? $conf['extTarget'] : $GLOBALS['TSFE']->extTarget;
					if ($conf['extTarget.'])	{$target = $this->stdWrap($target, $conf['extTarget.']);}
					if ($forceTarget)	{$target=$forceTarget;}
					if ($linktxt=='') $linktxt = $link_param;
					if (!$pU['scheme'])	{$scheme='http://';} else {$scheme='';}
					if ($GLOBALS['TSFE']->config['config']['jumpurl_enable'])	{
						$this->lastTypoLinkUrl = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->config['mainScript'].$initP.'&jumpurl='.rawurlencode($scheme.$link_param).$GLOBALS['TSFE']->getMethodUrlIdToken;
					} else {
						$this->lastTypoLinkUrl = $scheme.$link_param;
					}
					$this->lastTypoLinkTarget = $target;
					$finalTagParts['url']=$this->lastTypoLinkUrl;
					$finalTagParts['targetParams'] = $target ? ' target="'.$target.'"' : '';
					$finalTagParts['TYPE']='url';
				} elseif ($containsSlash || $isLocalFile)	{	// file (internal)
					$splitLinkParam = explode('?', $link_param);
					if (@file_exists(rawurldecode($splitLinkParam[0])) || $isLocalFile)	{
						if ($linktxt=='') $linktxt = rawurldecode($link_param);
						if ($GLOBALS['TSFE']->config['config']['jumpurl_enable'])	{
							$this->lastTypoLinkUrl = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->config['mainScript'].$initP.'&jumpurl='.rawurlencode($link_param).$GLOBALS['TSFE']->getMethodUrlIdToken;
						} else {
							$this->lastTypoLinkUrl = $GLOBALS['TSFE']->absRefPrefix.$link_param;
						}
						if ($forceTarget)	{$target=$forceTarget;}
						$this->lastTypoLinkTarget = $target;

						$finalTagParts['url'] = $this->lastTypoLinkUrl;
						$finalTagParts['targetParams'] = $target ? ' target="'.$target.'"' : '';
						$finalTagParts['TYPE'] = 'file';
					} else {
						$GLOBALS['TT']->setTSlogMessage("typolink(): File '".$splitLinkParam[0]."' did not exist, so '".$linktxt."' was not linked.",1);
						return $linktxt;
					}
	 			} else {	// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to definition in $TCA!)
					if ($conf['no_cache.'])	$conf['no_cache']=$this->stdWrap($conf['no_cache'], $conf['no_cache.']);
					$link_params_parts=explode('#',$link_param);
					$link_param = trim($link_params_parts[0]);		// Link-data del
					if (!strcmp($link_param,''))	{$link_param=$GLOBALS['TSFE']->id;}	// If no id or alias is given
					if ($link_params_parts[1] && !$sectionMark)	{
						$sectionMark = trim($link_params_parts[1]);
						$sectionMark = (t3lib_div::testInt($sectionMark)?'#c':'#').$sectionMark;
					}
						// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/? pair
					unset($theTypeP);
					$pairParts = t3lib_div::trimExplode(',',$link_param);
					if (count($pairParts)>1)	{
						$link_param = $pairParts[0];
						$theTypeP = $pairParts[1];		// Overruling 'type'
					}
						// Checking if the id-parameter is an alias.
					if (!t3lib_div::testInt($link_param))	{
						$link_param = $GLOBALS['TSFE']->sys_page->getPageIdFromAlias($link_param);
					}

						// Link to page even if access is missing?
					if (strlen($conf['linkAccessRestrictedPages'])) {
						$disableGroupAccessCheck = ($conf['linkAccessRestrictedPages'] ? TRUE : FALSE);
					} else {
						$disableGroupAccessCheck = ($GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages'] ? TRUE : FALSE);
					}

						// Looking up the page record to verify its existence:
					$page = $GLOBALS['TSFE']->sys_page->getPage($link_param,$disableGroupAccessCheck);

					if (count($page))	{
							// MointPoints, look for closest MPvar:
						$MPvarAcc = array();
						if (!$GLOBALS['TSFE']->config['config']['MP_disableTypolinkClosestMPvalue'])	{
							$temp_MP = $this->getClosestMPvalueForPage($page['uid'],TRUE);
							if ($temp_MP)	$MPvarAcc['closest'] = $temp_MP;
						}
							// Look for overlay Mount Point:
						$mount_info = $GLOBALS['TSFE']->sys_page->getMountPointInfo($page['uid'], $page);
						if (is_array($mount_info) && $mount_info['overlay'])	{
							$page = $GLOBALS['TSFE']->sys_page->getPage($mount_info['mount_pid'],$disableGroupAccessCheck);
							if (!count($page))	{
								$GLOBALS['TT']->setTSlogMessage("typolink(): Mount point '".$mount_info['mount_pid']."' was not available, so '".$linktxt."' was not linked.",1);
								return $linktxt;
							}
							$MPvarAcc['re-map'] = $mount_info['MPvar'];
						}

							// Setting title if blank value to link:
						if ($linktxt=='') $linktxt = $page['title'];

							// Query Params:
						$addQueryParams = $conf['addQueryString'] ? $this->getQueryArguments($conf['addQueryString.']) : '';
						$addQueryParams .= trim($this->stdWrap($conf['additionalParams'],$conf['additionalParams.']));
						if (substr($addQueryParams,0,1)!='&')		{
							$addQueryParams = '';
						} elseif ($conf['useCacheHash']) {	// cache hashing:
							$pA = t3lib_div::cHashParams($addQueryParams.$GLOBALS['TSFE']->linkVars);	// Added '.$this->linkVars' dec 2003: The need for adding the linkVars is that they will be included in the link, but not the cHash. Thus the linkVars will always be the problem that prevents the cHash from working. I cannot see what negative implications in terms of incompatibilities this could bring, but for now I hope there are none. So here we go... (- kasper)
							$addQueryParams.= '&cHash='.t3lib_div::shortMD5(serialize($pA));
						}

						$tCR_domain = '';
						// Mount pages are always local and never link to another domain
						if (count($MPvarAcc))	{
							// Add "&MP" var:
							$addQueryParams.= '&MP='.rawurlencode(implode(',',$MPvarAcc));
						}
						elseif ($GLOBALS['TSFE']->config['config']['typolinkCheckRootline'])	{
							// This checks if the linked id is in the rootline of this site and if not it will find the domain for that ID and prefix it:
							$tCR_rootline = $GLOBALS['TSFE']->sys_page->getRootLine($page['uid']);	// Gets rootline of linked-to page
							$tCR_flag = 0;
							foreach ($tCR_rootline as $tCR_data)	{
								if ($tCR_data['uid']==$GLOBALS['TSFE']->tmpl->rootLine[0]['uid'])	{
									$tCR_flag = 1;	// OK, it was in rootline!
									break;
								}
							}
							if (!$tCR_flag)	{
								foreach ($tCR_rootline as $tCR_data)	{
									$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_domain', 'pid='.intval($tCR_data['uid']).' AND redirectTo=\'\''.$this->enableFields('sys_domain'), '', 'sorting');
									if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
										$tCR_domain = preg_replace('/\/$/','',$row['domainName']);
										break;
									}
								}
							}
						}
							// If other domain, overwrite
						if (strlen($tCR_domain))	{
							$target = isset($conf['extTarget']) ? $conf['extTarget'] : $GLOBALS['TSFE']->extTarget;
							if ($conf['extTarget.'])	{$target = $this->stdWrap($target, $conf['extTarget.']);}
							if ($forceTarget)	{$target=$forceTarget;}
							$LD['target'] = $target;
							$this->lastTypoLinkUrl = $this->URLqMark('http://'.$tCR_domain.'/index.php?id='.$page['uid'],$addQueryParams).$sectionMark;
						} else {	// Internal link:
							if ($forceTarget)	{$target=$forceTarget;}
							$LD = $GLOBALS['TSFE']->tmpl->linkData($page,$target,$conf['no_cache'],'','',$addQueryParams,$theTypeP);
							$this->lastTypoLinkUrl = $this->URLqMark($LD['totalURL'],'').$sectionMark;
						}

						$this->lastTypoLinkTarget = $LD['target'];
						$targetPart = $LD['target'] ? ' target="'.$LD['target'].'"' : '';

							// If sectionMark is set, there is no baseURL AND the current page is the page the link is to, check if there are any additional parameters and is not, drop the url.
						if ($sectionMark && !trim($addQueryParams) && $page['uid']==$GLOBALS['TSFE']->id && !$GLOBALS['TSFE']->config['config']['baseURL'])	{
							list(,$URLparams) = explode('?',$this->lastTypoLinkUrl);
							list($URLparams) = explode('#',$URLparams);
							parse_str ($URLparams.$LD['orig_type'], $URLparamsArray);
							if (intval($URLparamsArray['type'])==$GLOBALS['TSFE']->type)	{	// type nums must match as well as page ids
								unset($URLparamsArray['id']);
								unset($URLparamsArray['type']);
								if (!count($URLparamsArray))	{	// If there are no parameters left.... set the new url.
									$this->lastTypoLinkUrl = $sectionMark;
								}
							}
						}

							// If link is to a access restricted page which should be redirected, then find new URL:
						if ($GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages'] &&
								$GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages']!=='NONE' &&
								!$GLOBALS['TSFE']->checkPageGroupAccess($page))	{
									$thePage = $GLOBALS['TSFE']->sys_page->getPage($GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages']);

									$addParams = $GLOBALS['TSFE']->config['config']['typolinkLinkAccessRestrictedPages_addParams'];
									$addParams = str_replace('###RETURN_URL###',rawurlencode($this->lastTypoLinkUrl),$addParams);
									$addParams = str_replace('###PAGE_ID###',$page['uid'],$addParams);
									$LD = $GLOBALS['TSFE']->tmpl->linkData($thePage,$target,'','','',$addParams,$theTypeP);
									$this->lastTypoLinkUrl = $this->URLqMark($LD['totalURL'],'');
						}

							// Rendering the tag.
						$finalTagParts['url']=$this->lastTypoLinkUrl;
						$finalTagParts['targetParams']=$targetPart;
						$finalTagParts['TYPE']='page';
					} else {
						$GLOBALS['TT']->setTSlogMessage("typolink(): Page id '".$link_param."' was not found, so '".$linktxt."' was not linked.",1);
						return $linktxt;
					}
				}
			}

			if ($forceTitle)	{$title=$forceTitle;}

			if ($JSwindowParams)	{

					// Create TARGET-attribute only if the right doctype is used
				if (!t3lib_div::inList('xhtml_strict,xhtml_11,xhtml_2', $GLOBALS['TSFE']->xhtmlDoctype))	{
					$target = ' target="FEopenLink"';
				} else {
					$target = '';
				}

				$onClick="vHWin=window.open('".$GLOBALS['TSFE']->baseUrlWrap($finalTagParts['url'])."','FEopenLink','".$JSwindowParams."');vHWin.focus();return false;";
				$res = '<a href="'.htmlspecialchars($finalTagParts['url']).'"'. $target .' onclick="'.htmlspecialchars($onClick).'"'.($title?' title="'.$title.'"':'').($linkClass?' class="'.$linkClass.'"':'').$finalTagParts['aTagParams'].'>';
			} else {
				if ($GLOBALS['TSFE']->spamProtectEmailAddresses === 'ascii' && $finalTagParts['TYPE'] === 'mailto') {
					$res = '<a href="'.$finalTagParts['url'].'"'.($title?' title="'.$title.'"':'').$finalTagParts['targetParams'].($linkClass?' class="'.$linkClass.'"':'').$finalTagParts['aTagParams'].'>';
				} else {
					$res = '<a href="'.htmlspecialchars($finalTagParts['url']).'"'.($title?' title="'.$title.'"':'').$finalTagParts['targetParams'].($linkClass?' class="'.$linkClass.'"':'').$finalTagParts['aTagParams'].'>';
				}
			}

				// Call user function:
			if ($conf['userFunc'])	{
				$finalTagParts['TAG']=$res;
				$res = $this->callUserFunction($conf['userFunc'],$conf['userFunc.'],$finalTagParts);
			}

				// If flag "returnLastTypoLinkUrl" set, then just return the latest URL made:
			if ($conf['returnLast'])	{
				switch($conf['returnLast'])	{
					case 'url':
						return $this->lastTypoLinkUrl;
					break;
					case 'target':
						return $this->lastTypoLinkTarget;
					break;
				}
			}

			if ($conf['ATagBeforeWrap'])	{
				return $res.$this->wrap($linktxt, $conf['wrap']).'</a>';
			} else {
				return $this->wrap($res.$linktxt.'</a>', $conf['wrap']);
			}
		} else {
			return $linktxt;
		}
	}
	

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/patch/class.ux_tslib_content.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkhandler/patch/class.ux_tslib_content.php']);
}
?>