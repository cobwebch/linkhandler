<?php
namespace Aoe\Linkhandler\Browser;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
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
 * Extends the default page tree with the possibility to set an active
 * rootline.
 */
class PageTree extends \TBE_PageTree {

	/**
	 * Expands all mount points in the tree to the given PID
	 *
	 * @param int $pid
	 */
	public function expandToPage($pid) {

		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pid)) {
			return;
		}

		$this->initializePositionSaving();

		$rootLine = new \TYPO3\CMS\Core\Utility\RootlineUtility($pid);
		$rootLine = $rootLine->get();
		array_shift($rootLine);

		foreach ($this->MOUNTS as $mountIndex => $mountRootPageUid) {

			foreach ($rootLine as $pageData) {

				$this->stored[$mountIndex][$pageData['uid']] = 1;
			}
		}

		$this->savePosition();
	}

}