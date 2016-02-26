<?php
namespace Cobweb\Linkhandler;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class called by the signal slot dispatcher in the SoftReferenceIndex.
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
     * Updates the soft reference information.
     *
     * @param bool $linkHandlerFound Set this to TRUE in the returning array to tell the parent class that we succeeded.
     * @param array $typolinkProperties TypoLink properties.
     * @param string $content The content to process.
     * @param array $elements Reference to the array of elements to be modified with substitution / information entries.
     * @param string $index Index value of the found element - user to make unique but stable tokenID
     * @param string $tokenID Unique identifier for a link to a record
     * @param \TYPO3\CMS\Core\Database\SoftReferenceIndex $softReferenceIndex
     * @return array
     */
    public function setTypoLinkPartsElement(
            $linkHandlerFound,
            $typolinkProperties,
            $content,
            $elements,
            $index,
            $tokenID,
            $softReferenceIndex
    ) {

        if ($typolinkProperties['LINK_TYPE'] === 'linkhandler') {

            $content = $this->setTypoLinkPartsElementForLinkhandler($typolinkProperties['url'], $elements, $index, $tokenID, $content);
            $linkHandlerFound = true;
        }

        return array($linkHandlerFound, $typolinkProperties, $content, $elements, $index, $tokenID, $softReferenceIndex);
    }

    /**
     * Generates the SoftReferenceIndex data.
     *
     * @param string $url Raw URL pointing to a DB record (format will be "record:key:table:uid"
     * @param string $content The content to process.
     * @param array $elements Reference to the array of elements to be modified with substitution / information entries.
     * @param string $index Index value of the found element - user to make unique but stable tokenID
     * @param string $tokenID Unique identifyer for a link of an record
     * @return string
     */
    protected function setTypoLinkPartsElementForLinkhandler($url, &$elements, $index, $tokenID, $content)
    {

        $referenceParts = explode(':', $url);
        $elements[$tokenID . ':' . $index]['subst'] = array(
                'type' => 'db',
                'recordRef' => $referenceParts[2] . ':' . $referenceParts[3],
                'tokenID' => $tokenID,
                'tokenValue' => $content
        );

        return '{softref:' . $tokenID . '}';
    }
}