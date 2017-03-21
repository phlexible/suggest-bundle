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
    const MODE_REMOVE_UNUSED = 'remove_unused';
    const MODE_REMOVE_UNUSED_AND_INACTIVE = 'remove_unused_inactive';
    const MODE_MARK_UNUSED_INACTIVE = 'inactive';

    /**
     * @var DataSourceManagerInterface
     */
    private $dataSourceManager;

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
     * @param EventDispatcherInterface   $dispatcher
     * @param LoggerInterface            $logger
     */
    public function __construct(DataSourceManagerInterface $dataSourceManager, EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dataSourceManager = $dataSourceManager;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * Start garbage collection.
     *
     * @param string $mode
     * @param bool   $pretend
     *
     * @return array
     */
    public function run($mode = self::MODE_MARK_UNUSED_INACTIVE, $pretend = false)
    {
        $nums = [];

        $limit = 10;
        $offset = 0;

        foreach ($this->dataSourceManager->findBy([], null, $limit, $offset) as $dataSource) {
            $nums = array_merge($nums, $this->runDataSource($dataSource, $mode, $pretend));

            $offset += $limit;
        }

        return $nums;
    }

    /**
     * Start garbage collection.
     *
     * @param DataSource $dataSource
     * @param string     $mode
     * @param bool       $pretend
     *
     * @return array
     */
    public function runDataSource(DataSource $dataSource, $mode = self::MODE_MARK_UNUSED_INACTIVE, $pretend = false)
    {
        $nums = [];

        foreach ($dataSource->getValueBags() as $values) {
            $this->logger->notice("Garbage Collector | ".($pretend?"<error> PRETEND </> | ":"")."Data source <fg=cyan>{$dataSource->getTitle()}</> / <fg=cyan>{$dataSource->getId()}</> / Language <fg=cyan>{$values->getLanguage()}</>");

            $result = $this->garbageCollect($values, $mode, $pretend);

            $nums[$dataSource->getTitle()][$values->getLanguage()] = $result;

            $this->logger->notice("Garbage Collector | Active <fg=green>{$result->countActiveValues()}</> | Inactive <fg=yellow>{$result->countInactiveValues()}</> | Remove <fg=red>{$result->countRemoveValues()}</>");
            $this->logger->info("Garbage Collector | Active: ".json_encode($result->getActiveValues()));
            $this->logger->info("Garbage Collector | Inactive: ".json_encode($result->getInactiveValues()));
            $this->logger->info("Garbage Collector | Remove: ".json_encode($result->getRemoveValues()));
        }

        if (!$pretend) {
            $this->dataSourceManager->updateDataSource($dataSource);
        }

        return $nums;
    }

    /**
     * @param DataSourceValueBag $valueBag
     * @param string             $mode
     * @param bool               $pretend
     *
     * @return ValuesCollection
     */
    private function garbageCollect(DataSourceValueBag $valueBag, $mode, $pretend = false)
    {
        $event = new GarbageCollectEvent($valueBag);
        if ($this->dispatcher->dispatch(SuggestEvents::BEFORE_GARBAGE_COLLECT, $event)->isPropagationStopped()) {
            return new ValuesCollection();
        }

        $collectedValues = $event->getCollectedValues();
        $activeValues = $collectedValues->getActiveValues();
        $inactiveValues = $collectedValues->getInactiveValues();

        //dump('raw active', $activeValues);dump('raw inactive', $inactiveValues);exit;

        $values = $valueBag->getValues();

        $activeValues = array_values(array_unique($activeValues));
        $removeValues = array_values(array_unique(array_diff($values, $activeValues, $inactiveValues)));
        $inactiveValues = array_values(array_unique(array_diff($inactiveValues, $activeValues)));

        //dump('remove', $removeValues);dump('active', $activeValues);dump('inactive', $inactiveValues);exit;

        // for MODE_REMOVE_UNUSED is no change necessary
        if ($mode === self::MODE_MARK_UNUSED_INACTIVE) {
            $inactiveValues = array_merge($inactiveValues, $removeValues);
            sort($inactiveValues);
            $removeValues = [];
        } elseif ($mode === self::MODE_REMOVE_UNUSED_AND_INACTIVE) {
            $removeValues = array_merge($removeValues, $inactiveValues);
            sort($removeValues);
            $inactiveValues = [];
        }

        if (!$pretend) {
            if (count($removeValues)) {
                // apply changes if there is changeable data
                foreach ($removeValues as $value) {
                    $valueBag->removeActiveValue($value);
                    $valueBag->removeInactiveValue($value);
                }
            }

            if (count($activeValues)) {
                // apply changes if there is changeable data
                foreach ($activeValues as $value) {
                    $valueBag->addActiveValue($value);
                    $valueBag->removeInactiveValue($value);
                }
            }

            if (count($inactiveValues)) {
                // apply changes if there is changeable data
                foreach ($inactiveValues as $value) {
                    $valueBag->addInactiveValue($value);
                    $valueBag->removeActiveValue($value);
                }
            }
        }

        $event = new GarbageCollectEvent($valueBag);
        $this->dispatcher->dispatch(SuggestEvents::GARBAGE_COLLECT, $event);

        return new ValuesCollection($activeValues, $inactiveValues, $removeValues);
    }
}
