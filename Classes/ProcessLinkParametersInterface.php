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

/**
 * This interface must be implemented by any class wanting to use the
 * $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'] hook.
 *
 * @package Cobweb\Linkhandler
 */
interface ProcessLinkParametersInterface
{
    /**
     * @param \Cobweb\Linkhandler\TypolinkHandler $linkHandler Back-reference to the calling object
     * @return void
     */
    public function process($linkHandler);
}