<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\Util;

use Phlexible\Bundle\ElementBundle\Entity\ElementMetaDataValue;
use Phlexible\Bundle\ElementtypeBundle\Model\Elementtype;
use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection;
use Phlexible\Bundle\TeaserBundle\Model\TeaserManagerInterface;
use Phlexible\Bundle\TreeBundle\ContentTree\ContentTreeManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaDataManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaSetField;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;

/**
 * Utility class for suggest fields.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ElementMetaSuggestFieldUtil implements Util
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
     * @var ContentTreeManagerInterface
     */
    private $treeManager;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @var ValueSplitter
     */
    private $splitter;

    /**
     * @param MetaSetManagerInterface     $metaSetManager
     * @param MetaDataManagerInterface    $metaDataManager
     * @param ContentTreeManagerInterface $treeManager
     * @param TeaserManagerInterface      $teaserManager
     * @param string                      $separatorChar
     */
    public function __construct(
        MetaSetManagerInterface $metaSetManager,
        MetaDataManagerInterface $metaDataManager,
        ContentTreeManagerInterface $treeManager,
        TeaserManagerInterface $teaserManager,
        $separatorChar
    ) {
        $this->metaSetManager = $metaSetManager;
        $this->metaDataManager = $metaDataManager;
        $this->treeManager = $treeManager;
        $this->teaserManager = $teaserManager;
        $this->splitter = new ValueSplitter($separatorChar);
    }

    /**
     * Fetch all data source values used in any element versions.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValuesCollection
     */
    public function fetchValues(DataSourceValueBag $valueBag)
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

        $values = new ValuesCollection();

        foreach ($fields as $field) {
            /* @var $field MetaSetField */
            foreach ($this->metaDataManager->findByField($field) as $metaDataValue) {
                $suggestValues = $this->splitter->split($metaDataValue->getValue());

                if (!count($suggestValues)) {
                    continue;
                }

                if ($this->isOnline($metaDataValue)) {
                    $values->addActiveValues($suggestValues);
                } else {
                    $values->addInactiveValues($suggestValues);
                }
            }
        }

        return $values;
    }

    /**
     * @param ElementMetaDataValue $metaDataValue
     *
     * @return bool
     */
    private function isOnline(ElementMetaDataValue $metaDataValue)
    {
        $elementVersion = $metaDataValue->getElementVersion();
        $element = $elementVersion->getElement();
        $elementSource = $elementVersion->getElementSource();

        if ($elementSource->getType() === Elementtype::TYPE_PART) {
            foreach ($this->teaserManager->findBy(array('typeId' => $element->getEid())) as $teaser) {
                if ($this->teaserManager->getPublishedVersion(
                        $teaser,
                        $metaDataValue->getLanguage()
                    ) === $elementVersion->getVersion()
                ) {
                    return true;
                }
            }
        } else {
            foreach ($this->treeManager->findAll() as $tree) {
                foreach ($tree->getByTypeId($element->getEid()) as $node) {
                    if ($tree->getPublishedVersion(
                            $node,
                            $metaDataValue->getLanguage()
                        ) === $elementVersion->getVersion()
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
