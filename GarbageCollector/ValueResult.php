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
     * @var ValuesCollection
     */
    private $values;

    /**
     * @param DataSource       $dataSource
     * @param string           $language
     * @param ValuesCollection $values
     */
    public function __construct(DataSource $dataSource, $language, ValuesCollection $values)
    {
        $this->dataSource = $dataSource;
        $this->language = $language;
        $this->values = $values;
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
     * @return ValuesCollection
     */
    public function getValues()
    {
        return $this->values;
    }
}
