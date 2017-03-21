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

use Countable;
use Phlexible\Bundle\SuggestBundle\Entity\DataSource;

/**
 * Value result.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ValueResult
{
    /**
     * @var DataSource
     */
    private $dataSource;

    /**
     * @var string
     */
    private $language;

    /**
     * @var ValueCollection
     */
    private $newValues;

    /**
     * @var ValueCollection
     */
    private $existingValues;

    /**
     * @var ValueCollection
     */
    private $obsoleteValues;

    /**
     * @param DataSource      $dataSource
     * @param string          $language
     * @param ValueCollection $newValues
     * @param ValueCollection $existingValues
     * @param ValueCollection $obsoleteValues
     */
    public function __construct(DataSource $dataSource, $language, ValueCollection $newValues, ValueCollection $existingValues, ValueCollection $obsoleteValues)
    {
        $this->dataSource = $dataSource;
        $this->language = $language;
        $this->newValues = $newValues;
        $this->existingValues = $existingValues;
        $this->obsoleteValues = $obsoleteValues;
    }

    /**
     * @return DataSource
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return ValueCollection
     */
    public function getNewValues()
    {
        return $this->newValues;
    }

    /**
     * @return ValueCollection
     */
    public function getExistingValues()
    {
        return $this->existingValues;
    }

    /**
     * @return ValueCollection
     */
    public function getObsoleteValues()
    {
        return $this->obsoleteValues;
    }
}
