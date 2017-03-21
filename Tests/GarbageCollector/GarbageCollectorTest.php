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

use Phlexible\Bundle\SuggestBundle\Entity\DataSource;
use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\Event\GarbageCollectEvent;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\GarbageCollector;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValueCollection;
use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Phlexible\Bundle\SuggestBundle\SuggestEvents;
use Phlexible\Bundle\SuggestBundle\ValueCollector\ValueCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Garbage collector test.
 *
 * @author Phillip Look <pl@brainbits.net>
 *
 * @covers \Phlexible\Bundle\SuggestBundle\GarbageCollector\GarbageCollector
 */
class GarbageCollectorTest extends TestCase
{
    /**
     * @var GarbageCollector
     */
    private $garbageCollector;

    /**
     * @var DataSourceManagerInterface|ObjectProphecy
     */
    private $manager;

    /**
     * @var ValueCollector|ObjectProphecy
     */
    private $collector;

    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DataSource
     */
    private $datasource;

    /**
     * @var DataSourceValueBag
     */
    private $datasourceValues;

    public function setUp()
    {
        $this->manager = $this->prophesize(DataSourceManagerInterface::class);
        $this->collector = $this->prophesize(ValueCollector::class);
        $this->eventDispatcher = new EventDispatcher();
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->garbageCollector = new GarbageCollector($this->manager->reveal(), $this->collector->reveal(), $this->eventDispatcher, $this->logger->reveal());

        $this->datasource = new DataSource();
        $this->datasource->setTitle('testDatasource');
        $this->datasourceValues = new DataSourceValueBag();
        $this->datasourceValues->setLanguage('de');
        $this->datasource->addValueBag($this->datasourceValues);

        $this->collector->collect($this->datasourceValues)->willReturn(new ValueCollection());
    }

    public function testEventsAreFired()
    {
        $fired = 0;
        $this->eventDispatcher->addListener(
            SuggestEvents::BEFORE_GARBAGE_COLLECT,
            function() use (&$fired) {
                ++$fired;
            }
        );
        $this->eventDispatcher->addListener(
            SuggestEvents::GARBAGE_COLLECT,
            function() use (&$fired) {
                ++$fired;
            }
        );

        $this->manager->findBy(Argument::cetera())->willReturn([$this->datasource]);
        $this->manager->updateDataSource(Argument::any())->shouldBeCalled();

        $this->garbageCollector->run();

        $this->assertSame(2, $fired);
    }

    public function testRunWithNoValues()
    {
        $this->manager->findBy(Argument::cetera())->willReturn([$this->datasource]);
        $this->manager->updateDataSource(Argument::any())->shouldBeCalled();

        $result = $this->garbageCollector->run();

        $this->assertCount(1, $result);
        $this->assertCount(0, $result[0]->getActiveValues());
        $this->assertCount(0, $result[0]->getObsoleteValues());
    }

    public function testRunRemovesUnusedValues()
    {
        $this->datasource->addValueForLanguage('de', 'value1');
        $this->datasource->addValueForLanguage('de', 'value2');

        $this->manager->findBy(Argument::cetera())->willReturn([$this->datasource]);
        $this->manager->updateDataSource(Argument::any())->shouldBeCalled();

        $result = $this->garbageCollector->run();
        $this->assertCount(1, $result);
        $this->assertSame([], $result[0]->getActiveValues()->getValues());
        $this->assertSame(['value1', 'value2'], $result[0]->getObsoleteValues()->getValues());
    }
}
