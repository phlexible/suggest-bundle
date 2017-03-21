<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Data source value bag.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 *
 * @ORM\Entity
 * @ORM\Table(name="datasource_value", indexes={@ORM\Index(columns={"datasource_id", "language"})})
 */
class DataSourceValueBag
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="string", length=36, options={"fixed"=true})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=2, options={"fixed"=true})
     */
    private $language;

    /**
     * @var array
     * @ORM\Column(name="values", type="json_array")
     */
    private $values = [];

    /**
     * @var DataSource
     * @ORM\ManyToOne(targetEntity="DataSource", inversedBy="valueBags")
     * @ORM\JoinColumn(name="datasource_id", referencedColumnName="id")
     */
    private $datasource;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return array_values($this->values);
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function setValues(array $values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function addValue($value)
    {
        if (!$this->hasValue($value)) {
            $this->values[] = $value;
            sort($this->values);
        }

        return $this;
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
     * @param string $value
     *
     * @return $this
     */
    public function removeValue($value)
    {
        if ($this->hasValue($value)) {
            unset($this->values[array_search($value, $this->values)]);
            sort($this->values);
        }

        return $this;
    }

    /**
     * @return DataSource
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @param DataSource $datasource
     *
     * @return $this
     */
    public function setDatasource(DataSource $datasource = null)
    {
        $this->datasource = $datasource;

        return $this;
    }
}
