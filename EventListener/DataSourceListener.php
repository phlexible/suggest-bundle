<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\EventListener;

use Phlexible\Bundle\SuggestBundle\SuggestEvents;
use Phlexible\Bundle\SuggestBundle\Event\GarbageCollectEvent;
use Phlexible\Bundle\SuggestBundle\Util\Util;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Data source listener
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class DataSourceListener implements EventSubscriberInterface
{
    /**
     * @var Util[]
     */
    private $utils;

    /**
     * @param Util[] $utils
     */
    public function __construct($utils)
    {
        $this->utils = $utils;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SuggestEvents::BEFORE_GARBAGE_COLLECT => 'onGarbageCollect',
        ];
    }

    /**
     * Ensure used values are marked active.
     *
     * @param GarbageCollectEvent $event
     */
    public function onGarbageCollect(GarbageCollectEvent $event)
    {
        $values = $event->getDataSourceValueBag();
        $collectedValues = $event->getCollectedValues();

        foreach ($this->utils as $util) {
            $collectedValues->merge($util->fetchValues($values));
        }
    }
}
