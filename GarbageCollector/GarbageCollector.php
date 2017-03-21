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
     * @param bool $commit
     *
     * @return array
     */
    public function run($commit = false)
    {
        $results = [];

        $limit = 10;
        $offset = 0;

        foreach ($this->dataSourceManager->findBy([], null, $limit, $offset) as $dataSource) {
            $results = array_merge($results, $this->runDataSource($dataSource, $commit));

            $offset += $limit;
        }

        return $results;
    }

    /**
     * Start garbage collection.
     *
     * @param DataSource $dataSource
     * @param bool       $commit
     *
     * @return ValueResult[]
     */
    public function runDataSource(DataSource $dataSource, $commit = false)
    {
        $results = [];

        foreach ($dataSource->getValueBags() as $dataSourceValues) {
            $this->logger->notice("Garbage Collector | ".($commit?"<error> COMMIT </>":"<question> SHOW </>")." | Data source <fg=cyan>{$dataSource->getTitle()}</> / <fg=cyan>{$dataSource->getId()}</> | Language <fg=cyan>{$dataSourceValues->getLanguage()}</> | <fg=cyan>{$dataSourceValues->countValues()}</> Values");

            $results[] = $result = $this->garbageCollect($dataSource, $dataSourceValues, $commit);

            $this->logger->notice("Garbage Collector | New <fg=green>{$result->getNewValues()->count()}</> | Existing <fg=yellow>{$result->getExistingValues()->count()}</> | Obsolete <fg=red>{$result->getObsoleteValues()->count()}</>");
            $this->logger->debug("Garbage Collector | New: ".json_encode($result->getNewValues()->getValues()));
            $this->logger->debug("Garbage Collector | Obsolete: ".json_encode($result->getObsoleteValues()->getValues()));
        }

        if ($commit) {
            $this->dataSourceManager->updateDataSource($dataSource);
        }

        return $results;
    }

    /**
     * @param DataSource         $dataSource
     * @param DataSourceValueBag $dataSourceValues
     * @param bool               $pretend
     *
     * @return ValueResult
     */
    private function garbageCollect(DataSource $dataSource, DataSourceValueBag $dataSourceValues, $pretend = false)
    {
        $event = new GarbageCollectEvent($dataSourceValues);
        if ($this->dispatcher->dispatch(SuggestEvents::BEFORE_GARBAGE_COLLECT, $event)->isPropagationStopped()) {
            return null;
        }

        $collectedValues = $this->valueCollector->collect($dataSourceValues);
        $activeValues = $collectedValues->getValues();

        $existingValues = $dataSourceValues->getValues();
        $newValues = array_values(array_unique(array_diff($activeValues, $existingValues)));
        $obsoleteValues = array_values(array_unique(array_diff($existingValues, $activeValues)));
        $existingValues = array_values(array_unique(array_diff($existingValues, $obsoleteValues)));

        if (!$pretend) {
            if (count($obsoleteValues)) {
                foreach ($obsoleteValues as $value) {
                    $dataSourceValues->removeValue($value);
                }
            }

            if (count($activeValues)) {
                foreach ($activeValues as $value) {
                    $dataSourceValues->addValue($value);
                }
            }
        }

        $event = new GarbageCollectEvent($dataSourceValues);
        $this->dispatcher->dispatch(SuggestEvents::GARBAGE_COLLECT, $event);

        return new ValueResult($dataSource, $dataSourceValues->getLanguage(), new ValueCollection($newValues), new ValueCollection($existingValues), new ValueCollection($obsoleteValues));
    }
}
