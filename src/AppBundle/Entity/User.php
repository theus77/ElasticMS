<?php
// src/AppBundle/Entity/User.php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="`user`")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
     * @var \ObjectPickerType
     * 
     * @ORM\Column(name="circles", type="json_array", nullable=true)
     */
    private $circles;
    
    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true)
     */
    private $displayName;

    
    public function __construct()
    {
        parent::__construct();
        // your own logic
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
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
    	return $this->modified;
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
     * Get expiresAt
     *
     * @return \DateTime
     */
    public function getExpiresAt()
    {
    	return $this->expiresAt;
    }
    
    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     *
     * @return User
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }
    
    /**
     * Set circles
     *
     * @param \ObjectPickerType $circles
     *
     * @return User
     */
    public function setCircles($circles)
    {
    	$this->circles = $circles;
    
    	return $this;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     *
     * @return User
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
    	if(empty($this->displayName))
    		return $this->getUsername();
        return $this->displayName;
    }
}
