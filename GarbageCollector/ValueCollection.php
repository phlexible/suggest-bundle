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

/**
 * Value collection.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ValueCollection implements Countable
{
    /**
     * @var array
     */
    private $values = [];

    /**
     * @param array $values
     */
    public function __construct($values = [])
    {
        $this->addValues($values);
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function addValue($value)
    {
        $value = trim($value);

        if ($value && !$this->hasValue($value)) {
            $this->values[] = $value;
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function addValues($values)
    {
        foreach ($values as $value) {
            $this->addValue($value);
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function setValues(array $values)
    {
        $this->values = [];

        $this->addValues($values);

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function removeValue($value)
    {
        if ($this->hasValue($value)) {
            unset($this->values[array_search($value, $this->values)]);
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function removeValues(array $values)
    {
        foreach ($values as $value) {
            $this->removeValue($value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function hasValue($value)
    {
        return in_array($value, $this->values);
    }

    /**
     * @param ValueCollection $values
     *
     * @return $this
     */
    public function merge(ValueCollection $values)
    {
        $this->addValues($values->getValues());

        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->values);
    }
}
