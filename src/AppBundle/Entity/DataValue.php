<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DataValue
 *
 * @ORM\Table(name="data_value")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DataValueRepository")
 */
class DataValue
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
     * @ORM\ManyToOne(targetEntity="DataField", inversedBy="dataValues")
     * @ORM\JoinColumn(name="data_field_id", referencedColumnName="id", nullable=false)
     */
    private $dataField;
    
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
     * @ORM\Column(name="sha1", type="string", length=20, nullable=true)
     */
    private $sha1;

//     /**
//      * @var string
//      *
//      * @ORM\Column(name="language", type="string", length=100, nullable=true)
//      */
//     private $language;
    
    /**
     * @var int
     *
     * @ORM\Column(name="index_key", type="integer")
     */
    private $indexKey;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set integerValue
     *
     * @param integer $integerValue
     *
     * @return DataValue
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
     * Set floatValue
     *
     * @param float $floatValue
     *
     * @return DataValue
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
     * @return DataValue
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
     * @return DataValue
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
     * @param string $sha1
     *
     * @return DataValue
     */
    public function setSha1($sha1)
    {
        $this->sha1 = $sha1;

        return $this;
    }

    /**
     * Get sha1
     *
     * @return string
     */
    public function getSha1()
    {
        return $this->sha1;
    }

    /**
     * Set indexKey
     *
     * @param integer $indexKey
     *
     * @return DataValue
     */
    public function setIndexKey($indexKey)
    {
        $this->indexKey = $indexKey;

        return $this;
    }

    /**
     * Get indexKey
     *
     * @return integer
     */
    public function getIndexKey()
    {
        return $this->indexKey;
    }

    /**
     * Set dataField
     *
     * @param \AppBundle\Entity\DataField $dataField
     *
     * @return DataValue
     */
    public function setDataField(\AppBundle\Entity\DataField $dataField = null)
    {
        $this->dataField = $dataField;

        return $this;
    }

    /**
     * Get dataField
     *
     * @return \AppBundle\Entity\DataField
     */
    public function getDataField()
    {
        return $this->dataField;
    }
}
