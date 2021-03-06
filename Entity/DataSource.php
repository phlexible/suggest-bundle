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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Data source.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 *
 * @ORM\Entity
 * @ORM\Table(name="datasource")
 */
class DataSource
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
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="create_user_id", type="string", length=36, options={"fixed"=true})
     */
    private $createUserId;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     * @ORM\Column(name="modify_user_id", type="string", length=36, options={"fixed"=true})
     */
    private $modifyUserId;

    /**
     * @var \DateTime
     * @ORM\Column(name="modified_at", type="datetime")
     */
    private $modifiedAt;

    /**
     * @var DataSourceValueBag[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="DataSourceValueBag", mappedBy="datasource", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $valueBags;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->valueBags = new ArrayCollection();
    }

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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreateUserId()
    {
        return $this->createUserId;
    }

    /**
     * @param string $createUserId
     *
     * @return $this
     */
    public function setCreateUserId($createUserId)
    {
        $this->createUserId = $createUserId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getModifyUserId()
    {
        return $this->modifyUserId;
    }

    /**
     * @param string $modifyUserId
     *
     * @return $this
     */
    public function setModifyUserId($modifyUserId)
    {
        $this->modifyUserId = $modifyUserId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * @param \DateTime $modifyTime
     *
     * @return $this
     */
    public function setModifiedAt(\DateTime $modifyTime)
    {
        $this->modifiedAt = $modifyTime;

        return $this;
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        $languages = [];
        foreach ($this->valueBags as $value) {
            $languages[] = $value->getLanguage();
        }

        return array_unique($languages);
    }

    /**
     * @return DataSourceValueBag[]|ArrayCollection
     */
    public function getValueBags()
    {
        return $this->valueBags;
    }

    /**
     * @param DataSourceValueBag $value
     *
     * @return $this
     */
    public function addValueBag(DataSourceValueBag $value)
    {
        if (!$this->valueBags->contains($value)) {
            $this->valueBags->add($value);
            $value->setDatasource($this);
        }

        return $this;
    }

    /**
     * @param DataSourceValueBag $value
     *
     * @return $this
     */
    public function removeValueBag(DataSourceValueBag $value)
    {
        if ($this->valueBags->contains($value)) {
            $this->valueBags->removeElement($value);
            $value->setDatasource(null);
        }

        return $this;
    }

    /**
     * @param string $language
     *
     * @return array
     */
    public function getValuesForLanguage($language)
    {
        foreach ($this->valueBags as $value) {
            if ($value->getLanguage() === $language) {
                return $value->getValues();
            }
        }

        return [];
    }

    /**
     * @param string $language
     * @param array  $values
     *
     * @return $this
     */
    public function setValues($language, array $values)
    {
        return $this->setValuesForLanguage($language, $values);
    }

    /**
     * @param string $language
     *
     * @return DataSourceValueBag
     */
    private function findValueBagForLanguage($language)
    {
        $targetValueBag = null;
        foreach ($this->valueBags as $valueBag) {
            if ($valueBag->getLanguage() === $language) {
                $targetValueBag = $valueBag;
            }
        }

        if (!$targetValueBag) {
            $targetValueBag = new DataSourceValueBag();
            $targetValueBag
                ->setDatasource($this)
                ->setLanguage($language);

            $this->valueBags->add($targetValueBag);
        }

        return $targetValueBag;
    }

    /**
     * @param string $language
     * @param array  $values
     *
     * @return $this
     */
    public function setValuesForLanguage($language, array $values)
    {
        $this->findValueBagForLanguage($language)
            ->setValues($values);

        return $this;
    }

    /**
     * @param string $language
     * @param string $value
     *
     * @return $this
     */
    public function addValueForLanguage($language, $value)
    {
        $this->findValueBagForLanguage($language)
            ->addValue($value);

        return $this;
    }

    /**
     * Remove keys from data source.
     *
     * @param string $language
     * @param array  $values
     *
     * @return $this
     */
    public function removeValuesForLanguage($language, array $values)
    {
        $valueBag = $this->findValueBagForLanguage($language);

        foreach ($values as $value) {
            $valueBag->removeValue($value);
        }

        return $this;
    }
}
