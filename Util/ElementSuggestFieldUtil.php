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
use Phlexible\Bundle\ElementBundle\Entity\ElementStructureValue;
use Phlexible\Bundle\ElementBundle\Entity\ElementVersion;
use Phlexible\Bundle\ElementBundle\Model\ElementSourceManagerInterface;
use Phlexible\Bundle\ElementtypeBundle\Model\Elementtype;
use Phlexible\Bundle\ElementtypeBundle\Model\ElementtypeStructureNode;
use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection;
use Phlexible\Bundle\TeaserBundle\Model\TeaserManagerInterface;
use Phlexible\Bundle\TreeBundle\ContentTree\ContentTreeManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Utility class for suggest fields.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ElementSuggestFieldUtil implements Util
{
    /**
     * @var MetaSetManagerInterface
     */
    private $metaSetManager;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @var ElementSourceManagerInterface
     */
    private $elementSourceManager;

    /**
     * @var ContentTreeManagerInterface
     */
    private $treeManager;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValueSplitter
     */
    private $splitter;

    /**
     * @param MetaSetManagerInterface       $metaSetManager
     * @param ElementService                $elementService
     * @param ElementSourceManagerInterface $elementSourceManager
     * @param ContentTreeManagerInterface   $treeManager
     * @param TeaserManagerInterface        $teaserManager
     * @param LoggerInterface               $logger
     */
    public function __construct(
        MetaSetManagerInterface $metaSetManager,
        ElementService $elementService,
        ElementSourceManagerInterface $elementSourceManager,
        ContentTreeManagerInterface $treeManager,
        TeaserManagerInterface $teaserManager,
        LoggerInterface $logger
    ) {
        $this->metaSetManager = $metaSetManager;
        $this->elementService = $elementService;
        $this->elementSourceManager = $elementSourceManager;
        $this->treeManager = $treeManager;
        $this->teaserManager = $teaserManager;
        $this->logger = $logger;
        $this->splitter = new JsonValueSplitter();
    }

    /**
     * Fetch all data source values used in any element versions.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValuesCollection
     */
    public function fetchValues(DataSourceValueBag $valueBag)
    {
        $metaSets = $this->metaSetManager->findAll();

        $fields = array();
        foreach ($metaSets as $metaSet) {
            foreach ($metaSet->getFields() as $field) {
                if ($field->getOptions() === $valueBag->getDatasource()->getId()) {
                    $fields[] = $field;
                }
            }
        }

        $structureNodeRows = array();
        foreach ($this->elementSourceManager->findAll() as $elementSource) {
            if ($elementSource->getType() !== 'full' && $elementSource->getType() !== 'structure' && $elementSource->getType() !== 'part') {
                continue;
            }

            $elementtype = $this->elementSourceManager->findElementtype($elementSource->getElementtypeId());

            $rii = new \RecursiveIteratorIterator($elementtype->getStructure()->getIterator(), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($rii as $structureNode) {
                if (!$structureNode) {
                    continue;
                }
                if ($structureNode->getType() === 'suggest') {
                    if (!empty($structureNode->getConfigurationValue('suggest_source'))) {
                        $source = $structureNode->getConfigurationValue('suggest_source');
                        if ($source === $valueBag->getDatasource()->getId()) {
                            $structureNodeRows[] = array($elementtype, $structureNode);
                        }
                    }
                }
            }
        }

        $values = new ValuesCollection();

        $this->logger->info("Element Suggest Field | Found suggest fields in <fg=cyan>".count($structureNodeRows)."</> elementtypes.");

        foreach ($structureNodeRows as $structureNodeRow) {
            /* @var $elementtype Elementtype */
            $elementtype = $structureNodeRow[0];
            /* @var $suggestNode ElementtypeStructureNode */
            $suggestNode = $structureNodeRow[1];

            $structureValues = $this->elementService->findElementStructureValues($suggestNode->getDsId());

            if (!count($structureValues)) {
                continue;
            }

            $this->logger->info("Element Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Elementtype <fg=cyan>{$elementtype->getUniqueId()}</> | Field <fg=cyan>{$suggestNode->getName()}</> / <fg=cyan>{$suggestNode->getDsId()}</> | <fg=cyan>".count($structureValues)."</> Element Version Values");

            $subValues = new ValuesCollection();

            foreach ($structureValues as $structureValue) {
                if (!$structureValue->getContent()) {
                    continue;
                }
                $content = $this->splitter->split($structureValue->getContent());
                if (!$content) {
                    continue;
                }
                if ($this->isOnline((int) $structureValue->getElement()->getEid(), $structureValue->getVersion(), $valueBag->getLanguage(), $elementtype->getType())) {
                    $subValues->addActiveValues($content);
                } else {
                    $subValues->addInactiveValues($content);
                }
            }

            if (!count($subValues)) {
                continue;
            }

            $values->merge($subValues);

            $this->logger->debug("Element Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Active <fg=green>{$subValues->countActiveValues()}</> | Inactive <fg=yellow>{$subValues->countInactiveValues()}</> | Remove <fg=red>{$subValues->countRemoveValues()}</>");
        }

        if (count($values)) {
            $this->logger->info("Element Suggest Field | Active <fg=green>{$values->countActiveValues()}</> | Inactive <fg=yellow>{$values->countInactiveValues()}</> | Remove <fg=red>{$values->countRemoveValues()}</>");
            $this->logger->debug("Element Suggest Field | Active: ".json_encode($values->getActiveValues()));
            $this->logger->debug("Element Suggest Field | Inactive: ".json_encode($values->getInactiveValues()));
            $this->logger->debug("Element Suggest Field | Remove: ".json_encode($values->getRemoveValues()));
        }

        return $values;
    }

    /**
     * @param int    $eid
     * @param int    $version
     * @param string $language
     * @param string $type
     *
     * @return bool
     */
    private function isOnline($eid, $version, $language, $type)
    {
        if ($type === Elementtype::TYPE_PART) {
            foreach ($this->teaserManager->findBy(array('typeId' => $eid)) as $teaser) {
                if ($this->teaserManager->getPublishedVersion($teaser, $language) === $version) {
                    return true;
                }
            }
        } else {
            foreach ($this->treeManager->findAll() as $tree) {
                foreach ($tree->getByTypeId($eid) as $node) {
                    if ($tree->getPublishedVersion($node, $language) === (int) $version) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
