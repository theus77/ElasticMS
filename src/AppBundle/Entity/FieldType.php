<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FieldType
 *
 * @ORM\Table(name="field_type")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FieldTypeRepository")
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
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    private $label;

    /**
     * @ORM\ManyToOne(targetEntity="ContentType", inversedBy="fieldTypes", cascade={"persist"})
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
     * @ORM\Column(name="mapping", type="text", nullable=true)
     */
    private $mapping;

    /**
     * @var string
     *
     * @ORM\Column(name="editOptions", type="text", nullable=true)
     */
    private $editOptions;

    /**
     * @var string
     *
     * @ORM\Column(name="viewOptions", type="text", nullable=true)
     */
    private $viewOptions;

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

    /**
     * Set mapping
     *
     * @param string $mapping
     *
     * @return FieldType
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * Get mapping
     *
     * @return string
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Set editOptions
     *
     * @param string $editOptions
     *
     * @return FieldType
     */
    public function setEditOptions($editOptions)
    {
        $this->editOptions = $editOptions;

        return $this;
    }

    /**
     * Get editOptions
     *
     * @return string
     */
    public function getEditOptions()
    {
        return $this->editOptions;
    }

    /**
     * Set viewOptions
     *
     * @param string $viewOptions
     *
     * @return FieldType
     */
    public function setViewOptions($viewOptions)
    {
        $this->viewOptions = $viewOptions;

        return $this;
    }

    /**
     * Get viewOptions
     *
     * @return string
     */
    public function getViewOptions()
    {
        return $this->viewOptions;
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
        return $this->contentType;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return FieldType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}
