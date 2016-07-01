<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Notification
 *
 * @ORM\Table(name="notification")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NotificationRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Notification
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
     * @ORM\ManyToOne(targetEntity="Template")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id")
     */
    private $templateId;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=100)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_timestamp", type="datetime")
     */
    private $sentTimestamp;

    /**
     * @var string
     *
     * @ORM\Column(name="response_text", type="text", nullable=true)
     */
    private $responseText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="response_timestamp", type="datetime", nullable=true)
     */
    private $responseTimestamp;

    /**
     * @ORM\ManyToOne(targetEntity="Revision")
     * @ORM\JoinColumn(name="revision_id", referencedColumnName="id")
     */
    private $revisionId;

   	/**
     * @ORM\ManyToOne(targetEntity="Environment")
     * @ORM\JoinColumn(name="environment_id", referencedColumnName="id")
     */
    private $environmentId;
    
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
     * Set templateId
     *
     * @param \AppBundle\Entity\Template $templateId
     *
     * @return Notification
     */
    public function setTemplateId(\AppBundle\Entity\Template $templateId)
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return \AppBundle\Entity\Template
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return Notification
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Notification
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set sentTimestamp
     *
     * @param \DateTime $sentTimestamp
     *
     * @return Notification
     */
    public function setSentTimestamp($sentTimestamp)
    {
        $this->sentTimestamp = $sentTimestamp;

        return $this;
    }

    /**
     * Get sentTimestamp
     *
     * @return \DateTime
     */
    public function getSentTimestamp()
    {
        return $this->sentTimestamp;
    }

    /**
     * Set responseText
     *
     * @param string $responseText
     *
     * @return Notification
     */
    public function setResponseText($responseText)
    {
        $this->responseText = $responseText;

        return $this;
    }

    /**
     * Get responseText
     *
     * @return string
     */
    public function getResponseText()
    {
        return $this->responseText;
    }

    /**
     * Set responseTimestamp
     *
     * @param \DateTime $responseTimestamp
     *
     * @return Notification
     */
    public function setResponseTimestamp($responseTimestamp)
    {
        $this->responseTimestamp = $responseTimestamp;

        return $this;
    }

    /**
     * Get responseTimestamp
     *
     * @return \DateTime
     */
    public function getResponseTimestamp()
    {
        return $this->responseTimestamp;
    }

    /**
     * Set revisionId
     *
     * @param \AppBundle\Entity\Revision $revisionId
     *
     * @return Notification
     */
    public function setRevisionId(\AppBundle\Entity\Revision $revisionId)
    {
        $this->revisionId = $revisionId;

        return $this;
    }

    /**
     * Get revisionId
     *
     * @return \AppBundle\Entity\Revision
     */
    public function getRevisionId()
    {
        return $this->revisionId;
    }

    /**
     * Set environmentId
     *
     * @param \AppBundle\Entity\Environment $environmentId
     *
     * @return Notification
     */
    public function setEnvironmentId(\AppBundle\Entity\Environment $environmentId)
    {
        $this->environmentId = $environmentId;

        return $this;
    }

    /**
     * Get environmentId
     *
     * @return \AppBundle\Entity\Environment
     */
    public function getEnvironmentId()
    {
        return $this->environmentId;
    }
}
