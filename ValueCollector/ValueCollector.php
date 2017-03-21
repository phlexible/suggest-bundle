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
 * Value collector.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
interface ValueCollector
{
    /**
     * Fetch values.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValueCollection
     */
    public function collect(DataSourceValueBag $valueBag);
}
