<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\GarbageCollector;

use Phlexible\Bundle\SuggestBundle\Entity\DataSource;
use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\Event\GarbageCollectEvent;
use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Phlexible\Bundle\SuggestBundle\SuggestEvents;
use Phlexible\Bundle\SuggestBundle\ValueCollector\ValueCollector;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Garbage collector for data source values.
 * - unused values can be removed.
 *
 * @author Phillip Look <pl@brainbits.net>
 */
class GarbageCollector
{
    /**
     * @var DataSourceManagerInterface
     */
    private $dataSourceManager;

    /**
     * @var ValueCollector
     */
    private $valueCollector;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DataSourceManagerInterface $dataSourceManager
     * @param ValueCollector             $valueCollector
     * @param EventDispatcherInterface   $dispatcher
     * @param LoggerInterface            $logger
     */
    public function __construct(DataSourceManagerInterface $dataSourceManager, ValueCollector $valueCollector, EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dataSourceManager = $dataSourceManager;
        $this->valueCollector = $valueCollector;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * Start garbage collection.
     *
     * @param bool $pretend
     *
     * @return array
     */
    public function run($pretend = false)
    {
        $results = [];

        $limit = 10;
        $offset = 0;

        foreach ($this->dataSourceManager->findBy([], null, $limit, $offset) as $dataSource) {
            $results = array_merge($results, $this->runDataSource($dataSource, $pretend));

            $offset += $limit;
        }

        return $results;
    }

    /**
     * Start garbage collection.
     *
     * @param DataSource $dataSource
     * @param bool       $pretend
     *
     * @return ValueResult[]
     */
    public function runDataSource(DataSource $dataSource, $pretend = false)
    {
        $results = [];

        foreach ($dataSource->getValueBags() as $values) {
            $this->logger->notice("Garbage Collector | ".($pretend?"<error> PRETEND </> | ":"")."Data source <fg=cyan>{$dataSource->getTitle()}</> / <fg=cyan>{$dataSource->getId()}</> / Language <fg=cyan>{$values->getLanguage()}</>");

            $collectedValues = $this->garbageCollect($values, $pretend);

            $results[] = new ValueResult($dataSource, $values->getLanguage(), $collectedValues);

            $this->logger->notice("Garbage Collector | Active <fg=green>{$collectedValues->countActiveValues()}</> | Remove <fg=red>{$collectedValues->countRemoveValues()}</>");
            $this->logger->info("Garbage Collector | Active: ".json_encode($collectedValues->getActiveValues()));
            $this->logger->info("Garbage Collector | Remove: ".json_encode($collectedValues->getRemoveValues()));
        }

        if (!$pretend) {
            $this->dataSourceManager->updateDataSource($dataSource);
        }

        return $results;
    }

    /**
     * @param DataSourceValueBag $valueBag
     * @param bool               $pretend
     *
     * @return ValuesCollection
     */
    private function garbageCollect(DataSourceValueBag $valueBag, $pretend = false)
    {
        $event = new GarbageCollectEvent($valueBag);
        if ($this->dispatcher->dispatch(SuggestEvents::BEFORE_GARBAGE_COLLECT, $event)->isPropagationStopped()) {
            return null;
        }

        $collectedValues = $this->valueCollector->collect($valueBag);
        $activeValues = $collectedValues->getActiveValues();

        $existingValues = $valueBag->getValues();

        $removeValues = array_values(array_unique(array_diff($existingValues, $activeValues)));

        if (!$pretend) {
            if (count($removeValues)) {
                // apply changes if there is changeable data
                foreach ($removeValues as $value) {
                    $valueBag->removeValue($value);
                }
            }

            if (count($activeValues)) {
                // apply changes if there is changeable data
                foreach ($activeValues as $value) {
                    $valueBag->addValue($value);
                }
            }
        }

        $event = new GarbageCollectEvent($valueBag);
        $this->dispatcher->dispatch(SuggestEvents::GARBAGE_COLLECT, $event);

        return new ValuesCollection($activeValues, $removeValues);
    }
}
