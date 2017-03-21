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

use Phlexible\Bundle\ElementBundle\Entity\ElementMetaDataValue;
use Phlexible\Bundle\ElementtypeBundle\Model\Elementtype;
use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection;
use Phlexible\Bundle\TeaserBundle\Model\TeaserManagerInterface;
use Phlexible\Bundle\TreeBundle\ContentTree\ContentTreeManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaDataManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaSetField;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Utility class for suggest fields.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ElementMetaSuggestFieldUtil implements Util
{
    /**
     * @var MetaSetManagerInterface
     */
    private $metaSetManager;

    /**
     * @var MetaDataManagerInterface
     */
    private $metaDataManager;

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
     * @param MetaSetManagerInterface     $metaSetManager
     * @param MetaDataManagerInterface    $metaDataManager
     * @param ContentTreeManagerInterface $treeManager
     * @param TeaserManagerInterface      $teaserManager
     * @param LoggerInterface             $logger
     * @param string                      $separatorChar
     */
    public function __construct(
        MetaSetManagerInterface $metaSetManager,
        MetaDataManagerInterface $metaDataManager,
        ContentTreeManagerInterface $treeManager,
        TeaserManagerInterface $teaserManager,
        LoggerInterface $logger,
        $separatorChar
    ) {
        $this->metaSetManager = $metaSetManager;
        $this->metaDataManager = $metaDataManager;
        $this->treeManager = $treeManager;
        $this->teaserManager = $teaserManager;
        $this->logger = $logger;
        $this->splitter = new ValueSplitter($separatorChar);
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

        $this->logger->info("Element Meta Suggest Field | Found suggest fields in ".count($fields)." element metasets.");

        $values = new ValuesCollection();

        foreach ($fields as $field) {
            $metaDataValues = $this->metaDataManager->findRawByField($field);

            if (!count($metaDataValues)) {
                continue;
            }

            $this->logger->info("Element Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Metaset {$field->getMetaSet()->getName()} / <info>{$field->getMetaSet()->getId()}</> | Field <info>{$field->getName()}</> / <info>{$field->getId()}</> | <info>".count($metaDataValues)."</> Meta Data Values");

            $subValues = new ValuesCollection();

            /* @var $field MetaSetField */
            foreach ($metaDataValues as $metaDataValue) {
                $suggestValues = $this->splitter->split($metaDataValue->getValue());

                if (!count($suggestValues)) {
                    continue;
                }

                if ($this->isOnline($metaDataValue)) {
                    $subValues->addActiveValues($suggestValues);
                } else {
                    $subValues->addInactiveValues($suggestValues);
                }
            }

            if (!count($subValues)) {
                continue;
            }

            $values->merge($subValues);

            $this->logger->debug("Element Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Active <fg=green>{$subValues->countActiveValues()}</> | Inactive <fg=yellow>{$subValues->countInactiveValues()}</> | Remove <fg=red>{$subValues->countRemoveValues()}</>");
        }

        if (count($values)) {
            $this->logger->info("Element Meta Suggest Field | Active <fg=green>{$values->countActiveValues()}</> | Inactive <fg=yellow>{$values->countInactiveValues()}</> | Remove <fg=red>{$values->countRemoveValues()}</>");
            $this->logger->debug("Element Meta Suggest Field | Active: ".json_encode($values->getActiveValues()));
            $this->logger->debug("Element Meta Suggest Field | Inactive: ".json_encode($values->getInactiveValues()));
            $this->logger->debug("Element Meta Suggest Field | Remove: ".json_encode($values->getRemoveValues()));
        }

        return $values;
    }

    /**
     * @param ElementMetaDataValue $metaDataValue
     *
     * @return bool
     */
    private function isOnline(ElementMetaDataValue $metaDataValue)
    {
        $elementVersion = $metaDataValue->getElementVersion();
        $element = $elementVersion->getElement();
        $elementSource = $elementVersion->getElementSource();

        if ($elementSource->getType() === Elementtype::TYPE_PART) {
            foreach ($this->teaserManager->findBy(array('typeId' => $element->getEid())) as $teaser) {
                if ($this->teaserManager->getPublishedVersion(
                        $teaser,
                        $metaDataValue->getLanguage()
                    ) === $elementVersion->getVersion()
                ) {
                    return true;
                }
            }
        } else {
            foreach ($this->treeManager->findAll() as $tree) {
                foreach ($tree->getByTypeId($element->getEid()) as $node) {
                    if ($tree->getPublishedVersion(
                            $node,
                            $metaDataValue->getLanguage()
                        ) === $elementVersion->getVersion()
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
