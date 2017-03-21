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

use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection;
use Phlexible\Component\MetaSet\Model\MetaDataManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaSetField;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Utility class for suggest fields.
 *
 * @author Phillip Look <pl@brainbits.net>
 */
class MediaMetaSuggestFieldUtil implements Util
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValueSplitter
     */
    private $splitter;

    /**
     * @var string
     */
    private $typeHint;

    /**
     * @param MetaSetManagerInterface  $metaSetManager
     * @param MetaDataManagerInterface $metaDataManager
     * @param LoggerInterface          $logger
     * @param string                   $separatorChar
     * @param string                   $typeHint
     */
    public function __construct(MetaSetManagerInterface $metaSetManager, MetaDataManagerInterface $metaDataManager, LoggerInterface $logger, $separatorChar, $typeHint)
    {
        $this->metaSetManager = $metaSetManager;
        $this->metaDataManager = $metaDataManager;
        $this->logger = $logger;
        $this->splitter = new ValueSplitter($separatorChar);
        $this->typeHint = $typeHint;
    }

    /**
     * Fetch all data source values used in any media file metaset.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValuesCollection
     */
    public function fetchValues(DataSourceValueBag $valueBag)
    {
        $metaSets = $this->metaSetManager->findAll();

        $fields = [];
        foreach ($metaSets as $metaSet) {
            foreach ($metaSet->getFields() as $field) {
                if ($field->getOptions() === $valueBag->getDatasource()->getId()) {
                    $fields[] = $field;
                }
            }
        }

        $this->logger->info("{$this->typeHint} Meta Suggest Field | Found suggest fields in ".count($fields)." media metasets.");

        $values = new ValuesCollection();

        foreach ($fields as $field) {
            /* @var $field MetaSetField */

            $metaDataValues = $this->metaDataManager->findRawByField($field);
            if (!count($metaDataValues)) {
                continue;
            }

            $this->logger->info("{$this->typeHint} Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Metaset {$field->getMetaSet()->getName()} / <info>{$field->getMetaSet()->getId()}</> | Field <info>{$field->getName()}</> / <info>{$field->getId()}</> | <info>".count($metaDataValues)."</> Meta Data Values");

            $subValues = new ValuesCollection();

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

            $this->logger->debug("{$this->typeHint} Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Active <fg=green>{$subValues->countActiveValues()}</> | Inactive <fg=yellow>{$subValues->countInactiveValues()}</> | Remove <fg=red>{$subValues->countRemoveValues()}</>");
        }

        if (count($values)) {
            $this->logger->info("{$this->typeHint} Meta Suggest Field | Active <fg=green>{$values->countActiveValues()}</> | Inactive <fg=yellow>{$values->countInactiveValues()}</> | Remove <fg=red>{$values->countRemoveValues()}</>");
            $this->logger->debug("{$this->typeHint} Meta Suggest Field | Active: ".json_encode($values->getActiveValues()));
            $this->logger->debug("{$this->typeHint} Meta Suggest Field | Inactive: ".json_encode($values->getInactiveValues()));
            $this->logger->debug("{$this->typeHint} Meta Suggest Field | Remove: ".json_encode($values->getRemoveValues()));
        }

        return $values;
    }

    /**
     * @param mixed $metaDataValue
     *
     * @return bool
     */
    private function isOnline($metaDataValue)
    {
        return true;
    }
}
