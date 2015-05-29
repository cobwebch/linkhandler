<?php
namespace Aoe\Linkhandler\Utility;

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
 * Utility functions concerning legacy code.
 */
class LegacyUtility {

	/**
	 * Returns TRUE when the external link fix is implemented in the
	 * current TYPO3 version.
	 *
	 * @return bool
	 */
	public static function externalLinkFixIsImplemented() {

		$numericVersionNumber = static::getNumericVersionNumber();

		return !(version_compare($numericVersionNumber, '6.1.10', '<')
			|| (
				version_compare($numericVersionNumber, '6.2.0', '>=')
				&& version_compare($numericVersionNumber, '6.2.4', '<')
			));
	}

	/**
	 * Returns the numeric TYPO3 version number. Required for unit testing.
	 * 
	 * @return string
	 */
	protected static function getNumericVersionNumber() {
		return \TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version();
	}
} 