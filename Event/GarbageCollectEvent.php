<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Event;

use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Symfony\Component\EventDispatcher\Event;

/**
 * Garbage collect event.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class GarbageCollectEvent extends Event
{
    /**
     * @var DataSourceValueBag
     */
    private $values;

    /**
     * @param DataSourceValueBag $values
     */
    public function __construct(DataSourceValueBag $values)
    {
        $this->values = $values;
    }

    /**
     * @return DataSourceValueBag
     */
    public function getDataSourceValueBag()
    {
        return $this->values;
    }
}
