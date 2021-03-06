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

use Phlexible\Bundle\ElementBundle\ElementEvents;
use Phlexible\Bundle\ElementBundle\ElementService;
use Phlexible\Bundle\ElementBundle\Event\ElementStructureEvent;
use Phlexible\Bundle\ElementBundle\Model\ElementStructureValue;
use Phlexible\Bundle\ElementtypeBundle\Model\ElementtypeStructure;
use Phlexible\Bundle\SuggestBundle\Entity\DataSource;
use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Element structure listener.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ElementStructureListener implements EventSubscriberInterface
{
    /**
     * @var DataSourceManagerInterface
     */
    private $dataSourceManager;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @param DataSourceManagerInterface $dataSourceManager
     * @param ElementService             $elementService
     */
    public function __construct(DataSourceManagerInterface $dataSourceManager, ElementService $elementService)
    {
        $this->dataSourceManager = $dataSourceManager;
        $this->elementService = $elementService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ElementEvents::CREATE_ELEMENT_STRUCTURE => 'onCreateElementStructure',
        ];
    }

    /**
     * @param ElementStructureEvent $event
     */
    public function onCreateElementStructure(ElementStructureEvent $event)
    {
        $elementStructure = $event->getElementStructure();

        $values = array();

        foreach ($elementStructure->getLanguages() as $language) {
            foreach ($elementStructure->getValues($language) as $value) {
                if ($value->getType() === 'suggest') {
                    $values[] = $value;
                }
            }
            $rii = new \RecursiveIteratorIterator($elementStructure->getIterator(), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($rii as $structure) {
                foreach ($structure->getValues($language) as $value) {
                    if ($value->getType() === 'suggest') {
                        $values[] = $value;
                    }
                }
            }
        }

        if (!count($values)) {
            return;
        }

        $elementVersion = $elementStructure->getElementVersion();
        $elementtype = $this->elementService->findElementtype($elementVersion->getElement());

        foreach ($values as $value) {
            $this->handleValues($elementtype->getStructure(), $value);
        }
    }

    /**
     * @param ElementtypeStructure  $elementtypeStructure
     * @param ElementStructureValue $structureValue
     */
    private function handleValues(ElementtypeStructure $elementtypeStructure, ElementStructureValue $structureValue)
    {
        $structureNode = $elementtypeStructure->getNode($structureValue->getDsId());
        $dataSourceId = $structureNode->getConfigurationValue('suggest_source');

        if (!$dataSourceId) {
            return;
        }

        $dataSource = $this->dataSourceManager->find($dataSourceId);
        /* @var $dataSource DataSource */
        $values = (array) $structureValue->getValue();
        foreach ($values as $value) {
            $dataSource->addValueForLanguage($structureValue->getLanguage(), $value);
        }

        $this->dataSourceManager->updateDataSource($dataSource, false);
    }
}
