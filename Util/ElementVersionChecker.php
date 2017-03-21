<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Util;

use Phlexible\Bundle\ElementBundle\ElementService;
use Phlexible\Bundle\ElementBundle\Entity\Element;
use Phlexible\Bundle\ElementtypeBundle\Model\Elementtype;
use Phlexible\Bundle\TeaserBundle\Model\TeaserManagerInterface;
use Phlexible\Bundle\TreeBundle\ContentTree\ContentTreeManagerInterface;

/**
 * Utility class for element versions.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ElementVersionChecker
{
    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @var ContentTreeManagerInterface
     */
    private $treeManager;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @param ElementService              $elementService
     * @param ContentTreeManagerInterface $treeManager
     * @param TeaserManagerInterface      $teaserManager
     */
    public function __construct(
        ElementService $elementService,
        ContentTreeManagerInterface $treeManager,
        TeaserManagerInterface $teaserManager
    ) {
        $this->elementService = $elementService;
        $this->treeManager = $treeManager;
        $this->teaserManager = $teaserManager;
    }

    /**
     * @param Element $element
     * @param int     $version
     * @param string  $language
     * @param string  $type
     *
     * @return bool
     */
    public function isOnlineOrLAtestVersion(Element $element, $version, $language, $type)
    {
        if ($this->isLatestVersion($element, $version)) {
            return true;
        }

        return $this->isOnlineVersion($element, $version, $language, $type);
    }

    /**
     * @param Element $element
     * @param int     $version
     *
     * @return bool
     */
    public function isLatestVersion(Element $element, $version)
    {
        if ($version === $this->elementService->findLatestElementVersion($element)->getVersion()) {
            return true;
        }

        return false;
    }

    /**
     * @param Element $element
     * @param int     $version
     * @param string  $language
     * @param string  $type
     *
     * @return bool
     */
    public function isOnlineVersion(Element $element, $version, $language, $type)
    {
        if ($type === Elementtype::TYPE_PART) {
            foreach ($this->teaserManager->findBy(array('typeId' => $element->getEid())) as $teaser) {
                if ($this->teaserManager->getPublishedVersion($teaser, $language) === $version) {
                    return true;
                }
            }
        } else {
            foreach ($this->treeManager->findAll() as $tree) {
                foreach ($tree->getByTypeId($element->getEid()) as $node) {
                    if ($tree->getPublishedVersion($node, $language) === (int) $version) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
