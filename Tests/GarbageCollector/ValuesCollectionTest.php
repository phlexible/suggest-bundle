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

use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection;
use PHPUnit\Framework\TestCase;

/**
 * Values collection test.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 *
 * @covers \Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection
 */
class ValuesCollectionTest extends TestCase
{
    public function testAddActiveValue()
    {
        $values = new ValuesCollection();

        $values->addActiveValue('test1');
        $values->addActiveValue('test2');

        $this->assertCount(2, $values->getActiveValues());
    }

    public function testAddActiveValues()
    {
        $values = new ValuesCollection();

        $values->addActiveValues(array('test1', 'test2'));

        $this->assertCount(2, $values->getActiveValues());
    }

    public function testAddActiveValueDoesNotAddDuplicates()
    {
        $values = new ValuesCollection();

        $values->addActiveValue('test');
        $values->addActiveValue('test');

        $this->assertCount(1, $values->getActiveValues());
    }

    public function testAddInactiveValue()
    {
        $values = new ValuesCollection();

        $values->addInactiveValue('test1');
        $values->addInactiveValue('test2');

        $this->assertCount(2, $values->getInactiveValues());
    }

    public function testAddInactivesValue()
    {
        $values = new ValuesCollection();

        $values->addInactiveValues(array('test1', 'test2'));

        $this->assertCount(2, $values->getInactiveValues());
    }

    public function testAddInactiveValueDoesNotAddDuplicates()
    {
        $values = new ValuesCollection();

        $values->addInactiveValue('test');
        $values->addInactiveValue('test');

        $this->assertCount(1, $values->getInactiveValues());
    }

    public function testAddRemoveValue()
    {
        $values = new ValuesCollection();

        $values->addRemoveValue('test1');
        $values->addRemoveValue('test2');

        $this->assertCount(2, $values->getRemoveValues());
    }

    public function testAddRemovesValue()
    {
        $values = new ValuesCollection();

        $values->addRemoveValues(array('test1', 'test2'));

        $this->assertCount(2, $values->getRemoveValues());
    }

    public function testAddRemoveValueDoesNotAddDuplicates()
    {
        $values = new ValuesCollection();

        $values->addRemoveValue('test');
        $values->addRemoveValue('test');

        $this->assertCount(1, $values->getRemoveValues());
    }

    public function tesMergeValues()
    {
        $values1 = new ValuesCollection(array('active1', 'active2'), array('inactive1', 'inactive2'), array('remove1', 'remove2'));
        $values2 = new ValuesCollection(array('active3', 'active1'), array('inactive3', 'inactive1'), array('remove3', 'remove1'));

        $values = new ValuesCollection();
        $values->merge($values1);
        $values->merge($values2);

        $this->assertSame(array('active1', 'active2', 'active3'), $values->getActiveValues());
        $this->assertSame(array('inactive1', 'inactive2', 'inactive3'), $values->getInactiveValues());
        $this->assertSame(array('remove1', 'remove2', 'remove3'), $values->getRemoveValues());
    }
}
