<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DataField
 *
 * @ORM\Table(name="data_field")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DataFieldRepository")
 */
class DataField
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime")
     */
    private $modified;

    /**
     * @ORM\ManyToOne(targetEntity="Revision", inversedBy="dataFields", cascade={"persist"})
     * @ORM\JoinColumn(name="revision_id", referencedColumnName="id")
     */
    private $revision;

    /**
     * @ORM\ManyToOne(targetEntity="FieldType")
     * @ORM\JoinColumn(name="field_type_id", referencedColumnName="id")
     */
    private $fieldTypes;
    
    /**
     * @var int
     *
     * @ORM\Column(name="integer_value", type="bigint", nullable=true)
     */
    private $integerValue;

    /**
     * @var float
     *
     * @ORM\Column(name="float_value", type="float", nullable=true)
     */
    private $floatValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_value", type="datetime", nullable=true)
     */
    private $dateValue;

    /**
     * @var string
     *
     * @ORM\Column(name="text_value", type="text", nullable=true)
     */
    private $textValue;

    /**
     * @var binary
     *
     * @ORM\Column(name="sha1", type="binary", length=20, nullable=true)
     */
    private $sha1;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=255, nullable=true)
     */
    private $language;
    
    /**
     * @var int
     *
     * @ORM\Column(name="orderKey", type="integer")
     */
    private $orderKey;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return DataField
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     *
     * @return DataField
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set integreValue
     *
     * @param integer $integreValue
     *
     * @return DataField
     */
    public function setIntegreValue($integreValue)
    {
        $this->integreValue = $integreValue;

        return $this;
    }

    /**
     * Get integreValue
     *
     * @return int
     */
    public function getIntegreValue()
    {
        return $this->integreValue;
    }

    /**
     * Set floatValue
     *
     * @param float $floatValue
     *
     * @return DataField
     */
    public function setFloatValue($floatValue)
    {
        $this->floatValue = $floatValue;

        return $this;
    }

    /**
     * Get floatValue
     *
     * @return float
     */
    public function getFloatValue()
    {
        return $this->floatValue;
    }

    /**
     * Set dateValue
     *
     * @param \DateTime $dateValue
     *
     * @return DataField
     */
    public function setDateValue($dateValue)
    {
        $this->dateValue = $dateValue;

        return $this;
    }

    /**
     * Get dateValue
     *
     * @return \DateTime
     */
    public function getDateValue()
    {
        return $this->dateValue;
    }

    /**
     * Set textValue
     *
     * @param string $textValue
     *
     * @return DataField
     */
    public function setTextValue($textValue)
    {
        $this->textValue = $textValue;

        return $this;
    }

    /**
     * Get textValue
     *
     * @return string
     */
    public function getTextValue()
    {
        return $this->textValue;
    }

    /**
     * Set sha1
     *
     * @param binary $sha1
     *
     * @return DataField
     */
    public function setSha1($sha1)
    {
        $this->sha1 = $sha1;

        return $this;
    }

    /**
     * Get sha1
     *
     * @return binary
     */
    public function getSha1()
    {
        return $this->sha1;
    }

    /**
     * Set language
     *
     * @param string $language
     *
     * @return DataField
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set integerValue
     *
     * @param integer $integerValue
     *
     * @return DataField
     */
    public function setIntegerValue($integerValue)
    {
        $this->integerValue = $integerValue;

        return $this;
    }

    /**
     * Get integerValue
     *
     * @return integer
     */
    public function getIntegerValue()
    {
        return $this->integerValue;
    }

    /**
     * Set revision
     *
     * @param \AppBundle\Entity\Revision $revision
     *
     * @return DataField
     */
    public function setRevision(\AppBundle\Entity\Revision $revision = null)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Get revision
     *
     * @return \AppBundle\Entity\Revision
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Set orderKey
     *
     * @param integer $orderKey
     *
     * @return DataField
     */
    public function setOrderKey($orderKey)
    {
        $this->orderKey = $orderKey;

        return $this;
    }

    /**
     * Get orderKey
     *
     * @return integer
     */
    public function getOrderKey()
    {
        return $this->orderKey;
    }

    /**
     * Set fieldTypes
     *
     * @param \AppBundle\Entity\FieldType $fieldTypes
     *
     * @return DataField
     */
    public function setFieldTypes(\AppBundle\Entity\FieldType $fieldTypes = null)
    {
        $this->fieldTypes = $fieldTypes;

        return $this;
    }

    /**
     * Get fieldTypes
     *
     * @return \AppBundle\Entity\FieldType
     */
    public function getFieldTypes()
    {
        return $this->fieldTypes;
    }
}
