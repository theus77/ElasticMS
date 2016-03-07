<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Form\DataField\OuuidType;

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
     * @var int
     *
     * @ORM\Column(name="revision_id", type="integer", nullable=true)
     */
    private $revision_id;


    /**
     * @ORM\ManyToOne(targetEntity="FieldType")
     * @ORM\JoinColumn(name="field_type_id", referencedColumnName="id")
     */
    private $fieldType;
    
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
     * @ORM\OneToMany(targetEntity="DataField", mappedBy="parent", cascade={"persist", "remove"})
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
    
    public function propagateOuuid($ouuid) {
    	if(strcmp(OuuidType::class, $this->getFieldType()->getType()) == 0) {
    		dump($this);
    		$this->setTextValue($ouuid);
    	}
    	foreach ($this->children as $child){
    		$child->propagateOuuid($ouuid);
    	}
    }
    
    public function getObjectArray(){
    	$out = [];
    	$classname = $this->getFieldType()->getType();
    	$classname::buildObjectArray($this, $out);    	
    	return $out;
    }

    public function orderChildren(){
    	$temp = new \Doctrine\Common\Collections\ArrayCollection();
    	foreach ($this->getFieldType()->getChildren() as $childField){
    		if(!$childField->getDeleted()){    			
	    		$value = $this->__get('ems_'.$childField->getName());
	    		if(isset($value)){
		    		$temp->add($value);
	    		}
    		}
    	}

    	$this->children = $temp;
    	
    	foreach ($this->children as $child){
    		$child->orderChildren();
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
     * Set fieldType
     *
     * @param \AppBundle\Entity\FieldType $fieldType
     *
     * @return DataField
     */
    public function setFieldType(\AppBundle\Entity\FieldType $fieldType = null)
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * Get fieldType
     *
     * @return \AppBundle\Entity\FieldType
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }
    
    public function __set($key, $input){
    	if(strpos($key, 'ems_') !== 0){
    		dump('warning not ems prefixed call');
     		throw new \Exception('unprotected ems set');
    	}
    	else{
    		$key = substr($key, 4);
    	}
    	
    	$found = false;
    	/** @var DataField $dataField */
    	foreach ($this->children as &$dataField){
    		if(strcmp($key,  $dataField->getFieldType()->getName()) == 0){
    			$found = true;
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
     	
     	if(strpos($key, 'ems_') !== 0){
     		dump('warning not ems prefixed call');
     		throw new \Exception('unprotected ems get');
     	}
     	else{
     		$key = substr($key, 4);
     	}
     	
    	/** @var DataField $dataField */
    	foreach ($this->children as $dataField){
    		if(!$dataField->getFieldType()->getDeleted() && strcmp($key,  $dataField->getFieldType()->getName()) == 0){
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
        
        $a = func_get_args();
        $i = func_num_args();
        if($i >= 1 && $a[0] instanceof DataField){
        	/** @var \DataField $ancestor */
        	$ancestor = $a[0];
        	$this->dateValue = $ancestor->dateValue;
        	$this->fieldType = $ancestor->getFieldType();
        	$this->floatValue = $ancestor->floatValue;
        	$this->integerValue = $ancestor->integerValue;
        	$this->language = $ancestor->language;
        	$this->orderKey = $ancestor->orderKey;
        	$this->sha1 = $ancestor->sha1;
        	$this->textValue = $ancestor->textValue;
        	if($i >= 2 && $a[1] instanceof DataField){
        		$this->parent = $a[1];
        	}
        		
        	foreach ($ancestor->getChildren() as $child){
        		$this->addChild(new DataField($child, $this));
        	}	
    	}
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

    /**
     * Set revisionId
     *
     * @param integer $revisionId
     *
     * @return DataField
     */
    public function setRevisionId($revisionId)
    {
        $this->revision_id = $revisionId;

        return $this;
    }

    /**
     * Get revisionId
     *
     * @return integer
     */
    public function getRevisionId()
    {
        return $this->revision_id;
    }
}
