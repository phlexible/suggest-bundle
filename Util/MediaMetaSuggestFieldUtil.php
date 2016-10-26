<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\Util;

use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection;
use Phlexible\Component\MetaSet\Model\MetaSetField;
use Phlexible\Component\MetaSet\Model\MetaDataInterface;
use Phlexible\Component\MetaSet\Model\MetaDataManagerInterface;
use Phlexible\Component\MetaSet\Model\MetaSetManagerInterface;

/**
 * Utility class for suggest fields.
 *
 * @author Phillip Look <pl@brainbits.net>
 */
class MediaMetaSuggestFieldUtil implements Util
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
     * @var ValueSplitter
     */
    private $splitter;

    /**
     * @param MetaSetManagerInterface  $metaSetManager
     * @param MetaDataManagerInterface $metaDataManager
     * @param string                   $separatorChar
     */
    public function __construct(MetaSetManagerInterface $metaSetManager, MetaDataManagerInterface $metaDataManager, $separatorChar)
    {
        $this->metaSetManager = $metaSetManager;
        $this->metaDataManager = $metaDataManager;
        $this->splitter = new ValueSplitter($separatorChar);
    }

    /**
     * Fetch all data source values used in any media file metaset.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValuesCollection
     */
    public function fetchValues(DataSourceValueBag $valueBag)
    {
        $metaSets = $this->metaSetManager->findAll();

        $fields = [];
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
            foreach ($this->metaDataManager->findRawByField($field) as $metaDataValue) {
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
     * @param mixed $metaDataValue
     *
     * @return bool
     */
    private function isOnline($metaDataValue)
    {
        return true;
    }
}
