<?php
namespace Aoe\Linkhandler\Tests\Unit\Utility;

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
 * Unit tests for the legacy utility.
 */
class LegacyUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{

    /**
     * @test
     * @dataProvider externalLinkFixIsImplementedMatchesCorrectVersionsDataProvider
     */
    public function externalLinkFixIsImplementedMatchesCorrectVersions($version, $result)
    {

        $legacyUtilityMock = $this->getMock('Aoe\\Linkhandler\\Utility\\LegacyUtility',
                array('getNumericVersionNumber'));

        $legacyUtilityMock->staticExpects($this->at(0))
                ->method('getNumericVersionNumber')
                ->will($this->returnValue($version));

        $fixImplemented = $legacyUtilityMock::externalLinkFixIsImplemented();
        $this->assertEquals($result, $fixImplemented);
    }

    /**
     * Data provider for the externalLinkFixIsImplementedMatchesCorrectVersions test.
     *
     * @return array
     */
    public function externalLinkFixIsImplementedMatchesCorrectVersionsDataProvider()
    {
        return array(
                'Version 4.9' => array('4.9.0', false),
                'Version 6.1.9' => array('6.1.9', false),
                'Version 6.1.10' => array('6.1.10', true),
                'Version 6.2.1' => array('6.2.1', false),
                'Version 6.2.3' => array('6.2.3', false),
                'Version 6.2.4' => array('6.2.4', true),
        );
    }
}