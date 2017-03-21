<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\ValueCollector;

use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValueCollection;

/**
 * Chain value collector.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ChainValueCollector implements ValueCollector
{
    /**
     * @var ValueCollector[]
     */
    private $collectors;

    /**
     * @param ValueCollector[] $collectors
     */
    public function __construct(array $collectors)
    {
        $this->collectors = $collectors;
    }

    /**
     * Fetch all data source values used in any element versions.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValueCollection
     */
    public function collect(DataSourceValueBag $valueBag)
    {
        $values = new ValueCollection();

        foreach ($this->collectors as $collector) {
            $values->merge($collector->collect($valueBag));
        }

        return $values;
    }
}
