<?php
namespace AppBundle\Entity\Form;


/**
 * RebuildIndex
 */
class Search
{
	private $filters;
	private $boolean;
	private $typeFacet;
	private $aliasFacet;
	
	
	function __construct(){
		$this->filters = [];//new \Doctrine\Common\Collections\ArrayCollection();
		$this->filters[] = new SearchFilter();
		$this->page = 1;
		$this->boolean = "and";
	}

	
	public function addFilter(SearchFilter $filter)
	{
		$this->filters[] = $filter;
	
		return $this;
	}


	public function getFilters()
	{
		return $this->filters;
	}
	
	
	public function setFilters($filters)
	{
		$this->filters = $filters;
	
		return $this;
	}


	public function getBoolean()
	{
		return $this->boolean;
	}
	
	
	public function setBoolean($boolean)
	{
		$this->boolean = $boolean;
	
		return $this;
	}


	public function getAliasFacet()
	{
		return $this->aliasFacet;
	}
	
	
	public function setAliasFacet($aliasFacet)
	{
		$this->aliasFacet = $aliasFacet;
	
		return $this;
	}


	public function getTypeFacet()
	{
		return $this->typeFacet;
	}
	
	
	public function setTypeFacet($typeFacet)
	{
		$this->typeFacet = $typeFacet;
	
		return $this;
	}
	
}