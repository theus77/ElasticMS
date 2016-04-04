<?php

namespace AppBundle\Entity;

use AppBundle\Form\DataField\OuuidFieldType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use AppBundle\Form\DataField\CollectionFieldType;
use AppBundle\Form\DataField\DateFieldType;

/**
 * DataField
 *
 * @ORM\Table(name="data_field")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DataFieldRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Assert\Callback({"Vendor\Package\Validator", "validate"})
 */
class DataField implements \ArrayAccess, \IteratorAggregate
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

    
    public function setChildrenFieldType(FieldType $fieldType){
    	//TODO: test if sub colletion for nested collection
    	/** @var FieldType $subType */
    	$this->children->first();
    	foreach ($fieldType->getChildren() as $subType){
    		if(! $subType->getDeleted() ){
    			$child = $this->children->current();
	    		if($child){
	    			$child->setFieldType($subType);
	    			$child->setOrderKey($subType->getOrderKey());
	    			$child->setChildrenFieldType($subType);		
	    		}  		
	    		$this->children->next();
    		}
    	}
    }
    
    public function offsetSet($offset, $value) {
    	/** @var DataField $value */
    	$value->setParent($this);
    	$value->setRevisionIdRecursively($this->getRevisionId());
    	$value->setOrderKey($offset);
    	$value->setChildrenFieldType($this->fieldType);
    	return $this->children->offsetSet($offset, $value);
    }
    
    public function offsetExists($offset) {
    	return $this->children->offsetExists($offset);
    }
    
    public function offsetUnset($offset) {
    	return $this->children->offsetUnset($offset);
    }
    
    public function offsetGet($offset) {
    	return $this->children->offsetGet($offset);
    }   
    
    

    public function getIterator() {
    	return $this->children->getIterator();
    }
    
    
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
    	
    	if($input instanceof DataField){
	    	$found = false;
	    	/** @var DataField $input */
	    	$input->setParent($this);
	    	$input->setRevisionId($this->getRevisionId());
	    	
	    	
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
    	}
    	else {
    		throw new \Exception('__set a DataField wich is not a valid object'.$key);
    	}
	    	
    	return $this;
    }


    public function setRevisionIdRecursively($revisionId)
    {
    	$this->revision_id = $revisionId;
    	foreach ($this->children as $child){
    		$child->setRevisionIdRecursively($revisionId);
    	}
    
    	return $this;
    }
    
    public function updateDataStructure(FieldType $meta){
    
    	//no need to generate the structure for subfields (
    	$type = $this->getFieldType()->getType();
    	$datFieldType = new $type;
    	if($datFieldType->isContainer()){
    		/** @var FieldType $field */
    		foreach ($meta->getChildren() as $field){
    			//no need to generate the structure for delete field
    			if(!$field->getDeleted()){
    				$child = $this->__get('ems_'.$field->getName());
    				if(null == $child){
    					$child = new DataField();
    					$child->setFieldType($field);
    					$child->setOrderKey($field->getOrderKey());
    					$child->setParent($this);
    					$child->setRevisionId($this->getRevisionId());
    					$this->addChild($child);
    				}
    				if( strcmp($field->getType(), CollectionFieldType::class) != 0 ) {
    					$child->updateDataStructure($field);
    				}
    			}
    		}
    	}
    }
    
    
    public function linkFieldType(PersistentCollection $fieldTypes){
    	
    	$index = 0;
    	/** @var FieldType $fieldType */
    	foreach ($fieldTypes as $fieldType){
    		if(!$fieldType->getDeleted()){
    			/** @var DataField $child */
    			$child = $this->children->get($index);
    			$child->setFieldType($fieldType);
    			$child->setParent($this);
	    		$child->setRevisionId($this->getRevisionId());
	    		$child->linkFieldType($fieldType->getChildren());
	    		++$index;
    		}
    	}
    
    	
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
     	
     	if($this->getFieldType() && strcmp ($this->getFieldType()->getType(), CollectionFieldType::class) == 0){
      		//Symfony wants iterate on children
     		return $this;
     	}
     	else {     		
	    	/** @var DataField $dataField */
	    	foreach ($this->children as $dataField){
	    		if(null != $dataField->getFieldType() && !$dataField->getFieldType()->getDeleted() && strcmp($key,  $dataField->getFieldType()->getName()) == 0){	    			
	    			return $dataField;
	    		}
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
		
		$dateFormat = $this->getFieldType()->getMappingOptions()['format'];

		//TODO: naive approch....find a way to comvert java date format into php
		$dateFormat = str_replace('dd', 'd', $dateFormat);
		$dateFormat = str_replace('mm', 'm', $dateFormat);
		$dateFormat = str_replace('yyyy', 'Y', $dateFormat);
		
		$value->setDateValue( \DateTime::createFromFormat($dateFormat, $dateValue) );
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
			$dateFormat = $this->getFieldType()->getMappingOptions()['format'];
			
			//TODO: naive approch.... find a way to comvert java date format into php
			$dateFormat = str_replace('dd', 'd', $dateFormat);
			$dateFormat = str_replace('mm', 'm', $dateFormat);
			$dateFormat = str_replace('yyyy', 'Y', $dateFormat);
			
			return $value->getDateValue()->format($dateFormat);
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
			return $value->getIntegerValue() != 0;
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
