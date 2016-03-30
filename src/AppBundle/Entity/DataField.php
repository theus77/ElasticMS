<?php

namespace AppBundle\Entity;

use AppBundle\Form\DataField\OuuidFieldType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * DataField
 *
 * @ORM\Table(name="data_field")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DataFieldRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Assert\Callback({"Vendor\Package\Validator", "validate"})
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
     * @ORM\OneToMany(targetEntity="DataValue", mappedBy="dataField", cascade={"persist", "remove"})
     * @ORM\OrderBy({"indexKey" = "ASC"})
     */
    private $dataValues;

    
    /**
     * @Assert\Callback
     */
    public function isDataFieldValid(ExecutionContextInterface $context)
    {
    	//TODO: why is it not working?
    	$context->addViolationAt('textValue', 'Haaaaha', array(), null);
    }
    
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
    	if(strcmp(OuuidFieldType::class, $this->getFieldType()->getType()) == 0) {
    		$this->setTextValue($ouuid);
    	}
    	foreach ($this->children as $child){
    		$child->propagateOuuid($ouuid);
    	}
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
     * Constructor
     */
    public function __construct()
    {
    	$this->children = new \Doctrine\Common\Collections\ArrayCollection();
    	$this->dataValues = new \Doctrine\Common\Collections\ArrayCollection();
    	
    	//TODO: should use the clone method
    	$a = func_get_args();
    	$i = func_num_args();
    	if($i >= 1 && $a[0] instanceof DataField){
    		/** @var \DataField $ancestor */
    		$ancestor = $a[0];
    		$this->fieldType = $ancestor->getFieldType();
    		$this->orderKey = $ancestor->orderKey;
    		if($i >= 2 && $a[1] instanceof DataField){
    			$this->parent = $a[1];
    		}
    
    		foreach ($ancestor->getChildren() as $child){
    			$this->addChild(new DataField($child, $this));
    		}
    		
    		foreach ($ancestor->dataValues as $value){
    			/** @var \AppBundle\Entity\DataValue $newValue */
    			$newValue = clone $value;
    			$newValue->setDataField($this);
    			$this->dataValues->add($newValue);
    		}
    	}/**/
    }
    
    public function __set($key, $input){
    	if(strpos($key, 'ems_') !== 0){
     		throw new \Exception('unprotected ems set with key '.$key);
    	}
    	else{
    		$key = substr($key, 4);
    	}
    	
    	$found = false;
    	/** @var DataField $dataField */
    	foreach ($this->children as &$dataField){
    		if(null != $dataField->getFieldType() && !$dataField->getFieldType()->getDeleted() && strcmp($key,  $dataField->getFieldType()->getName()) == 0){
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
     		throw new \Exception('unprotected ems get with key '.$key);
     	}
     	else{
     		$key = substr($key, 4);
     	}
     	
    	/** @var DataField $dataField */
    	foreach ($this->children as $dataField){
    		if(null != $dataField->getFieldType() && !$dataField->getFieldType()->getDeleted() && strcmp($key,  $dataField->getFieldType()->getName()) == 0){
    			return $dataField;
    		}
    	}
    	
    	return null;
    }
	
	/**
	 * Set textValue
	 *
	 * @param string $textValue        	
	 *
	 * @return DataField
	 */
	public function setTextValue($textValue) {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if (! $value) {
			$value = new DataValue ();
			$this->dataValues->set ( 0, $value );
		}
		$value->setTextValue ( $textValue );
		$value->setIndexKey(0);
		$value->setDataField($this);
		
		return $this;
	}
	
	/**
	 * Get textValue
	 *
	 * @return string
	 */
	public function getTextValue() {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if (! $value) {
			return null;
		}
		return $value->getTextValue ();
	}
	
	/**
	 * Set passwordValue
	 *
	 * @param string $passwordValue        	
	 *
	 * @return DataField
	 */
	public function setPasswordValue($passwordValue) {
		if (isset ( $passwordValue )) {
        	$this->setTextValue($passwordValue);
		}
		
		return $this;
	}
	
	/**
	 * Get passwordValue
	 *
	 * @return string
	 */
	public function getPasswordValue() {
		return $this->getTextValue();
	}
	
	/**
	 * Set resetPasswordValue
	 *
	 * @param string $resetPasswordValue        	
	 *
	 * @return DataField
	 */
	public function setResetPasswordValue($resetPasswordValue) {
		if (isset ( $resetPasswordValue ) && $resetPasswordValue) {
			/** @var DataValue $value */
			$value = $this->dataValues->get ( 0 );
			if ($value) {
				$value->setTextValue(null);
			}
		}
		
		return $this;
	}
	
	/**
	 * Get resetPasswordValue
	 *
	 * @return string
	 */
	public function getResetPasswordValue() {
		return false;
	}
	
	
	
	/**
	 * Set floatValue
	 *
	 * @param float $floatValue        	
	 *
	 * @return DataField
	 */
	public function setFloatValue($floatValue) {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if (! $value) {
			$value = new DataValue ();
			$this->dataValues->set ( 0, $value );
		}
		$value->setFloatValue( $floatValue );
		$value->setIndexKey(0);
		$value->setDataField($this);
		
		return $this;
	}
	
	/**
	 * Get floatValue
	 *
	 * @return float
	 */
	public function getFloatValue() {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if (! $value) {
			return null;
		}
		return $value->getFloatValue();
	}
	
	/**
	 * Set dateValue
	 *
	 * @param \DateTime $dateValue        	
	 *
	 * @return DataField
	 */
	public function setDateValue($dateValue) {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if (! $value) {
			$value = new DataValue ();
			$this->dataValues->set ( 0, $value );
		}
		$value->setDateValue( $dateValue );
		$value->setIndexKey(0);
		$value->setDataField($this);
		
		return $this;
	}
	
	/**
	 * Get dateValue
	 *
	 * @return \DateTime
	 */
	public function getDateValue() {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if ($value) {
			return $value->getDateValue();
		}
		
		return null;
	}
	
	/**
	 * Set arrayTextValue
	 *
	 * @param array $arrayValue        	
	 *
	 * @return DataField
	 */
	public function setArrayTextValue($arrayValue) {
		
		if( count($arrayValue) < $this->dataValues->count()){
			for($i=count($arrayValue); $i < $this->dataValues->count(); ++$i){
				$this->dataValues->get($i)->setTextValue(null);
			}
		}
		$count = 0;
		dump($arrayValue);
		foreach ($arrayValue as $textValue) {
			$data = $this->dataValues->get($count);
			if(!isset($data)) {
				$data = new DataValue();
				$data->setIndexKey($count);
				$data->setDataField($this);
				$this->addDataValue($data);				
			}
			$data->setTextValue($textValue);
			++$count;
			
		}
		return $this;
	}
	
	/**
	 * Get arrayValue
	 *
	 * @return string
	 */
	public function getArrayTextValue() {
		$out = [];
		/** @var DataValue $dataValue */
		foreach ($this->dataValues as $dataValue) {
			if($dataValue->getTextValue() !== null){
				$out[] = $dataValue->getTextValue();				
			}
 		}		

		return $out;
	}
	
	/**
	 * Get integerValue
	 *
	 * @return integer
	 */
	public function getIntegerValue() {
		
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if ($value) {
			return $value->getIntegerValue();
		}
		
		return null;
	}
	
	/**
	 * Set integerValue
	 *
	 * @param integer $integerValue        	
	 *
	 * @return DataField
	 */
	public function setIntegerValue($integerValue) {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if (! $value) {
			$value = new DataValue ();
			$this->dataValues->set ( 0, $value );
		}
		$value->setIntegerValue($integerValue);
		$value->setIndexKey(0);
		$value->setDataField($this);
		
		return $this;
	}    
	
	/**
	 * Get booleanValue
	 *
	 * @return boolean
	 */
	public function getBooleanValue() {
		/** @var DataValue $value */
		$value = $this->dataValues->get ( 0 );
		if ($value) {
			dump($value);
			return $value->getIntegerValue() !== 0;
		}
		
		return null;
	}

    /**
     * Set booleanValue
     *
     * @param boolean $booleanValue
     *
     * @return DataField
     */
    public function setBooleanValue($booleanValue)
    {

    	/** @var DataValue $value */
    	$value = $this->dataValues->get ( 0 );
    	if (! $value) {
    		$value = new DataValue ();
    		$this->dataValues->set ( 0, $value );
    	}
        $value->setIntegerValue($booleanValue?1:0);
		$value->setIndexKey(0);
		$value->setDataField($this);

        return $this;
    }
	
	
    /****************************
     * Generated methods
     ****************************
     */
    




    



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
     * Add dataValue
     *
     * @param \AppBundle\Entity\DataValue $dataValue
     *
     * @return DataField
     */
    public function addDataValue(\AppBundle\Entity\DataValue $dataValue)
    {
        $this->dataValues[] = $dataValue;

        return $this;
    }

    /**
     * Remove dataValue
     *
     * @param \AppBundle\Entity\DataValue $dataValue
     */
    public function removeDataValue(\AppBundle\Entity\DataValue $dataValue)
    {
        $this->dataValues->removeElement($dataValue);
    }

    /**
     * Get dataValues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDataValues()
    {
        return $this->dataValues;
    }
}
