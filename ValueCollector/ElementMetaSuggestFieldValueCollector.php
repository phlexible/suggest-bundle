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
use Phlexible\Bundle\SuggestBundle\Util\ElementVersionChecker;
use Phlexible\Bundle\SuggestBundle\Util\ValueSplitter;
use Phlexible\Component\MetaSet\Model\MetaDataManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaSetField;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Utility class for element meta suggest fields.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ElementMetaSuggestFieldValueCollector implements ValueCollector
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
     * @var ElementVersionChecker
     */
    private $versionChecker;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValueSplitter
     */
    private $splitter;

    /**
     * @param MetaSetManagerInterface  $metaSetManager
     * @param MetaDataManagerInterface $metaDataManager
     * @param ElementVersionChecker    $versionChecker
     * @param LoggerInterface          $logger
     * @param string                   $separatorChar
     */
    public function __construct(
        MetaSetManagerInterface $metaSetManager,
        MetaDataManagerInterface $metaDataManager,
        ElementVersionChecker $versionChecker,
        LoggerInterface $logger,
        $separatorChar
    ) {
        $this->metaSetManager = $metaSetManager;
        $this->metaDataManager = $metaDataManager;
        $this->versionChecker = $versionChecker;
        $this->logger = $logger;
        $this->splitter = new ValueSplitter($separatorChar);
    }

    /**
     * Fetch all data source values used in any element versions.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValueCollection
     */
    public function collect(DataSourceValueBag $valueBag)
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

        $this->logger->info("Element Meta Suggest Field | Found suggest fields in <fg=cyan>".count($fields)."</> element metasets.");

        $values = new ValueCollection();

        foreach ($fields as $field) {
            $metaDataValues = $this->metaDataManager->findRawByField($field);

            if (!count($metaDataValues)) {
                continue;
            }

            $this->logger->info("Element Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Metaset <fg=cyan>{$field->getMetaSet()->getName()}</> / <fg=cyan>{$field->getMetaSet()->getId()}</> | Field <fg=cyan>{$field->getName()}</> / <fg=cyan>{$field->getId()}</> | <fg=cyan>".count($metaDataValues)."</> Meta Data Values");

            $subValues = new ValueCollection();

            /* @var $field MetaSetField */
            foreach ($metaDataValues as $metaDataValue) {
                $suggestValues = $this->splitter->split($metaDataValue->getValue());

                if (!count($suggestValues)) {
                    continue;
                }

                if ($this->versionChecker->isOnlineOrLatestVersion($metaDataValue->getElementVersion()->getElement(), $metaDataValue->getElementVersion()->getVersion(), $valueBag->getLanguage(), $metaDataValue->getElementVersion()->getElementSource()->getType())) {
                    $subValues->addValues($suggestValues);
                }
            }

            if (!count($subValues)) {
                continue;
            }

            $values->merge($subValues);

            $this->logger->debug("Element Meta Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | # Active <fg=green>{$subValues->count()}</>");
        }

        if (count($values)) {
            $this->logger->info("Element Meta Suggest Field | # Active <fg=green>{$values->count()}</>");
            $this->logger->debug("Element Meta Suggest Field | Active: ".json_encode($values->getValues()));
        }

        return $values;
    }
}
