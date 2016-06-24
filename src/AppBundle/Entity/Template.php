<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DataField
 *
 * @ORM\Table(name="template")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LinkRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Template
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name; 

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     */
    private $icon; 
    
    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="edit_with_wysiwyg", type="boolean")
     */
    private $editWithWysiwyg;
    
    /** @var string
     * 
     * @ORM\Column(name="render_option", type="string")
     */
    private $renderOption;
    
    /**
     * @var int
     *
     * @ORM\Column(name="orderKey", type="integer")
     */
    private $orderKey;

    /**
     * @ORM\ManyToOne(targetEntity="ContentType", inversedBy="templates")
     * @ORM\JoinColumn(name="content_type_id", referencedColumnName="id")
     */
    private $contentType;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="download_result_url", type="boolean")
     */
    private $downloadResultUrl;
    
    /** @var string
    
    /**
     * @var bool
     *
     * @ORM\Column(name="preview", type="boolean")
     */
    private $preview;
    
    /** @var string
     * 
     * @ORM\Column(name="mime_type", type="string", nullable=true)
     */
    private $mimeType;
    
    /** @var string
     *
     * @ORM\Column(name="filename", type="text", nullable=true)
     */
    private $filename;
    
    /** @var string
     *
     * @ORM\Column(name="extension", type="string", nullable=true)
     */
    private $extension;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;
    
    /**
     * @var string
     *
     * @ORM\Column(name="role", type="json_array")
     */
   	private $role;
   	
   	/**
   	 * @ORM\ManyToOne(targetEntity="Environment")
   	 * @ORM\JoinColumn(name="environment_id", referencedColumnName="id")
   	 */
   	private $environment;
   	
   	/** @var string
   	*
   	* @ORM\Column(name="role_to", type="json_array")
   	*/
   	private $roleTo;
   	
   	/** @var string
   	*
   	* @ORM\Column(name="role_cc", type="json_array")
   	*/
   	private $roleCc;
   	
   	/**
   	 * @var \ObjectPickerType
   	 *
   	 * @ORM\Column(name="circles", type="json_array", nullable=true)
   	 */
   	private $circles;
   	
   	/**
   	 * @var string
   	 *
   	 * @ORM\Column(name="response_template", type="text", nullable=true)
   	 */
   	private $responseTemplate;
    
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
     * @return Template
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
     * @return Template
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
     * @return Template
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
     * @return Template
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
     * Set body
     *
     * @param string $body
     *
     * @return Template
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set editWithWysiwyg
     *
     * @param boolean $editWithWysiwyg
     *
     * @return Template
     */
    public function setEditWithWysiwyg($editWithWysiwyg)
    {
        $this->editWithWysiwyg = $editWithWysiwyg;

        return $this;
    }

    /**
     * Get editWithWysiwyg
     *
     * @return boolean
     */
    public function getEditWithWysiwyg()
    {
        return $this->editWithWysiwyg;
    }
    
    /**
     * Set renderOption
     *
     * @param string $renderOption
     *
     * @return Template
     */
    public function setRenderOption($renderOption)
    {
    	$this->renderOption = $renderOption;
    	return $this;
    }
    
    /**
     * Get renderOption
     *
     * @return string
     */
    public function getRenderOption()
    {
    	return $this->renderOption;
    }

    /**
     * Set orderKey
     *
     * @param integer $orderKey
     *
     * @return Template
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
     * Set contentType
     *
     * @param \AppBundle\Entity\ContentType $contentType
     *
     * @return Template
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
     * Set downloadResultUrl
     *
     * @param bool $downloadResultUrl
     *
     * @return Template
     */
    public function setDownloadResultUrl($downloadResultUrl)
    {
    	$this->downloadResultUrl = $downloadResultUrl;
    
    	return $this;
    }
    
    /**
     * Get downloadResultUrl
     *
     * @return bool
     */
    public function getDownloadResultUrl()
    {
    	return $this->downloadResultUrl;
    }
    
    /**
     * Set mimeType
     *
     * @param string $mimeType
     *
     * @return Template
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }
    
    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return Template
     */
    public function setFilename($filename)
    {
    	$this->filename = $filename;
    
    	return $this;
    }
    
    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
    	return $this->filename;
    }
    
    /**
     * Set extension
     *
     * @param string $extension
     *
     * @return Template
     */
    public function setExtension($extension)
    {
    	$this->extension = $extension;
    
    	return $this;
    }
    
    /**
     * Get extension
     *
     * @return string
     */
    public function getExtension()
    {
    	return $this->extension;
    }
  
    /**
     * Set preview
     *
     * @param boolean $preview
     *
     * @return Template
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * Get preview
     *
     * @return boolean
     */
    public function getPreview()
    {
        return $this->preview;
    }
    
    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Template
     */
    public function setActive($active)
    {
    	$this->active = $active;
    
    	return $this;
    }
    
    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
    	return $this->active;
    }
    
    /**
     * Set role
     *
     * @param string $role
     *
     * @return Template
     */
    public function setRole($role)
    {
    	$this->role = $role;
    
    	return $this;
    }
    
    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
    	return $this->role;
    }
    
    /**
     * Set environment
     *
     * @param \AppBundle\Entity\Environment $environmentId
     *
     * @return Template
     */
    public function setEnvironment(\AppBundle\Entity\Environment $environment)
    {
    	$this->environment = $environment;
    
    	return $this;
    }
    
    /**
     * Get environment
     *
     * @return \AppBundle\Entity\Environment
     */
    public function getEnvironment()
    {
    	return $this->environment;
    }
    
    
    /**
     * Set roleTo
     *
     * @param string $roleTo
     *
     * @return Template
     */
    public function setRoleTo($roleTo)
    {
    	$this->roleTo = $roleTo;
    
    	return $this;
    }
    
    /**
     * Get roleTo
     *
     * @return string
     */
    public function getRoleTo()
    {
    	return $this->roleTo;
    }
    
    /**
     * Set roleCc
     *
     * @param string $roleCc
     *
     * @return Template
     */
    public function setRoleCc($roleCc)
    {
    	$this->roleCc = $roleCc;
    
    	return $this;
    }
    
    /**
     * Get roleCc
     *
     * @return string
     */
    public function getRoleCc()
    {
    	return $this->roleCc;
    }
    
    /**
     * Set circles
     *
     * @param \ObjectPickerType $circles
     *
     * @return Template
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
     * Set responseTemplate
     *
     * @param string $responseTemplate
     *
     * @return Template
     */
    public function setResponseTemplate($responseTemplate)
    {
    	$this->responseTemplate = $responseTemplate;
    
    	return $this;
    }
    
    /**
     * Get responseTemplate
     *
     * @return string
     */
    public function getResponseTemplate()
    {
    	return $this->responseTemplate;
    }
}
