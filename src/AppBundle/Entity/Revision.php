<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Revision
 *
 * @ORM\Table(name="revision", uniqueConstraints={@ORM\UniqueConstraint(name="tuple_index", columns={"end_time", "ouuid"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RevisionRepository")
 */
class Revision
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
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var ContentType
     *
     * @ORM\ManyToOne(targetEntity="ContentType")
     * @ORM\JoinColumn(name="content_type_id", referencedColumnName="id")
     */
    private $contentType;
    
    /**
     * @var DataField
     *
     * @ORM\OneToMany(targetEntity="DataField", mappedBy="revision", cascade={"persist"})
     * @ORM\OrderBy({"orderKey" = "ASC"})
     */
    private $dataFields;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="version", type="integer")
     * @ORM\Version
     */
    private $version;
    
    /**
     * @var string
     *
     * @ORM\Column(name="ouuid", type="string", length=255, nullable=true)
     */
    private $ouuid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private $endTime;

    /**
     * @var bool
     *
     * @ORM\Column(name="draft", type="boolean")
     */
    private $draft;

    /**
     * @var string
     *
     * @ORM\Column(name="lock_by", type="string", length=255, nullable=true)
     */
    private $lockBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lock_until", type="datetime", nullable=true)
     */
    private $lockUntil;

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
     * @return Revision
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
     * @return Revision
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
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return Revision
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
     * Set ouuid
     *
     * @param string $ouuid
     *
     * @return Revision
     */
    public function setOuuid($ouuid)
    {
        $this->ouuid = $ouuid;

        return $this;
    }

    /**
     * Get ouuid
     *
     * @return string
     */
    public function getOuuid()
    {
        return $this->ouuid;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return Revision
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return Revision
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set draft
     *
     * @param boolean $draft
     *
     * @return Revision
     */
    public function setDraft($draft)
    {
        $this->draft = $draft;

        return $this;
    }

    /**
     * Get draft
     *
     * @return bool
     */
    public function getDraft()
    {
        return $this->draft;
    }

    /**
     * Set lockBy
     *
     * @param string $lockBy
     *
     * @return Revision
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
     * @return Revision
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
     * Set contentType
     *
     * @param \AppBundle\Entity\ContentType $contentType
     *
     * @return Revision
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
        return $this->contentType;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dataFields = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add dataField
     *
     * @param \AppBundle\Entity\DataField $dataField
     *
     * @return Revision
     */
    public function addDataField(\AppBundle\Entity\DataField $dataField)
    {
        $this->dataFields[] = $dataField;

        return $this;
    }

    /**
     * Remove dataField
     *
     * @param \AppBundle\Entity\DataField $dataField
     */
    public function removeDataField(\AppBundle\Entity\DataField $dataField)
    {
        $this->dataFields->removeElement($dataField);
    }

    /**
     * Get dataFields
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDataFields()
    {
        return $this->dataFields;
    }
    
    public function __get($key){
    	/** @var DataField $dataField */
    	foreach ($this->dataFields as $dataField){
    		if(strcmp($key,  $dataField->getFieldTypes()->getName()) == 0){
    			return $dataField;
    		}
    	}
    	return null;
    }
    
    public static function getObjectArray($array){
    	$out = [];
    	/** @var DataField $dataField */
    	foreach ($array as $dataField){
    		switch ($dataField->getFieldTypes()->getType()){
				case "string":  
				case "ouuid":
	    			$out [$dataField->getFieldTypes()->getName()] = $dataField->getTextValue();
	    			break;
				case "container":
// 					dump(Revision::getObjectArray($dataField->getChildren()));
					$out = array_merge($out, Revision::getObjectArray($dataField->getChildren()) );
					break;
	    			
    		}
    	}
    	return $out;
    }


    /**
     * Set version
     *
     * @param integer $version
     *
     * @return Revision
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }
}
