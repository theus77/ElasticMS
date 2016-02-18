<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DataField
 *
 * @ORM\Table(name="data_field")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DataFieldRepository")
 * @ORM\HasLifecycleCallbacks()
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
     * @var FieldType
     *
     * @ORM\ManyToOne(targetEntity="DataField", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;
    
    /**
     * @ORM\OneToMany(targetEntity="DataField", mappedBy="parent", cascade={"persist"})
     * @ORM\OrderBy({"orderKey" = "ASC"})
     */
    private $children;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateModified()
    {
    	$this->modified = new \DateTime();
        	if(!isset($this->created)){
    		$this->created = $this->modified;
    	}
    	if(!isset($this->orderKey)){
    		$this->orderKey = 0;
    	}
    }
    
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
    
    public function __set($key, $input){
    	dump($this);
    	dump($key);
    	dump($input);
    	
    	$found = false;
    	/** @var DataField $dataField */
    	foreach ($this->children as &$dataField){
    		if(strcmp($key,  $dataField->getFieldTypes()->getName()) == 0){
    			$dataField = $input;
    			break;
    		}
    	}
    	if(! $found){    		
	    	$this->children->add($input);
    	}
	    	
    	return $this;
    }
    
    /**
     * get a child
     *
     * @return DataField
     */    
     public function __get($key){
//     	dump($key);
//     	dump($this);
    	/** @var DataField $dataField */
    	foreach ($this->children as $dataField){
    		if(strcmp($key,  $dataField->getFieldTypes()->getName()) == 0){
    			return $dataField;
    		}
    	}
    	
    	return null;
    }
    
	/**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set parent
     *
     * @param \AppBundle\Entity\DataField $parent
     *
     * @return DataField
     */
    public function setParent(\AppBundle\Entity\DataField $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\DataField
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\DataField $child
     *
     * @return DataField
     */
    public function addChild(\AppBundle\Entity\DataField $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\DataField $child
     */
    public function removeChild(\AppBundle\Entity\DataField $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }
}
