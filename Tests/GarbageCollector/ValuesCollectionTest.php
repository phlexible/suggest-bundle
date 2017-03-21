<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Tests\GarbageCollector;

use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValueCollection;
use PHPUnit\Framework\TestCase;

/**
 * Values collection test.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 *
 * @covers \Phlexible\Bundle\SuggestBundle\GarbageCollector\ValueCollection
 */
class ValuesCollectionTest extends TestCase
{
    public function testAddActiveValue()
    {
        $values = new ValueCollection();

        $values->addValue('test1');
        $values->addValue('test2');

        $this->assertCount(2, $values->getValues());
    }

    public function testAddActiveValues()
    {
        $values = new ValueCollection();

        $values->addValues(array('test1', 'test2'));

        $this->assertCount(2, $values->getValues());
    }

    public function testAddActiveValueDoesNotAddDuplicates()
    {
        $values = new ValueCollection();

        $values->addValue('test');
        $values->addValue('test');

        $this->assertCount(1, $values->getValues());
    }
}
