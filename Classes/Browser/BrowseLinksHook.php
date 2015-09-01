<?php
namespace Aoe\Linkhandler\Browser;

/*                                                                        *
 * This script belongs to the TYPO3 extension "linkhandler".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Hook to adjust linkbrowser
 */
class BrowseLinksHook {

	/**
	 * Adds JS code
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Rtehtmlarea\BrowseLinks $parent
	 *
	 * @return string
	 */
	public function extendJScode($params, $parent) {
		$code = '
			function link_spec(theLink) {
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				browse_links_setAdditionalValue("data-htmlarea-external", "");
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
		';

		return $code;
	}

}