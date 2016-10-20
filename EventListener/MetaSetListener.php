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

use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Phlexible\Component\MetaSet\Event\MetaDataValueEvent;
use Phlexible\Component\MetaSet\MetaSetEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Meta Ste listener.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class MetaSetListener implements EventSubscriberInterface
{
    /**
     * @var DataSourceManagerInterface
     */
    private $dataSourceManager;

    /**
     * @param DataSourceManagerInterface $dataSourceManager
     */
    public function __construct(DataSourceManagerInterface $dataSourceManager)
    {
        $this->dataSourceManager = $dataSourceManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            MetaSetEvents::UPDATE_META_DATA_VALUE => 'onUpdateMetaDataValue',
        ];
    }

    /**
     * @param MetaDataValueEvent $event
     */
    public function onUpdateMetaDataValue(MetaDataValueEvent $event)
    {
        $metaField = $event->getMetaField();
        $value = $event->getValue();

        if ('suggest' !== $metaField->getType()) {
            return;
        }

        $dataSourceId = $metaField->getOptions();
        $dataSource = $this->dataSourceManager->find($dataSourceId);
        foreach (explode(',', $value->getValue()) as $singleValue) {
            $dataSource->addValueForLanguage($value->getLanguage(), $singleValue, true);
        }
        $this->dataSourceManager->updateDataSource($dataSource);
    }
}
