<?php
namespace AppBundle\Entity\Form;

use Doctrine\ORM\Mapping as ORM;

/**
 * Search
 *
 * @ORM\Table(name="search")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SearchRepository")
 */
class Search
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
	 * @var SearchFilter $filters
	 * 
     * @ORM\OneToMany(targetEntity="SearchFilter", mappedBy="search", cascade={"persist", "remove"})
	 */
	private $filters;
	
	/**
	 * @var string $boolean
	 * 
     * @ORM\Column(name="boolean", type="string", length=100)
	 */
	private $boolean;
	
	/**
	 * @var string $typeFacet
	 * 
     * @ORM\Column(name="type_facet", type="string", length=100)
	 */
	private $typeFacet;
	
	/**
	 * @var string $typeFacet
	 * 
     * @ORM\Column(name="alias_facet", type="string", length=100)
	 */
	private $aliasFacet;
	
	/**
	 * @var string $typeFacet
	 * 
     * @ORM\Column(name="user", type="string", length=100)
	 */
	private $user;
	
	/**
	 * @var string $typeFacet
	 * 
     * @ORM\Column(name="name", type="string", length=100)
	 */
	private $name;
	
	
	function __construct(){
		$this->filters = [];//new \Doctrine\Common\Collections\ArrayCollection();
		$this->filters[] = new SearchFilter();
		$this->page = 1;
		$this->boolean = "and";
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
     * Set boolean
     *
     * @param string $boolean
     *
     * @return Search
     */
    public function setBoolean($boolean)
    {
        $this->boolean = $boolean;

        return $this;
    }

    /**
     * Get boolean
     *
     * @return string
     */
    public function getBoolean()
    {
        return $this->boolean;
    }

    /**
     * Set typeFacet
     *
     * @param string $typeFacet
     *
     * @return Search
     */
    public function setTypeFacet($typeFacet)
    {
        $this->typeFacet = $typeFacet;

        return $this;
    }

    /**
     * Get typeFacet
     *
     * @return string
     */
    public function getTypeFacet()
    {
        return $this->typeFacet;
    }

    /**
     * Set aliasFacet
     *
     * @param string $aliasFacet
     *
     * @return Search
     */
    public function setAliasFacet($aliasFacet)
    {
        $this->aliasFacet = $aliasFacet;

        return $this;
    }

    /**
     * Get aliasFacet
     *
     * @return string
     */
    public function getAliasFacet()
    {
        return $this->aliasFacet;
    }

    /**
     * Set user
     *
     * @param string $user
     *
     * @return Search
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Search
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
     * Add filter
     *
     * @param \AppBundle\Entity\Form\SearchFilter $filter
     *
     * @return Search
     */
    public function addFilter(\AppBundle\Entity\Form\SearchFilter $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Remove filter
     *
     * @param \AppBundle\Entity\Form\SearchFilter $filter
     */
    public function removeFilter(\AppBundle\Entity\Form\SearchFilter $filter)
    {
        $this->filters->removeElement($filter);
    }

    /**
     * Get filters
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFilters()
    {
        return $this->filters;
    }
}
