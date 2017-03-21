<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\ValueCollector;

use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValueCollection;
use Phlexible\Bundle\SuggestBundle\Util\ValueSplitter;
use Phlexible\Component\MetaSet\Model\MetaDataManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaSetField;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Utility class for media meta suggest fields.
 *
 * @author Phillip Look <pl@brainbits.net>
 */
class MediaMetaSuggestFieldValueCollector implements ValueCollector
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
     * @return ValueCollection
     */
    public function collect(DataSourceValueBag $valueBag)
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

        $this->logger->info("{$this->typeHint} Meta Suggest Field | Found suggest fields in <fg=cyan>".count($fields)."</> media metasets.");

        $values = new ValueCollection();

        foreach ($fields as $field) {
            /* @var $field MetaSetField */

            $metaDataValues = $this->metaDataManager->findRawByField($field);
            if (!count($metaDataValues)) {
                continue;
            }

            $this->logger->info("{$this->typeHint} Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Metaset <fg=cyan>{$field->getMetaSet()->getName()}</> / <fg=cyan>{$field->getMetaSet()->getId()}</> | Field <fg=cyan>{$field->getName()}</> / <fg=cyan>{$field->getId()}</> | <fg=cyan>".count($metaDataValues)."</> Meta Data Values");

            $subValues = new ValueCollection();

            foreach ($metaDataValues as $metaDataValue) {
                if ($metaDataValue->getLanguage() !== $valueBag->getLanguage()) {
                    continue;
                }

                $suggestValues = $this->splitter->split($metaDataValue->getValue());

                if (!count($suggestValues)) {
                    continue;
                }

                $subValues->addValues($suggestValues);
            }

            if (!count($subValues)) {
                continue;
            }

            $values->merge($subValues);

            $this->logger->debug("{$this->typeHint} Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | # Active <fg=green>{$subValues->count()}</>");
        }

        if (count($values)) {
            $this->logger->info("{$this->typeHint} Meta Suggest Field | # Active <fg=green>{$values->count()}</>");
            $this->logger->debug("{$this->typeHint} Meta Suggest Field | Active: ".json_encode($values->getValuesWithCount()));
        }

        return $values;
    }
}
