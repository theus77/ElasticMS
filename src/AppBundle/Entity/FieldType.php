<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Form\FieldType\DataFieldOptionsType;
use AppBundle\Entity\FieldOptions\DataFieldOptions;

/**
 * FieldType
 *
 * @ORM\Table(name="field_type")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FieldTypeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FieldType
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
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="ContentType")
     * @ORM\JoinColumn(name="content_type_id", referencedColumnName="id")
     */
    private $contentType;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;


    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="options", type="text", nullable=true)
     */
    private $options;

    /**
     * @var int
     *
     * @ORM\Column(name="orderKey", type="integer")
     */
    private $orderKey;

    /**
     * @var bool
     *
     * @ORM\Column(name="many", type="boolean")
     */
    private $many;

    /**
     * @var FieldType
     *
     * @ORM\ManyToOne(targetEntity="FieldType", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="FieldType", mappedBy="parent", cascade={"persist"})
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
     * @return FieldType
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    public function updateOrderKeys() {
    	if(null != $this->children){
	    	/** @var FieldType $child */
	    	foreach ( $this->children as $key => $child ) {
	    		$child->setOrderKey($key);
	    		$child->updateOrderKeys();
	    	}    		
    	}
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
     * @return FieldType
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
     * Set type
     *
     * @param string $type
     *
     * @return FieldType
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return FieldType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return FieldType
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return bool
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return FieldType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    
    
    public function setStructuredOptions( $options) {
    	$this->options = json_encode($options);
    	
    	return $this;
    }
    
    public function getStructuredOptions() {
    	return json_decode($this->options, true);
    	 
    }

    public function getDisplayOptions(){
    	$options = $this->getStructuredOptions();
    	if(isset($options['displayOptions'])){
    		return $options['displayOptions'];
    	}
    	return [];
    }
    
    /**
     * Set orderKey
     *
     * @param integer $orderKey
     *
     * @return FieldType
     */
    public function setOrderKey($orderKey)
    {
        $this->orderKey = $orderKey;

        return $this;
    }

    /**
     * Get orderKey
     *
     * @return int
     */
    public function getOrderKey()
    {
        return $this->orderKey;
    }

    /**
     * Set many
     *
     * @param boolean $many
     *
     * @return FieldType
     */
    public function setMany($many)
    {
        $this->many = $many;

        return $this;
    }

    /**
     * Get many
     *
     * @return bool
     */
    public function getMany()
    {
        return $this->many;
    }

    /**
     * Set contentType
     *
     * @param \AppBundle\Entity\ContentType $contentType
     *
     * @return FieldType
     */
    public function setContentType(\AppBundle\Entity\ContentType $contentType = null)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Get contentType
     *
     * @return \AppBundle\Entity\ContentType
     */
    public function getContentType()
    {
    	$parent = $this->parent;
    	while($parent != null){
    		$parent = $parent->parent;
    	}
        return $parent->contentType;
    }
 
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * get a child
     *
     * @return FieldType
     */
    public function __get($key){
//     	if(strpos($key, 'ems_') !== 0){
//     		dump('warning not ems prefixed call');
//      		throw new \Exception('unprotected ems set');
//     	}
//     	else{
//     		$key = substr($key, 4);
//     	}
    	
    	/** @var FieldType $fieldType */
    	foreach ($this->children as $fieldType){
    		if(strcmp($key,  $fieldType->getName()) == 0){
    			return $fieldType;
    		}
    	}
    
    	return null;
    }    
    
    /**
     * set a child
     *
     * @return DataField
     */
    public function __set($key, $input ){
//     	if(strpos($key, 'ems_') !== 0){
//     		dump('warning not ems prefixed call');
//      		throw new \Exception('unprotected ems set');
//     	}
//     	else{
//     		$key = substr($key, 4);
//     	}
    	
    	$found = false;
    	/** @var FieldType $child */
    	foreach ($this->children as &$child){
    		if(strcmp($key,  $child->getName()) == 0){
    			$found = true;
    			$child = $input;
    			break;
    		}
    	}
    	if(! $found){    		
	    	$this->children->add($input);
    	}
    	 
    	return $this;
    }

    public function getTypeClass(){
	    return new $this->type;
    }
    


    public function getOptionsFormType(){
    	return $this->getTypeClass()->getOptionsFormType();
    }
    
    /**
     * Set parent
     *
     * @param \AppBundle\Entity\FieldType $parent
     *
     * @return FieldType
     */
    public function setParent(\AppBundle\Entity\FieldType $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\FieldType
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\FieldType $child
     *
     * @return FieldType
     */
    public function addChild(\AppBundle\Entity\FieldType $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\FieldType $child
     */
    public function removeChild(\AppBundle\Entity\FieldType $child)
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
     * Set options
     *
     * @param string $options
     *
     * @return FieldType
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }
}
