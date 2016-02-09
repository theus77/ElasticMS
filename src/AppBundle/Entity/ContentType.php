<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContentType
 *
 * @ORM\Table(name="content_type")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContentTypeRepository")
 */
class ContentType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
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
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="pluralName", type="string", length=100)
     */
    private $pluralName;

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=100, nullable=true)
     */
    private $icon;

    /**
     * @var string
     *
     * @ORM\Column(name="defaultEnvironmentId", type="bigint")
     */
    private $defaultEnvironmentId;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="lockBy", type="string", length=100, nullable=true)
     */
    private $lockBy;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lockUntil", type="datetime", nullable=true)
     */
    private $lockUntil;
    
    /**
     * @var array
     *
     * @ORM\Column(name="circles", type="simple_array", nullable=true)
     */
    private $circles;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;
    
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=50, nullable=true)
     */
    private $color;
    
    /**
     * @var int
     *
     * @ORM\Column(name="labelField", type="bigint")
     */
    private $labelField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="parentField", type="bigint")
     */
    private $parentField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="dateField", type="bigint")
     */
    private $dateField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="startDateField", type="bigint")
     */
    private $startDateField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="endDateField", type="bigint")
     */
    private $endDateField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="locationField", type="bigint")
     */
    private $locationField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="ouuidField", type="bigint")
     */
    private $ouuidField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="imageField", type="bigint")
     */
    private $imageField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="videoField", type="bigint")
     */
    private $videoField;
    
    /**
     * @var int
     *
     * @ORM\Column(name="orderKey", type="integer")
     */
    private $orderKey;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="rootContentType", type="boolean")
     */
    private $rootContentType;
    

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
     * @return ContentType
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
     * @return ContentType
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
     * Set name
     *
     * @param string $name
     *
     * @return ContentType
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
     * Set icon
     *
     * @param string $icon
     *
     * @return ContentType
     */
    public function setIcon($icon)
    {
    	$this->icon = $icon;
    
    	return $this;
    }
    
    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
    	return $this->icon;
    }

    
    /**
     * Set description
     *
     * @param string $description
     *
     * @return ContentType
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

    /**
     * Set defaultEnvironmentId
     *
     * @param integer $defaultEnvironmentId
     *
     * @return ContentType
     */
    public function setDefaultEnvironmentId($defaultEnvironmentId)
    {
        $this->defaultEnvironmentId = $defaultEnvironmentId;

        return $this;
    }

    /**
     * Get defaultEnvironmentId
     *
     * @return integer
     */
    public function getDefaultEnvironmentId()
    {
        return $this->defaultEnvironmentId;
    }

    /**
     * Set lockBy
     *
     * @param string $lockBy
     *
     * @return ContentType
     */
    public function setLockBy($lockBy)
    {
        $this->lockBy = $lockBy;

        return $this;
    }

    /**
     * Get lockBy
     *
     * @return string
     */
    public function getLockBy()
    {
        return $this->lockBy;
    }

    /**
     * Set lockUntil
     *
     * @param \DateTime $lockUntil
     *
     * @return ContentType
     */
    public function setLockUntil($lockUntil)
    {
        $this->lockUntil = $lockUntil;

        return $this;
    }

    /**
     * Get lockUntil
     *
     * @return \DateTime
     */
    public function getLockUntil()
    {
        return $this->lockUntil;
    }

    /**
     * Set circles
     *
     * @param array $circles
     *
     * @return ContentType
     */
    public function setCircles($circles)
    {
        $this->circles = $circles;

        return $this;
    }

    /**
     * Get circles
     *
     * @return array
     */
    public function getCircles()
    {
        return $this->circles;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return ContentType
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set color
     *
     * @param string $color
     *
     * @return ContentType
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set labelField
     *
     * @param integer $labelField
     *
     * @return ContentType
     */
    public function setLabelField($labelField)
    {
        $this->labelField = $labelField;

        return $this;
    }

    /**
     * Get labelField
     *
     * @return integer
     */
    public function getLabelField()
    {
        return $this->labelField;
    }

    /**
     * Set parentField
     *
     * @param integer $parentField
     *
     * @return ContentType
     */
    public function setParentField($parentField)
    {
        $this->parentField = $parentField;

        return $this;
    }

    /**
     * Get parentField
     *
     * @return integer
     */
    public function getParentField()
    {
        return $this->parentField;
    }

    /**
     * Set dateField
     *
     * @param integer $dateField
     *
     * @return ContentType
     */
    public function setDateField($dateField)
    {
        $this->dateField = $dateField;

        return $this;
    }

    /**
     * Get dateField
     *
     * @return integer
     */
    public function getDateField()
    {
        return $this->dateField;
    }

    /**
     * Set endDateField
     *
     * @param integer $endDateField
     *
     * @return ContentType
     */
    public function setEndDateField($endDateField)
    {
        $this->endDateField = $endDateField;

        return $this;
    }

    /**
     * Get endDateField
     *
     * @return integer
     */
    public function getEndDateField()
    {
        return $this->endDateField;
    }

    /**
     * Set locationField
     *
     * @param integer $locationField
     *
     * @return ContentType
     */
    public function setLocationField($locationField)
    {
        $this->locationField = $locationField;

        return $this;
    }

    /**
     * Get locationField
     *
     * @return integer
     */
    public function getLocationField()
    {
        return $this->locationField;
    }

    /**
     * Set ouuidField
     *
     * @param integer $ouuidField
     *
     * @return ContentType
     */
    public function setOuuidField($ouuidField)
    {
        $this->ouuidField = $ouuidField;

        return $this;
    }

    /**
     * Get ouuidField
     *
     * @return integer
     */
    public function getOuuidField()
    {
        return $this->ouuidField;
    }

    /**
     * Set imageField
     *
     * @param integer $imageField
     *
     * @return ContentType
     */
    public function setImageField($imageField)
    {
        $this->imageField = $imageField;

        return $this;
    }

    /**
     * Get imageField
     *
     * @return integer
     */
    public function getImageField()
    {
        return $this->imageField;
    }

    /**
     * Set videoField
     *
     * @param integer $videoField
     *
     * @return ContentType
     */
    public function setVideoField($videoField)
    {
        $this->videoField = $videoField;

        return $this;
    }

    /**
     * Get videoField
     *
     * @return integer
     */
    public function getVideoField()
    {
        return $this->videoField;
    }

    /**
     * Set orderKey
     *
     * @param integer $orderKey
     *
     * @return ContentType
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
     * Set rootContentType
     *
     * @param boolean $rootContentType
     *
     * @return ContentType
     */
    public function setRootContentType($rootContentType)
    {
        $this->rootContentType = $rootContentType;

        return $this;
    }

    /**
     * Get rootContentType
     *
     * @return boolean
     */
    public function getRootContentType()
    {
        return $this->rootContentType;
    }

    /**
     * Set pluralName
     *
     * @param string $pluralName
     *
     * @return ContentType
     */
    public function setPluralName($pluralName)
    {
        $this->pluralName = $pluralName;

        return $this;
    }

    /**
     * Get pluralName
     *
     * @return string
     */
    public function getPluralName()
    {
        return $this->pluralName;
    }

    /**
     * Set startDateField
     *
     * @param integer $startDateField
     *
     * @return ContentType
     */
    public function setStartDateField($startDateField)
    {
        $this->startDateField = $startDateField;

        return $this;
    }

    /**
     * Get startDateField
     *
     * @return integer
     */
    public function getStartDateField()
    {
        return $this->startDateField;
    }
}
