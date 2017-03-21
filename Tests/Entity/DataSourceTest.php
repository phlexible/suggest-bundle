<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Tests\Entity;

use Phlexible\Bundle\SuggestBundle\Entity\DataSource;
use PHPUnit\Framework\TestCase;

/**
 * Data source test.
 *
 * @author Phillip Look <pl@brainbits.net>
 *
 * @covers \Phlexible\Bundle\SuggestBundle\Entity\DataSource
 */
class DataSourceTest extends TestCase
{
    /**
     * Set title.
     */
    public function testSetTitle()
    {
        $title = 'mytitle';

        $dataSource = new DataSource();
        $dataSource->setTitle($title);

        $this->assertEquals($title, $dataSource->getTitle());
    }

    /**
     * Set active keys.
     */
    public function testSetActiveKeys()
    {
        $keys = ['key-1', 'key-2'];

        $dataSource = new DataSource();
        $dataSource->setValues('de', $keys);

        $this->assertEquals($keys, $dataSource->getValuesForLanguage('de'));
        $this->assertEquals([], $dataSource->getValuesForLanguage('en'));
    }

    /**
     * Remove values from active keys.
     */
    public function testRemoveValuesFromActiveKeys()
    {
        $removeKeys = $this->createKeysAlphabet('a', 'f');
        $expected = $this->createKeysAlphabet('g', 'z');

        $dataSource = $this->createDataSourceAlphabet();
        $dataSource->removeValuesForLanguage('de', $removeKeys);

        $this->assertEquals($expected, $dataSource->getValuesForLanguage('de'));
    }

    /**
     * Create data source 'alphabet'.
     *
     * @param string $startChar [Optional] default = 'a'
     * @param string $endChar   [Optional] default = 'z'
     * @param string $language  [Optional] default = 'de'
     *
     * @return DataSource
     */
    public function createDataSourceAlphabet($startChar = 'a', $endChar = 'z', $language = 'de')
    {
        $dataSource = new DataSource();
        $dataSource->setValues($language, $this->createKeysAlphabet($startChar, $endChar));

        return $dataSource;
    }

    /**
     * Create a keys array.
     *
     * @param string $startChar [Optional] default = 'a'
     * @param string $endChar   [Optional] default = 'z'
     *
     * @return array
     */
    public function createKeysAlphabet($startChar = 'a', $endChar = 'z')
    {
        $startOrd = ord($startChar);
        $endOrd = ord($endChar);

        $keys = [];
        for ($i = $startOrd; $i <= $endOrd; ++$i) {
            $c = chr($i);
            $keys[] = $c;
        }

        return $keys;
    }
}
