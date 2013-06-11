<?php
/**
 * Created by JetBrains PhpStorm.
 * User: astehlik
 * Date: 10.06.13
 * Time: 14:16
 * To change this template use File | Settings | File Templates.
 */

namespace Aoe\Linkhandler\Browser;

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