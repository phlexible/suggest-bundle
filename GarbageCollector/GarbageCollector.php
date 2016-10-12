<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\GarbageCollector;

use Phlexible\Bundle\SuggestBundle\SuggestEvents;
use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\Event\GarbageCollectEvent;
use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Garbage collector for data source values.
 * - unused values can be removed
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
     * Event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param DataSourceManagerInterface $dataSourceManager
     * @param EventDispatcherInterface   $dispatcher
     */
    public function __construct(DataSourceManagerInterface $dataSourceManager, EventDispatcherInterface $dispatcher)
    {
        $this->dataSourceManager = $dataSourceManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Start garbage collection.
     *
     * @param string  $mode
     * @param boolean $pretend
     *
     * @return array
     */
    public function run($mode = self::MODE_MARK_UNUSED_INACTIVE, $pretend = false)
    {
        $nums = [];

        $limit = 10;
        $offset = 0;
        foreach ($this->dataSourceManager->findBy([], null, $limit, $offset) as $dataSource) {
            foreach ($dataSource->getValueBags() as $values) {
                $result = $this->garbageCollect($values, $mode, $pretend);

                $nums[$dataSource->getTitle()][$values->getLanguage()] = $result;
            }

            if (!$pretend) {
                $this->dataSourceManager->updateDataSource($dataSource);
            }

            $offset += $limit;
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