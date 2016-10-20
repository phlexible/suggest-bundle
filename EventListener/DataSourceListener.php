<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
