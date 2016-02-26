<?php
namespace Aoe\Linkhandler;

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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class will be called by the signal slot dispatcher in the SoftReferenceIndex.
 *
 * @author Michael Klapper <klapper@aoemedia.de>>
 * @author Alexander Stehlik <astehlik.deleteme@intera.de>
 */
class SoftReferenceHandler
{

    /**
     * Will be called by the SoftReferenceIndex signal slot for getting the TypoLink parts.
     *
     * @param bool $linkHandlerFound Return with value TRUE to
     * @param array $finalTagParts
     * @param string $linkHandlerKeyword
     * @param string $linkHandlerValue
     * @return array
     */
    public function getTypoLinkParts($linkHandlerFound, $finalTagParts, $linkHandlerKeyword, $linkHandlerValue)
    {

        if ($linkHandlerKeyword === 'record') {
            $finalTagParts['LINK_TYPE'] = 'linkhandler';
            $finalTagParts['url'] = trim($linkHandlerKeyword . ':' . $linkHandlerValue);
            $linkHandlerFound = true;
        }

        return array($linkHandlerFound, $finalTagParts, $linkHandlerKeyword, $linkHandlerValue);
    }

    /**
     * Will be called by the SoftReferenceIndex signal slot for updating the given SoftReference information.
     *
     * @param bool $linkHandlerFound Set this to TRUE in the returning array to tell the parent class that we succeeded.
     * @param array $tLP TypoLink properties.
     * @param string $content The content to process.
     * @param array $elements Reference to the array of elements to be modified with substitution / information entries.
     * @param string $idx Index value of the found element - user to make unique but stable tokenID
     * @param string $tokenID Unique identifyer for a link of an record
     * @param \TYPO3\CMS\Core\Database\SoftReferenceIndex $softReferenceIndex
     * @return array
     */
    public function setTypoLinkPartsElement(
            $linkHandlerFound,
            $tLP,
            $content,
            $elements,
            $idx,
            $tokenID,
            $softReferenceIndex
    ) {

        if ($tLP['LINK_TYPE'] === 'linkhandler') {

            $linkInfo = $this->getTabHandlerFactory()->getLinkInfoArrayFromMatchingHandler($tLP['url']);

            if (count($linkInfo)) {
                $content = $this->setTypoLinkPartsElementForLinkhandler($linkInfo, $elements, $idx, $tokenID, $content);
                $linkHandlerFound = true;
            }
        }

        return array($linkHandlerFound, $tLP, $content, $elements, $idx, $tokenID, $softReferenceIndex);
    }

    /**
     * @return \Aoe\Linkhandler\Browser\TabHandlerFactory
     */
    protected function getTabHandlerFactory()
    {
        return GeneralUtility::makeInstance('Aoe\\Linkhandler\\Browser\\TabHandlerFactory');
    }

    /**
     * Generates the SoftReferenceIndex data.
     *
     * @param array $linkInfo Link information provied by the matching tab handler.
     * @param string $content The content to process.
     * @param array $elements Reference to the array of elements to be modified with substitution / information entries.
     * @param string $idx Index value of the found element - user to make unique but stable tokenID
     * @param string $tokenID Unique identifyer for a link of an record
     * @return string
     */
    protected function setTypoLinkPartsElementForLinkhandler($linkInfo, &$elements, $idx, $tokenID, $content)
    {

        $elements[$tokenID . ':' . $idx]['subst'] = array(
                'type' => 'db',
                'recordRef' => $linkInfo['recordTable'] . ':' . $linkInfo['recordUid'],
                'tokenID' => $tokenID,
                'tokenValue' => $content
        );

        return '{softref:' . $tokenID . '}';
    }
}