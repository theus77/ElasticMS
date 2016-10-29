<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;

/**
 * Environment
 *
 * @ORM\Table(name="environment")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EnvironmentRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Environment
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=255)
     */
    private $alias;
    
    /**
     * @var string
     */
    private $index;
    
    /**
     * @var string
     */
    private $total;
    
    /**
     * @var integer
     */
    private $counter;
    
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=50, nullable=true)
     */
    private $color;
    
    /**
     * @var string
     *
     * @ORM\Column(name="baseUrl", type="string", length=1024, nullable=true)
     */
    private $baseUrl;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="managed", type="boolean")
     */
    private $managed;

    /**
     * @ORM\ManyToMany(targetEntity="Revision", mappedBy="environments")
     */
    private $revisions;

    /**
     * @var \ObjectPickerType
     *
     * @ORM\Column(name="circles", type="json_array", nullable=true)
     */
    private $circles;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="in_default_search", type="boolean", nullable=true)
     */
    private $inDefaultSearch;

    /**
     * @var string
     *
     * @ORM\Column(name="extra", type="text", nullable=true)
     */
    private $extra;
    

    /**
     * @ORM\OneToMany(targetEntity="ContentType", mappedBy="environment", cascade={"remove"})
     */
    private $contentTypesHavingThisAsDefault;
    
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
    
    
    public function getIndexedCounter(){
    	$count = 0;
    	/** @var Revision $revision */
    	foreach($this->revisions as $revision){
    		if(!$revision->getContentType()->getDeleted()){
    			++ $count;
    		}
    	}
    	return $count;
    }
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->revisions = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
	/**
	 * ToString
	 */
    public function __toString() {
    	return $this->name;
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
     * @return Environment
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
     * @return Environment
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
     * @return Environment
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
     * Set index
     *
     * @param string $index
     *
     * @return Environment
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get index
     *
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }
    /**
     * Set total
     *
     * @param string $total
     *
     * @return Environment
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }
    

    /**
     * Add revision
     *
     * @param \AppBundle\Entity\Revision $revision
     *
     * @return Environment
     */
    public function addRevision(\AppBundle\Entity\Revision $revision)
    {
        $this->revisions[] = $revision;

        return $this;
    }

    /**
     * Remove revision
     *
     * @param \AppBundle\Entity\Revision $revision
     */
    public function removeRevision(\AppBundle\Entity\Revision $revision)
    {
        $this->revisions->removeElement($revision);
    }

    /**
     * Get revisions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRevisions()
    {
        return $this->revisions;
    }

    /**
     * Set managed
     *
     * @param boolean $managed
     *
     * @return Environment
     */
    public function setManaged($managed)
    {
        $this->managed = $managed;

        return $this;
    }

    /**
     * Get managed
     *
     * @return boolean
     */
    public function getManaged()
    {
        return $this->managed;
    }

    /**
     * Set color
     *
     * @param string $color
     *
     * @return Environment
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
     * Set alias
     *
     * @param string $alias
     *
     * @return Environment
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set baseUrl
     *
     * @param string $baseUrl
     *
     * @return Environment
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Get baseUrl
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Set circles
     *
     * @param \ObjectPickerType $circles
     *
     * @return Environment
     */
    public function setCircles($circles)
    {
    	$this->circles = $circles;
    
    	return $this;
    }
    
    /**
     * Get circles
     *
     * @return \ObjectPickerType
     */
    public function getCircles()
    {
    	return $this->circles;
    }

    /**
     * Set inDefaultSearch
     *
     * @param boolean $inDefaultSearch
     *
     * @return Environment
     */
    public function setInDefaultSearch($inDefaultSearch)
    {
        $this->inDefaultSearch = $inDefaultSearch;

        return $this;
    }

    /**
     * Get inDefaultSearch
     *
     * @return boolean
     */
    public function getInDefaultSearch()
    {
        return $this->inDefaultSearch;
    }

    /**
     * Set extra
     *
     * @param string $extra
     *
     * @return Environment
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get extra
     *
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Add contentTypesHavingThisAsDefault
     *
     * @param \AppBundle\Entity\ContentType $contentTypesHavingThisAsDefault
     *
     * @return Environment
     */
    public function addContentTypesHavingThisAsDefault(\AppBundle\Entity\ContentType $contentTypesHavingThisAsDefault)
    {
        $this->contentTypesHavingThisAsDefault[] = $contentTypesHavingThisAsDefault;

        return $this;
    }

    /**
     * Remove contentTypesHavingThisAsDefault
     *
     * @param \AppBundle\Entity\ContentType $contentTypesHavingThisAsDefault
     */
    public function removeContentTypesHavingThisAsDefault(\AppBundle\Entity\ContentType $contentTypesHavingThisAsDefault)
    {
        $this->contentTypesHavingThisAsDefault->removeElement($contentTypesHavingThisAsDefault);
    }

    /**
     * Get contentTypesHavingThisAsDefault
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContentTypesHavingThisAsDefault()
    {
        return $this->contentTypesHavingThisAsDefault;
    }
}
