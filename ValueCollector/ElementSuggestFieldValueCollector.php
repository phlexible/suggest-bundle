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

use Doctrine\ORM\EntityManagerInterface;
use Phlexible\Bundle\ElementBundle\Entity\ElementStructureValue;
use Phlexible\Bundle\ElementBundle\Model\ElementSourceManagerInterface;
use Phlexible\Bundle\ElementtypeBundle\Model\Elementtype;
use Phlexible\Bundle\ElementtypeBundle\Model\ElementtypeStructureNode;
use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValueCollection;
use Phlexible\Bundle\SuggestBundle\Util\ElementVersionChecker;
use Phlexible\Bundle\SuggestBundle\Util\JsonValueSplitter;
use Phlexible\Bundle\SuggestBundle\Util\ValueSplitter;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Utility class for element suggest fields.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ElementSuggestFieldValueCollector implements ValueCollector
{
    /**
     * @var MetaSetManagerInterface
     */
    private $metaSetManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ElementSourceManagerInterface
     */
    private $elementSourceManager;

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
     * @param MetaSetManagerInterface       $metaSetManager
     * @param EntityManagerInterface        $entityManager
     * @param ElementSourceManagerInterface $elementSourceManager
     * @param ElementVersionChecker         $versionChecker
     * @param LoggerInterface               $logger
     */
    public function __construct(
        MetaSetManagerInterface $metaSetManager,
        EntityManagerInterface $entityManager,
        ElementSourceManagerInterface $elementSourceManager,
        ElementVersionChecker $versionChecker,
        LoggerInterface $logger
    ) {
        $this->metaSetManager = $metaSetManager;
        $this->entityManager = $entityManager;
        $this->elementSourceManager = $elementSourceManager;
        $this->versionChecker = $versionChecker;
        $this->logger = $logger;
        $this->splitter = new JsonValueSplitter();
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

        $values = new ValueCollection();

        $this->logger->info("Element Suggest Field | Found suggest fields in <fg=cyan>".count($structureNodeRows)."</> elementtypes.");

        foreach ($structureNodeRows as $structureNodeRow) {
            /* @var $elementtype Elementtype */
            $elementtype = $structureNodeRow[0];
            /* @var $suggestNode ElementtypeStructureNode */
            $suggestNode = $structureNodeRow[1];

            $structureValues = $this->findValues($suggestNode->getDsId(), $valueBag->getLanguage(), $elementtype);

            if (!count($structureValues)) {
                continue;
            }

            $this->logger->info("Element Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | Elementtype <fg=cyan>{$elementtype->getUniqueId()}</> | Field <fg=cyan>{$suggestNode->getName()}</> / <fg=cyan>{$suggestNode->getDsId()}</> | <fg=cyan>".count($structureValues)."</> Element Version Values");

            $subValues = new ValueCollection();

            foreach ($structureValues as $structureValue) {
                if (!$structureValue->getContent()) {
                    continue;
                }

                $content = $this->splitter->split($structureValue->getContent());
                if (!$content) {
                    continue;
                }

                if ($this->versionChecker->isOnlineOrLatestVersion($structureValue->getElement(), $structureValue->getVersion(), $valueBag->getLanguage(), $elementtype->getType())) {
                    $subValues->addValues($content);
                }
            }

            if (!count($subValues)) {
                continue;
            }

            $values->merge($subValues);

            $this->logger->debug("Element Suggest Field | Memory: ".number_format(memory_get_usage(true)/1024/1024, 2)." MB | # Active <fg=green>{$subValues->count()}</>");
        }

        if (count($values)) {
            $this->logger->info("Element Suggest Field | # Active <fg=green>{$values->count()}</>");
            $this->logger->debug("Element Suggest Field | Active: ".json_encode($values->getValuesWithCount()));
        }

        return $values;
    }

    /**
     * @param string $dsId
     * @param string $language
     * @param array  $versions
     *
     * @return ElementStructureValue[]
     */
    public function findValues($dsId, $language, Elementtype $elementtype)
    {
        $repository = $this->entityManager->getRepository(ElementStructureValue::class);

        $elements = $this->versionChecker->getElements($elementtype);

        $values = array();
        foreach ($elements as $element) {
            $criteria = array(
                'dsId' => $dsId,
                'language' => $language,
                'version' => $this->versionChecker->getOnlineAndLatestVersion($element, $language, $elementtype->getType()),
            );

            $values = array_merge($values, $repository->findBy($criteria));
        }

        return $values;
    }
}
