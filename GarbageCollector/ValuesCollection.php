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
 * Values collection.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class ValuesCollection implements Countable
{
    /**
     * @var array
     */
    private $activeValues = [];

    /**
     * @var array
     */
    private $removeValues = [];

    /**
     * ValuesCollection constructor.
     *
     * @param array $activeValues
     * @param array $removeValues
     */
    public function __construct($activeValues = [], $removeValues = [])
    {
        $this->addActiveValues($activeValues);
        $this->addRemoveValues($removeValues);
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function addActiveValue($value)
    {
        $value = trim($value);

        if ($value && !$this->hasActiveValue($value)) {
            $this->activeValues[] = $value;
        }

        $this->removeRemoveValue($value);

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function addActiveValues($values)
    {
        foreach ($values as $value) {
            $this->addActiveValue($value);
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function setActiveValues($values)
    {
        $this->activeValues = [];

        $this->addActiveValues($values);

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function removeActiveValue($value)
    {
        if ($this->hasActiveValue($value)) {
            unset($this->activeValues[array_search($value, $this->activeValues)]);
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function removeActiveValues($values)
    {
        foreach ($values as $value) {
            $this->removeActiveValue($value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getActiveValues()
    {
        return $this->activeValues;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function hasActiveValue($value)
    {
        return in_array($value, $this->activeValues);
    }

    /**
     * @return int
     */
    public function countActiveValues()
    {
        return count($this->activeValues);
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function addRemoveValue($value)
    {
        $value = trim($value);

        if ($value && !$this->hasRemoveValue($value) && !$this->hasActiveValue($value)) {
            $this->removeValues[] = $value;
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function addRemoveValues($values)
    {
        foreach ($values as $value) {
            $this->addRemoveValue($value);
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function setRemoveValues($values)
    {
        $this->removeValues = [];

        $this->addRemoveValues($values);

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function removeRemoveValue($value)
    {
        if ($this->hasRemoveValue($value)) {
            unset($this->removeValues[array_search($value, $this->removeValues)]);
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function removeRemoveValues($values)
    {
        foreach ($values as $value) {
            $this->removeRemoveValue($value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getRemoveValues()
    {
        return $this->removeValues;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function hasRemoveValue($value)
    {
        return in_array($value, $this->removeValues);
    }

    /**
     * @return int
     */
    public function countRemoveValues()
    {
        return count($this->removeValues);
    }

    /**
     * @param ValuesCollection $values
     *
     * @return $this
     */
    public function merge(ValuesCollection $values)
    {
        $this->addActiveValues($values->getActiveValues());
        $this->addRemoveValues($values->getRemoveValues());

        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->countActiveValues() + $this->countRemoveValues();
    }
}
